<?php
session_start();
require_once '../../config.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: ../../login.php"); exit; }

$user_id = $_SESSION['usuario_id'];

// Buscar Produção de Derivados
$stmt = $pdo->prepare("SELECT * FROM agro_laticinio_producao WHERE usuario_id = ? ORDER BY data_fabricacao DESC");
$stmt->execute([$user_id]);
$estoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Laticínios - BDSoft Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../includes/sidebar_agro.php'; ?>

<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark">Gestão de Laticínios</h2>
            <p class="text-muted">Controle de beneficiamento e estoque de derivados.</p>
        </div>
        <button class="btn btn-success rounded-pill px-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalNovoLaticinio">+ LANÇAR PRODUÇÃO</button>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small"><th>Data</th><th>Produto</th><th>Quantidade</th><th>Lote</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php foreach($estoque as $e): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($e['data_fabricacao'])); ?></td>
                    <td class="fw-bold"><?php echo htmlspecialchars($e['produto']); ?></td>
                    <td><?php echo $e['quantidade'] . ' ' . $e['unidade']; ?></td>
                    <td><span class="badge bg-light text-dark border"><?php echo $e['lote']; ?></span></td>
                    <td><a href="../acoes.php?del_laticinio=<?php echo $e['id']; ?>" class="text-danger"><i class="fas fa-trash-alt"></i></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>