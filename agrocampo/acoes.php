<?php
/**
 * BDSoft Workspace - AGRO CAMPO (ACOES)
 * Local: agrocampo/acoes.php
 */
session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) { exit; }
$user_id = $_SESSION['usuario_id'];

// --- 1. LER XML ---
if (isset($_POST['acao']) && $_POST['acao'] === 'ler_xml_agro') {
    $xml_str = file_get_contents($_FILES['xml_file']['tmp_name']);
    $xml_str = str_replace(['xmlns=', 'xmlns:'], ['ns=', 'ns:'], $xml_str);
    $xml = simplexml_load_string($xml_str);

    if ($xml && isset($xml->infCFe)) {
        $info = $xml->infCFe;
        echo json_encode([
            'status' => 'success',
            'dados' => [
                'emitente' => (string)$info->emit->xNome,
                'valor' => number_format((float)$info->total->ICMSTot->vCFe, 2, ',', '.'),
                'valor_limpo' => (float)$info->total->ICMSTot->vCFe,
                'produto' => (string)$info->det[0]->prod->xProd
            ]
        ]);
    } else { echo json_encode(['status' => 'error', 'message' => 'Erro ao ler XML.']); }
    exit;
}

// --- 2. CONFIRMAR XML ---
if (isset($_POST['acao']) && $_POST['acao'] === 'confirmar_xml_agro') {
    $stmt = $pdo->prepare("INSERT INTO agro_financeiro (tipo, descricao, fornecedor, valor, categoria, data_vencimento, status, metodo_pagamento, usuario_id) 
                           VALUES ('Saida', ?, 'COMEVAP - COOPERATIVA', ?, 'Consignado', ?, 'Pendente', 'Consignado', ?)");
    $stmt->execute([$_POST['descricao'], $_POST['valor'], $_POST['vencimento'], $user_id]);
    echo "Sucesso"; exit;
}

// --- 3. BAIXAR PAGAMENTO ---
if (isset($_GET['acao']) && $_GET['acao'] === 'confirmar_pagamento') {
    $partes = explode('/', $_GET['data']);
    $data_sql = $partes[2].'-'.$partes[1].'-'.$partes[0];
    $pdo->prepare("UPDATE agro_financeiro SET status = 'Pago', data_pagamento = ? WHERE id = ? AND usuario_id = ?")
        ->execute([$data_sql, (int)$_GET['id'], $user_id]);
    header("Location: financeiro.php"); exit;
}

// --- 4. ESTORNAR ---
if (isset($_GET['acao']) && $_GET['acao'] === 'estornar_pagamento') {
    $pdo->prepare("UPDATE agro_financeiro SET status = 'Pendente', data_pagamento = NULL WHERE id = ? AND usuario_id = ?")
        ->execute([(int)$_GET['id'], $user_id]);
    header("Location: financeiro.php"); exit;
}

// --- 5. NOVO MANUAL ---
if (isset($_POST['acao']) && $_POST['acao'] === 'novo_fin') {
    $data_pago = ($_POST['status'] === 'Pago') ? date('Y-m-d') : null;
    $stmt = $pdo->prepare("INSERT INTO agro_financeiro (tipo, descricao, fornecedor, valor, data_vencimento, data_pagamento, status, metodo_pagamento, usuario_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['tipo'], $_POST['descricao'], $_POST['fornecedor'], $_POST['valor'], $_POST['data_vencimento'], $data_pago, $_POST['status'], $_POST['metodo_pagamento'], $user_id]);
    header("Location: financeiro.php"); exit;
}

// --- 6. EXCLUIR ---
if (isset($_GET['del_fin'])) {
    $pdo->prepare("DELETE FROM agro_financeiro WHERE id = ? AND usuario_id = ?")->execute([(int)$_GET['del_fin'], $user_id]);
    header("Location: financeiro.php"); exit;
}