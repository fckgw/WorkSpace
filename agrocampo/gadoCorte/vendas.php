<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../../login.php"); exit; }
$user_id = $_SESSION['usuario_id'];

// Buscar Lotes
$stmt_lotes = $pdo->prepare("SELECT l.id, l.nome_lote FROM agro_gadocorte_lotes l INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id WHERE p.usuario_id = ?");
$stmt_lotes->execute([$user_id]);
$meus_lotes = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);

// Listar Vendas (Corrigido para a tabela agro_gadocorte_vendas)
$stmt_vendas = $pdo->prepare("SELECT v.*, l.nome_lote FROM agro_gadocorte_vendas v INNER JOIN agro_gadocorte_lotes l ON v.lote_id = l.id INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id WHERE p.usuario_id = ? ORDER BY v.data_venda DESC");
$stmt_vendas->execute([$user_id]);
$vendas = $stmt_vendas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Vendas - Gado de Corte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrapper">
    <h2 class="fw-bold">Vendas e Faturamento</h2>
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Lote</th><th>Valor Líquido</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php foreach($vendas as $v): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($v['data_venda'])); ?></td>
                    <td><?php echo $v['nome_lote']; ?></td>
                    <td class="text-success fw-bold">R$ <?php echo number_format($v['valor_liquido'], 2, ',', '.'); ?></td>
                    <td><a href="acoes.php?acao=excluir_venda&id=<?php echo $v['id']; ?>" class="text-danger"><i class="fas fa-trash"></i></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>