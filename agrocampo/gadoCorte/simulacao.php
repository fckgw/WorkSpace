<?php
/**
 * BDSoft Workspace - SIMULADOR DE RENTABILIDADE
 */
session_start();
require_once '../../config.php';

$id_lote = $_GET['id_lote'] ?? 0;
// Dados de exemplo se não houver lote selecionado
$peso_inicial = 300;
$custo_total_compra = 2500;
$gmd = 1.2;
$custo_diario = 10.50;
$preco_arroba = 280.00;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Simulador de Lucro - BDSoft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sim-box { border-radius: 20px; padding: 25px; transition: 0.3s; }
        .bg-venda { background: #fff3cd; border: 2px solid #ffeeba; }
        .bg-manter { background: #d4edda; border: 2px solid #c3e6cb; }
    </style>
</head>
<body class="p-5" style="background:#f4f1ea;">

<div class="container">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Simulador de Cenários Futuros</h2>
        <p class="text-muted">Vale a pena vender agora ou manter o gado no cocho?</p>
    </div>

    <div class="row g-4">
        <!-- CENÁRIO HOJE -->
        <div class="col-md-6">
            <div class="sim-box bg-venda h-100">
                <h4 class="fw-bold">Vender Hoje</h4>
                <hr>
                <p>Peso Médio: <b><?php echo $peso_inicial; ?> kg</b></p>
                <p>Total Arrobas: <b><?php echo $peso_inicial/30; ?> @</b></p>
                <h3 class="fw-bold text-dark">R$ <?php echo number_format(($peso_inicial/30)*$preco_arroba, 2); ?></h3>
                <small class="text-muted">Estimativa de lucro bruto por animal</small>
            </div>
        </div>

        <!-- CENÁRIO +90 DIAS -->
        <div class="col-md-6">
            <div class="sim-box bg-manter h-100">
                <h4 class="fw-bold text-success">Vender em 90 Dias</h4>
                <hr>
                <?php 
                    $peso_90 = $peso_inicial + (90 * $gmd);
                    $custo_90 = 90 * $custo_diario;
                    $receita_90 = ($peso_90/30) * $preco_arroba;
                ?>
                <p>Peso Médio: <b><?php echo $peso_90; ?> kg</b></p>
                <p>Custo Adicional: <b class="text-danger">R$ <?php echo number_format($custo_90, 2); ?></b></p>
                <h3 class="fw-bold text-success">R$ <?php echo number_format($receita_90 - $custo_90, 2); ?></h3>
                <small class="text-muted">Estimativa de lucro líquido (descontando trato)</small>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-5">
        <a href="index.php" class="btn btn-secondary rounded-pill">Voltar ao Painel</a>
    </div>
</div>

</body>
</html>