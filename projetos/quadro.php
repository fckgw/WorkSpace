<?php
/**
 * BDSoft Workspace - PROJETOS / QUADRO COM FILTROS
 * Local: projetos/quadro.php
 */
session_start();
require_once '../config.php';

// 1. Verificação de Segurança
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: ../login.php"); 
    exit; 
}

$id_quadro = (int)$_GET['id'];
$user_id = $_SESSION['usuario_id'];

// 2. Capturar Filtros via GET
$filtro_status = isset($_GET['f_status']) ? $_GET['f_status'] : '';
$filtro_mes    = isset($_GET['f_mes']) ? $_GET['f_mes'] : ''; // Formato esperado: YYYY-MM

// 3. Validar se o Quadro existe
$stmtQ = $pdo->prepare("SELECT * FROM quadros_projetos WHERE id = ?");
$stmtQ->execute([$id_quadro]);
$quadro = $stmtQ->fetch();

if (!$quadro) {
    die("<div style='padding:50px; text-align:center;'><h2>❌ Quadro não encontrado</h2><a href='index.php'>Voltar</a></div>");
}

// 4. Carregar Status cadastrados para este Quadro (Para o Filtro e ComboBox)
$stmtS = $pdo->prepare("SELECT * FROM quadros_status WHERE quadro_id = ? ORDER BY id ASC");
$stmtS->execute([$id_quadro]);
$meus_status = $stmtS->fetchAll(PDO::FETCH_ASSOC);

// 5. Carregar Grupos vinculados ao Quadro
$stmtG = $pdo->prepare("SELECT * FROM projetos_grupos WHERE quadro_id = ? ORDER BY id ASC");
$stmtG->execute([$id_quadro]);
$grupos = $stmtG->fetchAll(PDO::FETCH_ASSOC);

// 6. Gerar Lista de Meses Dinâmica (Baseada nas tarefas existentes para o filtro)
$stmt_meses = $pdo->prepare("SELECT DISTINCT DATE_FORMAT(data_fim, '%Y-%m') as mes_ano 
                             FROM tarefas_projetos 
                             WHERE quadro_id = ? AND data_fim IS NOT NULL 
                             ORDER BY data_fim DESC");
$stmt_meses->execute([$id_quadro]);
$lista_meses_disponiveis = $stmt_meses->fetchAll(PDO::FETCH_COLUMN);

function verCor($lista, $id) {
    foreach($lista as $s) { if($s['id'] == $id) return $s['cor']; }
    return "#c4c4c4";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quadro['nome']); ?> - BDSoft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f5f6f8; font-family:'Segoe UI', sans-serif; margin:0; }
        .nav-board { background:#fff; border-bottom:1px solid #dee2e6; padding:12px 30px; position:sticky; top:0; z-index:1000; }
        .table-monday { width:100%; background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1); margin-bottom:25px; border-collapse:collapse; }
        .table-monday th { font-size:11px; color:#676879; padding:12px; border-bottom: 1px solid #eee; font-weight:600; text-transform: uppercase; text-align: left; }
        .status-select { border:none; color:white; font-weight:bold; border-radius:4px; padding:6px; width:100%; cursor:pointer; text-align-last:center; outline:none; appearance: none; font-size: 12px; }
        .date-input { border:none; background:#f8f9fa; border-radius:4px; font-size:12px; padding:5px; width:100%; text-align:center; }
        .btn-eye { cursor: pointer; color: #676879; margin-right: 15px; font-size: 1.2rem; transition: 0.2s; }
        .group-collapsed { display: none !important; }
        
        /* Estilo da Barra de Filtros */
        .filter-bar { background: #fff; padding: 10px 30px; border-bottom: 1px solid #eee; display: flex; gap: 15px; align-items: center; }
    </style>
</head>
<body>

<nav class="nav-board d-flex justify-content-between align-items-center shadow-sm">
    <div class="d-flex align-items-center">
        <a href="index.php" class="btn btn-sm btn-light border rounded-circle me-3"><i class="fas fa-arrow-left"></i></a>
        <h4 class="fw-bold mb-0 text-primary"><?php echo htmlspecialchars($quadro['nome']); ?></h4>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalNovoGrupo">+ NOVO GRUPO</button>
        <a href="relatorios.php?id=<?php echo $id_quadro; ?>" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold">RELATÓRIOS</a>
    </div>
</nav>

<!-- BARRA DE FILTROS -->
<div class="filter-bar no-print">
    <div class="d-flex align-items-center gap-2">
        <i class="fas fa-filter text-muted small"></i>
        <span class="small fw-bold text-muted">FILTRAR POR:</span>
    </div>
    
    <form method="GET" class="d-flex gap-2 align-items-center mb-0">
        <input type="hidden" name="id" value="<?php echo $id_quadro; ?>">
        
        <!-- Filtro Status -->
        <select name="f_status" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
            <option value="">Status: Todos</option>
            <?php foreach($meus_status as $st): ?>
                <option value="<?php echo $st['id']; ?>" <?php echo ($filtro_status == $st['id']) ? 'selected' : ''; ?>>
                    <?php echo $st['label']; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Filtro Meses -->
        <select name="f_mes" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
            <option value="">Mês: Todos</option>
            <?php foreach($lista_meses_disponiveis as $mes): 
                $dataObj = DateTime::createFromFormat('Y-m', $mes);
                $mes_extenso = strftime('%B / %Y', $dataObj->getTimestamp()); // Tradução manual pode ser necessária dependendo do servidor
            ?>
                <option value="<?php echo $mes; ?>" <?php echo ($filtro_mes == $mes) ? 'selected' : ''; ?>>
                    <?php echo date('m/Y', strtotime($mes."-01")); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if($filtro_status || $filtro_mes): ?>
            <a href="quadro.php?id=<?php echo $id_quadro; ?>" class="btn btn-sm btn-link text-decoration-none text-danger small">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<div class="container-fluid p-4">
    <?php foreach($grupos as $g): ?>
    <div class="mb-5">
        <div class="group-header d-flex align-items-center mb-2 justify-content-between">
            <div class="d-flex align-items-center flex-grow-1">
                <i class="fas fa-eye btn-eye" id="eye_<?php echo $g['id']; ?>" onclick="toggleGrupo(<?php echo $g['id']; ?>)"></i>
                <input type="text" class="group-title-input" style="color:<?php echo $g['cor']; ?>;" value="<?php echo htmlspecialchars($g['nome']); ?>" onblur="upG(<?php echo $g['id']; ?>, 'nome', this.value)">
            </div>
            <a href="acoes.php?del_grupo=<?php echo $g['id']; ?>&quadro_id=<?php echo $id_quadro; ?>" class="text-danger opacity-25" onclick="return confirm('Excluir grupo?')"><i class="fas fa-trash-alt"></i></a>
        </div>
        
        <div id="wrapper_<?php echo $g['id']; ?>">
            <table class="table-monday">
                <thead>
                    <tr>
                        <th style="width:40px;"></th>
                        <th>TAREFA / ITEM</th>
                        <th style="width:180px;" class="text-center">STATUS</th>
                        <th style="width:140px;" class="text-center">INICIAL</th>
                        <th style="width:140px;" class="text-center">FINAL</th>
                        <th style="width:60px;" class="text-center">OBS</th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // --- CONSULTA DE TAREFAS COM FILTROS ---
                    $sql_t = "SELECT * FROM tarefas_projetos WHERE grupo_id = ? AND quadro_id = ?";
                    $params_t = [$g['id'], $id_quadro];

                    if (!empty($filtro_status)) {
                        $sql_t .= " AND status_id = ?";
                        $params_t[] = $filtro_status;
                    }
                    if (!empty($filtro_mes)) {
                        $sql_t .= " AND DATE_FORMAT(data_fim, '%Y-%m') = ?";
                        $params_t[] = $filtro_mes;
                    }

                    $stmtT = $pdo->prepare($sql_t . " ORDER BY id ASC");
                    $stmtT->execute($params_t);
                    $tarefas = $stmtT->fetchAll(PDO::FETCH_ASSOC);

                    foreach($tarefas as $t):
                        $cor_bg = verCor($meus_status, $t['status_id']);
                    ?>
                    <tr class="task-row border-bottom">
                        <td class="text-center"><input type="checkbox" class="form-check-input"></td>
                        <td><input type="text" class="form-control form-control-sm border-0 bg-transparent fw-medium" value="<?php echo htmlspecialchars($t['titulo']); ?>" onblur="upT(<?php echo $t['id']; ?>, 'titulo', this.value)"></td>
                        <td class="p-2">
                            <select class="status-select" style="background-color:<?php echo $cor_bg; ?>" onchange="upT(<?php echo $t['id']; ?>, 'status_id', this.value); location.reload();">
                                <?php foreach($meus_status as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo ($t['status_id']==$s['id'])?'selected':''; ?>><?php echo htmlspecialchars($s['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="date" class="date-input" value="<?php echo $t['data_inicio']; ?>" onchange="upT(<?php echo $t['id']; ?>, 'data_inicio', this.value)"></td>
                        <td><input type="date" class="date-input" value="<?php echo $t['data_fim']; ?>" onchange="upT(<?php echo $t['id']; ?>, 'data_fim', this.value)"></td>
                        <td class="text-center"><i class="fas fa-file-signature text-primary cursor-pointer fa-lg" onclick="openTimeline(<?php echo $t['id']; ?>, '<?php echo addslashes($t['titulo']); ?>')"></i></td>
                        <td class="text-center"><a href="acoes.php?excluir_tarefa=<?php echo $t['id']; ?>&id_quadro=<?php echo $id_quadro; ?>" class="text-danger opacity-25"><i class="fas fa-times"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($tarefas) && ($filtro_status || $filtro_mes)): ?>
                        <tr><td colspan="7" class="text-center p-3 text-muted small italic">Nenhuma tarefa encontrada com estes filtros neste grupo.</td></tr>
                    <?php endif; ?>

                    <!-- Adição Rápida (Escondida se houver filtros ativos para não bagunçar a lógica) -->
                    <?php if(!$filtro_status && !$filtro_mes): ?>
                    <tr>
                        <td></td>
                        <td colspan="7" class="p-2">
                            <input type="text" class="form-control form-control-sm border-0 text-primary fw-bold px-3" placeholder="+ Adicionar Tarefa (Enter)" onkeypress="if(event.key==='Enter') addT(this.value, <?php echo $g['id']; ?>)">
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- MODAL EDITOR E NOVO GRUPO (Mantenha os que já possui) -->
<div class="modal fade" id="modalEditor" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content border-0 shadow-lg position-relative"><div class="loader" id="editorLoader"><div class="spinner-border text-primary"></div><div class="mt-2 fw-bold">Sincronizando...</div></div><div class="modal-header bg-dark text-white border-0"><h5 class="modal-title" id="modalTitulo"></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body bg-light"><div id="editor-evidencias" contenteditable="true"></div></div><div class="modal-footer"><button class="btn btn-primary px-5 rounded-pill fw-bold" id="btnSalvar" onclick="saveE()">SALVAR</button></div></div></div></div>

<div class="modal fade" id="modalNovoGrupo" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form action="acoes.php" method="POST" class="modal-content shadow border-0"><div class="modal-body p-4">
    <input type="hidden" name="acao" value="novo_grupo"><input type="hidden" name="quadro_id" value="<?php echo $id_quadro; ?>">
    <label class="small fw-bold mb-1">NOME DO GRUPO</label><input type="text" name="nome_grupo" class="form-control mb-3" required autofocus>
    <label class="small fw-bold mb-1">COR DO TEMA</label><input type="color" name="cor" class="form-control form-control-color w-100" value="#1a73e8">
    <button type="submit" class="btn btn-primary w-100 rounded-pill mt-4 fw-bold">CRIAR GRUPO</button>
</div></form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Lógica de Memória do Olho
window.onload = function() {
    let hidden = JSON.parse(localStorage.getItem('hidden_groups_q<?php echo $id_quadro; ?>')) || [];
    hidden.forEach(id => {
        const wrap = document.getElementById('wrapper_' + id);
        const eye = document.getElementById('eye_' + id);
        if(wrap) wrap.classList.add('group-collapsed');
        if(eye) eye.classList.replace('fa-eye', 'fa-eye-slash');
    });
};

function toggleGrupo(id) {
    const wrap = document.getElementById('wrapper_' + id);
    const eye = document.getElementById('eye_' + id);
    let hidden = JSON.parse(localStorage.getItem('hidden_groups_q<?php echo $id_quadro; ?>')) || [];

    if (wrap.classList.contains('group-collapsed')) {
        wrap.classList.remove('group-collapsed');
        eye.classList.replace('fa-eye-slash', 'fa-eye');
        hidden = hidden.filter(i => i !== id);
    } else {
        wrap.classList.add('group-collapsed');
        eye.classList.replace('fa-eye', 'fa-eye-slash');
        if(!hidden.includes(id)) hidden.push(id);
    }
    localStorage.setItem('hidden_groups_q<?php echo $id_quadro; ?>', JSON.stringify(hidden));
}

function upG(id, c, v) { const fd = new FormData(); fd.append('acao', 'atualizar_campo_grupo'); fd.append('id', id); fd.append('campo', c); fd.append('valor', v); fetch('acoes.php', { method: 'POST', body: fd }).then(() => location.reload()); }
function upT(id, c, v) { const fd = new FormData(); fd.append('acao', 'atualizar_campo_tarefa'); fd.append('id', id); fd.append('campo', c); fd.append('valor', v); fetch('acoes.php', { method: 'POST', body: fd }); }
function addT(t, g) { if(!t.trim()) return; const fd = new FormData(); fd.append('acao', 'nova_tarefa'); fd.append('titulo', t); fd.append('grupo_id', g); fd.append('quadro_id', <?php echo $id_quadro; ?>); fetch('acoes.php', { method: 'POST', body: fd }).then(() => location.reload()); }
</script>
</body>
</html>