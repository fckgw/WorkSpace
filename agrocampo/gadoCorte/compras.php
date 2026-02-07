
<?php
/**
 * BDSoft Workspace - GADO DE CORTE (COMPRAS)
 * Local: agrocampo/gadoCorte/compras.php
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../../login.php"); exit; }
$user_id = $_SESSION['usuario_id'];

// 1. Buscar Lotes Ativos para o Dropdown
$stmt_lotes = $pdo->prepare("SELECT l.id, l.nome_lote FROM agro_gadocorte_lotes l INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id WHERE p.usuario_id = ? AND l.status = 'Ativo'");
$stmt_lotes->execute([$user_id]);
$meus_lotes = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);

// 2. Listar Histórico de Compras
$stmt_compras = $pdo->prepare("
    SELECT c.*, l.nome_lote 
    FROM agro_gadocorte_compras c
    INNER JOIN agro_gadocorte_lotes l ON c.lote_id = l.id
    INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id
    WHERE p.usuario_id = ?
    ORDER BY c.data_compra DESC
");
$stmt_compras->execute([$user_id]);
$compras = $stmt_compras->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Compras e Entradas - Gado de Corte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold">Compras e Entradas</h2>
            <p class="text-muted">Registro de novos animais e formação de lotes.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalCompra">+ REGISTRAR COMPRA</button>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-4">DATA</th>
                    <th>LOTE DESTINO</th>
                    <th>TIPO</th>
                    <th>QUANTIDADE</th>
                    <th>PESO MÉDIO</th>
                    <th>VALOR TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($compras as $c): ?>
                <tr>
                    <td class="ps-4 small fw-bold"><?php echo date('d/m/Y', strtotime($c['data_compra'])); ?></td>
                    <td><span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($c['nome_lote']); ?></span></td>
                    <td><?php echo $c['tipo_animal']; ?></td>
                    <td class="fw-bold"><?php echo $c['quantidade']; ?> cab.</td>
                    <td><?php echo number_format($c['peso_medio_inicial'], 2); ?> kg</td>
                    <td class="text-success fw-bold">R$ <?php echo number_format($c['quantidade'] * $c['preco_unidade'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL REGISTRAR COMPRA -->
<div class="modal fade" id="modalCompra" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-primary text-white"><h5>Registrar Compra de Animais</h5></div>
            <div class="modal-body p-4">
                <input type="hidden" name="acao" value="registrar_compra">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold">LOTE DE DESTINO</label>
                        <select name="lote_id" class="form-select" required>
                            <?php foreach($meus_lotes as $ml) echo "<option value='{$ml['id']}'>{$ml['nome_lote']}</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold">DATA DA COMPRA</label>
                        <input type="date" name="data_compra" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="small fw-bold">TIPO</label><select name="tipo_animal" class="form-select"><option>Bezerro</option><option>Garrote</option><option>Boi Magro</option></select></div>
                    <div class="col-md-4 mb-3"><label class="small fw-bold">QTD CABEÇAS</label><input type="number" name="quantidade" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="small fw-bold">PESO MÉDIO (KG)</label><input type="number" step="0.1" name="peso" class="form-control" required></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="small fw-bold">PREÇO P/ CABEÇA (R$)</label><input type="number" step="0.01" name="preco" class="form-control" required></div>
                    <div class="col-md-3 mb-3"><label class="small fw-bold">UF ORIGEM</label><input type="text" name="uf" class="form-control" maxlength="2" placeholder="SP"></div>
                    <div class="col-md-3 mb-3"><label class="small fw-bold">RAÇA</label><input type="text" name="raca" class="form-control" placeholder="Nelore"></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">EFETIVAR COMPRA</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>