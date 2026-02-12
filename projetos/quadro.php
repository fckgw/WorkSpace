<?php
/**
 * BDSoft Workspace - PROJETOS / QUADRO
 * Localização: public_html/projetos/quadro.php
 * Versão: 100% Estabilizada | Drawer | Status | Membros
 */

// 1. Configurações de Depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config.php';

// 2. Verificação de Segurança
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: ../login.php"); 
    exit; 
}

$id_quadro = (int)$_GET['id'];
$user_id_sessao = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');

// 3. Validar o Quadro e Permissões
$stmt_quadro = $pdo->prepare("SELECT * FROM quadros_projetos WHERE id = ?");
$stmt_quadro->execute([$id_quadro]);
$quadro = $stmt_quadro->fetch(PDO::FETCH_ASSOC);

if (!$quadro) {
    die("<div style='padding:50px; text-align:center; font-family:sans-serif;'><h2>❌ Quadro não encontrado</h2><a href='index.php'>Voltar para Projetos</a></div>");
}

// 4. Carregar Status cadastrados para este projeto
$stmt_status = $pdo->prepare("SELECT * FROM quadros_status WHERE quadro_id = ? ORDER BY id ASC");
$stmt_status->execute([$id_quadro]);
$lista_status = $stmt_status->fetchAll(PDO::FETCH_ASSOC);

// 5. Carregar Grupos de Trabalho
$stmt_grupos = $pdo->prepare("SELECT * FROM projetos_grupos WHERE quadro_id = ? ORDER BY id ASC");
$stmt_grupos->execute([$id_quadro]);
$grupos = $stmt_grupos->fetchAll(PDO::FETCH_ASSOC);

/**
 * FUNÇÃO: Identifica a cor da etiqueta de status
 */
function obterCorStatus($lista, $status_id) {
    foreach($lista as $s) { 
        if($s['id'] == $status_id) return $s['cor']; 
    }
    return "#c4c4c4"; // Cor cinza padrão
}

/**
 * FUNÇÃO: Calcula a Situação do Prazo e Entrega
 */
function calcularSituacaoTarefa($t, $hoje, $lista_status) {
    $nome_status = '';
    foreach($lista_status as $s) { 
        if($s['id'] == $t['status_id']) $nome_status = $s['label']; 
    }

    // Se o status for de entrega concluída
    if (stripos($nome_status, 'Concluído') !== false || stripos($nome_status, 'Concluido') !== false) {
        $data_finalizacao = $t['data_conclusao'] ?? $hoje;
        if ($data_finalizacao <= $t['data_fim']) {
            return '<span class="badge bg-success shadow-sm" style="font-size:10px;">ENTREGUE NO PRAZO</span>';
        } else {
            return '<span class="badge bg-warning text-dark shadow-sm" style="font-size:10px;">ENTREGUE COM ATRASO</span>';
        }
    }

    // Se ainda pendente
    if (empty($t['data_inicio']) || empty($t['data_fim'])) return '<span class="badge bg-light text-muted border" style="font-size:10px;">S/ DATA</span>';
    if ($t['data_inicio'] > $hoje) return '<span class="badge bg-secondary opacity-75" style="font-size:10px;">AGUARDANDO</span>';
    if ($hoje >= $t['data_inicio'] && $hoje <= $t['data_fim']) return '<span class="badge bg-primary shadow-sm" style="font-size:10px;">EM CURSO</span>';
    
    return '<span class="badge bg-danger shadow-sm" style="font-size:10px;">ATRASADO</span>';
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
        :root { --sidebar-mini-w: 70px; --primary-blue: #1a73e8; }
        body { background:#f8f9fa; font-family:'Segoe UI', sans-serif; margin:0; display: flex; }
        
        /* Sidebar lateral mini */
        .sidebar-mini { width: var(--sidebar-mini-w); background:#292f4c; height:100vh; position:fixed; left:0; top:0; z-index:1050; display: flex; flex-direction: column; align-items: center; padding-top: 25px; }
        .sidebar-mini a { color: rgba(255,255,255,0.6); margin-bottom: 25px; transition: 0.3s; text-decoration: none; }
        .sidebar-mini a:hover { color: #fff; }

        .main-wrapper { flex:1; margin-left: var(--sidebar-mini-w); min-width: 0; }
        .nav-board { background:#ffffff; border-bottom:1px solid #dee2e6; padding:12px 25px; position:sticky; top:0; z-index:900; }
        
        /* Estilo dos Grupos */
        .group-card { background: #ffffff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 35px; border: 1px solid #eee; overflow: hidden; }
        .group-header { padding: 15px 25px; display: flex; align-items: center; justify-content: space-between; border-left: 8px solid; background: #fff; }
        
        /* Tabela Monday */
        .table-clean { width: 100%; border-collapse: collapse; }
        .table-clean th { background: #fafafa; padding: 12px; font-size: 10px; color: #6c757d; text-transform: uppercase; border-bottom: 1px solid #eee; }
        .task-row { border-bottom: 1px solid #f8f9fa; transition: 0.2s; cursor: pointer; height: 50px; }
        .task-row:hover { background-color: #f0f7ff; }

        .status-select { border:none; color:white; font-weight:bold; border-radius:4px; padding:6px 12px; width: 100%; cursor:pointer; text-align-last:center; outline:none; appearance:none; font-size:11px; }
        
        /* Painel Lateral (Drawer) */
        .offcanvas { width: 45% !important; border-left: none; box-shadow: -10px 0 30px rgba(0,0,0,0.1); }
        #editor-timeline { min-height: 140px; border: 2px solid #e0e0e0; padding: 15px; border-radius: 12px; outline: none; background: #ffffff; margin-bottom: 15px; }
        #editor-timeline img { max-width: 100%; border-radius: 10px; margin: 15px 0; }

        .group-collapsed { display: none !important; }
        .loader-overlay { display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.8); z-index:2000; align-items:center; justify-content:center; flex-direction:column; border-radius: 15px; }

        @media (max-width: 991px) { .sidebar-mini { display:none; } .main-wrapper { margin-left: 0; } .offcanvas { width: 100% !important; } }
    </style>
</head>
<body>

<!-- SIDEBAR MINI (ÍCONES) -->
<div class="sidebar-mini shadow no-print">
    <a href="../portal.php" title="Portal Workspace"><i class="fas fa-th-large fa-2x"></i></a>
    <a href="index.php" title="Meus Projetos"><i class="fas fa-project-diagram fa-lg"></i></a>
    <hr class="w-75 opacity-25">
    <button class="btn btn-primary btn-sm rounded-circle shadow" data-bs-toggle="modal" data-bs-target="#modalNovoGrupo"><i class="fas fa-plus"></i></button>
</div>

<div class="main-wrapper">
    <!-- NAVBAR -->
    <nav class="nav-board d-flex justify-content-between align-items-center shadow-sm">
        <div class="d-flex align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($quadro['nome']); ?></h5>
            <div class="ms-3">
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalMembros"><i class="fas fa-user-plus me-1"></i> COLABORADORES</button>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalStatus"><i class="fas fa-tags me-1"></i> STATUS</button>
            <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoGrupo">+ GRUPO</button>
            <a href="relatorios.php?id=<?php echo $id_quadro; ?>" class="btn btn-info btn-sm text-white rounded-pill px-4 fw-bold">BI</a>
        </div>
    </nav>

    <div class="p-4">
        <?php foreach($grupos as $g): ?>
        <div class="group-card">
            <div class="group-header" style="border-left-color: <?php echo $g['cor']; ?>;">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-eye text-muted cursor-pointer" onclick="toggleVisibilidade(<?php echo $g['id']; ?>)"></i>
                    <input type="text" class="group-title-input" style="color:<?php echo $g['cor']; ?>;" value="<?php echo htmlspecialchars($g['nome']); ?>" onblur="ajaxUpdateGrupo(<?php echo $g['id']; ?>, 'nome', this.value)">
                </div>
                <div class="opacity-25 no-print">
                    <input type="color" class="form-control form-control-color border-0 p-0" value="<?php echo $g['cor']; ?>" onchange="ajaxUpdateGrupo(<?php echo $g['id']; ?>, 'cor', this.value)" style="width:20px;height:20px;background:none;display:inline-block;vertical-align:middle;">
                    <i class="fas fa-trash-alt cursor-pointer text-danger ms-2" onclick="if(confirm('Excluir este grupo?')) window.location.href='acoes.php?del_grupo=<?php echo $g['id']; ?>&quadro_id=<?php echo $id_quadro; ?>'"></i>
                </div>
            </div>
            
            <div id="wrap_<?php echo $g['id']; ?>">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th style="width:50px;"></th>
                            <th>NOME DA TAREFA</th>
                            <th style="width:180px;" class="text-center">SITUAÇÃO</th>
                            <th style="width:180px;" class="text-center">STATUS ATUAL</th>
                            <th style="width:120px;" class="text-center">DETALHES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmtT = $pdo->prepare("SELECT * FROM tarefas_projetos WHERE grupo_id = ? AND quadro_id = ? ORDER BY id ASC");
                        $stmtT->execute([$g['id'], $id_quadro]);
                        while($t = $stmtT->fetch(PDO::FETCH_ASSOC)):
                            $cor_fundo_status = obterCorStatus($lista_status, $t['status_id']);
                        ?>
                        <tr class="task-row">
                            <td class="text-center"><input type="checkbox" class="form-check-input"></td>
                            <td onclick="abrirPainelDetalhes(<?php echo $t['id']; ?>, '<?php echo addslashes($t['titulo']); ?>')">
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($t['titulo']); ?></span>
                            </td>
                            <td class="text-center" onclick="abrirPainelDetalhes(<?php echo $t['id']; ?>)">
                                <?php echo calcularSituacaoTarefa($t, $hoje, $lista_status); ?>
                            </td>
                            <td class="p-2">
                                <select class="status-select shadow-sm" style="background-color:<?php echo $cor_fundo_status; ?>" onchange="ajaxUpdateTarefa(<?php echo $t['id']; ?>, 'status_id', this.value); location.reload();">
                                    <option value="">Selecione...</option>
                                    <?php foreach($lista_status as $s_op): ?>
                                        <option value="<?php echo $s_op['id']; ?>" <?php echo ($t['status_id'] == $s_op['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s_op['label']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-light border rounded-pill px-3 fw-bold" onclick="abrirPainelDetalhes(<?php echo $t['id']; ?>, '<?php echo addslashes($t['titulo']); ?>')">Abrir</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <tr><td colspan="5" class="p-2 bg-light bg-opacity-25"><input type="text" class="form-control form-control-sm border-0 bg-transparent text-primary fw-bold px-3" placeholder="+ Adicionar tarefa..." onkeypress="if(event.key==='Enter') addTarefaRapida(this.value, <?php echo $g['id']; ?>)"></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- PAINEL LATERAL (OFFCANVAS / DRAWER) -->
<div class="offcanvas offcanvas-end shadow-lg" tabindex="-1" id="painelTarefa">
    <div class="offcanvas-header border-bottom">
        <h5 class="fw-bold mb-0 text-primary" id="painelTitulo"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-4 bg-light">
        <!-- SEÇÃO DATAS -->
        <div class="card border-0 shadow-sm p-4 mb-4 rounded-4">
            <div class="row g-3">
                <div class="col-6"><label class="small fw-bold text-muted uppercase">Início</label><input type="date" id="p_inicio" class="form-control border-light" onchange="salvarDetalhePainel('data_inicio', this.value)"></div>
                <div class="col-6"><label class="small fw-bold text-muted uppercase">Prazo</label><input type="date" id="p_fim" class="form-control border-light" onchange="salvarDetalhePainel('data_fim', this.value)"></div>
                <div class="col-12 mt-3"><label class="small fw-bold text-muted uppercase">Justificativa / Comentário</label><textarea id="p_justificativa" class="form-control border-light" rows="2" onblur="salvarDetalhePainel('justificativa', this.value)"></textarea></div>
            </div>
        </div>
        <!-- SEÇÃO TIMELINE -->
        <div class="card border-0 shadow-sm p-4 rounded-4">
            <h6 class="fw-bold text-muted mb-3 small uppercase">Timeline / Prints</h6>
            <div id="editor-timeline" contenteditable="true" placeholder="Cole um print (Ctrl+V)..."></div>
            <input type="hidden" id="edit_up_id" value="0">
            <div class="text-end mt-2 mb-4">
                <button class="btn btn-light btn-sm rounded-pill px-3" id="btnCancelUp" style="display:none;" onclick="resetarEdicaoTimeline()">Cancelar</button>
                <button class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow" id="btnSaveUp" onclick="salvarUpdateTimeline()">PUBLICAR</button>
            </div>
            <div id="timeline-feed"></div>
        </div>
    </div>
</div>

<!-- MODAL: MEMBROS / COLABORADORES -->
<div class="modal fade" id="modalMembros" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content p-4 border-0 shadow-lg">
    <h5 class="fw-bold mb-4 text-primary">Colaboradores do Quadro</h5>
    <div class="input-group mb-4">
        <select id="sel_colab" class="form-select">
            <option value="">Convidar usuário...</option>
            <?php 
            $usuarios_all = $pdo->query("SELECT id, nome FROM usuarios WHERE status = 'ativo' ORDER BY nome ASC")->fetchAll();
            foreach($usuarios_all as $ua) echo "<option value='{$ua['id']}'>{$ua['nome']}</option>";
            ?>
        </select>
        <button class="btn btn-primary" onclick="adicionarColaborador()">CONVIDAR</button>
    </div>
    <div class="list-group list-group-flush">
        <?php 
        $stmt_m = $pdo->prepare("SELECT u.nome, u.id FROM usuarios u INNER JOIN quadro_membros qm ON u.id = qm.usuario_id WHERE qm.quadro_id = ?");
        $stmt_m->execute([$id_quadro]);
        while($m = $stmt_m->fetch()): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-circle text-muted me-2"></i> <?php echo htmlspecialchars($m['nome']); ?></span>
                <?php if($m['id'] != $quadro['usuario_id']): ?>
                    <button class="btn btn-sm text-danger" onclick="removerColaborador(<?php echo $m['id']; ?>)"><i class="fas fa-user-minus"></i></button>
                <?php else: ?>
                    <small class="text-muted">Proprietário</small>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</div></div></div>

<!-- MODAL: STATUS -->
<div class="modal fade" id="modalStatus" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content p-4 border-0 shadow-lg">
    <h5 class="fw-bold mb-4">Gerenciar Etiquetas</h5>
    <div class="input-group mb-4">
        <input type="text" id="ns_label" class="form-control" placeholder="Novo status...">
        <input type="color" id="ns_color" class="form-control form-control-color" value="#1a73e8">
        <button class="btn btn-primary" onclick="adicionarStatus()">ADD</button>
    </div>
    <div class="list-group">
        <?php foreach($lista_status as $ls): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fas fa-circle me-2" style="color:<?php echo $ls['cor']; ?>;"></i> <?php echo htmlspecialchars($ls['label']); ?></span>
                <button class="btn btn-sm text-danger" onclick="excluirStatus(<?php echo $ls['id']; ?>)"><i class="fas fa-trash"></i></button>
            </div>
        <?php endforeach; ?>
    </div>
</div></div></div>

<!-- MODAL: NOVO GRUPO -->
<div class="modal fade" id="modalNovoGrupo" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form action="acoes.php" method="POST" class="modal-content shadow border-0"><div class="modal-body p-4">
    <input type="hidden" name="acao" value="novo_grupo"><input type="hidden" name="quadro_id" value="<?php echo $id_quadro; ?>">
    <label class="small fw-bold mb-1">NOME DO GRUPO</label><input type="text" name="nome_grupo" class="form-control mb-3" required autofocus>
    <label class="small fw-bold mb-1">COR DO TEMA</label><input type="color" name="cor" class="form-control form-control-color w-100" value="#1a73e8">
    <button type="submit" class="btn btn-primary w-100 rounded-pill mt-4 fw-bold shadow">CRIAR</button>
</div></form></div></div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let curId = 0; 
const offcanvasElement = document.getElementById('painelTarefa');
const drawer = new bootstrap.Offcanvas(offcanvasElement);

// 1. Visibilidade de Grupos
window.onload = function() {
    let hid = JSON.parse(localStorage.getItem('h_q<?php echo $id_quadro; ?>')) || [];
    hid.forEach(id => { const w = document.getElementById('wrap_'+id); if(w) w.classList.add('group-collapsed'); });
};
function toggleVisibilidade(id) {
    const w = document.getElementById('wrap_'+id);
    let hid = JSON.parse(localStorage.getItem('h_q<?php echo $id_quadro; ?>')) || [];
    if(w.classList.contains('group-collapsed')) { w.classList.remove('group-collapsed'); hid = hid.filter(i => i !== id); }
    else { w.classList.add('group-collapsed'); if(!hid.includes(id)) hid.push(id); }
    localStorage.setItem('h_q<?php echo $id_quadro; ?>', JSON.stringify(hid));
}

// 2. Ações AJAX
function ajaxUpdateGrupo(id, campo, valor) {
    const fd = new FormData(); fd.append('acao', 'atualizar_campo_grupo'); fd.append('id', id); fd.append('campo', campo); fd.append('valor', valor);
    fetch('acoes.php', { method: 'POST', body: fd }).then(() => { if(campo === 'cor') location.reload(); });
}
function ajaxUpdateTarefa(id, campo, valor) {
    const fd = new FormData(); fd.append('acao', 'atualizar_campo_tarefa'); fd.append('id', id); fd.append('campo', campo); fd.append('valor', valor);
    fetch('acoes.php', { method: 'POST', body: fd });
}
function addTarefaRapida(titulo, grupo_id) {
    if(!titulo.trim()) return;
    const fd = new FormData(); fd.append('acao', 'nova_tarefa_completa'); fd.append('titulo', titulo); fd.append('grupo_id', grupo_id); fd.append('quadro_id', <?php echo $id_quadro; ?>); fd.append('data_inicio', '<?php echo $hoje; ?>'); fd.append('data_fim', '<?php echo $hoje; ?>');
    fetch('acoes.php', { method: 'POST', body: fd }).then(() => location.reload());
}

// 3. Painel de Detalhes
function abrirPainelDetalhes(id, titulo) {
    curId = id; document.getElementById('painelTitulo').innerText = titulo;
    const fd = new FormData(); fd.append('acao', 'get_full_task'); fd.append('id', id);
    fetch('acoes.php', { method:'POST', body:fd }).then(r => r.json()).then(data => {
        document.getElementById('p_inicio').value = data.data_inicio || '';
        document.getElementById('p_fim').value = data.data_fim || '';
        document.getElementById('p_justificativa').value = data.justificativa || '';
        carregarTimeline(); drawer.show();
    });
}
function salvarDetalhePainel(campo, valor) {
    const fd = new FormData(); fd.append('acao', 'atualizar_campo_tarefa'); fd.append('id', curId); fd.append('campo', campo); fd.append('valor', valor);
    fetch('acoes.php', { method:'POST', body:fd }).then(() => { if(campo.includes('data')) location.reload(); });
}

// 4. Timeline
function carregarTimeline() { fetch('acoes.php?acao=get_updates&id='+curId).then(r => r.text()).then(h => { document.getElementById('timeline-feed').innerHTML = h; }); }
function salvarUpdateTimeline() {
    const ed = document.getElementById('editor-timeline'); const upId = document.getElementById('edit_up_id').value;
    const fd = new FormData(); fd.append('acao', 'salvar_update'); fd.append('id', curId); fd.append('update_id', upId); fd.append('conteudo', ed.innerHTML);
    fetch('acoes.php', { method:'POST', body:fd }).then(() => { ed.innerHTML = ""; resetarEdicaoTimeline(); carregarTimeline(); });
}
function prepararEdicaoUpdate(id) { document.getElementById('editor-timeline').innerHTML = document.getElementById('texto_update_'+id).innerHTML; document.getElementById('edit_up_id').value = id; document.getElementById('btnSaveUp').innerText = "ATUALIZAR"; document.getElementById('btnCancelUp').style.display = 'inline-block'; }
function resetarEdicaoTimeline() { document.getElementById('editor-timeline').innerHTML = ""; document.getElementById('edit_up_id').value = 0; document.getElementById('btnSaveUp').innerText = "SALVAR"; document.getElementById('btnCancelUp').style.display = 'none'; }
function excluirUpdate(id) { if(confirm("Excluir?")) fetch('acoes.php?acao=excluir_update&id_update='+id).then(() => carregarTimeline()); }

// 5. Colaboradores e Status
function adicionarColaborador() { const uid = document.getElementById('sel_colab').value; const fd = new FormData(); fd.append('acao', 'add_membro'); fd.append('quadro_id', <?php echo $id_quadro; ?>); fd.append('usuario_id', uid); fetch('acoes.php', { method:'POST', body:fd }).then(() => location.reload()); }
function removerColaborador(uid) { if(confirm("Remover?")) { window.location.href = `acoes.php?acao=remover_membro&uid=${uid}&qid=<?php echo $id_quadro; ?>`; } }
function adicionarStatus() { const l = document.getElementById('ns_label').value; const c = document.getElementById('ns_color').value; const fd = new FormData(); fd.append('acao', 'add_status'); fd.append('quadro_id', <?php echo $id_quadro; ?>); fd.append('label', l); fd.append('cor', c); fetch('acoes.php', { method:'POST', body:fd }).then(() => location.reload()); }
function excluirStatus(id) { if(confirm("Excluir?")) { const fd = new FormData(); fd.append('acao', 'excluir_status'); fd.append('status_id', id); fetch('acoes.php', { method:'POST', body:fd }).then(() => location.reload()); } }

// Colar Imagem
document.getElementById('editor-timeline').addEventListener('paste', function(e) {
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    for (let i in items) {
        if (items[i].kind === 'file') {
            e.preventDefault();
            const blob = items[i].getAsFile(); const r = new FileReader();
            r.onload = function(ev) { const img = document.createElement('img'); img.src = ev.target.result; document.getElementById('editor-timeline').appendChild(img); };
            r.readAsDataURL(blob);
        }
    }
});
</script>
</body>
</html>