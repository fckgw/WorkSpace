<?php
/**
 * BDSoft Workspace - PROCESSAMENTO DE UPLOAD
 */
session_start();
require_once 'config.php';

// Configurações para arquivos grandes
set_time_limit(1800);
ini_set('memory_limit', '1024M');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit(json_encode(['status' => 'error', 'message' => 'Sessão expirada.']));
}

$user_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivos'])) {
    
    $arquivos = $_FILES['arquivos'];
    $pasta_id = (!empty($_POST['pasta_id']) && $_POST['pasta_id'] !== 'null') ? (int)$_POST['pasta_id'] : null;

    // 1. Criar caminho físico: uploads/user_1/
    $diretorio_base = "uploads/user_" . $user_id . "/";
    if (!is_dir($diretorio_base)) {
        mkdir($diretorio_base, 0755, true);
        file_put_contents($diretorio_base . "index.php", ""); // Proteção
    }

    $sucessos = 0;

    foreach ($arquivos['name'] as $key => $nome_original) {
        if ($arquivos['error'][$key] === UPLOAD_ERR_OK) {
            
            $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
            $nome_sistema = uniqid("bds_") . "_" . date("His") . "." . $extensao;
            $caminho_final = $diretorio_base . $nome_sistema;

            if (move_uploaded_file($arquivos['tmp_name'][$key], $caminho_final)) {
                
                $sql = "INSERT INTO arquivos (nome_original, nome_sistema, caminho, tipo, tamanho, usuario_id, pasta_id, data_upload) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $nome_original, 
                    $nome_sistema, 
                    $caminho_final, 
                    $arquivos['type'][$key], 
                    $arquivos['size'][$key], 
                    $user_id, 
                    $pasta_id
                ]);
                $sucessos++;
            }
        }
    }

    registrarLog($pdo, $user_id, "Upload", "Enviou $sucessos arquivo(s).");
    echo json_encode(['status' => 'success', 'message' => 'Upload concluído.']);
}