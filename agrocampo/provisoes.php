<?php
/**
 * BDSoft Workspace - PROVISIONAMENTO (VERSÃO COMPLETA COM CALCULADORA)
 * Local: agrocampo/provisoes.php
 */
session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }
$user_id = $_SESSION['usuario_id'];

// Buscar as provisões cadastradas
$stmt = $pdo->prepare("SELECT * FROM agro_provisoes WHERE usuario_id = ? ORDER BY data_criacao DESC");
$stmt->execute([$user_id]);
$provisoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provisionamento - BDSoft Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f4; display: flex; min-height: 100vh; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .main-wrapper { flex: 1; margin-left: 280px; padding: 40px; width: calc(100% - 280px); transition: 0.3s; }
        .card-prov { border: none; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: #fff; height: 100%; }
        
        /* Estilo da Tabela de Parcelas */
        .table-parcelas { font-size: 12px; margin-top: 15px; display: none; background: #fafafa; border-radius: 10px; padding: 10px; border: 1px solid #eee; }
        .btn-eye { cursor: pointer; color: #1a73e8; font-size: 1.1rem; transition: 0.2s; }
        
        /* Area de Cálculo no Modal */
        .calc-preview { background: #e8f0fe; border: 2px dashed #1a73e8; border-radius: 15px; padding: 15px; margin: 15px 0; }

        @media (max-width: 991px) { .main-wrapper { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>

<?php include 'sidebar_agro.php'; ?>

<div class="main-wrapper">
    
    <?php if(isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-pill mb-4 text-center">
            <i class="fas fa-check-circle me-2"></i> Operação realizada com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark mb-0">Provisionamento</h2>
            <p class="text-muted small">Gestão de Compras Parceladas e Acordos</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoProv">
            <i class="fas fa-plus me-2"></i>NOVA PROVISÃO
        </button>
    </div>

    <div class="row g-4">
        <?php if (empty($provisoes)): ?>
            <div class="col-12 text-center py-5 opacity-50">
                <i class="fas fa-calendar-alt fa-4x mb-3"></i>
                <h5>Nenhum parcelamento registrado.</h5>
            </div>
        <?php else: ?>
            <?php foreach($provisoes as $p): 
                // Buscar as parcelas deste acordo
                $stmt_p = $pdo->prepare("SELECT * FROM agro_provisoes_parcelas WHERE provisao_id = ? ORDER BY parcela_numero ASC");
                $stmt_p->execute([$p['id']]);
                $parcelas = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card card-prov p-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 80%;"><?php echo htmlspecialchars($p['nome_provisao']); ?></h5>
                        <div class="d-flex gap-2">
                            <i class="fas fa-eye btn-eye" onclick="toggleTabela(<?php echo $p['id']; ?>)" title="Ver Parcelas"></i>
                            <a href="acoes.php?acao=excluir_provisao&id=<?php echo $p['id']; ?>" class="text-danger opacity-25" onclick="return confirm('Excluir este acordo?')"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </div>

                    <h3 class="fw-bold text-primary mb-0">R$ <?php echo number_format($p['valor_total'], 2, ',', '.'); ?></h3>
                    <small class="text-muted"><?php echo $p['quantidade_parcelas']; ?>x de R$ <?php echo number_format($p['valor_total'] / $p['quantidade_parcelas'], 2, ',', '.'); ?></small>

                    <?php if(!empty($p['observacao'])): ?>
                        <div class="mt-3 p-2 bg-light rounded small text-muted border-start border-3 border-primary">
                            <?php echo nl2br(htmlspecialchars($p['observacao'])); ?>
                        </div>
                    <?php endif; ?>

                    <!-- TABELA DE PARCELAS OCULTA (ACIONADA PELO OLHO) -->
                    <div class="table-parcelas" id="tabela_<?php echo $p['id']; ?>">
                        <table class="table table-sm table-borderless mt-2 mb-0">
                            <thead>
                                <tr class="text-muted small" style="border-bottom: 1px solid #eee;">
                                    <th>PARC.</th>
                                    <th>VENCIMENTO</th>
                                    <th class="text-end">STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($parcelas as $pa): ?>
                                <tr>
                                    <td><?php echo $pa['parcela_numero']; ?>/<?php echo $p['quantidade_parcelas']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($pa['data_vencimento'])); ?></td>
                                    <td class="text-end">
                                        <span class="badge <?php echo $pa['status'] == 'Pendente' ? 'bg-light text-dark border' : 'bg-success'; ?> rounded-pill" style="font-size: 9px;">
                                            <?php echo $pa['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <a href="detalhe_provisao.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-primary btn-sm w-100 mt-4 rounded-pill fw-bold">PAGAR / ANTECIPAR</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL NOVA PROVISÃO COM CALCULADORA -->
<div class="modal fade" id="modalNovoProv" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 bg-primary text-white p-4">
                <h5 class="fw-bold mb-0">Novo Lançamento Parcelado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="acao" value="gerar_provisao">
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted text-uppercase">Descrição do Acordo</label>
                    <input type="text" name="nome" class="form-control" placeholder="Ex: Financiamento de Gado" required>
                </div>

                <div class="row">
                    <div class="col-7 mb-3">
                        <label class="small fw-bold text-muted text-uppercase">Valor Total (R$)</label>
                        <input type="text" id="calc_total" name="valor_bruto_input" class="form-control form-control-lg fw-bold" placeholder="0,00" required>
                    </div>
                    <div class="col-5 mb-3">
                        <label class="small fw-bold text-muted text-uppercase">Nº Parcelas</label>
                        <input type="number" id="calc_qtd" name="parcelas" class="form-control form-control-lg fw-bold" value="12" min="1" required>
                    </div>
                </div>

                <!-- PREVISÃO DA PARCELA (CALCULADORA) -->
                <div class="calc-preview text-center">
                    <small class="text-primary fw-bold text-uppercase">Valor Mensal Estimado</small>
                    <h2 class="fw-bold text-dark mb-0" id="display_parcela">R$ 0,00</h2>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="small fw-bold text-muted text-uppercase">Vencimento da 1ª Parcela</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="small fw-bold text-muted text-uppercase">Observações</label>
                    <textarea name="observacao" class="form-control" rows="3" maxlength="1000" placeholder="Detalhes do contrato..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow">GERAR CRONOGRAMA</button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function(){
    // Máscara para dinheiro brasileiro
    $('#calc_total').mask('#.##0,00', {reverse: true});

    // Função Calculadora em Tempo Real
    function calcularParcela() {
        let totalStr = $('#calc_total').val().replace(/\./g, '').replace(',', '.');
        let total = parseFloat(totalStr) || 0;
        let qtd = parseInt($('#calc_qtd').val()) || 1;
        
        let valorParcela = total / qtd;
        
        document.getElementById('display_parcela').innerText = valorParcela.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'});
    }

    $('#calc_total, #calc_qtd').on('keyup change', calcularParcela);
});

// Alternar visibilidade das parcelas no card
function toggleTabela(id) {
    const el = document.getElementById('tabela_' + id);
    el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}
</script>
</body>
</html>