<?php
/**
 * BDSoft Workspace - RELATÓRIOS BI AVANÇADO
 * Local: projetos/relatorios.php
 * Funcionalidades: Filtro por Grupo, Filtro por Período, Paginação e Drill-down.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

$id_quadro = (int)$_GET['id'];
$user_id = $_SESSION['usuario_id'];

// --- 1. CAPTURA DE FILTROS ---
$f_grupo = $_GET['f_grupo'] ?? '';
$f_data_ini = $_GET['f_data_ini'] ?? '';
$f_data_fim = $_GET['f_data_fim'] ?? '';

// --- 2. LÓGICA DE PAGINAÇÃO ---
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// --- 3. CONSTRUÇÃO DA QUERY DINÂMICA (PARA O GRÁFICO E TOTAIS) ---
// Precisamos buscar todos os registros filtrados para alimentar o gráfico
$sql_base = "FROM tarefas_projetos t
             INNER JOIN quadros_status s ON t.status_id = s.id
             INNER JOIN projetos_grupos g ON t.grupo_id = g.id
             WHERE t.quadro_id = :qid";

$params = [':qid' => $id_quadro];

if (!empty($f_grupo)) {
    $sql_base .= " AND t.grupo_id = :gid";
    $params[':gid'] = $f_grupo;
}

if (!empty($f_data_ini) && !empty($f_data_fim)) {
    $sql_base .= " AND t.data_fim BETWEEN :dini AND :dfim";
    $params[':dini'] = $f_data_ini;
    $params[':dfim'] = $f_data_fim;
}

// Query para os Gráficos (Tudo sem limite de paginação)
$stmt_all = $pdo->prepare("SELECT t.*, s.label as st_nome, s.cor as st_cor, g.nome as gr_nome " . $sql_base);
$stmt_all->execute($params);
$todas_tarefas_filtradas = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

// --- 4. CÁLCULOS PARA O BI ---
$total_geral = count($todas_tarefas_filtradas);
$contagem_status = [];
foreach ($todas_tarefas_filtradas as $tar) {
    $label = $tar['st_nome'];
    if (!isset($contagem_status[$label])) {
        $contagem_status[$label] = ['qtd' => 0, 'cor' => $tar['st_cor']];
    }
    $contagem_status[$label]['qtd']++;
}

// --- 5. BUSCA DAS TAREFAS PARA O GRID (COM PAGINAÇÃO) ---
$stmt_grid = $pdo->prepare("SELECT t.*, s.label as st_nome, s.cor as st_cor, g.nome as gr_nome " . $sql_base . " ORDER BY t.id DESC LIMIT $itens_por_pagina OFFSET $offset");
$stmt_grid->execute($params);
$tarefas_paginadas = $stmt_grid->fetchAll(PDO::FETCH_ASSOC);

$total_paginas = ceil($total_geral / $itens_por_pagina);

// Buscar grupos para o select do filtro
$stmt_grupos = $pdo->prepare("SELECT id, nome FROM projetos_grupos WHERE quadro_id = ?");
$stmt_grupos->execute([$id_quadro]);
$lista_grupos = $stmt_grupos->fetchAll();

// Dados do Quadro
$stmt_q = $pdo->prepare("SELECT nome FROM quadros_projetos WHERE id = ?");
$stmt_q->execute([$id_quadro]);
$nome_projeto = $stmt_q->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>BI - <?php echo htmlspecialchars($nome_projeto); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background:#f4f7f6; font-family:'Segoe UI', sans-serif; }
        .card-bi { background:#fff; border:none; border-radius:20px; box-shadow:0 4px 15px rgba(0,0,0,0.05); }
        .stat-card { border-radius: 15px; border-left: 5px solid; transition: 0.3s; }
        .status-badge { padding: 4px 10px; border-radius: 20px; color: #fff; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .pagination .page-link { border-radius: 50%; margin: 0 3px; border: none; color: #333; font-weight: bold; }
        .pagination .active .page-link { background-color: #1a73e8; color: #fff; }
        @media print { .no-print { display:none !important; } .container { width: 100% !important; max-width: 100% !important; } }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h3 class="fw-bold">Analytics: <?php echo htmlspecialchars($nome_projeto); ?></h3>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-danger rounded-pill px-4 shadow-sm fw-bold">PDF</button>
            <a href="quadro.php?id=<?php echo $id_quadro; ?>" class="btn btn-light border rounded-pill px-4">Voltar</a>
        </div>
    </div>

    <!-- BARRA DE FILTROS AVANÇADA -->
    <div class="card-bi p-4 mb-4 no-print">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="id" value="<?php echo $id_quadro; ?>">
            
            <div class="col-md-4">
                <label class="small fw-bold">FASE / GRUPO</label>
                <select name="f_grupo" class="form-select border-0 bg-light">
                    <option value="">Todas as Fases</option>
                    <?php foreach($lista_grupos as $lg) echo "<option value='{$lg['id']}' ".($f_grupo==$lg['id']?'selected':'').">{$lg['nome']}</option>"; ?>
                </select>
            </div>

            <div class="col-md-5">
                <label class="small fw-bold text-muted">PERÍODO (VENCIMENTO)</label>
                <div class="input-group">
                    <span class="input-group-text border-0 bg-light small">DE</span>
                    <input type="date" name="f_data_ini" class="form-control border-0 bg-light" value="<?php echo $f_data_ini; ?>">
                    <span class="input-group-text border-0 bg-light small">ATÉ</span>
                    <input type="date" name="f_data_fim" class="form-control border-0 bg-light" value="<?php echo $f_data_fim; ?>">
                </div>
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">APLICAR FILTROS</button>
            </div>
        </form>
    </div>

    <!-- QUADRINHOS DE KPI (PORCENTAGEM) -->
    <div class="row g-3 mb-4">
        <?php foreach ($contagem_status as $nome => $info): 
            $porcentagem = ($total_geral > 0) ? round(($info['qtd'] / $total_geral) * 100, 1) : 0;
        ?>
        <div class="col-md-3">
            <div class="card-bi p-3 stat-card" style="border-left-color: <?php echo $info['cor']; ?>;">
                <small class="text-muted fw-bold uppercase"><?php echo $nome; ?></small>
                <div class="d-flex justify-content-between align-items-end mt-2">
                    <h3 class="fw-bold mb-0"><?php echo $porcentagem; ?>%</h3>
                    <span class="badge bg-light text-dark border"><?php echo $info['qtd']; ?></span>
                </div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar" style="width: <?php echo $porcentagem; ?>%; background-color: <?php echo $info['cor']; ?>;"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <!-- GRÁFICO -->
        <div class="col-md-5">
            <div class="card-bi p-5 text-center h-100">
                <h6 class="fw-bold text-muted mb-4">DISTRIBUIÇÃO</h6>
                <canvas id="chartStatus"></canvas>
            </div>
        </div>

        <!-- LISTAGEM PAGINADA -->
        <div class="col-md-7">
            <div class="card-bi p-4 h-100 d-flex flex-column">
                <h6 class="fw-bold text-muted mb-4 text-uppercase small">Listagem de Atividades</h6>
                <div class="table-responsive flex-grow-1">
                    <table class="table table-hover align-middle" style="font-size: 13px;">
                        <thead><tr class="text-muted small"><th>Tarefa</th><th>Grupo</th><th class="text-center">Status</th></tr></thead>
                        <tbody>
                            <?php foreach($tarefas_paginadas as $t): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($t['titulo']); ?></td>
                                <td class="small text-muted"><?php echo htmlspecialchars($t['gr_nome']); ?></td>
                                <td class="text-center">
                                    <span class="status-badge" style="background:<?php echo $t['st_cor']; ?>">
                                        <?php echo $t['st_nome']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINAÇÃO DINÂMICA -->
                <?php if ($total_paginas > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination pagination-sm justify-content-center">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): 
                            $url_pag = "?id=$id_quadro&f_grupo=$f_grupo&f_data_ini=$f_data_ini&f_data_fim=$f_data_fim&p=$i";
                        ?>
                            <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                                <a class="page-link shadow-sm" href="<?php echo $url_pag; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Configuração do Gráfico (Chart.js)
    const ctx = document.getElementById('chartStatus');
    const myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($contagem_status)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($contagem_status, 'qtd')); ?>,
                backgroundColor: <?php echo json_encode(array_column($contagem_status, 'cor')); ?>,
                borderWidth: 0
            }]
        },
        options: {
            cutout: '70%',
            plugins: { legend: { position: 'bottom' } }
        }
    });
</script>
</body>
</html>