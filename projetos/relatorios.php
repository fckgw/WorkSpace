<?php
/**
 * BDSoft Workspace - RELATÓRIOS BI INTEGRADO
 */
session_start();
require_once '../config.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }
$id_quadro = (int)$_GET['id'];
$user_id = $_SESSION['usuario_id'];

// 1. Capturar Filtros
$f_grupo = $_GET['f_grupo'] ?? '';

// 2. Buscar Dados do Quadro
$stmtQ = $pdo->prepare("SELECT nome FROM quadros_projetos WHERE id = ?");
$stmtQ->execute([$id_quadro]);
$nome_projeto = $stmtQ->fetchColumn();

// 3. Query Dinâmica
$sql = "SELECT t.*, s.label as st_nome, s.cor as st_cor, g.nome as gr_nome 
        FROM tarefas_projetos t
        INNER JOIN quadros_status s ON t.status_id = s.id
        INNER JOIN projetos_grupos g ON t.grupo_id = g.id
        WHERE t.quadro_id = :qid";

$params = [':qid' => $id_quadro];
if($f_grupo) { $sql .= " AND t.grupo_id = :gid"; $params[':gid'] = $f_grupo; }

$stmt = $pdo->prepare($sql . " ORDER BY g.ordem, t.id DESC");
$stmt->execute($params);
$tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Buscar Grupos para o Filtro
$grupos = $pdo->prepare("SELECT id, nome FROM projetos_grupos WHERE quadro_id = ?");
$grupos->execute([$id_quadro]);
$lista_grupos = $grupos->fetchAll();

// 5. Agrupar para KPIs e Gráficos
$total_tarefas = count($tarefas);
$contagem = [];
foreach($tarefas as $t) {
    if(!isset($contagem[$t['st_nome']])) $contagem[$t['st_nome']] = ['qtd' => 0, 'cor' => $t['st_cor']];
    $contagem[$t['st_nome']]['qtd']++;
}

$js_labels = array_keys($contagem);
$js_valores = array_column($contagem, 'qtd');
$js_cores = array_column($contagem, 'cor');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>BI - BDSoft Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background:#f4f7f6; font-family:sans-serif; }
        .card-bi { background:#fff; border:none; border-radius:20px; box-shadow:0 4px 15px rgba(0,0,0,0.05); }
        .stat-card { border-radius: 15px; border-left: 5px solid; transition: 0.3s; }
        .row-hidden { display:none; }
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

    <!-- FILTRO -->
    <div class="card-bi p-4 mb-4 no-print">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="id" value="<?php echo $id_quadro; ?>">
            <div class="col-md-6">
                <label class="small fw-bold">FILTRAR POR FASE / GRUPO</label>
                <select name="f_grupo" class="form-select border-0 bg-light" onchange="this.form.submit()">
                    <option value="">Todas as Fases</option>
                    <?php foreach($lista_grupos as $lg) echo "<option value='{$lg['id']}' ".($f_grupo==$lg['id']?'selected':'').">{$lg['nome']}</option>"; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- QUADRINHOS DE KPI (PORCENTAGEM) -->
    <div class="row g-3 mb-4">
        <?php foreach ($contagem as $nome => $info): 
            $porcentagem = ($total_tarefas > 0) ? round(($info['qtd'] / $total_tarefas) * 100, 1) : 0;
        ?>
        <div class="col-md-3">
            <div class="card-bi p-3 stat-card" style="border-left-color: <?php echo $info['cor']; ?>;">
                <small class="text-muted fw-bold uppercase"><?php echo $nome; ?></small>
                <div class="d-flex justify-content-between align-items-end mt-2">
                    <h3 class="fw-bold mb-0"><?php echo $porcentagem; ?>%</h3>
                    <span class="badge bg-light text-dark border"><?php echo $info['qtd']; ?> itens</span>
                </div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar" style="width: <?php echo $porcentagem; ?>%; background-color: <?php echo $info['cor']; ?>;"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card-bi p-5 text-center h-100">
                <h6 class="fw-bold text-muted mb-4">DISTRIBUIÇÃO POR STATUS</h6>
                <canvas id="chartStatus"></canvas>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card-bi p-4 h-100 overflow-hidden">
                <h6 class="fw-bold text-muted mb-4">LISTAGEM DE ATIVIDADES</h6>
                <table class="table table-sm align-middle">
                    <thead><tr><th>Tarefa</th><th>Grupo</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($tarefas as $t): ?>
                        <tr class="t-row" data-st="<?php echo $t['st_nome']; ?>">
                            <td class="fw-bold"><?php echo htmlspecialchars($t['titulo']); ?></td>
                            <td class="small"><?php echo $t['gr_nome']; ?></td>
                            <td><span class="badge" style="background:<?php echo $t['st_cor']; ?>"><?php echo $t['st_nome']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const labels = <?php echo json_encode($js_labels); ?>;
    const ctx = document.getElementById('chartStatus').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'doughnut',
        data: { labels: labels, datasets: [{ data: <?php echo json_encode($js_valores); ?>, backgroundColor: <?php echo json_encode($js_cores); ?>, borderWidth: 0 }] },
        options: {
            cutout: '70%',
            plugins: { legend: { position: 'bottom' } },
            onClick: (e, el) => {
                if (el.length > 0) {
                    const st = labels[el[0].index];
                    document.querySelectorAll('.t-row').forEach(r => {
                        r.style.display = (r.getAttribute('data-st') === st) ? '' : 'none';
                    });
                }
            }
        }
    });
</script>
</body>
</html>