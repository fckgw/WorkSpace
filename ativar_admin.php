<?php
/**
 * SCRIPT DE ATIVAÇÃO SUPREMA - BDSoft Workspace
 * Resolve erros de chaves estrangeiras e garante acesso Admin
 */
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Você precisa estar logado para rodar este script. <a href='login.php'>Ir para Login</a>");
}

$id_logado = $_SESSION['usuario_id'];

try {
    // 1. Desativar verificações de chave estrangeira para permitir a limpeza
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 2. Limpar tabelas de módulos e permissões
    $pdo->exec("TRUNCATE TABLE usuarios_modulos");
    $pdo->exec("TRUNCATE TABLE modulos");

    // 3. Reativar verificações
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 4. Inserir os módulos padrão
    $stmtMod = $pdo->prepare("INSERT INTO modulos (nome, slug, icone, descricao) VALUES (?, ?, ?, ?)");
    
    $stmtMod->execute(['Cloud Drive', 'dashboard.php', 'fa-cloud', 'Armazenamento de arquivos e documentos.']);
    $id_drive = $pdo->lastInsertId();
    
    $stmtMod->execute(['Gestão de Projetos', 'projetos_home.php', 'fa-tasks', 'Gestão de tarefas estilo Monday.com.']);
    $id_projetos = $pdo->lastInsertId();

    // 5. Forçar o seu usuário a ser ADMINISTRADOR no banco
    $stmtUser = $pdo->prepare("UPDATE usuarios SET nivel = 'admin', status = 'ativo' WHERE id = ?");
    $stmtUser->execute([$id_logado]);

    // 6. Dar permissão total de acesso aos módulos para o seu usuário
    $stmtPerm = $pdo->prepare("INSERT INTO usuarios_modulos (usuario_id, modulo_id) VALUES (?, ?)");
    $stmtPerm->execute([$id_logado, $id_drive]);
    $stmtPerm->execute([$id_logado, $id_projetos]);

    // 7. Atualizar a SESSÃO atual para refletir a mudança sem precisar deslogar
    $_SESSION['usuario_nivel'] = 'admin';

    echo "<div style='font-family:sans-serif; padding:50px; text-align:center;'>";
    echo "<h1 style='color:green;'>✅ Ecossistema Resetado com Sucesso!</h1>";
    echo "<p>Seu usuário (ID: $id_logado) agora é <b>ADMINISTRADOR</b>.</p>";
    echo "<p>Os módulos foram recriados e vinculados à sua conta.</p>";
    echo "<br><a href='portal.php' style='padding:15px 30px; background:#1a73e8; color:white; text-decoration:none; border-radius:30px; font-weight:bold;'>ACESSAR PORTAL BDSOFT</a>";
    echo "</div>";

} catch (Exception $e) {
    // Garantir que a verificação de chaves volte ao normal mesmo em erro
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<h1 style='color:red;'>❌ Erro ao processar:</h1>" . $e->getMessage();
}