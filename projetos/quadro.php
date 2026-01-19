<?php
/**
 * BDSoft Workspace - PROJETOS / QUADRO
 * Local: projetos/quadro.php
 * Versão: Estabilizada com Botão de Relatórios
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

// 2. Validar se o Quadro existe no banco de dados
$stmtQ = $pdo->prepare("SELECT * FROM quadros_projetos WHERE id = ?");
$stmtQ->execute([$id_quadro]);
$quadro = $stmtQ->fetch();

if (!$quadro) {
    die("<div style='padding:50px; text-align:center; font-family:sans-serif;'><h2>❌ Quadro não encontrado</h2><a href='index.php'>Voltar para Projetos</a></div>");
}

// 3. Carregar Status cadastrados para este Quadro (ComboBox)
$stmtS = $pdo->prepare("SELECT * FROM quadros_status WHERE quadro_id = ? ORDER BY id ASC");
$stmtS->execute([$id_quadro]);
$meus_status = $stmtS->fetchAll(PDO::FETCH_ASSOC);

// 4. Carregar Grupos de Tarefas (Sprints) vinculados ao Quadro
$stmtG = $pdo->prepare("SELECT * FROM projetos_grupos WHERE quadro_id = ? ORDER BY id ASC");
$stmtG->execute([$id_quadro]);
$grupos = $stmtG->fetchAll(PDO::FETCH_ASSOC);

/**
 * Função Auxiliar para identificar a cor do Status na renderização inicial
 */
function verCorStatus($lista, $id) {
    foreach($lista as $s) { 
        if($s['id'] == $id) return $s['cor']; 
    }
    return "#c4c4c4"; // Cor padrão caso o status seja nulo
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quadro['nome']); ?> - BDSoft Workspace</title>
    
    <!-- CSS: Bootstrap 5 e FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f5f6f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; }
        
        /* Barra de Navegação Interna */
        .nav-board { background: #ffffff; border-bottom: 1px solid #dee2e6; padding: 12px 30px; position: sticky; top: 0; z-index: 1000; }
        
        /* Cabeçalho dos Grupos */
        .group-header { display: flex; align-items: center; justify-content: space-between; margin-top: 35px; padding-bottom: 8px; }
        .group-title-input { border: 1px solid transparent; background: transparent; font-weight: 700; font-size: 1.15rem; outline: none; padding: 2px 8px; border-radius: 4px; transition: 0.2s; }
        .group-title-input:focus { border-color: #ddd; background: #fff; color: #333 !important; }
        
        /* Estilo da Tabela Estilo Monday */
        .table-monday { width: 100%; background: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px; border-collapse: collapse; table-layout: fixed; }
        .table-monday th { font-size: 11px; color: #676879; padding: 12px; border-bottom: 1px solid #eee; font-weight: 600; text-transform: uppercase; }
        .task-row { border-bottom: 1px solid #eee; height: 45px; }
        .task-row:hover { background-color: #f8fafc; }

        /* ComboBox de Status Customizado */
        .status-select { border: none; color: white; font-weight: bold; border-radius: 4px; padding: 8px; width: 100%; cursor: pointer; text-align-last: center; outline: none; appearance: none; }
        
        /* Inputs de Data (Calendário) */
        .date-input { border: 1px solid transparent; background: #f8f9fa; border-radius: 4px; font-size: 12px; padding: 6px; width: 100%; text-align: center; cursor: pointer; color: #676879; }
        .date-input:hover { background: #eef0f1; border-color: #ddd; }
        .date-input:focus { background: #fff; border-color: #1a73e8; outline: none; }

        /* Editor de Evidências (Pop-up OBS) */
        #editor-evidencias { min-height: 450px; border: 1px solid #ddd; padding: 25px; background: #ffffff; border-radius: 12px; overflow-y: auto; outline: none; font-size: 1.05rem; }
        #editor-evidencias img { max-width: 100%; border-radius: 10px; margin: 15px 0; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        /* Overlay de Carregamento */
        .loader-overlay { display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.85); z-index: 2000; align-items: center; justify-content: center; flex-direction: column; border-radius: 15px; }
        
        .cursor-pointer { cursor: pointer; }
        .no-select { appearance: none; -webkit-appearance: none; }
    </style>
</head>
<body>

<!-- NAVBAR DO PROJETO -->
<nav class="nav-board d-flex justify-content-between align-items-center shadow-sm">
    <div class="d-flex align-items-center">
        <a href="index.php" class="btn btn-sm btn-light border rounded-circle me-3" title="Voltar ao Lobby"><i class="fas fa-arrow-left text-muted"></i></a>
        <h4 class="fw-bold mb-0 text-primary"><?php echo htmlspecialchars($quadro['nome']); ?></h4>
    </div>
    
    <div class="d-flex gap-2">
        <!-- BOTÃO DE RELATÓRIOS (NOVO) -->
        <a href="relatorios.php?id=<?php echo $id_quadro; ?>" class="btn btn-sm btn-info text-white rounded-pill px-4 fw-bold shadow-sm">
            <i class="fas fa-chart-line me-1"></i> RELATÓRIOS
        </a>
        
        <!-- BOTÃO NOVO GRUPO -->
        <button class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoGrupo">
            <i class="fas fa-plus me-1"></i> NOVO GRUPO
        </button>
        
        <!-- BOTÃO VOLTAR AO PORTAL -->
        <a href="../portal.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">PORTAL</a>
    </div>
</nav>

<div class="container-fluid p-4">
    
    <?php if (empty($grupos)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm border">
            <i class="fas fa-layer-group fa-4x text-light mb-3"></i>
            <h4 class="text-muted">Nenhum grupo encontrado.</h4>
            <button class="btn btn-primary mt-3 rounded-pill px-5" data-bs-toggle="modal" data-bs-target="#modalNovoGrupo">Criar Primeiro Grupo</button>
        </div>
    <?php endif; ?>

    <?php foreach($grupos as $grupo): ?>
    <div class="mb-5">
        
        <!-- CABEÇALHO DO GRUPO (EDITÁVEL) -->
        <div class="group-header">
            <div class="d-flex align-items-center flex-grow-1">
                <i class="fas fa-caret-down me-2" style="color: <?php echo $grupo['cor']; ?>;"></i>
                <input type="text" class="group-title-input" style="color: <?php echo $grupo['cor']; ?>;" 
                       value="<?php echo htmlspecialchars($grupo['nome']); ?>" 
                       onblur="ajaxUpdateGrupo(<?php echo $grupo['id']; ?>, 'nome', this.value)">
                
                <input type="color" class="form-control form-control-color border-0 p-0 ms-2" 
                       style="width:22px; height:22px; background:none; cursor:pointer;" 
                       value="<?php echo $grupo['cor']; ?>" 
                       onchange="ajaxUpdateGrupo(<?php echo $grupo['id']; ?>, 'cor', this.value)">
            </div>
            <a href="acoes.php?del_grupo=<?php echo $grupo['id']; ?>&quadro_id=<?php echo $id_quadro; ?>" 
               class="text-danger opacity-25" onclick="return confirm('Atenção: Isso excluirá o grupo e todas as tarefas vinculadas. Continuar?')">
                <i class="fas fa-trash-alt"></i>
            </a>
        </div>

        <table class="table-monday">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>ITEM / TAREFA</th>
                    <th style="width: 180px;" class="text-center">STATUS</th>
                    <th style="width: 150px;" class="text-center">INICIAL</th>
                    <th style="width: 150px;" class="text-center">FINAL</th>
                    <th style="width: 70px;" class="text-center">OBS</th>
                    <th style="width: 40px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmtT = $pdo->prepare("SELECT * FROM tarefas_projetos WHERE grupo_id = ? ORDER BY id ASC");
                $stmtT->execute([$grupo['id']]);
                while($tarefa = $stmtT->fetch(PDO::FETCH_ASSOC)):
                    $bg_status = verCorStatus($meus_status, $tarefa['status_id']);
                ?>
                <tr class="task-row">
                    <td class="text-center"><input type="checkbox" class="form-check-input"></td>
                    <td class="p-0">
                        <input type="text" class="form-control form-control-sm border-0 bg-transparent fw-medium px-3" 
                               style="height: 44px;" value="<?php echo htmlspecialchars($tarefa['titulo']); ?>" 
                               onblur="ajaxUpdateTarefa(<?php echo $tarefa['id']; ?>, 'titulo', this.value)">
                    </td>
                    <td class="p-2">
                        <select class="status-select" style="background-color:<?php echo $bg_status; ?>" 
                                onchange="ajaxUpdateTarefa(<?php echo $tarefa['id']; ?>, 'status_id', this.value); location.reload();">
                            <?php foreach($meus_status as $st): ?>
                                <option value="<?php echo $st['id']; ?>" <?php echo ($tarefa['status_id'] == $st['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($st['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="px-2">
                        <input type="date" class="date-input" value="<?php echo $tarefa['data_inicio']; ?>" 
                               onchange="ajaxUpdateTarefa(<?php echo $tarefa['id']; ?>, 'data_inicio', this.value)">
                    </td>
                    <td class="px-2">
                        <input type="date" class="date-input" value="<?php echo $tarefa['data_fim']; ?>" 
                               onchange="ajaxUpdateTarefa(<?php echo $tarefa['id']; ?>, 'data_fim', this.value)">
                    </td>
                    <td class="text-center">
                        <i class="fas fa-file-signature text-primary fa-lg cursor-pointer" 
                           onclick="abrirPopUpAnotacoes(<?php echo $tarefa['id']; ?>, '<?php echo addslashes($tarefa['titulo']); ?>')"></i>
                    </td>
                    <td class="text-center">
                        <a href="acoes.php?excluir_tarefa=<?php echo $tarefa['id']; ?>&id_quadro=<?php echo $id_quadro; ?>" 
                           class="text-danger opacity-25" onclick="return confirm('Excluir esta tarefa?')">
                            <i class="fas fa-times"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <!-- Linha de Adição Rápida -->
                <tr>
                    <td></td>
                    <td colspan="6" class="p-2">
                        <input type="text" class="form-control form-control-sm border-0 text-primary fw-bold px-3" 
                               placeholder="+ Adicionar Tarefa e pressione Enter..." 
                               onkeypress="if(event.key==='Enter') addTarefaRapida(this.value, <?php echo $grupo['id']; ?>)">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>

<!-- MODAL EDITOR (POP-UP OBS) -->
<div class="modal fade" id="modalEditor" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg position-relative">
            <div class="loader-overlay" id="editorLoader">
                <div class="spinner-border text-primary mb-2"></div>
                <div class="fw-bold">Salvando no Banco de Dados...</div>
            </div>
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title" id="modalTituloTarefa">Anotações Detalhadas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="editor-evidencias" contenteditable="true" placeholder="Escreva seu relatório ou cole capturas de tela (Ctrl+V)..."></div>
            </div>
            <div class="modal-footer bg-white border-0">
                <button class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">FECHAR</button>
                <button class="btn btn-primary rounded-pill px-5 fw-bold shadow" id="btnSalvarNotas" onclick="salvarEvidenciasNoBanco()">SALVAR AGORA</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NOVO GRUPO -->
<div class="modal fade" id="modalNovoGrupo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-light"><h5 class="fw-bold">Novo Grupo de Trabalho</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <input type="hidden" name="acao" value="novo_grupo">
                <input type="hidden" name="quadro_id" value="<?php echo $id_quadro; ?>">
                <div class="mb-3">
                    <label class="small fw-bold mb-1">NOME DO GRUPO (EX: SPRINT 2)</label>
                    <input type="text" name="nome_grupo" class="form-control" required autofocus>
                </div>
                <div>
                    <label class="small fw-bold mb-1">COR DO TEMA</label>
                    <input type="color" name="cor" class="form-control form-control-color w-100" value="#1a73e8">
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">CRIAR GRUPO</button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let idTarefaGlobal = 0;
const instanciaModalEditor = new bootstrap.Modal(document.getElementById('modalEditor'));

// 1. Atualizar Grupo via AJAX
function ajaxUpdateGrupo(id, campo, valor) {
    const fd = new FormData();
    fd.append('acao', 'atualizar_campo_grupo');
    fd.append('id', id);
    fd.append('campo', campo);
    fd.append('valor', valor);
    fetch('acoes.php', { method: 'POST', body: fd }).then(() => {
        if (campo === 'cor' || campo === 'nome') location.reload();
    });
}

// 2. Atualizar Tarefa via AJAX
function ajaxUpdateTarefa(id, campo, valor) {
    const fd = new FormData();
    fd.append('acao', 'atualizar_campo_tarefa');
    fd.append('id', id);
    fd.append('campo', campo);
    fd.append('valor', valor);
    fetch('acoes.php', { method: 'POST', body: fd });
}

// 3. Adicionar Nova Tarefa via Enter
function addTarefaRapida(titulo, grupo_id) {
    if (!titulo.trim()) return;
    const fd = new FormData();
    fd.append('acao', 'nova_tarefa');
    fd.append('titulo', titulo);
    fd.append('grupo_id', grupo_id);
    fd.append('quadro_id', <?php echo $id_quadro; ?>);
    fetch('acoes.php', { method: 'POST', body: fd }).then(r => { 
        if(r.ok) location.reload(); 
    });
}

// 4. Lógica do Pop-up de Anotações (OBS)
function abrirPopUpAnotacoes(id, titulo) {
    idTarefaGlobal = id;
    document.getElementById('modalTituloTarefa').innerText = "Tarefa: " + titulo;
    document.getElementById('editor-evidencias').innerHTML = "<div class='text-center p-5'><div class='spinner-border text-primary'></div></div>";
    
    instanciaModalEditor.show();
    
    fetch('acoes.php?acao=get_evidencia&id=' + id)
    .then(r => r.text())
    .then(html => {
        document.getElementById('editor-evidencias').innerHTML = html;
    });
}

// 5. Salvar Conteúdo do Editor (Texto + Imagens)
function salvarEvidenciasNoBanco() {
    const loader = document.getElementById('editorLoader');
    const btn = document.getElementById('btnSalvarNotas');
    loader.style.display = 'flex';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('acao', 'salvar_evidencia');
    fd.append('id', idTarefaGlobal);
    fd.append('conteudo', document.getElementById('editor-evidencias').innerHTML);

    fetch('acoes.php', { method: 'POST', body: fd })
    .then(() => {
        loader.style.display = 'none';
        btn.disabled = false;
        alert("Sincronizado com sucesso!");
    });
}

// 6. Lógica de Colar Imagem da Área de Transferência (Print)
document.getElementById('editor-evidencias').addEventListener('paste', function(e) {
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    for (let i in items) {
        if (items[i].kind === 'file') {
            e.preventDefault(); // Impede a duplicação nativa
            const blob = items[i].getAsFile();
            const reader = new FileReader();
            reader.onload = function(evento) {
                const img = document.createElement('img');
                img.src = evento.target.result;
                document.getElementById('editor-evidencias').appendChild(img);
            };
            reader.readAsDataURL(blob);
        }
    }
});
</script>

</body>
</html>