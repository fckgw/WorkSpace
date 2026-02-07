<?php
/**
 * BDSoft Workspace - PORTAL AGRO CAMPO
 * Local: agrocampo/index.php
 */
session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) { 
    header("Location: ../login.php"); 
    exit; 
}

$user_id = $_SESSION['usuario_id'];
$user_nivel = $_SESSION['usuario_nivel'];

// Lógica de Permissões para carregar os submódulos
if (trim(strtolower($user_nivel)) === 'admin') {
    $stmt = $pdo->query("SELECT * FROM agro_submodulos ORDER BY id ASC");
    $submodulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT s.* FROM agro_submodulos s 
                           INNER JOIN usuarios_agro_permissões up ON s.id = up.submodulo_id 
                           WHERE up.usuario_id = ? ORDER BY s.id ASC");
    $stmt->execute([$user_id]);
    $submodulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroCampo - Painel de Tecnologias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f4f0; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; }
        .agro-card {
            background: #ffffff;
            border: 1px solid #e0e6ed;
            border-radius: 24px;
            padding: 40px 25px;
            text-align: center;
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
            text-decoration: none;
            color: #334155;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            position: relative;
        }
        .agro-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(45, 90, 39, 0.15);
            border-color: #2d5a27;
            color: #2d5a27;
        }
        .agro-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            background: #f8fafc;
            width: 90px;
            height: 90px;
            line-height: 90px;
            border-radius: 20px;
            transition: 0.3s;
            color: #2d5a27;
        }
        .agro-card:hover .agro-icon { background: #eef7ee; }
        .agro-title { font-weight: 700; font-size: 1.2rem; margin-bottom: 10px; }
        .agro-desc { font-size: 0.85rem; color: #64748b; line-height: 1.4; }
        .admin-tag { position: absolute; top: 15px; right: 15px; font-size: 0.6rem; background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 10px; font-weight: 800; text-transform: uppercase; }
    </style>
</head>
<body>

<nav class="navbar bg-white border-bottom shadow-sm py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <span class="fw-bold text-success fs-4"><i class="fas fa-seedling me-2"></i>AgroCampo</span>
        <a href="../portal.php" class="btn btn-outline-dark btn-sm rounded-pill px-4 fw-bold">WORKSPACE</a>
    </div>
</nav>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold text-dark">Ambientes de Gestão</h1>
        <p class="text-muted fs-5">Selecione uma tecnologia agrícola para iniciar.</p>
    </div>

    <div class="row g-4 justify-content-center">
        <?php foreach ($submodulos as $sub): 
            $link = ($sub['slug'] == 'index.php') ? 'talhoes.php' : $sub['slug'];
        ?>
        <div class="col-lg-4 col-md-6">
            <a href="<?php echo htmlspecialchars($link); ?>" class="agro-card">
                <div class="agro-icon"><i class="fas <?php echo htmlspecialchars($sub['icone']); ?>"></i></div>
                <div class="agro-title"><?php echo htmlspecialchars($sub['nome']); ?></div>
                <div class="agro-desc"><?php echo htmlspecialchars($sub['descricao']); ?></div>
            </a>
        </div>
        <?php endforeach; ?>

        <!-- CARD ADMINISTRATIVO AGRO (Só para Admin) -->
        <?php if (trim(strtolower($user_nivel)) === 'admin'): ?>
        <div class="col-lg-4 col-md-6">
            <a href="admin_config.php" class="agro-card border-danger border-opacity-25 bg-light">
                <span class="admin-tag">Configurações</span>
                <div class="agro-icon text-danger"><i class="fas fa-cogs"></i></div>
                <div class="agro-title text-danger">Gestão do Módulo</div>
                <div class="agro-desc">Gerenciar tecnologias internas, permissões de usuários e nomes dos ambientes.</div>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>