<?php
/**
 * BDSoft Workspace - MANEJO INDIVIDUAL (GADO A GADO)
 * Local: agrocampo/gadoCorte/manejo_individual.php
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: ../../login.php"); exit; }
$user_id = $_SESSION['usuario_id'];

try {
    // Buscar Lotes ativos para o cadastro
    $stmt_lotes = $pdo->prepare("SELECT l.id, l.nome_lote FROM agro_gadocorte_lotes l 
                                 INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id 
                                 WHERE p.usuario_id = ? AND l.status = 'Ativo'");
    $stmt_lotes->execute([$user_id]);
    $meus_lotes = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);

    // Listar Animais (Gado a Gado)
    $sql_animais = "SELECT a.*, l.nome_lote 
                    FROM agro_gadocorte_animais a 
                    INNER JOIN agro_gadocorte_lotes l ON a.lote_id = l.id 
                    INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id 
                    WHERE p.usuario_id = ? ORDER BY a.brinco ASC";
    $stmt_a = $pdo->prepare($sql_animais);
    $stmt_a->execute([$user_id]);
    $animais = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gado a Gado - BDSoft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark">Rastreabilidade Animal</h2>
            <p class="text-muted">Gestão individual por brinco e lote.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalNovoAnimal">+ CADASTRAR BRINCO</button>
    </div>

    <div class="row g-3">
        <?php if(empty($animais)): ?>
            <div class="col-12 text-center py-5 opacity-25"><h5>Nenhum animal cadastrado individualmente.</h5></div>
        <?php else: ?>
            <?php foreach($animais as $a): ?>
            <div class="col-md-3">
                <div class="card p-3 border-0 shadow-sm rounded-4 text-center" style="background: #fff;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-dark">#<?php echo htmlspecialchars($a['brinco']); ?></span>
                        <span class="badge <?php echo $a['status']=='Tratamento'?'bg-danger':'bg-success'; ?>"><?php echo $a['status']; ?></span>
                    </div>
                    <i class="fas fa-cow fa-3x text-muted my-3 opacity-50"></i>
                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($a['raca']); ?></h6>
                    <small class="text-muted d-block mb-3">Lote: <?php echo htmlspecialchars($a['nome_lote']); ?></small>
                    <div class="bg-light p-2 rounded fw-bold text-primary"><?php echo number_format($a['peso_atual'], 1, ',', '.'); ?> kg</div>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-sm btn-outline-danger flex-fill"><i class="fas fa-medkit"></i> Saúde</button>
                        <button class="btn btn-sm btn-outline-dark flex-fill"><i class="fas fa-weight"></i> Pesar</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL NOVO ANIMAL -->
<div class="modal fade" id="modalNovoAnimal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-dark text-white"><h5>Novo Cadastro Animal</h5></div>
            <div class="modal-body p-4">
                <input type="hidden" name="acao" value="cadastrar_animal_individual">
                <div class="mb-3">
                    <label class="small fw-bold">LOTE DE DESTINO</label>
                    <select name="lote_id" class="form-select" required>
                        <?php foreach($meus_lotes as $ml) echo "<option value='{$ml['id']}'>{$ml['nome_lote']}</option>"; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">NÚMERO DO BRINCO / TAG</label>
                    <input type="text" name="brinco" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="small fw-bold">RAÇA</label><input type="text" name="raca" class="form-control" placeholder="Ex: Nelore"></div>
                    <div class="col-6 mb-3"><label class="small fw-bold">PESO INICIAL (KG)</label><input type="number" step="0.1" name="peso" class="form-control" required></div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">SEXO</label>
                    <select name="sexo" class="form-select"><option value="Macho">Macho</option><option value="Fêmea">Fêmea</option></select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow">CADASTRAR ANIMAL</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>