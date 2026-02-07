<?php
/**
 * BDSoft Workspace - GADO DE CORTE (CUSTOS DE PRODUÇÃO)
 * Local: agrocampo/gadoCorte/custos.php
 */

// 1. Configurações de Depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../config.php';

// 2. Verificação de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id_sessao = $_SESSION['usuario_id'];

// 3. Buscar Lotes Ativos para o formulário de lançamento
$stmt_lotes = $pdo->prepare("
    SELECT l.id, l.nome_lote 
    FROM agro_gadocorte_lotes l
    INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id
    WHERE p.usuario_id = ? AND l.status = 'Ativo'
");
$stmt_lotes->execute([$user_id_sessao]);
$meus_lotes = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);

// 4. Listar Histórico de Custos
$stmt_custos = $pdo->prepare("
    SELECT c.*, l.nome_lote 
    FROM agro_gadocorte_custos c
    INNER JOIN agro_gadocorte_lotes l ON c.lote_id = l.id
    INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id
    WHERE p.usuario_id = ?
    ORDER BY c.data_registro DESC
");
$stmt_custos->execute([$user_id_sessao]);
$lista_custos = $stmt_custos->fetchAll(PDO::FETCH_ASSOC);

// Função para formatar moeda
function formatarReal($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custos de Produção - Gado de Corte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark mb-0">Custos de Produção</h2>
            <p class="text-muted">Gestão de despesas fixas e variáveis por lote.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalNovoCusto">
            <i class="fas fa-plus me-2"></i>LANÇAR CUSTO
        </button>
    </div>

    <!-- TABELA DE CUSTOS -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted">
                        <th class="ps-4">DATA</th>
                        <th>LOTE</th>
                        <th>CATEGORIA</th>
                        <th>TIPO</th>
                        <th>VALOR</th>
                        <th class="text-center">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($lista_custos)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Nenhum custo registrado.</td></tr>
                    <?php else: ?>
                        <?php foreach($lista_custos as $custo): ?>
                        <tr>
                            <td class="ps-4 small"><?php echo date('d/m/Y', strtotime($custo['data_registro'])); ?></td>
                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($custo['nome_lote']); ?></td>
                            <td><?php echo $custo['categoria']; ?></td>
                            <td>
                                <span class="badge <?php echo $custo['tipo_costo'] == 'Fixo' ? 'bg-secondary' : 'bg-info'; ?> rounded-pill">
                                    <?php echo $custo['tipo_costo']; ?>
                                </span>
                            </td>
                            <td class="fw-bold text-danger"><?php echo formatarReal($custo['valor']); ?></td>
                            <td class="text-center">
                                <a href="acoes.php?acao=excluir_custo&id=<?php echo $custo['id']; ?>" class="text-danger opacity-50" onclick="return confirm('Excluir este lançamento?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL NOVO CUSTO -->
<div class="modal fade" id="modalNovoCusto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="fw-bold">Novo Lançamento de Custo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="acao" value="registrar_custo">
                
                <div class="mb-3">
                    <label class="small fw-bold">LOTE DESTINO</label>
                    <select name="lote_id" class="form-select" required>
                        <option value="">Selecione o Lote...</option>
                        <?php foreach($meus_lotes as $l) echo "<option value='{$l['id']}'>{$l['nome_lote']}</option>"; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold">TIPO DE CUSTO</label>
                        <select name="tipo_costo" class="form-select">
                            <option value="Variável">Variável (Produção)</option>
                            <option value="Fixo">Fixo (Estrutura)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold">VALOR (R$)</label>
                        <input type="number" step="0.01" name="valor" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold">CATEGORIA</label>
                    <select name="categoria" class="form-select" required>
                        <option value="Alimentação">Alimentação</option>
                        <option value="Suplementação">Suplementação</option>
                        <option value="Sanidade">Sanidade (Medicamentos)</option>
                        <option value="Mão de Obra">Mão de Obra</option>
                        <option value="Transporte">Transporte</option>
                        <option value="Manejo">Manejo</option>
                        <option value="Energia">Energia</option>
                        <option value="Máquinas">Máquinas / Combustível</option>
                        <option value="Arrendamento">Arrendamento</option>
                        <option value="Depreciação">Depreciação</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold">DATA DO REGISTRO</label>
                    <input type="date" name="data_registro" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">SALVAR DESPESA</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>