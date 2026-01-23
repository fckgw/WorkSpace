<?php
/**
 * BDSoft Workspace - PROJETOS / QUADRO
 * Local: projetos/quadro.php
 */
session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

$id_quadro = (int)$_GET['id'];
$user_id = $_SESSION['usuario_id'];

// 1. Validar Quadro
$stmtQ = $pdo->prepare("SELECT * FROM quadros_projetos WHERE id = ?");
$stmtQ->execute([$id_quadro]);
$quadro = $stmtQ->fetch();
if (!$quadro) die("Quadro não encontrado.");

// 2. Carregar Status
$lista_status = $pdo->prepare("SELECT * FROM quadros_status WHERE quadro_id = ? ORDER BY id ASC");
$lista_status->execute([$id_quadro]);
$meus_status = $lista_status->fetchAll(PDO::FETCH_ASSOC);

// 3. Carregar Grupos
$stmtG = $pdo->prepare("SELECT * FROM projetos_grupos WHERE quadro_id = ? ORDER BY id ASC");
$stmtG->execute([$id_quadro]);
$grupos = $stmtG->fetchAll(PDO::FETCH_ASSOC);

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
    <title><?php echo htmlspecialchars($quadro['nome']); ?> - BDSoft Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f5f6f8; font-family:'Segoe UI', system-ui, sans-serif; margin:0; }
        .nav-board { background:#fff; border-bottom:1px solid #dee2e6; padding:12px 30px; position:sticky; top:0; z-index:1000; }
        
        .group-header { display: flex; align-items: center; justify-content: space-between; margin-top: 35px; border-bottom: 2px solid #ddd; padding-bottom: 8px; }
        .group-title-input { border: 1px solid transparent; background: transparent; font-weight: 700; font-size: 1.15rem; outline: none; padding: 2px 8px; border-radius: 4px; transition: 0.2s; width: 350px; }
        .group-title-input:focus { border-color: #ddd; background: #fff; }
        
        .table-monday { width:100%; background:#fff; border-radius: 0 0 8px 8px; box-shadow:0 1px 3px rgba(0,0,0,0.1); margin-bottom:25px; border-collapse:collapse; table-layout: fixed; }
        .table-monday th { font-size:11px; color:#676879; padding:12px; border-bottom: 1px solid #eee; font-weight:600; text-transform: uppercase; text-align: left; }
        .task-row { border-bottom: 1px solid #eee; height: 45px; }
        .task-row:hover { background-color: #f8fafc; }

        .status-select { border:none; color:white; font-weight:bold; border-radius:4px; padding:8px; width:100%; cursor:pointer; text-align-last:center; outline:none; appearance: none; font-size: 12px; }
        
        /* Layout Calendário */
        .date-input { border: 1px solid transparent; background:#f8f9fa; border-radius:6px; font-size:12px; padding:6px; width:100%; text-align:center; cursor: pointer; color: #676879; transition: 0.2s; }
        .date-input:hover { background: #eef0f1; border-color: #ced4da; }
        .date-input:focus { background: #fff; border-color: #1a73e8; outline: none; }

        /* Estilo da Timeline */
        #editor-timeline { min-height:120px; border:2px solid #e0e0e0; padding:15px; background:#fff; border-radius:12px; overflow-y:auto; outline:none; margin-bottom: 15px; }
        #lista-updates { max-height: 550px; overflow-y: auto; }
        .loader-overlay { display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.8); z-index:2000; align-items:center; justify-content:center; flex-direction:column; border-radius: 15px; }
        
        /* Olho de Visibilidade */
        .btn-eye { cursor: pointer; color: #676879; margin-right: 15px; font-size: 1.2rem; transition: 0.2s; }
        .btn-eye:hover { color: #1a73e8; }
        .group-collapsed { display: none !important; }
    </style>
</head>
<body>

<nav class="nav-board d-flex justify-content-between align-items-center shadow-sm">
    <div class="d-flex align-items-center">
        <a href="index.php" class="btn btn-sm btn-light border rounded-circle me-3"><i class="fas fa-arrow-left"></i></a>
        <h4 class="fw-bold mb-0 text-primary"><?php echo htmlspecialchars($quadro['nome']); ?></h4>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoGrupo">+ NOVO GRUPO</button>
        <a href="relatorios.php?id=<?php echo $id_quadro; ?>" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">RELATÓRIOS</a>
    </div>
</nav>

<div class="container-fluid p-4">
    <?php foreach($grupos as $g): ?>
    <div class="mb-5">
        
        <div class="group-header" style="border-bottom-color: <?php echo $g['cor']; ?>;">
            <div class="d-flex align-items-center flex-grow-1">
                <i class="fas fa-eye btn-eye" id="eye_<?php echo $g['id']; ?>" onclick="toggleGrupo(<?php echo $g['id']; ?>)"></i>
                
                <input type="text" class="group-title-input" style="color: <?php echo $g['cor']; ?>;" 
                       value="<?php echo htmlspecialchars($g['nome']); ?>" 
                       onblur="upG(<?php echo $g['id']; ?>, 'nome', this.value)">
                
                <input type="color" class="form-control form-control-color border-0 p-0 ms-2" style="width:20px; height:20px; background:none;" 
                       value="<?php echo $g['cor']; ?>" 
                       onchange="upG(<?php echo $g['id']; ?>, 'cor', this.value)">
            </div>
            <a href="acoes.php?del_grupo=<?php echo $g['id']; ?>&quadro_id=<?php echo $id_quadro; ?>" 
               class="text-danger opacity-25" onclick="return confirm('Excluir grupo?')"><i class="fas fa-trash-alt"></i></a>
        </div>
        
        <div id="wrapper_<?php echo $g['id']; ?>">
            <table class="table-monday">
                <thead>
                    <tr>
                        <th style="width:40px;"></th>
                        <th>ITEM / TAREFA</th>
                        <th style="width:180px;" class="text-center">STATUS</th>
                        <th style="width:140px;" class="text-center">INICIAL</th>
                        <th style="width:140px;" class="text-center">FINAL</th>
                        <th style="width:60px;" class="text-center">OBS</th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmtT = $pdo->prepare("SELECT * FROM tarefas_projetos WHERE grupo_id = ? ORDER BY id ASC");
                    $stmtT->execute([$g['id']]);
                    while($t = $stmtT->fetch(PDO::FETCH_ASSOC)):
                        $cor_bg = verCor($meus_status, $t['status_id']);
                    ?>
                    <tr class="task-row">
                        <td class="text-center"><input type="checkbox" class="form-check-input"></td>
                        <td><input type="text" class="form-control form-control-sm border-0 bg-transparent fw-medium" value="<?php echo htmlspecialchars($t['titulo']); ?>" onblur="upT(<?php echo $t['id']; ?>, 'titulo', this.value)"></td>
                        <td class="p-2">
                            <select class="status-select" style="background-color:<?php echo $cor_bg; ?>" onchange="upT(<?php echo $t['id']; ?>, 'status_id', this.value); location.reload();">
                                <?php foreach($meus_status as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo ($t['status_id']==$s['id'])?'selected':''; ?>><?php echo htmlspecialchars($s['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="px-2"><input type="date" class="date-input" value="<?php echo $t['data_inicio']; ?>" onchange="upT(<?php echo $t['id']; ?>, 'data_inicio', this.value)"></td>
                        <td class="px-2"><input type="date" class="date-input" value="<?php echo $t['data_fim']; ?>" onchange="upT(<?php echo $t['id']; ?>, 'data_fim', this.value)"></td>
                        <td class="text-center"><i class="fas fa-comments text-primary cursor-pointer fa-lg" onclick="openTimeline(<?php echo $t['id']; ?>, '<?php echo addslashes($t['titulo']); ?>')"></i></td>
                        <td class="text-center"><a href="acoes.php?excluir_tarefa=<?php echo $t['id']; ?>&id_quadro=<?php echo $id_quadro; ?>" class="text-danger opacity-25"><i class="fas fa-times"></i></a></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr>
                        <td></td>
                        <td colspan="6" class="p-2">
                            <input type="text" class="form-control form-control-sm border-0 text-primary fw-bold px-3" placeholder="+ Adicionar Tarefa (Enter)" onkeypress="if(event.key==='Enter') addT(this.value, <?php echo $g['id']; ?>)">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- MODAIS -->
<div class="modal fade" id="modalTimeline" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg position-relative" style="border-radius:20px; background: #f0f2f5;">
            <div class="loader-overlay" id="editorLoader"><div class="spinner-border text-primary mb-2"></div><div class="fw-bold">Sincronizando...</div></div>
            <div class="modal-header bg-white border-bottom">
                <h5 class="modal-title fw-bold" id="modalTitulo">Anotações</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetarEstadoEdicao()"></button>
            </div>
            <div class="modal-body">
                <div class="card p-3 mb-4 border-0 shadow-sm" id="area-editor" style="border-radius:15px;">
                    <label class="small fw-bold text-muted mb-2 text-uppercase" id="label-editor">Nova Atualização / Print Screen</label>
                    <div id="editor-timeline" contenteditable="true" placeholder="O que há de novo?"></div>
                    <input type="hidden" id="edit_update_id" value="0">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-light rounded-pill px-4" id="btnCancelarEdit" style="display:none;" onclick="resetarEstadoEdicao()">Cancelar</button>
                        <button class="btn btn-primary rounded-pill px-5 fw-bold" id="btnSalvar" onclick="saveUpdate()">SALVAR QUADRO</button>
                    </div>
                </div>
                <div id="lista-updates"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoGrupo" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form action="acoes.php" method="POST" class="modal-content shadow border-0"><div class="modal-body p-4">
    <input type="hidden" name="acao" value="novo_grupo"><input type="hidden" name="quadro_id" value="<?php echo $id_quadro; ?>">
    <label class="small fw-bold mb-1">NOME DO GRUPO</label><input type="text" name="nome_grupo" class="form-control mb-3" required autofocus>
    <label class="small fw-bold mb-1">COR DO TEMA</label><input type="color" name="cor" class="form-control form-control-color w-100" value="#1a73e8">
    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 mt-4 fw-bold shadow">CRIAR GRUPO</button>
</div></form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let curTaskId = 0; 
const modalT = new bootstrap.Modal(document.getElementById('modalTimeline'));

/**
 * LOGICA DE VISIBILIDADE PERSISTENTE
 */
function toggleGrupo(id) {
    const wrapper = document.getElementById('wrapper_' + id);
    const eye = document.getElementById('eye_' + id);
    
    // Pega a lista de grupos ocultos do LocalStorage
    let hiddenGroups = JSON.parse(localStorage.getItem('hidden_groups_q<?php echo $id_quadro; ?>')) || [];

    if (wrapper.classList.contains('group-collapsed')) {
        // MOSTRAR
        wrapper.classList.remove('group-collapsed');
        eye.classList.replace('fa-eye-slash', 'fa-eye');
        hiddenGroups = hiddenGroups.filter(item => item !== id);
    } else {
        // ESCONDER
        wrapper.classList.add('group-collapsed');
        eye.classList.replace('fa-eye', 'fa-eye-slash');
        if (!hiddenGroups.includes(id)) hiddenGroups.push(id);
    }

    // Salva de volta no LocalStorage
    localStorage.setItem('hidden_groups_q<?php echo $id_quadro; ?>', JSON.stringify(hiddenGroups));
}

// Restaura os grupos fechados ao carregar a página
function restoreVisibility() {
    let hiddenGroups = JSON.parse(localStorage.getItem('hidden_groups_q<?php echo $id_quadro; ?>')) || [];
    hiddenGroups.forEach(id => {
        const wrapper = document.getElementById('wrapper_' + id);
        const eye = document.getElementById('eye_' + id);
        if (wrapper && eye) {
            wrapper.classList.add('group-collapsed');
            eye.classList.replace('fa-eye', 'fa-eye-slash');
        }
    });
}

// Executa ao carregar
window.onload = restoreVisibility;

function upG(id, c, v) { const fd = new FormData(); fd.append('acao', 'atualizar_campo_grupo'); fd.append('id', id); fd.append('campo', c); fd.append('valor', v); fetch('acoes.php', { method: 'POST', body: fd }).then(() => { if(c === 'cor') location.reload(); }); }
function upT(id, c, v) { const fd = new FormData(); fd.append('acao', 'atualizar_campo_tarefa'); fd.append('id', id); fd.append('campo', c); fd.append('valor', v); fetch('acoes.php', { method: 'POST', body: fd }); }
function addT(t, g) { if(!t.trim()) return; const fd = new FormData(); fd.append('acao', 'nova_tarefa'); fd.append('titulo', t); fd.append('grupo_id', g); fd.append('quadro_id', <?php echo $id_quadro; ?>); fetch('acoes.php', { method: 'POST', body: fd }).then(() => location.reload()); }
function openTimeline(id, t) { curTaskId = id; document.getElementById('modalTitulo').innerText = t; resetarEstadoEdicao(); modalT.show(); carregarUpdates(); }
function carregarUpdates() { fetch('acoes.php?acao=get_updates&id='+curTaskId).then(r => r.text()).then(h => { document.getElementById('lista-updates').innerHTML = h; }); }
function saveUpdate() { 
    const editor = document.getElementById('editor-timeline'); const upId = document.getElementById('edit_update_id').value;
    if(!editor.innerText.trim() && !editor.innerHTML.includes('<img')) return alert("Escreva algo!");
    document.getElementById('editorLoader').style.display='flex';
    const fd = new FormData(); fd.append('acao', 'salvar_update'); fd.append('id', curTaskId); fd.append('update_id', upId); fd.append('conteudo', editor.innerHTML);
    fetch('acoes.php', { method: 'POST', body: fd }).then(() => { document.getElementById('editorLoader').style.display='none'; resetarEstadoEdicao(); carregarUpdates(); });
}
function prepararEdicao(id) {
    const conteudo = document.getElementById('conteudo_update_' + id).innerHTML;
    document.getElementById('editor-timeline').innerHTML = conteudo;
    document.getElementById('edit_update_id').value = id;
    document.getElementById('label-editor').innerText = "Editando Atualização:";
    document.getElementById('btnSalvar').innerText = "ATUALIZAR QUADRO";
    document.getElementById('btnCancelarEdit').style.display = "block";
}
function resetarEstadoEdicao() {
    document.getElementById('editor-timeline').innerHTML = "";
    document.getElementById('edit_update_id').value = "0";
    document.getElementById('btnSalvar').innerText = "SALVAR QUADRO";
    document.getElementById('btnCancelarEdit').style.display = "none";
}
function excluirUpdate(id) { if(confirm("Excluir comentário?")) fetch('acoes.php?acao=excluir_update&id_update=' + id).then(() => carregarUpdates()); }

document.getElementById('editor-timeline').addEventListener('paste', function(e) {
    const items = (e.clipboardData || e.originalEvent.clipboardData).items; 
    for (let i in items) { if (items[i].kind === 'file') { e.preventDefault(); const blob = items[i].getAsFile(); const r = new FileReader(); r.onload = function(ev) { const img = document.createElement('img'); img.src = ev.target.result; document.getElementById('editor-timeline').appendChild(img); }; r.readAsDataURL(blob); } }
});
</script>
</body>
</html>