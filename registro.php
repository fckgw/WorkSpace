<?php
/**
 * BDSoft Workspace - CADASTRO COM ENVIO SMTP
 * Localização: public_html/registro.php
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Caminhos para carregar a biblioteca (Certifique-se de que os arquivos estão nesta pasta)
require 'includes/PHPMailer/Exception.php';
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

$mensagem = "";
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $cpf = trim($_POST['cpf']);
    $rg = trim($_POST['rg']);
    $email = trim($_POST['email']);
    $cupom = trim($_POST['cupom']);

    try {
        // 1. Validar se CPF ou E-mail já existem
        $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? OR cpf = ?");
        $stmt_check->execute([$email, $cpf]);

        if ($stmt_check->rowCount() > 0) {
            $mensagem = "Atenção: Este E-mail ou CPF já está cadastrado.";
        } else {
            // 2. Gerar Senha Aleatória
            $senha_temp = substr(str_shuffle("abcdefghjkmnpqrstuvwxyz23456789"), 0, 8);
            $senha_hash = password_hash($senha_temp, PASSWORD_DEFAULT);

            $pdo->beginTransaction();

            // 3. Inserir Usuário no Banco
            $sql = "INSERT INTO usuarios (nome, cpf, rg, usuario, senha, trocar_senha, data_criacao, nivel, status, quota_limite) 
                    VALUES (?, ?, ?, ?, ?, 1, NOW(), 'usuario', 'ativo', 1073741824)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $cpf, $rg, $email, $senha_hash]);
            $novo_id = $pdo->lastInsertId();

            // 4. Enviar E-mail via SMTP (Office 365)
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'email-ssl.com.br';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'souzafelipe@bdsoft.com.br';
            $mail->Password   = 'BDSoft@2020';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('souzafelipe@bdsoft.com.br', 'BDSoft Workspace');
            $mail->addAddress($email, $nome);

            $mail->isHTML(true);
            $mail->Subject = 'Sua Senha de Acesso - BDSoft Workspace';
            $mail->Body    = "
                <div style='font-family:sans-serif; color:#333;'>
                    <h2>Bem-vindo ao BDSoft Workspace, $nome!</h2>
                    <p>Sua conta foi criada com sucesso. Use as credenciais abaixo para seu primeiro acesso:</p>
                    <div style='background:#f4f7f6; padding:20px; border-radius:10px; border:1px solid #ddd;'>
                        <strong>Usuário:</strong> $email<br>
                        <strong>Senha Temporária:</strong> <span style='color:red; font-size:18px; font-weight:bold;'>$senha_temp</span>
                    </div>
                    <p><strong>Aviso:</strong> Por segurança, você deverá alterar esta senha ao logar pela primeira vez.</p>
                </div>";

            $mail->send();
            $pdo->commit();
            $sucesso = true;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensagem = "Erro no processo: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - BDSoft Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .reg-card { width: 100%; max-width: 500px; background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.08); border: 1px solid #eee; }
        .form-control { padding: 12px; border-radius: 10px; }
        .btn-primary { padding: 12px; border-radius: 10px; font-weight: bold; background-color: #1a73e8; border: none; }
    </style>
</head>
<body>
<div class="reg-card">
    <div class="text-center mb-4">
        <h3 class="fw-bold">Cadastre-se</h3>
        <p class="text-muted small">BDSoft Workspace - Tecnologia e Gestão</p>
    </div>

    <?php if($sucesso): ?>
        <div class="alert alert-success text-center p-4">
            <h5 class="fw-bold">Tudo pronto!</h5>
            <p>Sua senha temporária foi enviada para <strong><?php echo $email; ?></strong>.</p>
            <a href="login.php" class="btn btn-primary w-100 mt-3 rounded-pill">IR PARA LOGIN</a>
        </div>
    <?php else: ?>
        <?php if($mensagem) echo "<div class='alert alert-danger small text-center mb-4'>$mensagem</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="small fw-bold">NOME COMPLETO</label>
                <input type="text" name="nome" class="form-control" placeholder="Seu nome" required>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="small fw-bold">CPF</label>
                    <input type="text" name="cpf" id="cpf" class="form-control" placeholder="000.000.000-00" required>
                </div>
                <div class="col-6 mb-3">
                    <label class="small fw-bold">RG</label>
                    <input type="text" name="rg" id="rg" class="form-control" placeholder="00.000.000-0" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="small fw-bold">E-MAIL</label>
                <input type="email" name="email" class="form-control" placeholder="contato@exemplo.com" required>
            </div>
            <div class="mb-4">
                <label class="small fw-bold">CUPOM (OPCIONAL)</label>
                <input type="text" name="cupom" class="form-control" placeholder="Código de desconto">
            </div>
            <button type="submit" class="btn btn-primary w-100 shadow-sm">CADASTRAR E RECEBER SENHA</button>
            <div class="text-center mt-3"><a href="login.php" class="text-decoration-none small text-muted">Já tenho conta</a></div>
        </form>
    <?php endif; ?>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    $(document).ready(function(){
        $('#cpf').mask('000.000.000-00');
        $('#rg').mask('00.000.000-0');
    });
</script>
</body>
</html>