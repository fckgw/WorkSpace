<?php
/**
 * BDSoft Workspace - AGRO ACOES (MOTOR DE PRODUÇÃO)
 */
session_start();
require_once '../config.php';
if (!isset($_SESSION['usuario_id'])) { exit; }
$uid = $_SESSION['usuario_id'];

try {
    // --- EXCLUIR ---
    if (isset($_GET['del_fin'])) {
        $stmt = $pdo->prepare("DELETE FROM agro_financeiro WHERE id = ? AND usuario_id = ?");
        $stmt->execute([(int)$_GET['del_fin'], $uid]);
        header("Location: financeiro.php?sucesso=1");
        exit;
    }

    // --- ESTORNAR ---
    if (isset($_GET['acao']) && $_GET['acao'] === 'estornar_pagamento') {
        $pdo->prepare("UPDATE agro_financeiro SET status = 'Pendente', data_pagamento = NULL WHERE id = ? AND usuario_id = ?")
            ->execute([(int)$_GET['id'], $uid]);
        header("Location: financeiro.php?sucesso=1");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';

        // --- NOVA CONTA ---
        if ($acao === 'novo_fin') {
            $data_p = ($_POST['status'] === 'Pago') ? date('Y-m-d') : null;
            $sql = "INSERT INTO agro_financeiro (tipo, descricao, fornecedor, valor, data_vencimento, data_pagamento, status, metodo_pagamento, usuario_id) VALUES (?,?,?,?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute([$_POST['tipo'], $_POST['descricao'], $_POST['fornecedor'], $_POST['valor'], $_POST['data_vencimento'], $data_p, $_POST['status'], $_POST['metodo_pagamento'], $uid]);
            header("Location: financeiro.php?sucesso=1");
            exit;
        }

        // --- BAIXA PROFISSIONAL ---
        if ($acao === 'confirmar_pagamento_v2') {
            $pdo->prepare("UPDATE agro_financeiro SET status = 'Pago', data_pagamento = ?, metodo_pagamento = ? WHERE id = ? AND usuario_id = ?")
                ->execute([$_POST['data_pago'], $_POST['metodo_pagamento'], (int)$_POST['id_registro'], $uid]);
            header("Location: financeiro.php?sucesso=1");
            exit;
        }
    }
} catch (Exception $e) {
    die("Erro Crítico: " . $e->getMessage());
}