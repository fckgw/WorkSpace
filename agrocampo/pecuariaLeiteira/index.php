<?php
/**
 * BDSoft Workspace - AGRO MONITORAMENTO
 * Local: agrocampo/talhoes.php
 */
session_start();
require_once '../config.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

$user_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT * FROM agro_talhoes WHERE usuario_id = ? ORDER BY nome ASC");
$stmt->execute([$user_id]);
$talhoes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Monitoramento de Campo - AgroCampo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- INCLUI SIDEBAR (Contém o CSS Mestre) -->
<?php include 'sidebar_agro.php'; ?>

<!-- CONTEÚDO ENVOLVIDO NO WRAPPER -->
<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold">Talhões e Glebas</h2>
        <button class="btn btn-success rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalNovo">+ NOVO TALHÃO</button>
    </div>

    <div class="row g-4">
        <?php foreach($talhoes as $t): ?>
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm rounded-4" style="border-bottom: 5px solid #2d5a27; background:#fff;">
                    <h5 class="fw-bold"><?php echo htmlspecialchars($t['nome']); ?></h5>
                    <p class="text-muted small">Área: <?php echo $t['area_ha']; ?> ha</p>
                    <div class="d-flex justify-content-end">
                        <a href="acoes.php?del_talhao=<?php echo $t['id']; ?>" class="text-danger small"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>