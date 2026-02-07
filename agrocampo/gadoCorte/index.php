<?php
/**
 * BDSoft Workspace - GADO DE CORTE (BI CORRIGIDO)
 */
session_start();
require_once '../../config.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: ../../login.php"); exit; }
$user_id = $_SESSION['usuario_id'];

// CORREÇÃO DA QUERY ( JOIN COM PROPRIEDADES )
$stmt_anis = $pdo->prepare("SELECT a.*, l.nome_lote FROM agro_gadocorte_animais a 
                            INNER JOIN agro_gadocorte_lotes l ON a.lote_id = l.id 
                            INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id 
                            WHERE p.usuario_id = ?");
$stmt_anis->execute([$user_id]);
$animais = $stmt_anis->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas de Lotação
$stmt_area = $pdo->prepare("SELECT SUM(area_ha) FROM agro_gadocorte_propriedades WHERE usuario_id = ?");
$stmt_area->execute([$user_id]);
$area_total = $stmt_area->fetchColumn() ?: 0;

$total_cabs = count($animais);
$taxa_lotacao = ($area_total > 0) ? ($total_cabs / $area_total) : 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>BI Gado de Corte - BDSoft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { background: #f4f1ea; font-family: sans-serif; } .card-bi { background: #fff; border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold">Inteligência Estratégica</h2>
        <a href="abate.php" class="btn btn-danger rounded-pill px-4 fw-bold shadow"><i class="fas fa-file-pdf me-2"></i>RELATÓRIO DE ABATE</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card-bi p-4 text-center border-start border-success border-5">
                <small class="fw-bold text-muted uppercase">TAXA DE LOTAÇÃO</small>
                <h1 class="fw-bold mt-2"><?php echo number_format($taxa_lotacao, 2); ?> <small class="h6">cab/ha</small></h1>
                <p class="small text-muted mb-0">Total: <?php echo $total_cabs; ?> animais em <?php echo $area_total; ?> ha</p>
            </div>
        </div>
    </div>

    <div class="card-bi p-4">
        <h5 class="fw-bold mb-4">Manejo Individual Ativo</h5>
        <table class="table table-hover">
            <thead><tr><th>Brinco</th><th>Lote</th><th>Peso Atual</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach($animais as $an): ?>
                <tr>
                    <td><b>#<?php echo $an['brinco']; ?></b></td>
                    <td><?php echo $an['nome_lote']; ?></td>
                    <td><?php echo $an['peso_atual']; ?> kg</td>
                    <td><span class="badge <?php echo $an['status']=='Tratamento'?'bg-danger':'bg-success'; ?>"><?php echo $an['status']; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>