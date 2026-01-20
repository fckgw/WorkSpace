<?php
/**
 * BDSoft Workspace - AGRO CAMPO (RELATÓRIO BI)
 * Local: agrocampo/relatorio_financeiro.php
 */
session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }
$user_id = $_SESSION['usuario_id'];

// Filtros Iniciais
$d_inicio = $_GET['d_inicio'] ?? date('Y-m-01');
$d_fim    = $_GET['d_fim'] ?? date('Y-m-t');
$f_fornecedor = $_GET['f_fornecedor'] ?? '';
$f_desc = $_GET['f_desc'] ?? '';
$f_metodo = $_GET['f_metodo'] ?? '';

// Construção da Query Dinâmica
$sql = "SELECT * FROM agro_financeiro WHERE usuario_id = :uid AND data_vencimento BETWEEN :inicio AND :fim";
$params = [':uid' => $user_id, ':inicio' => $d_inicio, ':fim' => $d_fim];

if ($f_fornecedor) { $sql .= " AND fornecedor LIKE :forn"; $params[':forn'] = "%$f_fornecedor%"; }
if ($f_desc) { $sql .= " AND descricao LIKE :descr"; $params[':descr'] = "%$f_desc%"; }
if ($f_metodo) { $sql .= " AND metodo_pagamento = :meto"; $params[':meto'] = $f_metodo; }

$stmt = $pdo->prepare($sql . " ORDER BY data_vencimento ASC");
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cálculos Totais
$ent = 0; $sai = 0;
foreach($dados as $r) {
    if($r['tipo'] == 'Entrada') $ent += $r['valor'];
    else $sai += $r['valor'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatórios BI - AgroCampo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: sans-serif; }
        .sidebar { width: 260px; background: #1e3d1a; color: white; position: fixed; height: 100vh; }
        .sidebar .nav-link { color: rgba(255,255,255,0.7); padding: 12px 25px; }
        .sidebar .active { background: #2d5a27; color: white; border-left: 5px solid #8bc34a; }
        .main-content { flex: 1; margin-left: 260px; padding: 40px; }
        .card-report { background: #fff; border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        @media print { .no-print { display: none !important; } body { padding: 0; } .main-content { margin: 0; } .card-report { box-shadow: none; border: 1px solid #ddd; } }
    </style>
</head>
<body class="d-flex">

<div class="sidebar no-print shadow">
    <div class="p-4 text-center"><h4>AgroCampo</h4></div>
    <nav class="nav flex-column mt-3">
        <a class="nav-link" href="index.php">Painel Geral</a>
        <a class="nav-link" href="financeiro.php">Contas Pagar/Receber</a>
        <a class="nav-link active" href="relatorio_financeiro.php">Relatórios / BI</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="fw-bold">Relatórios BI</h2>
        <button onclick="window.print()" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">
            <i class="fas fa-file-pdf me-2"></i>EXPORTAR PDF
        </button>
    </div>

    <!-- FILTROS -->
    <div class="card-report p-4 mb-4 no-print">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="small fw-bold">INÍCIO</label>
                <input type="date" name="d_inicio" class="form-control" value="<?php echo $d_inicio; ?>">
            </div>
            <div class="col-md-2">
                <label class="small fw-bold">FIM</label>
                <input type="date" name="d_fim" class="form-control" value="<?php echo $d_fim; ?>">
            </div>
            <div class="col-md-3">
                <label class="small fw-bold">FORNECEDOR</label>
                <input type="text" name="f_fornecedor" class="form-control" value="<?php echo $f_fornecedor; ?>">
            </div>
            <div class="col-md-2">
                <label class="small fw-bold">MÉTODO</label>
                <select name="f_metodo" class="form-select">
                    <option value="">Todos</option>
                    <option value="Boleto">Boleto</option>
                    <option value="PIX">PIX</option>
                    <option value="Consignado">Consignado</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-dark w-100 fw-bold rounded-pill">FILTRAR</button>
            </div>
        </form>
    </div>

    <!-- RELATÓRIO -->
    <div class="card-report p-4">
        <div class="text-center mb-4">
            <h4 class="fw-bold">Relatório Financeiro AgroCampo</h4>
            <p class="text-muted">Período: <?php echo date('d/m/Y', strtotime($d_inicio)); ?> à <?php echo date('d/m/Y', strtotime($d_fim)); ?></p>
        </div>

        <div class="row g-3 mb-4 text-center">
            <div class="col-md-6"><div class="p-3 border rounded bg-light small fw-bold text-success">ENTRADAS: R$ <?php echo number_format($ent,2,',','.'); ?></div></div>
            <div class="col-md-6"><div class="p-3 border rounded bg-light small fw-bold text-danger">SAÍDAS: R$ <?php echo number_format($sai,2,',','.'); ?></div></div>
        </div>

        <table class="table table-sm table-bordered">
            <thead class="table-dark">
                <tr class="small">
                    <th>Data</th>
                    <th>Fornecedor</th>
                    <th>Descrição</th>
                    <th>Método</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php foreach($dados as $r): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($r['data_vencimento'])); ?></td>
                    <td><?php echo $r['fornecedor']; ?></td>
                    <td><?php echo $r['descricao']; ?></td>
                    <td><?php echo $r['metodo_pagamento']; ?></td>
                    <td class="fw-bold <?php echo $r['tipo']=='Entrada'?'text-success':'text-danger'; ?>">
                        R$ <?php echo number_format($r['valor'], 2, ',', '.'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h5 class="text-end fw-bold mt-4">SALDO: R$ <?php echo number_format($ent-$sai,2,',','.'); ?></h5>
    </div>
</div>

</body>
</html>