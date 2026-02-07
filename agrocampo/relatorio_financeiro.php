<?php
/**
 * BDSoft Workspace - RELATÓRIO BI
 */
session_start();
require_once '../config.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }
$user_id = $_SESSION['usuario_id'];

$d_inicio = $_GET['d_inicio'] ?? date('Y-m-01');
$d_fim = $_GET['d_fim'] ?? date('Y-m-t');

// Busca unificada
$sql = "SELECT 'Real' as origem, tipo, descricao, fornecedor, valor, data_vencimento, status FROM agro_financeiro WHERE usuario_id = ? AND data_vencimento BETWEEN ? AND ?
        UNION ALL
        SELECT 'Provisão' as origem, 'Saida' as tipo, pr.nome_provisao, 'Provisão', pp.valor_parcela, pp.data_vencimento, pp.status FROM agro_provisoes_parcelas pp INNER JOIN agro_provisoes pr ON pp.provisao_id = pr.id WHERE pr.usuario_id = ? AND pp.status = 'Pendente' AND pp.data_vencimento BETWEEN ? AND ?
        ORDER BY data_vencimento ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $d_inicio, $d_fim, $user_id, $d_inicio, $d_fim]);
$dados = $stmt->fetchAll();

$ent = 0; $sai = 0;
foreach($dados as $r) { if($r['tipo'] == 'Entrada') $ent += $r['valor']; else $sai += $r['valor']; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><title>Relatório BI - Agro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>@media print { .no-print { display:none !important; } .main-wrapper { margin-left: 0 !important; } }</style>
</head>
<body>
<?php include 'includes/sidebar_contextual.php'; ?>
<div class="main-wrapper">
    <div class="d-flex justify-content-between no-print mb-4">
        <h3 class="fw-bold">Relatório Financeiro Integrado</h3>
        <button onclick="window.print()" class="btn btn-danger rounded-pill px-4 fw-bold">PDF</button>
    </div>
    
    <form class="row g-2 mb-4 no-print bg-white p-3 rounded-4 shadow-sm">
        <input type="hidden" name="id" value="1">
        <div class="col-md-4"><input type="date" name="d_inicio" class="form-control" value="<?php echo $d_inicio; ?>"></div>
        <div class="col-md-4"><input type="date" name="d_fim" class="form-control" value="<?php echo $d_fim; ?>"></div>
        <div class="col-md-4"><button class="btn btn-dark w-100">FILTRAR</button></div>
    </form>

    <div class="bg-white p-5 rounded-4 shadow-sm border">
        <h4 class="text-center">Visão Geral de Caixa</h4>
        <p class="text-center text-muted"><?php echo date('d/m/Y', strtotime($d_inicio)); ?> à <?php echo date('d/m/Y', strtotime($d_fim)); ?></p>
        <div class="row text-center my-4">
            <div class="col-6"><div class="p-3 bg-light rounded fw-bold text-success">RECEITAS: R$ <?php echo number_format($ent,2,',','.'); ?></div></div>
            <div class="col-6"><div class="p-3 bg-light rounded fw-bold text-danger">DESPESAS: R$ <?php echo number_format($sai,2,',','.'); ?></div></div>
        </div>
        <table class="table table-bordered table-sm mt-3">
            <thead class="table-light"><tr><th>Vencimento</th><th>Origem</th><th>Fornecedor/Descrição</th><th>Valor</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach($dados as $r): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($r['data_vencimento'])); ?></td>
                    <td><span class="badge <?php echo $r['origem']=='Real'?'bg-primary':'bg-info'; ?>"><?php echo $r['origem']; ?></span></td>
                    <td><?php echo htmlspecialchars($r['fornecedor']." - ".$r['descricao']); ?></td>
                    <td class="fw-bold">R$ <?php echo number_format($r['valor'],2,',','.'); ?></td>
                    <td><?php echo $r['status']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>