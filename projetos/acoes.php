<?php
/**
 * BDSoft Workspace - MOTOR DE AÇÕES DO MÓDULO DE PROJETOS
 * Local: projetos/acoes.php
 */

// 1. Configurações de Depuração para ambiente de produção
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Aumentar limites para processamento de imagens e tempo de execução
set_time_limit(1200); 
ini_set('memory_limit', '512M');

session_start();

// 3. Importar conexão com o banco de dados
require_once __DIR__ . '/../config.php';

// 4. Verificação de Segurança de Sessão
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit("Erro: Sua sessão expirou. Por favor, realize o login novamente.");
}

$user_id_sessao = $_SESSION['usuario_id'];
$user_nivel_sessao = $_SESSION['usuario_nivel']; // 'admin' ou 'usuario'

try {
    
    // --- AÇÃO: BUSCAR EVIDÊNCIA (GET) ---
    if (isset($_GET['acao']) && $_GET['acao'] === 'get_evidencia') {
        $id_tarefa = (int)$_GET['id'];
        $stmt_ev = $pdo->prepare("SELECT evidencias FROM tarefas_projetos WHERE id = ?");
        $stmt_ev->execute([$id_tarefa]);
        echo $stmt_ev->fetchColumn() ?: "";
        exit;
    }

    // --- AÇÃO: EXCLUIR QUADRO COMPLETO (GET) ---
    if (isset($_GET['acao']) && $_GET['acao'] === 'deletar_quadro_completo') {
        $id_quadro_del = (int)$_GET['id'];
        if ($id_quadro_del > 0) {
            $sql_del_q = "DELETE FROM quadros_projetos WHERE id = ? AND (usuario_id = ? OR ? = 'admin')";
            $stmt_del_q = $pdo->prepare($sql_del_q);
            $stmt_del_q->execute([$id_quadro_del, $user_id_sessao, $user_nivel_sessao]);
        }
        header("Location: index.php");
        exit;
    }

    // --- AÇÃO: EXCLUIR GRUPO (GET) ---
    if (isset($_GET['del_grupo'])) {
        $id_grupo_del = (int)$_GET['del_grupo'];
        $id_quadro_ref = (int)$_GET['quadro_id'];
        if ($id_grupo_del > 0) {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM tarefas_projetos WHERE grupo_id = ?")->execute([$id_grupo_del]);
            $pdo->prepare("DELETE FROM projetos_grupos WHERE id = ?")->execute([$id_grupo_del]);
            $pdo->commit();
        }
        header("Location: quadro.php?id=" . $id_quadro_ref);
        exit;
    }

    // --- AÇÃO: EXCLUIR TAREFA (GET) ---
    if (isset($_GET['excluir_tarefa'])) {
        $id_tarefa_del = (int)$_GET['excluir_tarefa'];
        $id_quadro_ref = (int)$_GET['id_quadro'];
        if ($id_tarefa_del > 0) {
            $pdo->prepare("DELETE FROM tarefas_projetos WHERE id = ?")->execute([$id_tarefa_del]);
        }
        header("Location: quadro.php?id=" . $id_quadro_ref);
        exit;
    }

    // --- PROCESSAMENTO DE REQUISIÇÕES VIA POST ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';

        // --- EDITAR NOME DO QUADRO ---
        if ($acao === 'editar_nome_quadro') {
            $stmt = $pdo->prepare("UPDATE quadros_projetos SET nome = ? WHERE id = ? AND (usuario_id = ? OR ? = 'admin')");
            $stmt->execute([trim($_POST['nome']), (int)$_POST['id'], $user_id_sessao, $user_nivel_sessao]);
            echo "Sucesso"; exit;
        }

        // --- CRIAR NOVO GRUPO ---
        if ($acao === 'novo_grupo') {
            $nome_g = trim($_POST['nome_grupo']);
            $id_q = (int)$_POST['quadro_id'];
            $cor_g = $_POST['cor'] ?? '#1a73e8';
            if (!empty($nome_g)) {
                $stmt = $pdo->prepare("INSERT INTO projetos_grupos (nome, quadro_id, cor) VALUES (?, ?, ?)");
                $stmt->execute([$nome_g, $id_q, $cor_g]);
            }
            header("Location: quadro.php?id=" . $id_q); exit;
        }

        // --- CRIAR NOVO QUADRO (WORKSPACE) ---
        if ($acao === 'criar_quadro') {
            $nome_q = trim($_POST['nome']);
            $tipo_q = ((int)$_POST['privado'] === 1) ? 'Privado' : 'Publico';
            $pdo->beginTransaction();
            $stmt_ins_q = $pdo->prepare("INSERT INTO quadros_projetos (nome, tipo, usuario_id, data_criacao) VALUES (?, ?, ?, NOW())");
            $stmt_ins_q->execute([$nome_q, $tipo_q, $user_id_sessao]);
            $id_gerado = $pdo->lastInsertId();

            // Status Padrão (Incluindo Agarrado e Pausado)
            $status_padrao = [
                ['Novo','#c4c4c4'], ['Trabalhando','#fdab3d'], ['Agarrado','#a25ddc'], 
                ['Pausado','#797e93'], ['Travado','#e44258'], ['Concluído','#00ca72']
            ];
            foreach($status_padrao as $s) {
                $pdo->prepare("INSERT INTO quadros_status (quadro_id, label, cor) VALUES (?,?,?)")->execute([$id_gerado, $s[0], $s[1]]);
            }
            $pdo->prepare("INSERT INTO projetos_grupos (nome, quadro_id, cor) VALUES ('Minhas Tarefas', ?, '#1a73e8')")->execute([$id_gerado]);
            $pdo->prepare("INSERT INTO quadro_membros (quadro_id, usuario_id) VALUES (?, ?)")->execute([$id_gerado, $user_id_sessao]);
            $pdo->commit();
            header("Location: index.php"); exit;
        }

        // --- ADICIONAR NOVA TAREFA ---
        if ($acao === 'nova_tarefa') {
            $qid = (int)$_POST['quadro_id'];
            $stmt_st = $pdo->prepare("SELECT id FROM quadros_status WHERE quadro_id = ? ORDER BY id ASC LIMIT 1");
            $stmt_st->execute([$qid]);
            $st_id = $stmt_st->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO tarefas_projetos (titulo, grupo_id, quadro_id, usuario_id, status_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([trim($_POST['titulo']), (int)$_POST['grupo_id'], $qid, $user_id_sessao, $st_id]);
            echo "Sucesso"; exit;
        }

        // --- ATUALIZAR CAMPO TAREFA ---
        if ($acao === 'atualizar_campo_tarefa') {
            $pdo->prepare("UPDATE tarefas_projetos SET {$_POST['campo']} = ? WHERE id = ?")->execute([$_POST['valor'], (int)$_POST['id']]);
            echo "Sucesso"; exit;
        }

        // --- ATUALIZAR CAMPO GRUPO ---
        if ($acao === 'atualizar_campo_grupo') {
            $pdo->prepare("UPDATE projetos_grupos SET {$_POST['campo']} = ? WHERE id = ?")->execute([$_POST['valor'], (int)$_POST['id']]);
            echo "Sucesso"; exit;
        }

        // --- SALVAR EVIDÊNCIAS ---
        if ($acao === 'salvar_evidencia') {
            $pdo->prepare("UPDATE tarefas_projetos SET evidencias = ? WHERE id = ?")->execute([$_POST['conteudo'], (int)$_POST['id']]);
            echo "Sucesso"; exit;
        }
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    die("Erro: " . $e->getMessage());
}