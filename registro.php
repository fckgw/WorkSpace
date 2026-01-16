<?php
require_once 'config.php';
$msg = ""; $sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $cpf = trim($_POST['cpf']);
    $usuario = trim($_POST['usuario']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, cpf, usuario, senha, data_criacao, status, nivel, quota_limite) VALUES (?, ?, ?, ?, NOW(), 'ativo', 'usuario', 1073741824)");
        $stmt->execute([$nome, $cpf, $usuario, $senha]);

        // E-MAIL DE BOAS-VINDAS
        $assunto = "Bem-vindo ao BDSoft Workspace";
        $corpo = "<html><body><h2>Ola, $nome!</h2><p>Sua conta no <b>BDSoft Workspace</b> esta ativa.</p><p>Usuario: $usuario</p><p>Acesse em: https://driverbds.tecnologia.ws/</p></body></html>";
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: suporte@driverbds.tecnologia.ws";
        
        @mail($usuario, $assunto, $corpo, $headers);
        $sucesso = true;
    } catch (Exception $e) { $msg = "E-mail ou CPF já cadastrado."; }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><title>Cadastro - BDSoft Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height:100vh">
    <div class="card mx-auto p-4 shadow border-0" style="width:450px; border-radius:20px;">
        <h4 class="text-center fw-bold">Criar Conta</h4>
        <?php if($msg) echo "<div class='alert alert-danger'>$msg</div>"; ?>
        <?php if($sucesso) { echo "<div class='alert alert-success'>Conta criada! <a href='login.php'>Login</a></div>"; } else { ?>
        <form method="POST">
            <input type="text" name="nome" class="form-control mb-3" placeholder="Nome Completo" required>
            <input type="text" name="cpf" id="cpf" class="form-control mb-3" placeholder="000.000.000-00" required>
            <input type="email" name="usuario" class="form-control mb-3" placeholder="Seu E-mail" required>
            <input type="password" name="senha" class="form-control mb-4" placeholder="Crie uma Senha" required>
            <button class="btn btn-primary w-100 py-2 rounded-pill">REGISTRAR</button>
        </form>
        <?php } ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>$('#cpf').mask('000.000.000-00');</script>
</body>
</html>