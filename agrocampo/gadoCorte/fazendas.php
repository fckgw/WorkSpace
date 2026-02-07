<?php
/**
 * BDSoft Workspace - GESTÃO DE FAZENDAS
 * Local: agrocampo/gadoCorte/fazendas.php
 */
session_start();
require_once '../../config.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: ../../login.php"); exit; }
$user_id = $_SESSION['usuario_id'];

// Listar Fazendas do Usuário
$stmt = $pdo->prepare("SELECT * FROM agro_gadocorte_propriedades WHERE usuario_id = ? ORDER BY nombre ASC");
$stmt->execute([$user_id]);
$fazendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Fazendas - Gado de Corte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark">Minhas Fazendas</h2>
            <p class="text-muted">Gerencie suas propriedades rurais e contatos.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalNovaFazenda">+ NOVA FAZENDA</button>
    </div>

    <div class="row g-4">
        <?php if(empty($fazendas)): ?>
            <div class="col-12 text-center py-5 opacity-50"><h5>Nenhuma fazenda cadastrada.</h5></div>
        <?php else: ?>
            <?php foreach($fazendas as $f): ?>
            <div class="col-md-6">
                <div class="card p-4 border-0 shadow-sm rounded-4" style="border-left: 8px solid #5d4037; background: #fff;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($f['nombre']); ?></h4>
                            <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($f['localizacao']); ?></p>
                        </div>
                        <span class="badge bg-success px-3 py-2 rounded-pill"><?php echo number_format($f['area_ha'], 2, ',', '.'); ?> ha</span>
                    </div>
                    <div class="bg-light p-3 rounded-3 mb-3">
                        <div class="row small">
                            <div class="col-6">
                                <label class="text-muted fw-bold d-block">PROPRIETÁRIO</label>
                                <span><?php echo htmlspecialchars($f['proprietario_nome'] ?: 'Não informado'); ?></span>
                            </div>
                            <div class="col-6">
                                <label class="text-muted fw-bold d-block">CONTATO</label>
                                <span><?php echo htmlspecialchars($f['proprietario_celular'] ?: '-'); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <a href="acoes.php?acao=excluir_fazenda&id=<?php echo $f['id']; ?>" class="btn btn-sm btn-link text-danger" onclick="return confirm('Excluir fazenda?')"><i class="fas fa-trash-alt"></i> Excluir</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL NOVA FAZENDA -->
<div class="modal fade" id="modalNovaFazenda" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-dark text-white"><h5>Cadastrar Propriedade</h5></div>
            <div class="modal-body p-4">
                <input type="hidden" name="acao" value="nova_propriedade">
                <div class="row mb-3">
                    <div class="col-md-8"><label class="small fw-bold">NOME DA FAZENDA</label><input type="text" name="nombre" class="form-control" required></div>
                    <div class="col-md-4"><label class="small fw-bold">ÁREA TOTAL (HA)</label><input type="number" step="0.01" name="area_ha" class="form-control" required></div>
                </div>
                <div class="mb-3"><label class="small fw-bold">LOCALIZAÇÃO (CIDADE/UF)</label><input type="text" name="localizacao" class="form-control"></div>
                <hr>
                <h6 class="fw-bold mb-3">Dados do Proprietário</h6>
                <div class="mb-3"><label class="small fw-bold">NOME COMPLETO</label><input type="text" name="prop_nome" class="form-control"></div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="small fw-bold">CELULAR</label><input type="text" name="prop_cel" class="form-control" placeholder="(00) 00000-0000"></div>
                    <div class="col-md-6 mb-3"><label class="small fw-bold">E-MAIL</label><input type="email" name="prop_email" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">SALVAR PROPRIEDADE</button></div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>