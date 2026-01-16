<?php
/**
 * BDSoft Workspace - AÇÕES DE PASTAS
 */
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

$user_id = $_SESSION['usuario_id'];

// --- CRIAR NOVA PASTA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_pasta'])) {
    $nome = trim($_POST['nome_pasta']);
    $pai_id = (!empty($_POST['pai_id']) && $_POST['pai_id'] !== 'null') ? (int)$_POST['pai_id'] : null;

    if (!empty($nome)) {
        try {
            $sql = "INSERT INTO pastas (nome, usuario_id, pai_id, data_criacao) VALUES (?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $user_id, $pai_id]);

            registrarLog($pdo, $user_id, "Criar Pasta", "Pasta criada: $nome");

            // Redireciona mantendo o usuário dentro da pasta onde ele estava
            $redirect = "dashboard.php" . ($pai_id ? "?pasta=$pai_id" : "");
            header("Location: $redirect");
            exit;
        } catch (PDOException $e) {
            die("Erro ao criar pasta: " . $e->getMessage());
        }
    }
}

// --- MOVER ARQUIVO OU PASTA (VIA AJAX) ---
if (isset($_GET['mover_arq']) && isset($_GET['para_pasta'])) {
    $arq_id = (int)$_GET['mover_arq'];
    $destino = (int)$_GET['para_pasta'];
    $stmt = $pdo->prepare("UPDATE arquivos SET pasta_id = ? WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$destino, $arq_id, $user_id]);
    echo "Sucesso";
    exit;
}

if (isset($_GET['mover_pasta']) && isset($_GET['para_pasta'])) {
    $origem = (int)$_GET['mover_pasta'];
    $destino = (int)$_GET['para_pasta'];
    if ($origem !== $destino) {
        $stmt = $pdo->prepare("UPDATE pastas SET pai_id = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$destino, $origem, $user_id]);
    }
    echo "Sucesso";
    exit;
}

// --- EXCLUIR PASTA ---
if (isset($_GET['del_pasta'])) {
    $id_pasta = (int)$_GET['del_pasta'];
    // Deleta os arquivos da pasta do banco (os arquivos físicos permanecem na pasta user_x para segurança, ou você pode adicionar unlink aqui)
    $pdo->prepare("DELETE FROM arquivos WHERE pasta_id = ? AND usuario_id = ?")->execute([$id_pasta, $user_id]);
    $pdo->prepare("DELETE FROM pastas WHERE id = ? AND usuario_id = ?")->execute([$id_pasta, $user_id]);
    
    header("Location: dashboard.php");
    exit;
}