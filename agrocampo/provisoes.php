<?php
/**
 * BDSoft Workspace - PROVISIONAMENTO (VERSÃO COM MÁSCARA FINANCEIRA)
 */
session_start();
require_once '../config.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }
$user_id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("SELECT * FROM agro_provisoes WHERE usuario_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$provisoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Provisionamento - BDSoft Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f4f7f4; display: flex; }
        .main-wrapper { flex: 1; margin-left: 280px; padding: 40px; }
        .card-prov { border: none; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: #fff; }
        .preview-parcela { background: #e8f0fe; padding: 10px; border-radius: 10px; border: 1px dashed #1a73e8; margin-top: 10px; }
    </style>
</head>
<body>

<?php include 'sidebar_agro.php'; ?>

<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold">Provisionamento de Caixa</h2>
        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalNovoProv">+ NOVA PROVISÃO</button>
    </div>

    <div class="row g-4">
        <?php foreach($provisoes as $p): ?>
        <div class="col-md-4">
            <div class="card card-prov p-4">
                <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($p['nome_provisao']); ?></h5>
                <h3 class="fw-bold text-primary">R$ <?php echo number_format($p['valor_total'], 2, ',', '.'); ?></h3>
                <p class="text-muted small"><?php echo $p['quantidade_parcelas']; ?> parcelas de R$ <?php echo number_format($p['valor_total']/$p['quantidade_parcelas'], 2, ',', '.'); ?></p>
                <a href="detalhe_provisao.php?id=<?php echo $p['id']; ?>" class="btn btn-dark w-100 mt-3 rounded-pill fw-bold">VER PARCELAS / PAGAR</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MODAL: NOVA PROVISÃO (COM CALCULADORA) -->
<div class="modal fade" id="modalNovoProv" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-primary text-white"><h5>Cadastrar Compra Parcelada</h5></div>
            <div class="modal-body p-4">
                <input type="hidden" name="acao" value="gerar_provisao">
                
                <div class="mb-3">
                    <label class="small fw-bold">DESCRIÇÃO</label>
                    <input type="text" name="nome" class="form-control" placeholder="Ex: Investimento Maquinário" required>
                </div>

                <div class="row">
                    <div class="col-7 mb-3">
                        <label class="small fw-bold">VALOR TOTAL (R$)</label>
                        <!-- Usamos type="text" para a máscara funcionar com vírgula -->
                        <input type="text" id="valor_total_mask" name="total_visual" class="form-control form-control-lg money" placeholder="0,00" required>
                    </div>
                    <div class="col-5 mb-3">
                        <label class="small fw-bold">PARCELAS</label>
                        <input type="number" id="qtd_parcelas" name="parcelas" class="form-control form-control-lg" value="12" min="1" required>
                    </div>
                </div>

                <!-- PREVIEW DO CÁLCULO -->
                <div class="preview-parcela text-center">
                    <small class="text-muted d-block">VALOR ESTIMADO DA PARCELA</small>
                    <h4 class="fw-bold text-primary mb-0" id="valor_preview">R$ 0,00</h4>
                </div>

                <div class="mt-3">
                    <label class="small fw-bold">VENCIMENTO DA 1ª PARCELA</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="modal-footer border-0">
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
    // Máscara para o valor total (Padrão: 25.902,36)
    $('#valor_total_mask').mask('#.##0,00', {reverse: true});

    // Função para calcular a parcela em tempo real
    function calcular() {
        let totalStr = $('#valor_total_mask').val().replace(/\./g, '').replace(',', '.');
        let total = parseFloat(totalStr) || 0;
        let qtd = parseInt($('#qtd_parcelas').val()) || 1;
        
        let parcela = total / qtd;
        $('#valor_preview').text(parcela.toLocaleString('pt-br', {style: 'currency', currency: 'BRL'}));
    }

    $('#valor_total_mask, #qtd_parcelas').on('keyup change', calcular);
});
</script>
</body>
</html>