<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php"); exit;
}

$admin_id = $_SESSION['usuario_id'];
$feedback = "";

// --- AÇÃO: CADASTRAR USUÁRIO E ATRIBUIR MÓDULOS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_cadastrar'])) {
    $nome = trim($_POST['nome']);
    $cpf = trim($_POST['cpf']);
    $user = trim($_POST['usuario']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $quota = (int)$_POST['quota_mb'] * 1048576;
    $mods = $_POST['modulos'] ?? [];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, cpf, usuario, senha, quota_limite, data_criacao, nivel, status) VALUES (?, ?, ?, ?, ?, NOW(), 'usuario', 'ativo')");
        $stmt->execute([$nome, $cpf, $user, $senha, $quota]);
        $novo_id = $pdo->lastInsertId();

        foreach ($mods as $m_id) {
            $pdo->prepare("INSERT INTO usuarios_modulos (usuario_id, modulo_id) VALUES (?, ?)")->execute([$novo_id, $m_id]);
        }
        $pdo->commit();
        $feedback = "<div class='alert alert-success'>Usuário criado com sucesso!</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $feedback = "<div class='alert alert-danger'>Erro ao cadastrar. Verifique duplicidade.</div>";
    }
}

// Ações de Status
if (isset($_GET['acao']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['acao'] === 'excluir' && $id != $admin_id) $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
    if ($_GET['acao'] === 'suspender') $pdo->prepare("UPDATE usuarios SET status = 'suspenso' WHERE id = ?")->execute([$id]);
    if ($_GET['acao'] === 'ativar') $pdo->prepare("UPDATE usuarios SET status = 'ativo', data_criacao = NOW() WHERE id = ?")->execute([$id]);
    header("Location: admin_usuarios.php"); exit;
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC")->fetchAll();
$modulos_lista = $pdo->query("SELECT * FROM modulos ORDER BY nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Admin Usuários - BDS Cloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="p-4">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Gestão de Clientes e Módulos</h3>
        <div>
            <a href="admin_modulos.php" class="btn btn-dark rounded-pill px-4 me-2">Configurar Tecnologias</a>
            <button class="btn btn-primary rounded-pill px-4 me-2" data-bs-toggle="modal" data-bs-target="#modalNovo"><i class="fas fa-plus me-2"></i>Novo Usuário</button>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">Voltar</a>
        </div>
    </div>

    <?php echo $feedback; ?>

    <div class="card p-4">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>Nome</th><th>Usuário</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><b><?php echo $u['nome']; ?></b></td>
                    <td><?php echo $u['usuario']; ?></td>
                    <td><span class="badge <?php echo $u['status']=='ativo'?'bg-success':'bg-danger'; ?>"><?php echo $u['status']; ?></span></td>
                    <td>
                        <?php if($u['id'] != $admin_id): ?>
                        <a href="?acao=ativar&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></a>
                        <a href="?acao=suspender&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-warning mx-1"><i class="fas fa-ban"></i></a>
                        <a href="?acao=excluir&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir usuário?')"><i class="fas fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NOVO USUÁRIO -->
<div class="modal fade" id="modalNovo" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content p-4 border-0 shadow">
    <h5 class="fw-bold mb-4">Novo Usuário</h5>
    <div class="mb-3"><label class="small fw-bold">NOME COMPLETO</label><input type="text" name="nome" class="form-control" required></div>
    <div class="mb-3"><label class="small fw-bold">CPF</label><input type="text" name="cpf" class="form-control" required></div>
    <div class="mb-3"><label class="small fw-bold">E-MAIL (LOGIN)</label><input type="email" name="usuario" class="form-control" required></div>
    <div class="mb-3"><label class="small fw-bold">SENHA INICIAL</label><input type="password" name="senha" class="form-control" required></div>
    <div class="mb-3"><label class="small fw-bold">QUOTA EM MB</label><input type="number" name="quota_mb" class="form-control" value="1024"></div>
    
    <div class="mb-3">
        <label class="small fw-bold">LIBERAR TECNOLOGIAS:</label>
        <div class="p-3 bg-light rounded border">
            <?php foreach($modulos_lista as $m): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="modulos[]" value="<?php echo $m['id']; ?>" id="m_<?php echo $m['id']; ?>" checked>
                    <label class="form-check-label small" for="m_<?php echo $m['id']; ?>"><?php echo $m['nome']; ?></label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <button type="submit" name="btn_cadastrar" class="btn btn-primary w-100 rounded-pill py-2">CADASTRAR</button>
</form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>