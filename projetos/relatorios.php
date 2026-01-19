<?php
/**
 * BDSoft Workspace - RELATÓRIOS BI AVANÇADO
 * Local: projetos/relatorios.php
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

$id_quadro = (int)$_GET['id'];
$user_id = $_SESSION['usuario_id'];

// --- LÓGICA DE FILTROS DE PERÍODO ---
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'todos';
$data_limite = "";

if ($periodo == 'semana') {
    $data_limite = " AND t.data_fim >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($periodo == 'quinzena') {
    $data_limite = " AND t.data_fim >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)";
} elseif ($periodo == 'mes') {
    $data_limite = " AND t.data_fim >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

// 1. Validar Quadro
$stmtQ = $pdo->prepare("SELECT nome FROM quadros_projetos WHERE id = ?");
$stmtQ->execute([$id_quadro]);
$nome_projeto = $stmtQ->fetchColumn() ?: "Projeto";

// 2. Buscar Dados das Tarefas com Filtro de Período
$sql = "SELECT t.*, s.label as st_nome, s.cor as st_cor, g.nome as gr_nome 
        FROM tarefas_projetos t
        LEFT JOIN quadros_status s ON t.status_id = s.id
        LEFT JOIN projetos_grupos g ON t.grupo_id = g.id
        WHERE t.quadro_id = :qid $data_limite 
        ORDER BY t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':qid' => $id_quadro]);
$tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Processar Estatísticas e Percentuais
$total_tarefas = count($tarefas);
$contagem = [];

foreach ($tarefas as $t) {
    $n = $t['st_nome'] ?: 'Sem Status';
    if (!isset($contagem[$n])) {
        $contagem[$n] = ['total' => 0, 'cor' => ($t['st_cor'] ?: '#ccc')];
    }
    $contagem[$n]['total']++;
}

// Preparar dados para o JavaScript
$js_labels = array_keys($contagem);
$js_valores = array_column($contagem, 'total');
$js_cores = array_column($contagem, 'cor');
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
        .card-bi { background:#fff; border-radius:15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .stat-card { border-left: 5px solid; border-radius: 10px; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); }
        .t-row { transition: 0.2s; }
        .row-hidden { display: none; }
        .row-selected { background-color: #e8f0fe !important; font-weight: bold; }
        
        /* Botões de Filtro Fixos */
        .filter-btn { border-radius: 20px; font-size: 12px; font-weight: bold; padding: 5px 20px; }
        
        @media print { .no-print { display:none !important; } .card-bi { box-shadow:none; border: 1px solid #ddd; } }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    
    <!-- CABEÇALHO -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h3 class="fw-bold mb-0">BI & Analytics</h3>
            <p class="text-muted small">Dashboard: <?php echo htmlspecialchars($nome_projeto); ?></p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group me-3 bg-white rounded-pill shadow-sm p-1">
                <a href="?id=<?php echo $id_quadro; ?>&periodo=todos" class="btn filter-btn <?php echo $periodo=='todos'?'btn-dark':'btn-light'; ?>">Tudo</a>
                <a href="?id=<?php echo $id_quadro; ?>&periodo=semana" class="btn filter-btn <?php echo $periodo=='semana'?'btn-dark':'btn-light'; ?>">7 Dias</a>
                <a href="?id=<?php echo $id_quadro; ?>&periodo=quinzena" class="btn filter-btn <?php echo $periodo=='quinzena'?'btn-dark':'btn-light'; ?>">15 Dias</a>
                <a href="?id=<?php echo $id_quadro; ?>&periodo=mes" class="btn filter-btn <?php echo $periodo=='mes'?'btn-dark':'btn-light'; ?>">30 Dias</a>
            </div>
            <button onclick="window.print()" class="btn btn-danger btn-sm rounded-pill px-4 shadow-sm fw-bold">PDF</button>
            <a href="quadro.php?id=<?php echo $id_quadro; ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-4 fw-bold">Voltar</a>
        </div>
    </div>

    <!-- STATUS COM PORCENTAGEM (RESUMO SUPERIOR) -->
    <div class="row g-3 mb-4">
        <?php foreach ($contagem as $label => $info): 
            $pct = ($total_tarefas > 0) ? round(($info['total'] / $total_tarefas) * 100, 1) : 0;
        ?>
        <div class="col-md-3 col-sm-6">
            <div class="card-bi p-3 stat-card" style="border-left-color: <?php echo $info['cor']; ?>;">
                <div class="text-muted small fw-bold text-uppercase"><?php echo $label; ?></div>
                <div class="d-flex align-items-end justify-content-between mt-2">
                    <h3 class="fw-bold mb-0"><?php echo $pct; ?>%</h3>
                    <span class="badge bg-light text-dark border"><?php echo $info['total']; ?> itens</span>
                </div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar" style="width: <?php echo $pct; ?>%; background-color: <?php echo $info['cor']; ?>;"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <!-- GRÁFICO (CENTRAL) -->
        <div class="col-md-5">
            <div class="card-bi p-5 text-center h-100">
                <h6 class="fw-bold text-muted mb-4">DISTRIBUIÇÃO POR STATUS</h6>
                <?php if ($total_tarefas == 0): ?>
                    <div class="py-5 text-muted">Sem dados no período selecionado.</div>
                <?php else: ?>
                    <div style="max-width: 300px; margin: auto;">
                        <canvas id="chartBI"></canvas>
                    </div>
                    <p class="small text-muted mt-4"><i class="fas fa-info-circle me-1"></i> Clique no gráfico para filtrar a tabela.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- LISTAGEM (DETALHE) -->
        <div class="col-md-7">
            <div class="card-bi p-4 h-100">
                <h6 class="fw-bold text-muted mb-4">DETALHAMENTO DO PERÍODO</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th>Tarefa</th>
                                <th>Grupo</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tarefas as $task): ?>
                            <tr class="t-row" data-st="<?php echo htmlspecialchars($task['st_nome']); ?>">
                                <td class="fw-bold"><?php echo htmlspecialchars($task['titulo']); ?></td>
                                <td class="small text-muted"><?php echo htmlspecialchars($task['gr_nome']); ?></td>
                                <td class="text-center">
                                    <span class="badge" style="background:<?php echo $task['st_cor']; ?>; padding: 6px 12px; border-radius: 20px;">
                                        <?php echo $task['st_nome']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const labels = <?php echo json_encode($js_labels); ?>;
    const values = <?php echo json_encode($js_valores); ?>;
    const colors = <?php echo json_encode($js_cores); ?>;

    const canvas = document.getElementById('chartBI');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                cutout: '75%',
                plugins: { legend: { position: 'bottom' } },
                onClick: (e, el) => {
                    if (el.length > 0) {
                        const index = el[0].index;
                        const stName = labels[index];
                        
                        document.querySelectorAll('.t-row').forEach(r => {
                            if (r.getAttribute('data-st') === stName) {
                                r.classList.remove('row-hidden');
                                r.classList.add('row-selected');
                            } else {
                                r.classList.add('row-hidden');
                                r.classList.remove('row-selected');
                            }
                        });
                    }
                }
            }
        });
    }

    function mostrarTudo() {
        document.querySelectorAll('.t-row').forEach(r => r.classList.remove('row-hidden', 'row-selected'));
    }
</script>
</body>
</html>