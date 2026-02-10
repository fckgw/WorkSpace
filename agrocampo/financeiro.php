<?php
/**
 * BDSoft Workspace - AGRO FINANCEIRO
 * Local: agrocampo/financeiro.php
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }
$user_id = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');
$mes_atual = date('m');
$ano_atual = date('Y');

// --- 1. GATILHO AUTOMÁTICO DE VARREDURA (7 DIAS) ---
try {
    $stmt_v = $pdo->prepare("SELECT p.*, pr.nome_provisao 
        FROM agro_provisoes_parcelas p 
        INNER JOIN agro_provisoes pr ON p.provisao_id = pr.id 
        WHERE pr.usuario_id = ? AND p.status = 'Pendente' 
        AND p.data_vencimento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    $stmt_v->execute([$user_id]);
    $p_alvo = $stmt_v->fetchAll(PDO::FETCH_ASSOC);

    foreach ($p_alvo as $pa) {
        $descr = "PROVISÃO: " . $pa['nome_provisao'] . " (Parc. " . $pa['parcela_numero'] . ")";
        $stmt_i = $pdo->prepare("INSERT INTO agro_financeiro 
            (tipo, descricao, fornecedor, valor, categoria, data_vencimento, status, metodo_pagamento, usuario_id, provisao_parcela_id) 
            VALUES ('Saida', ?, 'Provisionamento', ?, 'Parcelamento', ?, 'Pendente', 'Boleto', ?, ?)");
        $stmt_i->execute([$descr, $pa['valor_parcela'], $pa['data_vencimento'], $user_id, $pa['id']]);
        $pdo->prepare("UPDATE agro_provisoes_parcelas SET status = 'Gerado' WHERE id = ?")->execute([$pa['id']]);
    }
} catch (Exception $e) { error_log($e->getMessage()); }

// --- 2. CÁLCULO DOS INDICADORES ---
$st_v = $pdo->prepare("SELECT SUM(valor) FROM agro_financeiro WHERE usuario_id = ? AND status = 'Pendente' AND data_vencimento < ?");
$st_v->execute([$user_id, $hoje]);
$vencido = $st_v->fetchColumn() ?: 0;

$st_r = $pdo->prepare("SELECT SUM(valor) FROM agro_financeiro WHERE usuario_id = ? AND tipo = 'Entrada' AND status = 'Pendente' AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ?");
$st_r->execute([$user_id, $mes_atual, $ano_atual]);
$receber = $st_r->fetchColumn() ?: 0;

$st_p = $pdo->prepare("SELECT SUM(valor) FROM agro_financeiro WHERE usuario_id = ? AND tipo = 'Saida' AND status = 'Pago' AND MONTH(data_pagamento) = ? AND YEAR(data_pagamento) = ?");
$st_p->execute([$user_id, $mes_atual, $ano_atual]);
$pago = $st_p->fetchColumn() ?: 0;

$st_pr = $pdo->prepare("SELECT SUM(valor_parcela) FROM agro_provisoes_parcelas p INNER JOIN agro_provisoes pr ON p.provisao_id = pr.id WHERE pr.usuario_id = ? AND p.status IN ('Pendente', 'Gerado') AND MONTH(p.data_vencimento) = ? AND YEAR(p.data_vencimento) = ?");
$st_pr->execute([$user_id, $mes_atual, $ano_atual]);
$prov = $st_pr->fetchColumn() ?: 0;

// --- LISTAGEM ---
$stmt_l = $pdo->prepare("SELECT * FROM agro_financeiro WHERE usuario_id = ? AND ((MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ?) OR (status = 'Pendente' AND data_vencimento < ?)) ORDER BY status ASC, data_vencimento ASC");
$stmt_l->execute([$user_id, $mes_atual, $ano_atual, $hoje]);
$contas = $stmt_l->fetchAll(PDO::FETCH_ASSOC);

function formatar($v) { return "R$ " . number_format($v, 2, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Financeiro - BDSoft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f4; display: flex; min-height: 100vh; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .main-wrapper { flex: 1; margin-left: 280px; padding: 40px; width: calc(100% - 280px); }
        .card-agro { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: #fff; }
        .badge-status { padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 10px; cursor: pointer; border: none; }
        @media (max-width: 991px) { .main-wrapper { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>

<?php include 'sidebar_agro.php'; ?>

<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold">Contas Pagar / Receber</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalXML">LER XML</button>
            <button class="btn btn-success rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalNovoConta">+ NOVA CONTA</button>
        </div>
    </div>

    <!-- KPI DASHBOARD -->
    <div class="row g-4 mb-5 text-center">
        <div class="col-md-3"><div class="card-agro p-3 border-start border-danger border-5"><small class="text-muted fw-bold">ATRASADOS</small><h4 class="text-danger fw-bold mb-0 mt-1"><?php echo formatar($vencido); ?></h4></div></div>
        <div class="col-md-3"><div class="card-agro p-3 border-start border-primary border-5"><small class="text-muted fw-bold">A RECEBER</small><h4 class="text-primary fw-bold mb-0 mt-1"><?php echo formatar($receber); ?></h4></div></div>
        <div class="col-md-3"><div class="card-agro p-3 border-start border-success border-5"><small class="text-muted fw-bold">PAGO NO MÊS</small><h4 class="text-success fw-bold mb-0 mt-1"><?php echo formatar($pago); ?></h4></div></div>
        <div class="col-md-3"><div class="card-agro p-3 border-start border-dark border-5"><small class="text-muted fw-bold">PROVISIONADO</small><h4 class="text-dark fw-bold mb-0 mt-1"><?php echo formatar($prov); ?></h4></div></div>
    </div>

    <!-- LISTAGEM -->
    <div class="card card-agro overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted">
                        <th class="ps-4">VENCIMENTO</th>
                        <th>DESCRIÇÃO</th>
                        <th>VALOR</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">AÇÃO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($contas as $c): 
                        $atrasada = ($c['status'] == 'Pendente' && $c['data_vencimento'] < $hoje);
                    ?>
                    <tr class="<?php echo $atrasada ? 'table-danger' : ''; ?>">
                        <td class="ps-4 small fw-bold"><?php echo date('d/m/Y', strtotime($c['data_vencimento'])); ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['fornecedor']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($c['descricao']); ?></small>
                        </td>
                        <td class="fw-bold <?php echo $c['tipo']=='Entrada'?'text-success':'text-danger'; ?>"><?php echo formatar($c['valor']); ?></td>
                        <td class="text-center">
                            <button onclick="gerenciarBaixa(<?php echo $c['id']; ?>, '<?php echo $c['status']; ?>', '<?php echo $c['valor']; ?>')" 
                                    class="badge-status <?php echo $c['status']=='Pago'?'bg-success text-white':($atrasada?'bg-danger text-white':'bg-warning text-dark'); ?>">
                                <?php echo ($atrasada && $c['status'] == 'Pendente') ? 'ATRASADO' : strtoupper($c['status']); ?>
                            </button>
                        </td>
                        <td class="text-center"><a href="acoes.php?del_fin=<?php echo $c['id']; ?>" class="text-danger opacity-50" onclick="return confirm('Excluir?')"><i class="fas fa-trash-alt"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: NOVA CONTA -->
<div class="modal fade" id="modalNovoConta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 bg-success text-white p-4">
                <h5 class="fw-bold mb-0">Novo Lançamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="acao" value="novo_fin">
                <div class="row mb-3">
                    <div class="col-6"><label class="small fw-bold">TIPO</label><select name="tipo" class="form-select"><option value="Saida">Saída</option><option value="Entrada">Entrada</option></select></div>
                    <div class="col-6"><label class="small fw-bold">VALOR (R$)</label><input type="number" step="0.01" name="valor" class="form-control" required></div>
                </div>
                <div class="mb-3"><label class="small fw-bold">FORNECEDOR</label><input type="text" name="fornecedor" class="form-control" required></div>
                <div class="mb-3"><label class="small fw-bold">DESCRIÇÃO</label><input type="text" name="descricao" class="form-control" required></div>
                <div class="row">
                    <div class="col-6"><label class="small fw-bold">VENCIMENTO</label><input type="date" name="data_vencimento" class="form-control" value="<?php echo $hoje; ?>" required></div>
                    <div class="col-6"><label class="small fw-bold">MÉTODO</label><select name="metodo_pagamento" class="form-select"><option>Boleto</option><option>PIX</option><option>Dinheiro</option></select></div>
                </div>
                <div class="mt-3"><label class="small fw-bold">STATUS</label><select name="status" class="form-select"><option value="Pendente">Pendente</option><option value="Pago">Já Pago</option></select></div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0"><button type="submit" class="btn btn-success w-100 rounded-pill py-2 fw-bold">SALVAR</button></div>
        </form>
    </div>
</div>

<!-- MODAL: BAIXA -->
<div class="modal fade" id="modalBaixa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 bg-primary text-white p-4"><h5 class="fw-bold mb-0">Confirmar Liquidação</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4 text-center">
                <input type="hidden" name="acao" value="confirmar_pagamento_v2">
                <input type="hidden" name="id_registro" id="baixa_id">
                <small class="text-muted fw-bold">VALOR DO LANÇAMENTO</small>
                <h2 class="fw-bold text-dark mb-4" id="baixa_valor_display">R$ 0,00</h2>
                <div class="text-start">
                    <label class="small fw-bold">DATA DO PAGAMENTO</label>
                    <input type="date" name="data_pago" class="form-control mb-3" value="<?php echo $hoje; ?>" required>
                    <label class="small fw-bold">MÉTODO</label>
                    <select name="metodo_pagamento" class="form-select"><option>PIX</option><option>Boleto</option><option>Dinheiro</option></select>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0"><button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">EFETIVAR</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalBaixa = new bootstrap.Modal(document.getElementById('modalBaixa'));
function gerenciarBaixa(id, status, valor) {
    if(status === 'Pago') {
        if(confirm("Estornar pagamento?")) window.location.href = 'acoes.php?acao=estornar_pagamento&id=' + id;
    } else {
        document.getElementById('baixa_id').value = id;
        document.getElementById('baixa_valor_display').innerText = 'R$ ' + parseFloat(valor).toLocaleString('pt-br', {minimumFractionDigits: 2});
        modalBaixa.show();
    }
}
</script>
</body>
</html>