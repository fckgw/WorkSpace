<?php
/**
 * BDSoft Workspace - GADO DE CORTE (ACOES)
 */
session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id'])) { exit; }
$user_id = $_SESSION['usuario_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';

        // --- CADASTRAR FAZENDA ---
        if ($acao === 'nova_propriedade') {
            $stmt = $pdo->prepare("INSERT INTO agro_gadocorte_propriedades (nombre, area_ha, localizacao, proprietario_nome, proprietario_celular, proprietario_email, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['nombre'], $_POST['area_ha'], $_POST['localizacao'], $_POST['prop_nome'], $_POST['prop_cel'], $_POST['prop_email'], $user_id]);
            header("Location: fazendas.php"); exit;
        }

        // --- CADASTRAR ANIMAL ---
        if ($acao === 'cadastrar_animal_individual') {
            $stmt = $pdo->prepare("INSERT INTO agro_gadocorte_animais (lote_id, brinco, sexo, raca, peso_entrada, peso_atual, data_entrada, status) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Ativo')");
            $stmt->execute([$_POST['lote_id'], $_POST['brinco'], $_POST['sexo'], $_POST['raca'], $_POST['peso'], $_POST['peso']]);
            header("Location: manejo_individual.php"); exit;
        }

        // --- NOVO LOTE ---
        if ($acao === 'novo_lote') {
            $stmt = $pdo->prepare("INSERT INTO agro_gadocorte_lotes (nome_lote, propriedade_id, sistema_id, fase_atual, status) VALUES (?, ?, ?, ?, 'Ativo')");
            $stmt->execute([$_POST['nome_lote'], $_POST['propriedade_id'], $_POST['sistema_id'], $_POST['fase_atual']]);
            header("Location: lotes.php"); exit;
        }
    }

    // --- DELETAR FAZENDA ---
    if (isset($_GET['acao']) && $_GET['acao'] === 'excluir_fazenda') {
        $pdo->prepare("DELETE FROM agro_gadocorte_propriedades WHERE id = ? AND usuario_id = ?")->execute([$_GET['id'], $user_id]);
        header("Location: fazendas.php"); exit;
    }

} catch (Exception $e) {
    die("Erro técnico: " . $e->getMessage());
}