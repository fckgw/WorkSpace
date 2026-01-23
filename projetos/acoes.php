<?php
/**
 * BDSoft Workspace - PROJETOS / ACOES
 * Local: projetos/acoes.php
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) { 
    exit("Sessão expirada. Faça login novamente."); 
}

$user_id_sessao = $_SESSION['usuario_id'];
$user_nivel = $_SESSION['usuario_nivel'] ?? 'usuario';

try {
    // --- 1. BUSCAR TODA A TIMELINE DA TAREFA (GET) ---
    if (isset($_GET['acao']) && $_GET['acao'] === 'get_updates') {
        $id_task = (int)$_GET['id'];
        
        $sql = "SELECT u.*, us.nome as autor 
                FROM tarefas_updates u 
                INNER JOIN usuarios us ON u.usuario_id = us.id 
                WHERE u.tarefa_id = ? 
                ORDER BY u.data_criacao DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_task]);
        $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($updates)) {
            echo "<div class='text-center text-muted my-5'><i class='fas fa-comments fa-3x mb-3 opacity-25'></i><p>Nenhuma atualização registrada ainda.</p></div>";
        } else {
            foreach ($updates as $up) {
                $data = date('d/m/Y H:i', strtotime($up['data_criacao']));
                $pode_gerenciar = ($up['usuario_id'] == $user_id_sessao || $user_nivel === 'admin');
                
                echo "
                <div class='card mb-3 shadow-sm border-0' id='card_update_{$up['id']}' style='border-radius:15px; background:#fff;'>
                    <div class='card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3 px-3'>
                        <div>
                            <span class='fw-bold text-primary'><i class='fas fa-user-circle me-1'></i> {$up['autor']}</span>
                            <small class='text-muted ms-2' style='font-size:11px;'>$data</small>
                        </div>";
                
                if ($pode_gerenciar) {
                    echo "
                        <div class='no-print'>
                            <button class='btn btn-sm btn-light border-0 text-primary me-1' onclick='prepararEdicao({$up['id']})' title='Editar'><i class='fas fa-edit'></i></button>
                            <button class='btn btn-sm btn-light border-0 text-danger' onclick='excluirUpdate({$up['id']})' title='Excluir'><i class='fas fa-trash-alt'></i></button>
                        </div>";
                }
                
                echo "
                    </div>
                    <div class='card-body px-3 pb-3 pt-1' id='conteudo_update_{$up['id']}' style='font-size:14px; color:#444; line-height:1.6;'>
                        {$up['conteudo']}
                    </div>
                </div>";
            }
        }
        exit;
    }

    // --- 2. EXCLUIR UM COMENTÁRIO DA TIMELINE (GET) ---
    if (isset($_GET['acao']) && $_GET['acao'] === 'excluir_update') {
        $id_up = (int)$_GET['id_update'];
        $stmt = $pdo->prepare("DELETE FROM tarefas_updates WHERE id = ? AND (usuario_id = ? OR ? = 'admin')");
        $stmt->execute([$id_up, $user_id_sessao, $user_nivel]);
        echo "Sucesso";
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';

        // --- 3. SALVAR OU ATUALIZAR COMENTÁRIO ---
        if ($acao === 'salvar_update') {
            $id_task = (int)$_POST['id'];
            $id_update = isset($_POST['update_id']) ? (int)$_POST['update_id'] : 0;
            $conteudo = $_POST['conteudo'];

            if ($id_update > 0) {
                $stmt = $pdo->prepare("UPDATE tarefas_updates SET conteudo = ? WHERE id = ? AND (usuario_id = ? OR ? = 'admin')");
                $stmt->execute([$conteudo, $id_update, $user_id_sessao, $user_nivel]);
                echo "Atualizado";
            } else {
                $stmt = $pdo->prepare("INSERT INTO tarefas_updates (tarefa_id, usuario_id, conteudo, data_criacao) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$id_task, $user_id_sessao, $conteudo]);
                echo "Criado";
            }
            exit;
        }

        // --- 4. NOVO GRUPO (SPRINT) ---
        if ($acao === 'novo_grupo') {
            $nome = trim($_POST['nome_grupo']);
            $id_q = (int)$_POST['quadro_id'];
            $cor = $_POST['cor'] ?? '#1a73e8';

            if (!empty($nome) && $id_q > 0) {
                $stmt = $pdo->prepare("INSERT INTO projetos_grupos (nome, quadro_id, cor) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $id_q, $cor]);
            }
            header("Location: quadro.php?id=" . $id_q);
            exit;
        }

        // --- 5. NOVA TAREFA ---
        if ($acao === 'nova_tarefa') {
            $qid = (int)$_POST['quadro_id'];
            $gid = (int)$_POST['grupo_id'];
            $tit = trim($_POST['titulo']);

            $stmt_st = $pdo->prepare("SELECT id FROM quadros_status WHERE quadro_id = ? ORDER BY id ASC LIMIT 1");
            $stmt_st->execute([$qid]);
            $st_id = $stmt_st->fetchColumn();

            $stmt = $pdo->prepare("INSERT INTO tarefas_projetos (titulo, grupo_id, quadro_id, usuario_id, status_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$tit, $gid, $qid, $user_id_sessao, $st_id]);
            echo "Sucesso"; exit;
        }

        // --- 6. ATUALIZAR TAREFA OU GRUPO ---
        if ($acao === 'atualizar_campo_tarefa') {
            $pdo->prepare("UPDATE tarefas_projetos SET {$_POST['campo']} = ? WHERE id = ?")->execute([$_POST['valor'], (int)$_POST['id']]);
            echo "Sucesso"; exit;
        }
        if ($acao === 'atualizar_campo_grupo') {
            $pdo->prepare("UPDATE projetos_grupos SET {$_POST['campo']} = ? WHERE id = ?")->execute([$_POST['valor'], (int)$_POST['id']]);
            echo "Sucesso"; exit;
        }
    }

    // --- 7. EXCLUSÕES GERAIS ---
    if (isset($_GET['del_grupo'])) {
        $id_g = (int)$_GET['del_grupo'];
        $id_q = (int)$_GET['quadro_id'];
        $pdo->prepare("DELETE FROM tarefas_projetos WHERE grupo_id = ?")->execute([$id_g]);
        $pdo->prepare("DELETE FROM projetos_grupos WHERE id = ?")->execute([$id_g]);
        header("Location: quadro.php?id=" . $id_q); exit;
    }
    if (isset($_GET['excluir_tarefa'])) {
        $pdo->prepare("DELETE FROM tarefas_projetos WHERE id = ?")->execute([(int)$_GET['excluir_tarefa']]);
        header("Location: quadro.php?id=" . $_GET['id_quadro']); exit;
    }

} catch (Exception $e) { 
    die("<h1>Erro</h1><p>" . $e->getMessage() . "</p><a href='index.php'>Voltar</a>"); 
}