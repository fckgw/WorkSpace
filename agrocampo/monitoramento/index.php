<?php
session_start();
require_once '../../config.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: ../../login.php"); exit; }

$user_id = $_SESSION['usuario_id'];

// Buscar Talhões
$stmt = $pdo->prepare("SELECT * FROM agro_talhoes WHERE usuario_id = ? ORDER BY nome ASC");
$stmt->execute([$user_id]);
$talhoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Monitoramento - AgroCampo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include '../includes/sidebar_agro.php'; ?>

<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold">Monitoramento de Campo</h2>
            <p class="text-muted">Gestão de talhões e glebas produtivas.</p>
        </div>
        <button class="btn btn-success rounded-pill px-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalNovo">+ NOVO TALHÃO</button>
    </div>

    <div class="row g-4">
        <?php if(empty($talhoes)): ?>
            <div class="col-12 text-center py-5 opacity-50"><h5>Nenhum talhão mapeado.</h5></div>
        <?php else: ?>
            <?php foreach($talhoes as $t): ?>
                <div class="col-md-4">
                    <div class="card p-4 border-0 shadow-sm rounded-4" style="border-bottom: 5px solid #2d5a27;">
                        <h5 class="fw-bold"><?php echo htmlspecialchars($t['nome']); ?></h5>
                        <p class="text-muted small mb-0">Área: <?php echo $t['area_ha']; ?> ha</p>
                        <p class="text-success small fw-bold">Cultura: <?php echo $t['cultura_atual']; ?></p>
                        <div class="text-end mt-3">
                            <a href="../acoes.php?del_talhao=<?php echo $t['id']; ?>" class="text-danger"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>