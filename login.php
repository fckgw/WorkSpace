<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$erro_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_login = trim($_POST['usuario']);
    $senha_login = trim($_POST['senha']);

    if (!empty($usuario_login) && !empty($senha_login)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
        $stmt->execute([$usuario_login]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha_login, $user['senha'])) {
            
            // Verificação de 14 dias para usuários comuns
            $data_criacao = new DateTime($user['data_criacao']);
            $hoje = new DateTime();
            $dias = $hoje->diff($data_criacao)->days;

            if ($dias > 14 && $user['nivel'] !== 'admin') {
                $erro_msg = "Seu período de teste no BDSoft Workspace expirou.";
            } elseif ($user['status'] === 'suspenso') {
                $erro_msg = "Sua conta está suspensa. Contate o administrador.";
            } else {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['usuario_nivel'] = $user['nivel'];
                $_SESSION['ultimo_acesso_info'] = ($user['ultimo_acesso']) ? date('d/m/Y H:i', strtotime($user['ultimo_acesso'])) : "Primeiro Acesso";

                $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?")->execute([$user['id']]);
                registrarLog($pdo, $user['id'], "Login", "Acesso ao BDSoft Workspace.");

                header("Location: index.php");
                exit;
            }
        } else {
            $erro_msg = "Usuário ou senha inválidos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BDSoft Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #ffffff; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .card-login { width: 100%; max-width: 400px; padding: 40px; border-radius: 20px; border: 1px solid #f0f0f0; box-shadow: 0 15px 35px rgba(0,0,0,0.05); }
        .input-group-text { background: #fff; cursor: pointer; border-left: none; color: #6c757d; }
        .form-control { border-right: none; padding: 12px; }
        .form-control:focus { box-shadow: none; border-color: #dee2e6; }
        .btn-primary { padding: 12px; font-weight: bold; border-radius: 10px; background-color: #1a73e8; border: none; }
    </style>
</head>
<body>
<div class="card-login text-center">
    <i class="fas fa-th-large text-primary fa-4x mb-3"></i>
    <h3 class="fw-bold text-dark">BDSoft Workspace</h3>
    <p class="text-muted small mb-4">Gerenciador de Tecnologias Cloud</p>

    <?php if($erro_msg) echo "<div class='alert alert-danger py-2 small'>$erro_msg</div>"; ?>

    <form method="POST" class="text-start">
        <div class="mb-3">
            <label class="form-label small fw-bold text-muted">USUÁRIO OU E-MAIL</label>
            <input type="text" name="usuario" class="form-control" style="border-right: 1px solid #dee2e6;" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold text-muted">SENHA</label>
            <div class="input-group">
                <input type="password" name="senha" id="pass" class="form-control" required>
                <span class="input-group-text" onclick="togglePassword()"><i class="fas fa-eye" id="eye"></i></span>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 shadow-sm">ACESSAR WORKSPACE</button>
    </form>
    <div class="mt-4 small"><a href="registro.php" class="text-decoration-none text-muted">Ainda não tem conta? <span class="text-primary fw-bold">Cadastre-se</span></a></div>
</div>
<script>
    function togglePassword() {
        const p = document.getElementById('pass');
        const i = document.getElementById('eye');
        if (p.type === 'password') { p.type = 'text'; i.classList.replace('fa-eye', 'fa-eye-slash'); }
        else { p.type = 'password'; i.classList.replace('fa-eye-slash', 'fa-eye'); }
    }
</script>
</body>
</html>