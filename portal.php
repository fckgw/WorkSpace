<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }
require_once 'config.php';

$user_id = $_SESSION['usuario_id'];
$nivel   = $_SESSION['usuario_nivel'];

/**
 * LÓGICA DE VISUALIZAÇÃO:
 * Se nível for 'admin' (em minúsculo), busca tudo da tabela módulos.
 */
if (trim(strtolower($nivel)) === 'admin') {
    $stmt = $pdo->query("SELECT * FROM modulos ORDER BY nome ASC");
    $meus_modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT m.* FROM modulos m 
                           INNER JOIN usuarios_modulos um ON m.id = um.modulo_id 
                           WHERE um.usuario_id = ? ORDER BY m.nome ASC");
    $stmt->execute([$user_id]);
    $meus_modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Workspace - BDSoft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f9; font-family: 'Segoe UI', sans-serif; }
        .app-card { background: #fff; border: 1px solid #e0e6ed; border-radius: 20px; padding: 30px; text-align: center; transition: 0.3s; text-decoration: none; color: #334155; display: block; height: 100%; position: relative; }
        .app-card:hover { transform: translateY(-5px); border-color: #1a73e8; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .app-icon { font-size: 3rem; margin-bottom: 15px; color: #1a73e8; }
        .app-title { font-weight: 700; font-size: 1.1rem; margin-bottom: 5px; }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h3 class="fw-bold text-primary"><i class="fas fa-th-large me-2"></i>BDSoft Workspace</h3>
        <div class="text-end">
            <small class="text-muted d-block">Logado como: <b><?php echo $_SESSION['usuario_nome']; ?></b></small>
            <span class="badge bg-info"><?php echo strtoupper($nivel); ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger ms-2">Sair</a>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <?php if (empty($meus_modulos)): ?>
            <div class="col-md-6">
                <div class="alert alert-warning text-center shadow-sm">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h5>Nenhum módulo encontrado</h5>
                    <p>Você está logado como <b><?php echo $nivel; ?></b>, mas a tabela de módulos está vazia ou seu acesso não foi liberado.</p>
                    <a href="ativar_admin.php" class="btn btn-dark btn-sm">Rodar Ativador de Admin</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($meus_modulos as $mod): ?>
            <div class="col-md-4 col-sm-6">
                <a href="<?php echo $mod['slug']; ?>" class="app-card">
                    <i class="fas <?php echo $mod['icone']; ?> app-icon"></i>
                    <div class="app-title"><?php echo $mod['nome']; ?></div>
                    <div class="app-desc small text-muted"><?php echo $mod['descricao']; ?></div>
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Atalho de Administração -->
        <?php if (trim(strtolower($nivel)) === 'admin'): ?>
            <div class="col-md-4 col-sm-6">
                <a href="admin_usuarios.php" class="app-card border-danger border-opacity-25">
                    <i class="fas fa-user-shield app-icon text-danger"></i>
                    <div class="app-title text-danger">Painel Admin</div>
                    <div class="app-desc small text-muted">Gerenciar o sistema global.</div>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>