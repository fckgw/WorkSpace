<?php
/**
 * BDSoft Workspace - AGRO CAMPO (ADMINISTRAÇÃO DE PERMISSÕES)
 * Local: agrocampo/admin_permissoes.php
 */
session_start();
require_once '../config.php';

// Proteção: Somente Administradores
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$user_id_logado = $_SESSION['usuario_id'];

// Buscar todos os usuários
$stmt_u = $pdo->prepare("SELECT id, nome, usuario, nivel FROM usuarios ORDER BY nivel ASC, nome ASC");
$stmt_u->execute();
$usuarios = $stmt_u->fetchAll(PDO::FETCH_ASSOC);

// Buscar submódulos do Agro
$stmt_s = $pdo->query("SELECT * FROM agro_submodulos ORDER BY id ASC");
$submodulos = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

$user_id_sel = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$permissoes_atuais = [];

if ($user_id_sel > 0) {
    $stmt_p = $pdo->prepare("SELECT submodulo_id FROM usuarios_agro_permissões WHERE usuario_id = ?");
    $stmt_p->execute([$user_id_sel]);
    $permissoes_atuais = $stmt_p->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt_nome = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt_nome->execute([$user_id_sel]);
    $nome_selecionado = $stmt_nome->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Acessos - BDSoft Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f4; font-family: 'Segoe UI', sans-serif; padding-top: 50px; }
        .card-custom { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; }
        .list-group-item { border: 1px solid #eee; margin-bottom: 5px; border-radius: 10px !important; }
        .alert-floating { border-radius: 15px; font-weight: bold; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container">
    <!-- MENSAGEM DE SUCESSO -->
    <?php if(isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show alert-floating mb-4 text-center" role="alert">
            <i class="fas fa-check-circle me-2"></i> As alterações foram salvas com sucesso no banco de dados!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-0">Controle de Acessos Agro</h2>
            <p class="text-muted">Gerencie permissões por usuário e plano contratado.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoUsuario">
                <i class="fas fa-user-plus me-2"></i>NOVO USUÁRIO
            </button>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold border-2">VOLTAR AO PAINEL</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="card card-custom overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th class="ps-4">NOME</th>
                            <th>NÍVEL</th>
                            <th class="text-center">AÇÃO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usuarios as $u): ?>
                        <tr class="<?php echo ($user_id_sel == $u['id']) ? 'table-primary' : ''; ?>">
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($u['nome']); ?></div>
                                <small class="text-muted"><?php echo $u['usuario']; ?></small>
                            </td>
                            <td>
                                <span class="badge <?php echo $u['nivel'] == 'admin' ? 'bg-danger' : 'bg-secondary'; ?> rounded-pill">
                                    <?php echo strtoupper($u['nivel']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if($u['nivel'] !== 'admin'): ?>
                                    <a href="?uid=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">CONFIGURAR</a>
                                <?php else: ?>
                                    <span class="text-success fw-bold small"><i class="fas fa-unlock me-1"></i> FULL ACCESS</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md-5">
            <?php if($user_id_sel > 0): ?>
            <div class="card card-custom p-4 shadow-lg">
                <h5 class="fw-bold mb-4">Módulos Liberados: <br><span class="text-primary"><?php echo $nome_selecionado; ?></span></h5>
                
                <form action="acoes.php" method="POST">
                    <input type="hidden" name="acao" value="salvar_permissoes_agro">
                    <input type="hidden" name="usuario_id" value="<?php echo $user_id_sel; ?>">
                    
                    <div class="list-group list-group-flush mb-4">
                        <?php foreach($submodulos as $sub): ?>
                        <label class="list-group-item d-flex align-items-center py-3">
                            <input class="form-check-input me-3" type="checkbox" name="submodulos[]" value="<?php echo $sub['id']; ?>" 
                                   <?php echo in_array($sub['id'], $permissoes_atuais) ? 'checked' : ''; ?> 
                                   style="width:22px; height:22px;">
                            <div>
                                <div class="fw-bold text-dark"><?php echo $sub['nome']; ?></div>
                                <small class="text-muted"><?php echo $sub['slug']; ?></small>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-success w-100 rounded-pill py-3 fw-bold shadow">GRAVAR ACESSOS</button>
                    <a href="admin_permissoes.php" class="btn btn-link w-100 text-muted mt-2 text-decoration-none">Cancelar</a>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL NOVO USUÁRIO AGRO -->
<div class="modal fade" id="modalNovoUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 bg-light p-4">
                <h5 class="fw-bold mb-0">Novo Usuário Agro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="acao" value="novo_usuario_agro">
                <div class="mb-3"><label class="small fw-bold">NOME COMPLETO</label><input type="text" name="nome" class="form-control" required></div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="small fw-bold">CPF</label><input type="text" name="cpf" id="cpf_mask" class="form-control" required></div>
                    <div class="col-6 mb-3"><label class="small fw-bold">RG</label><input type="text" name="rg" id="rg_mask" class="form-control"></div>
                </div>
                <div class="mb-3"><label class="small fw-bold">E-MAIL (LOGIN)</label><input type="email" name="usuario" class="form-control" required></div>
                
                <div class="p-3 border rounded bg-light">
                    <label class="small fw-bold text-primary mb-2">LIBERAR SUBMÓDULOS IMEDIATOS:</label>
                    <?php foreach($submodulos as $sub): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="submodulos[]" value="<?php echo $sub['id']; ?>" checked>
                            <label class="form-check-label small"><?php echo $sub['nome']; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">CADASTRAR E ENVIAR E-MAIL</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    $(document).ready(function(){
        $('#cpf_mask').mask('000.000.000-00');
        $('#rg_mask').mask('00.000.000-0');
    });
</script>
</body>
</html>