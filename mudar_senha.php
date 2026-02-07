<?php
/**
 * BDSoft Workspace - TROCA DE SENHA OBRIGATÓRIA
 * Localização: public_html/mudar_senha.php
 */
session_start();
require_once 'config.php';

// Proteção: Só entra aqui se houver a flag de troca obrigatória na sessão
if (!isset($_SESSION['troca_obrigatoria']) || !isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$erro = "";
$sucesso_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    if (strlen($nova_senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($nova_senha !== $confirma_senha) {
        $erro = "As senhas não coincidem. Digite novamente.";
    } else {
        try {
            $id_usuario = $_SESSION['usuario_id'];
            $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            // Atualiza a senha e desativa a flag de troca obrigatória
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, trocar_senha = 0, ultimo_acesso = NOW() WHERE id = ?");
            $stmt->execute([$novo_hash, $id_usuario]);

            // Limpa a trava da sessão
            unset($_SESSION['troca_obrigatoria']);

            // Registra Log
            if(function_exists('registrarLog')) {
                registrarLog($pdo, $id_usuario, "Segurança", "O usuário realizou a troca de senha obrigatória.");
            }

            $sucesso_msg = "Senha alterada com sucesso! Redirecionando...";
            header("refresh:2;url=index.php");
            
        } catch (Exception $e) {
            $erro = "Erro ao salvar nova senha. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segurança - Trocar Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .change-card { width: 100%; max-width: 400px; padding: 40px; background: #fff; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .form-control { padding: 12px; border-radius: 10px; }
    </style>
</head>
<body>

<div class="change-card">
    <div class="text-center mb-4">
        <i class="fas fa-shield-alt text-success fa-3x mb-3"></i>
        <h4 class="fw-bold">Nova Senha</h4>
        <p class="text-muted small">Para sua segurança, defina uma senha pessoal para continuar acessando o Workspace.</p>
    </div>

    <?php if($erro): ?>
        <div class="alert alert-danger py-2 small text-center"><?php echo $erro; ?></div>
    <?php endif; ?>

    <?php if($sucesso_msg): ?>
        <div class="alert alert-success py-2 small text-center"><?php echo $sucesso_msg; ?></div>
    <?php else: ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">NOVA SENHA</label>
                <input type="password" name="nova_senha" class="form-control" placeholder="Mínimo 6 dígitos" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">CONFIRME A NOVA SENHA</label>
                <input type="password" name="confirma_senha" class="form-control" placeholder="Repita a senha" required>
            </div>
            <button type="submit" class="btn btn-success w-100 py-2 fw-bold rounded-pill">SALVAR E ACESSAR</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>