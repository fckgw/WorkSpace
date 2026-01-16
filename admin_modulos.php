<?php
session_start();
require_once 'config.php';
if ($_SESSION['usuario_nivel'] !== 'admin') die("Acesso Negado");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_novo'])) {
    $stmt = $pdo->prepare("INSERT INTO modulos (nome, slug, icone, descricao) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['nome'], $_POST['slug'], $_POST['icone'], $_POST['descricao']]);
    header("Location: admin_modulos.php"); exit;
}

$modulos = $pdo->query("SELECT * FROM modulos")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><title>Tecnologias BDS Cloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { background: #f8f9fa; font-family: sans-serif; }</style>
</head>
<body class="p-5">
<div class="container">
    <div class="d-flex justify-content-between mb-4"><h3>Configurar Apps do Portal</h3><a href="admin_usuarios.php" class="btn btn-secondary">Voltar</a></div>
    
    <div class="card p-4 border-0 shadow-sm mb-4">
        <h5>Novo Aplicativo</h5>
        <form method="POST" class="row g-2">
            <div class="col-md-3"><input type="text" name="nome" class="form-control" placeholder="Nome" required></div>
            <div class="col-md-3"><input type="text" name="slug" class="form-control" placeholder="Link (ex: dash.php)" required></div>
            <div class="col-md-2"><input type="text" name="icone" class="form-control" placeholder="Ícone (fa-cloud)"></div>
            <div class="col-md-3"><input type="text" name="descricao" class="form-control" placeholder="Descrição"></div>
            <div class="col-md-1"><button name="btn_novo" class="btn btn-primary w-100">Add</button></div>
        </form>
    </div>

    <div class="card p-4 border-0 shadow-sm">
        <table class="table table-hover">
            <thead class="table-light"><tr><th>App</th><th>Link</th><th>Descrição</th></tr></thead>
            <tbody>
                <?php foreach($modulos as $m): ?>
                <tr>
                    <td><i class="fas <?php echo $m['icone']; ?> me-2 text-primary"></i><b><?php echo $m['nome']; ?></b></td>
                    <td><code><?php echo $m['slug']; ?></code></td>
                    <td class="small text-muted"><?php echo $m['descricao']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>