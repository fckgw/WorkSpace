<?php
/**
 * BDSoft Workspace - GADO DE CORTE (GESTÃO DE LOTES)
 * Local: agrocampo/gadoCorte/lotes.php
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id'])) { 
    header("Location: ../../login.php"); 
    exit; 
}

$user_id_sessao = $_SESSION['usuario_id'];

try {
    // 1. Buscar Propriedades cadastradas para o usuário logado
    $stmt_prop = $pdo->prepare("SELECT id, nombre FROM agro_gadocorte_propriedades WHERE usuario_id = ?");
    $stmt_prop->execute([$user_id_sessao]);
    $propriedades = $stmt_prop->fetchAll(PDO::FETCH_ASSOC);

    // 2. Buscar Sistemas Produtivos (Coluna 'tipo' corrigida no SQL)
    $sistemas = $pdo->query("SELECT id, tipo FROM agro_gadocorte_sistemas")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Listar Lotes vinculados ao usuário
    $sql_lotes = "SELECT l.*, p.nombre as fazenda_nome, s.tipo as sistema_nome 
                  FROM agro_gadocorte_lotes l
                  INNER JOIN agro_gadocorte_propriedades p ON l.propriedade_id = p.id
                  INNER JOIN agro_gadocorte_sistemas s ON l.sistema_id = s.id
                  WHERE p.usuario_id = ?
                  ORDER BY l.status ASC, l.id DESC";
    
    $stmt_lotes = $pdo->prepare($sql_lotes);
    $stmt_lotes->execute([$user_id_sessao]);
    $lista_lotes = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $erro_sql) {
    die("Erro Crítico no Banco de Dados: " . $erro_sql->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lotes - Gado de Corte - BDSoft Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark mb-0">Gestão de Lotes</h2>
            <p class="text-muted">Controle de ciclos produtivos por fazenda.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoLote">
            <i class="fas fa-plus me-2"></i>CRIAR NOVO LOTE
        </button>
    </div>

    <!-- TABELA DE EXIBIÇÃO DE LOTES -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted">
                        <th class="ps-4">NOME DO LOTE</th>
                        <th>FAZENDA</th>
                        <th>SISTEMA</th>
                        <th>FASE ATUAL</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lista_lotes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-layer-group fa-2x mb-2 opacity-25"></i><br>
                                Nenhum lote cadastrado para esta conta.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lista_lotes as $lote): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo htmlspecialchars($lote['nome_lote']); ?></td>
                            <td><?php echo htmlspecialchars($lote['fazenda_nome']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $lote['sistema_nome']; ?></span></td>
                            <td><?php echo $lote['fase_atual']; ?></td>
                            <td class="text-center">
                                <span class="badge <?php echo ($lote['status'] === 'Ativo') ? 'bg-success' : 'bg-secondary'; ?> rounded-pill px-3">
                                    <?php echo $lote['status']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="acoes.php?acao=excluir_lote&id=<?php echo $lote['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger border-0" 
                                   onclick="return confirm('Isso excluirá permanentemente o lote e todo o seu histórico. Confirma?')">
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

<!-- MODAL: CADASTRAR NOVO LOTE -->
<div class="modal fade" id="modalNovoLote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="fw-bold mb-0">Configurar Novo Lote</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="acao" value="novo_lote">
                
                <div class="mb-3">
                    <label class="small fw-bold mb-1 text-muted">NOME DO LOTE</label>
                    <input type="text" name="nome_lote" class="form-control" placeholder="Ex: Lote 01 - Engorda" required>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold mb-1 text-muted">PROPRIEDADE / FAZENDA</label>
                    <select name="propriedade_id" class="form-select" required>
                        <?php if (empty($propriedades)): ?>
                            <option value="">Nenhuma fazenda cadastrada!</option>
                        <?php else: ?>
                            <?php foreach ($propriedades as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold mb-1 text-muted">SISTEMA PRODUTIVO</label>
                        <select name="sistema_id" class="form-select" required>
                            <?php foreach ($sistemas as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['tipo']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold mb-1 text-muted">FASE DO CICLO</label>
                        <select name="fase_atual" class="form-select">
                            <option value="Cria">Cria</option>
                            <option value="Recria">Recria</option>
                            <option value="Engorda">Engorda</option>
                            <option value="Terminação">Terminação</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold shadow">SALVAR LOTE NO SISTEMA</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>