<?php
/**
 * BDSoft Workspace - FINANCEIRO (CONTAS PAGAR/RECEBER)
 * Local: agrocampo/financeiro.php
 */
session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }
$user_id = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');

// --- FILTRO INTELIGENTE ---
$sql_lista = "SELECT * FROM agro_financeiro 
              WHERE usuario_id = :uid 
              AND ((MONTH(data_vencimento) = MONTH(CURDATE()) AND YEAR(data_vencimento) = YEAR(CURDATE())) OR (status = 'Pendente' AND data_vencimento < CURDATE()))
              ORDER BY status ASC, data_vencimento ASC";
$stmt_l = $pdo->prepare($sql_lista);
$stmt_l->execute([':uid' => $user_id]);
$contas = $stmt_l->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stats = $pdo->prepare("SELECT SUM(CASE WHEN status='Pendente' AND data_vencimento < ? THEN valor ELSE 0 END) as v, SUM(CASE WHEN status='Pago' AND MONTH(data_pagamento)=MONTH(CURDATE()) AND YEAR(data_pagamento)=YEAR(CURDATE()) THEN valor ELSE 0 END) as p FROM agro_financeiro WHERE usuario_id = ?");
$stats->execute([$hoje, $user_id]);
$s = $stats->fetch();

function getStatusLabel($status, $venc, $hoje) {
    if ($status === 'Pago') return '<span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-check me-1"></i> PAGO</span>';
    if ($venc < $hoje) return '<span class="badge bg-danger rounded-pill px-3 py-2"><i class="fas fa-exclamation-triangle me-1"></i> ATRASADO</span>';
    return '<span class="badge bg-warning text-dark rounded-pill px-3 py-2">PENDENTE</span>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro - AgroCampo</title>
    <!-- CSS OFICIAL SEM ABREVIAÇÃO -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'sidebar_agro.php'; ?>

<div class="main-wrapper">
    <!-- Menu Mobile -->
    <button class="btn btn-success d-lg-none mb-3" onclick="document.getElementById('sidebar').classList.toggle('active')">
        <i class="fas fa-bars"></i> Menu
    </button>

    <?php if(isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4 text-center">
            <i class="fas fa-check-circle me-2"></i> Operação realizada com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark mb-0">Contas Pagar / Receber</h2>
            <p class="text-muted">Gestão de competência e débitos atrasados.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalXML"><i class="fas fa-qrcode me-2"></i>LER XML</button>
            <button class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoConta"><i class="fas fa-plus me-2"></i>NOVA CONTA</button>
        </div>
    </div>

    <!-- SUMÁRIO -->
    <div class="row g-4 mb-5 text-center">
        <div class="col-md-6">
            <div class="card p-4 border-0 shadow-sm border-start border-danger border-5 bg-white rounded-4">
                <small class="fw-bold text-muted uppercase">Total em Atraso</small>
                <h3 class="text-danger fw-bold mb-0">R$ <?php echo number_format($s['v']??0, 2, ',', '.'); ?></h3>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-4 border-0 shadow-sm border-start border-success border-5 bg-white rounded-4">
                <small class="fw-bold text-muted uppercase">Pago no Mês</small>
                <h3 class="text-success fw-bold mb-0">R$ <?php echo number_format($s['p']??0, 2, ',', '.'); ?></h3>
            </div>
        </div>
    </div>

    <!-- TABELA -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted">
                        <th class="ps-4">VENCIMENTO</th>
                        <th>FORNECEDOR / DESCRIÇÃO</th>
                        <th>VALOR</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($contas as $c): ?>
                    <tr>
                        <td class="ps-4 small"><?php echo date('d/m/Y', strtotime($c['data_vencimento'])); ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['fornecedor']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($c['descricao']); ?></small>
                        </td>
                        <td class="fw-bold text-dark">R$ <?php echo number_format($c['valor'], 2, ',', '.'); ?></td>
                        <td class="text-center">
                            <button onclick="gerenciarBaixa(<?php echo $c['id']; ?>, '<?php echo $c['status']; ?>', '<?php echo $c['valor']; ?>')" style="background:none; border:none;">
                                <?php echo getStatusLabel($c['status'], $c['data_vencimento'], $hoje); ?>
                            </button>
                        </td>
                        <td class="text-center">
                            <a href="acoes.php?del_fin=<?php echo $c['id']; ?>" class="text-danger opacity-50" onclick="return confirm('Excluir?')"><i class="fas fa-trash-alt"></i></a>
                        </td>
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
                <h5 class="fw-bold mb-0">Lançamento Manual</h5>
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
                    <div class="col-6"><label class="small fw-bold">STATUS</label><select name="status" class="form-select"><option value="Pendente">Pendente</option><option value="Pago">Pago</option></select></div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-success w-100 rounded-pill py-2 fw-bold shadow">SALVAR NO FLUXO</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: BAIXA DE PAGAMENTO -->
<div class="modal fade" id="modalBaixa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 bg-primary text-white p-4">
                <h5 class="fw-bold mb-0">Confirmar Pagamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <input type="hidden" name="acao" value="confirmar_pagamento_v2">
                <input type="hidden" name="id_registro" id="baixa_id">
                <small class="text-muted fw-bold">VALOR DO TÍTULO</small>
                <h2 class="fw-bold text-dark mb-4" id="baixa_valor_display">R$ 0,00</h2>
                
                <div class="text-start">
                    <label class="small fw-bold">DATA DO PAGAMENTO</label>
                    <input type="date" name="data_pago" class="form-control mb-3" value="<?php echo $hoje; ?>" required>
                    <label class="small fw-bold">MÉTODO</label>
                    <select name="metodo_pagamento" class="form-select">
                        <option value="PIX">PIX</option><option value="Boleto">Boleto</option><option value="Dinheiro">Dinheiro</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">EFETIVAR BAIXA</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts Oficiais -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const modalBaixa = new bootstrap.Modal(document.getElementById('modalBaixa'));

function gerenciarBaixa(id, status, valor) {
    if(status === 'Pago') {
        if(confirm("Deseja estornar este pagamento?")) {
            window.location.href = 'acoes.php?acao=estornar_pagamento&id=' + id;
        }
    } else {
        document.getElementById('baixa_id').value = id;
        document.getElementById('baixa_valor_display').innerText = 'R$ ' + parseFloat(valor).toLocaleString('pt-br', {minimumFractionDigits: 2});
        modalBaixa.show();
    }
}
</script>
</body>
</html>