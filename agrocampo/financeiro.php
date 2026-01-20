<?php
/**
 * BDSoft Workspace - AGRO CAMPO (CONTAS PAGAR/RECEBER)
 * Local: agrocampo/financeiro.php
 */
session_start();
require_once '../config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');

// --- ESTATÍSTICAS CORRIGIDAS ---
$stmt_stats = $pdo->prepare("SELECT 
    SUM(CASE WHEN status = 'Pendente' AND data_vencimento < ? THEN valor ELSE 0 END) as total_vencido,
    SUM(CASE WHEN status = 'Pendente' AND data_vencimento = ? THEN valor ELSE 0 END) as total_vence_hoje,
    SUM(CASE WHEN status = 'Pago' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) AND YEAR(data_pagamento) = YEAR(CURRENT_DATE()) THEN valor ELSE 0 END) as total_pago_mes
    FROM agro_financeiro WHERE usuario_id = ?");
$stmt_stats->execute([$hoje, $hoje, $user_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Listagem Geral (Limitado aos últimos 50 para performance)
$stmt_lista = $pdo->prepare("SELECT * FROM agro_financeiro WHERE usuario_id = ? ORDER BY status ASC, data_vencimento ASC LIMIT 50");
$stmt_lista->execute([$user_id]);
$contas = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);

function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro - AgroCampo BDSoft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --agro-primary: #2d5a27; --agro-sidebar: #1e3d1a; --agro-light: #f4f7f4; }
        body { background-color: var(--agro-light); display: flex; min-height: 100vh; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .sidebar { width: 260px; background: var(--agro-sidebar); color: white; position: fixed; height: 100vh; display: flex; flex-direction: column; z-index: 1000; }
        .sidebar .nav-link { color: rgba(255,255,255,0.7); padding: 12px 25px; font-weight: 500; border: none; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link.active { background: var(--agro-primary); color: white; border-left: 5px solid #8bc34a; }
        .main-content { flex: 1; margin-left: 260px; padding: 40px; min-width: 0; }
        .card-agro { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: #fff; }
        .status-btn { cursor: pointer; transition: 0.2s; border: none; font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar shadow">
    <div class="p-4 text-center">
        <h4 class="fw-bold mb-0 text-success"><i class="fas fa-seedling me-2"></i>AgroCampo</h4>
    </div>
    <nav class="nav flex-column mt-3">
        <a class="nav-link" href="index.php"><i class="fas fa-chart-line me-3"></i> Painel Geral</a>
        <a class="nav-link active" href="financeiro.php"><i class="fas fa-hand-holding-usd me-3"></i> Contas Pagar/Receber</a>
        <a class="nav-link" href="relatorio_financeiro.php"><i class="fas fa-file-invoice-dollar me-3"></i> Relatórios / BI</a>
        <a class="nav-link" href="ordenha.php"><i class="fas fa-cow me-3"></i> Ordenha Fácil</a>
        <hr class="mx-3 opacity-25">
        <a class="nav-link" href="../portal.php"><i class="fas fa-th me-3"></i> Workspace</a>
        <a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-3"></i> Sair</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold text-dark">Financeiro & Fluxo</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImportarXML">
                <i class="fas fa-qrcode me-2"></i>LER XML COMEVAP
            </button>
            <button class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovo">
                <i class="fas fa-plus me-2"></i>NOVA CONTA
            </button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-agro p-4 border-start border-danger border-5">
                <small class="text-muted fw-bold">TOTAL VENCIDO</small>
                <h3 class="text-danger fw-bold mb-0"><?php echo formatarMoeda($stats['total_vencido'] ?? 0); ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-agro p-4 border-start border-warning border-5">
                <small class="text-muted fw-bold">VENCE HOJE</small>
                <h3 class="text-warning fw-bold mb-0"><?php echo formatarMoeda($stats['total_vence_hoje'] ?? 0); ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-agro p-4 border-start border-success border-5">
                <small class="text-muted fw-bold">PAGO NESTE MÊS</small>
                <h3 class="text-success fw-bold mb-0"><?php echo formatarMoeda($stats['total_pago_mes'] ?? 0); ?></h3>
            </div>
        </div>
    </div>

    <div class="card-agro overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted">
                        <th class="ps-4">VENCIMENTO</th>
                        <th>FORNECEDOR / DESCRIÇÃO</th>
                        <th>VALOR</th>
                        <th>MÉTODO</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($contas as $c): 
                        $vencido = ($c['status'] == 'Pendente' && $c['data_vencimento'] < $hoje);
                    ?>
                    <tr class="<?php echo $vencido ? 'table-danger' : ''; ?>">
                        <td class="ps-4 small"><?php echo date('d/m/Y', strtotime($c['data_vencimento'])); ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['fornecedor']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($c['descricao']); ?></small>
                        </td>
                        <td class="fw-bold <?php echo $c['tipo'] == 'Entrada' ? 'text-success' : 'text-danger'; ?>">
                            <?php echo formatarMoeda($c['valor']); ?>
                        </td>
                        <td><small class="badge bg-light text-dark border"><?php echo $c['metodo_pagamento']; ?></small></td>
                        <td class="text-center">
                            <button onclick="baixarPagamento(<?php echo $c['id']; ?>, '<?php echo $c['status']; ?>')" 
                                    class="badge rounded-pill px-3 py-2 status-btn <?php echo $c['status']=='Pago'?'bg-success':'bg-warning text-dark'; ?>">
                                <?php echo $c['status']; ?>
                            </button>
                        </td>
                        <td class="text-center">
                            <a href="acoes.php?del_fin=<?php echo $c['id']; ?>" class="text-danger" onclick="return confirm('Excluir?')"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: NOVO LANÇAMENTO -->
<div class="modal fade" id="modalNovo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="acoes.php" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-success text-white">
                <h5 class="fw-bold mb-0">Novo Lançamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="acao" value="novo_fin">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="small fw-bold">TIPO</label>
                        <select name="tipo" class="form-select">
                            <option value="Saida">Saída (Despesa)</option>
                            <option value="Entrada">Entrada (Receita)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold">VALOR (R$)</label>
                        <input type="number" name="valor" step="0.01" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">FORNECEDOR / CLIENTE</label>
                    <input type="text" name="fornecedor" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">DESCRIÇÃO</label>
                    <input type="text" name="descricao" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="small fw-bold">VENCIMENTO</label>
                        <input type="date" name="data_vencimento" class="form-control" value="<?php echo $hoje; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold">MÉTODO</label>
                        <select name="metodo_pagamento" class="form-select">
                            <option value="Boleto">Boleto</option>
                            <option value="PIX">PIX</option>
                            <option value="Consignado">Consignado</option>
                            <option value="Dinheiro">Dinheiro</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-success w-100 rounded-pill py-2 fw-bold shadow">SALVAR NO FLUXO</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: IMPORTAR XML -->
<div class="modal fade" id="modalImportarXML" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formXML" enctype="multipart/form-data" class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-primary text-white">
                <h5 class="fw-bold mb-0">Importar Nota Comevap</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="fas fa-file-invoice-dollar fa-3x text-primary mb-3"></i>
                <input type="file" name="xml_file" class="form-control" accept=".xml" required>
            </div>
            <div class="modal-footer border-0">
                <button type="button" onclick="lerXML()" class="btn btn-primary w-100 rounded-pill fw-bold">ANALISAR XML</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: CONFIRMAR XML -->
<div class="modal fade" id="modalConfirmaXML" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-dark text-white"><h5>Conferir Dados da Nota</h5></div>
            <div class="modal-body p-4" id="corpoConfirmacao"></div>
            <div class="modal-footer border-0">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button onclick="efetivarXML()" class="btn btn-success rounded-pill px-5 fw-bold shadow">EFETIVAR</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let xmlDados = null;
const modalConfirm = new bootstrap.Modal(document.getElementById('modalConfirmaXML'));

function lerXML() {
    const fd = new FormData(document.getElementById('formXML'));
    fd.append('acao', 'ler_xml_agro');
    fetch('acoes.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.status === 'success') {
            xmlDados = res.dados;
            document.getElementById('corpoConfirmacao').innerHTML = `
                <h6><b>Emitente:</b> ${res.dados.emitente}</h6>
                <h4 class="text-danger"><b>Valor:</b> R$ ${res.dados.valor}</h4>
                <div class="mt-3">
                    <label class="small fw-bold">VENCIMENTO / DESCONTO NO SALÁRIO</label>
                    <input type="date" id="venc_xml" class="form-control" required>
                </div>
            `;
            bootstrap.Modal.getInstance(document.getElementById('modalImportarXML')).hide();
            modalConfirm.show();
        } else { alert(res.message); }
    });
}

function efetivarXML() {
    const d = document.getElementById('venc_xml').value;
    if(!d) return alert("Informe a data!");
    const fd = new FormData();
    fd.append('acao', 'confirmar_xml_agro');
    fd.append('valor', xmlDados.valor_limpo);
    fd.append('descricao', 'Compra Comevap: ' + xmlDados.produto);
    fd.append('vencimento', d);
    fetch('acoes.php', { method: 'POST', body: fd }).then(() => location.reload());
}

function baixarPagamento(id, status) {
    if(status === 'Pago') {
        if(confirm("Voltar para Pendente?")) window.location.href = 'acoes.php?acao=estornar_pagamento&id=' + id;
    } else {
        const d = prompt("Data do Pagamento (DD/MM/AAAA):", "<?php echo date('d/m/Y'); ?>");
        if(d) window.location.href = `acoes.php?acao=confirmar_pagamento&id=${id}&data=${d}`;
    }
}
</script>
</body>
</html>