<?php
/**
 * BDSoft Workspace - RELATÓRIO TÉCNICO PARA ABATE
 */
session_start();
require_once '../../config.php';
$user_id = $_SESSION['usuario_id'];

$lote_id = $_GET['lote_id'] ?? 0;

// Buscar Dados do Lote e Animais
if($lote_id) {
    $stmt = $pdo->prepare("SELECT a.*, l.nome_lote, p.nombre as fazenda FROM agro_gadocorte_animais a 
                            INNER JOIN agro_gadocorte_lotes l ON a.lote_id = l.id 
                            INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id 
                            WHERE l.id = ? AND p.usuario_id = ?");
    $stmt->execute([$lote_id, $user_id]);
    $animais = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Abate - BDSoft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .print-area { background: #fff; padding: 40px; border: 1px solid #ddd; border-radius: 8px; max-width: 900px; margin: 40px auto; }
        @media print { .no-print { display: none !important; } .print-area { border: none; margin: 0; width: 100%; } }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between mt-4 no-print">
        <form method="GET" class="d-flex gap-2">
            <select name="lote_id" class="form-select form-select-sm">
                <option value="">Selecione um Lote para o Abate</option>
                <?php 
                $res = $pdo->prepare("SELECT id, nome_lote FROM agro_gadocorte_lotes l INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id WHERE p.usuario_id = ?");
                $res->execute([$user_id]);
                while($row = $res->fetch()) echo "<option value='{$row['id']}'>{$row['nome_lote']}</option>";
                ?>
            </select>
            <button class="btn btn-dark btn-sm">GERAR</button>
        </form>
        <button onclick="window.print()" class="btn btn-danger btn-sm px-4 fw-bold">IMPRIMIR / PDF</button>
        <a href="index.php" class="btn btn-light border btn-sm">VOLTAR</a>
    </div>

    <?php if($lote_id): ?>
    <div class="print-area shadow-sm">
        <div class="text-center border-bottom pb-4 mb-4">
            <h2 class="fw-bold mb-1">RELATÓRIO DE EMBARQUE PARA ABATE</h2>
            <p class="text-muted small mb-0">BDSoft Workspace - Gestão Pecuária de Precisão</p>
        </div>

        <div class="row mb-4">
            <div class="col-6"><strong>ORIGEM:</strong> <?php echo $animais[0]['fazenda']; ?></div>
            <div class="col-6 text-end"><strong>DATA:</strong> <?php echo date('d/m/Y'); ?></div>
            <div class="col-6"><strong>IDENTIFICAÇÃO LOTE:</strong> <?php echo $animais[0]['nome_lote']; ?></div>
        </div>

        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr><th>BRINCO</th><th>RAÇA</th><th>SEXO</th><th>PESO ESTIMADO</th><th>CARÊNCIA SANITÁRIA</th></tr>
            </thead>
            <tbody>
                <?php foreach($animais as $a): ?>
                <tr>
                    <td>#<?php echo $a['brinco']; ?></td>
                    <td><?php echo $a['raca']; ?></td>
                    <td><?php echo $a['sexo']; ?></td>
                    <td><?php echo $a['peso_atual']; ?> kg</td>
                    <td class="text-success small fw-bold">LIBERADO</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row mt-5">
            <div class="col-6 border-top pt-2 text-center small">Assinatura do Produtor</div>
            <div class="col-6 border-top pt-2 text-center small">Assinatura Responsável RT</div>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>