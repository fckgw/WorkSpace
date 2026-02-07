<?php
/**
 * BDSoft Workspace - AGRO CAMPO (GESTÃO DE TECNOLOGIAS)
 * Local: agrocampo/admin_config.php
 */
session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$feedback = "";

// AÇÃO: CADASTRAR OU EDITAR SUBMÓDULO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_salvar'])) {
    $nome = trim($_POST['nome']);
    $slug = trim($_POST['slug']);
    $icone = trim($_POST['icone']);
    $desc = trim($_POST['descricao']);
    $id = (int)$_POST['id'];

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE agro_submodulos SET nome = ?, slug = ?, icone = ?, descricao = ? WHERE id = ?");
        $stmt->execute([$nome, $slug, $icone, $desc, $id]);
        $feedback = "Tecnologia atualizada!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO agro_submodulos (nome, slug, icone, descricao) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $slug, $icone, $desc]);
        $feedback = "Nova tecnologia adicionada ao AgroCampo!";
    }
}

// AÇÃO: EXCLUIR SUBMÓDULO
if (isset($_GET['excluir'])) {
    $id_del = (int)$_GET['excluir'];
    $pdo->prepare("DELETE FROM agro_submodulos WHERE id = ?")->execute([$id_del]);
    header("Location: admin_config.php");
    exit;
}

$submodulos = $pdo->query("SELECT * FROM agro_submodulos ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configurações Agro - BDSoft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .card-admin { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Gestão de Tecnologias Agro</h2>
            <p class="text-muted">Crie e gerencie os submódulos do AgroCampo.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="admin_permissoes.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">GESTÃO DE ACESSOS</a>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">VOLTAR</a>
        </div>
    </div>

    <?php if($feedback) echo "<div class='alert alert-success'>$feedback</div>"; ?>

    <div class="row">
        <!-- FORMULÁRIO -->
        <div class="col-md-4">
            <div class="card card-admin p-4 mb-4">
                <h5 class="fw-bold mb-3">Novo / Editar</h5>
                <form method="POST">
                    <input type="hidden" name="id" id="form_id" value="0">
                    <div class="mb-3">
                        <label class="small fw-bold">NOME DA TECNOLOGIA</label>
                        <input type="text" name="nome" id="form_nome" class="form-control" placeholder="Ex: Gado de Corte" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">CAMINHO (SLUG)</label>
                        <input type="text" name="slug" id="form_slug" class="form-control" placeholder="Ex: gadoCorte/index.php" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">ÍCONE (FONT AWESOME)</label>
                        <input type="text" name="icone" id="form_icone" class="form-control" placeholder="Ex: fa-cow" value="fa-cube">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">DESCRIÇÃO CURTA</label>
                        <textarea name="descricao" id="form_desc" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" name="btn_salvar" class="btn btn-success w-100 rounded-pill fw-bold">SALVAR TECNOLOGIA</button>
                    <button type="button" onclick="window.location.reload()" class="btn btn-link btn-sm w-100 mt-2 text-muted">Limpar</button>
                </form>
            </div>
        </div>

        <!-- LISTAGEM -->
        <div class="col-md-8">
            <div class="card card-admin p-0 overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th class="ps-4">ÍCONE / NOME</th>
                            <th>CAMINHO</th>
                            <th class="text-center">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($submodulos as $s): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light p-2 rounded text-success me-3"><i class="fas <?php echo $s['icone']; ?> fa-lg"></i></div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($s['nome']); ?></div>
                                </div>
                            </td>
                            <td><code><?php echo $s['slug']; ?></code></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" onclick="popularForm(<?php echo htmlspecialchars(json_encode($s)); ?>)"><i class="fas fa-edit"></i></button>
                                <a href="?excluir=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Excluir tecnologia?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function popularForm(dados) {
    document.getElementById('form_id').value = dados.id;
    document.getElementById('form_nome').value = dados.nome;
    document.getElementById('form_slug').value = dados.slug;
    document.getElementById('form_icone').value = dados.icone;
    document.getElementById('form_desc').value = dados.descricao;
    document.querySelector('button[name="btn_salvar"]').innerText = "ATUALIZAR TECNOLOGIA";
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>