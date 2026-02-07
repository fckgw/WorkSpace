<?php
/**
 * BDSoft Workspace - DETALHAMENTO DE PARCELAS (GRID)
 * Local: agrocampo/detalhe_provisao.php
 */
session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

$id_p = (int)$_GET['id'];
$user_id = $_SESSION['usuario_id'];

// 1. Validar Provisão
$stmt = $pdo->prepare("SELECT * FROM agro_provisoes WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id_p, $user_id]);
$provisao = $stmt->fetch();

if (!$provisao) { die("Acordo não encontrado."); }

// 2. Buscar Parcelas
$stmt_par = $pdo->prepare("SELECT * FROM agro_provisoes_parcelas WHERE provisao_id = ? ORDER BY parcela_numero ASC");
$stmt_par->execute([$id_p]);
$parcelas = $stmt_par->fetchAll(PDO::FETCH_ASSOC);

// 3. Totais
$total_pago = 0;
foreach($parcelas as $pa) { if($pa['status'] == 'Pago') $total_pago += $pa['valor_parcela']; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Parcelas: <?php echo $provisao['nome_provisao']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f4; display: flex; }
        .card-detalhe { background: #fff; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .sticky-summary { position: sticky; top: 20px; }
        .row-pago { background-color: #eefbee !important; opacity: 0.7; }
        .row-gerado { background-color: #fff8e1 !important; }
    </style>
</head>
<body>

<?php include 'sidebar_agro.php'; ?>

<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="fas fa-list-ol me-2 text-primary"></i>Cronograma de Pagamentos</h3>
        <a href="provisoes.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">VOLTAR</a>
    </div>

    <div class="row">
        <!-- GRID DE PARCELAS -->
        <div class="col-md-8">
            <div class="card card-detalhe p-4">
                <h5 class="fw-bold mb-4"><?php echo htmlspecialchars($provisao['nome_provisao']); ?></h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr class="small text-muted">
                                <th style="width: 40px;"></th>
                                <th>PARCELA</th>
                                <th>VENCIMENTO</th>
                                <th>VALOR UNITÁRIO</th>
                                <th class="text-center">STATUS</th>
                            </tr>
                        </thead>
                        <tbody id="listaParcelas">
                            <?php foreach ($parcelas as $p): ?>
                            <tr class="<?php echo $p['status']=='Pago'?'row-pago':($p['status']=='Gerado'?'row-gerado':''); ?>">
                                <td>
                                    <?php if($p['status'] == 'Pendente'): ?>
                                        <input type="checkbox" class="chk-parcela form-check-input" value="<?php echo $p['id']; ?>" data-valor="<?php echo $p['valor_parcela']; ?>" onchange="calcularSelecao()">
                                    <?php else: ?>
                                        <i class="fas <?php echo $p['status']=='Pago'?'fa-check-circle text-success':'fa-clock text-warning'; ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold">Parcela <?php echo $p['parcela_numero']; ?> / <?php echo $provisao['quantidade_parcelas']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($p['data_vencimento'])); ?></td>
                                <td class="fw-bold">R$ <?php echo number_format($p['valor_parcela'], 2, ',', '.'); ?></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill <?php echo $p['status']=='Pago'?'bg-success':($p['status']=='Gerado'?'bg-warning text-dark':'bg-light text-dark border'); ?>">
                                        <?php echo $p['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RESUMO E ANTECIPAÇÃO -->
        <div class="col-md-4">
            <div class="sticky-summary">
                <div class="card card-detalhe p-4 mb-4">
                    <h6 class="fw-bold text-muted mb-3 text-uppercase small">Resumo do Acordo</h6>
                    <div class="d-flex justify-content-between mb-2"><span>Total:</span> <b class="text-dark">R$ <?php echo number_format($provisao['valor_total'],2,',','.'); ?></b></div>
                    <div class="d-flex justify-content-between mb-2"><span>Pago:</span> <b class="text-success">R$ <?php echo number_format($total_pago,2,',','.'); ?></b></div>
                    <div class="d-flex justify-content-between border-top pt-2"><span>Saldo:</span> <b class="text-danger">R$ <?php echo number_format($provisao['valor_total'] - $total_pago,2,',','.'); ?></b></div>
                </div>

                <div class="card card-detalhe p-4 border-primary border-2 shadow">
                    <h5 class="fw-bold text-primary mb-3">Liquidar Seleção</h5>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">VALOR SELECIONADO</small>
                        <h2 class="fw-bold text-dark" id="display-soma">R$ 0,00</h2>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-muted">DESCONTO DE ANTECIPAÇÃO (R$)</label>
                        <input type="number" id="input_desconto" step="0.01" class="form-control form-control-lg border-primary" value="0.00" oninput="calcularSelecao()">
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-muted text-success">TOTAL A PAGAR AGORA</label>
                        <h3 class="fw-bold text-success" id="display-final">R$ 0,00</h3>
                    </div>

                    <button onclick="processarPagamentoLote()" id="btnPagar" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow" disabled>
                        LANÇAR NO FLUXO DE CAIXA
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let totalSelecionado = 0;

function calcularSelecao() {
    let soma = 0;
    const checks = document.querySelectorAll('.chk-parcela:checked');
    checks.forEach(c => {
        soma += parseFloat(c.getAttribute('data-valor'));
    });

    totalSelecionado = soma;
    const desconto = parseFloat(document.getElementById('input_desconto').value) || 0;
    const final = Math.max(0, totalSelecionado - desconto);

    document.getElementById('display-soma').innerText = soma.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});
    document.getElementById('display-final').innerText = final.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});
    
    document.getElementById('btnPagar').disabled = (soma <= 0);
}

function processarPagamentoLote() {
    const ids = Array.from(document.querySelectorAll('.chk-parcela:checked')).map(c => c.value);
    const desconto = document.getElementById('input_desconto').value;
    const final = totalSelecionado - desconto;

    if(!confirm(`Deseja liquidar ${ids.length} parcelas e lançar o valor de R$ ${final.toFixed(2)} no fluxo de caixa?`)) return;

    const fd = new FormData();
    fd.append('acao', 'liquidar_parcelas_provisao');
    fd.append('provisao_id', <?php echo $id_p; ?>);
    fd.append('ids_parcelas', ids.join(','));
    fd.append('valor_final', final);
    fd.append('desconto', desconto);

    fetch('acoes.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(res => {
        alert("Parcelas liquidadas e enviadas ao Financeiro!");
        location.reload();
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>