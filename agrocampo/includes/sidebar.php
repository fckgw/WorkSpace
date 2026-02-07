<?php
/**
 * SIDEBAR DINÂMICA CONTEXTUAL - AGRO CAMPO
 */
$usuario_id_sid = $_SESSION['usuario_id'];
$nivel_sid = $_SESSION['usuario_nivel'];

// Identifica em qual submódulo estamos para mudar o menu
// Ex: Se o caminho tem /financeiro/, o contexto é financeiro
$uri_atual = $_SERVER['REQUEST_URI'];
$contexto = 'geral';

if (strpos($uri_atual, 'financeiro') !== false) { $contexto = 'financeiro'; }
elseif (strpos($uri_atual, 'pecuariaLeiteira') !== false) { $contexto = 'pecuaria'; }
elseif (strpos($uri_atual, 'monitoramento') !== false) { $contexto = 'monitoramento'; }

// Função de permissão (ajustada para subir 2 níveis se necessário)
function validaAcessoSub($pdo, $uid, $slug, $nivel) {
    if ($nivel === 'admin') return true;
    $stmt = $pdo->prepare("SELECT 1 FROM usuarios_agro_permissões up INNER JOIN agro_submodulos s ON up.submodulo_id = s.id WHERE up.usuario_id = ? AND s.slug LIKE ?");
    $stmt->execute([$uid, "%$slug%"]);
    return $stmt->fetch() ? true : false;
}
?>
<style>
    :root { --agro-main: #2d5a27; --agro-dark: #1a3317; --sidebar-w: 260px; }
    .sidebar-agro { width: var(--sidebar-w); background: var(--agro-dark); color: white; position: fixed; height: 100vh; display: flex; flex-direction: column; z-index: 1000; transition: 0.3s; }
    .main-wrapper { flex: 1; margin-left: var(--sidebar-w); padding: 40px; width: calc(100% - var(--sidebar-w)); min-height: 100vh; }
    .nav-link { color: rgba(255,255,255,0.6); padding: 12px 25px; font-weight: 500; transition: 0.2s; border: none; display: flex; align-items: center; }
    .nav-link:hover, .nav-link.active { background: var(--agro-main); color: white; }
    .menu-label { padding: 20px 25px 10px; font-size: 11px; font-weight: bold; color: #8bc34a; text-transform: uppercase; letter-spacing: 1px; }
    @media (max-width: 991px) { .sidebar-agro { left: -260px; } .main-wrapper { margin-left: 0; width: 100%; } }
</style>

<div class="sidebar-agro shadow">
    <div class="p-4 text-center border-bottom border-secondary">
        <h5 class="fw-bold mb-0 text-success">AgroCampo</h5>
        <small class="opacity-50">BDSoft Workspace</small>
    </div>

    <nav class="nav flex-column mt-2">
        <!-- LINK VOLTAR SEMPRE PRESENTE -->
        <a class="nav-link" href="../index.php"><i class="fas fa-arrow-left me-2"></i> Voltar ao Portal Agro</a>

        <?php if ($contexto == 'financeiro'): ?>
            <div class="menu-label">Módulo Financeiro</div>
            <a class="nav-link active" href="index.php"><i class="fas fa-hand-holding-usd me-2"></i> Contas Pagar/Receber</a>
            <a class="nav-link" href="relatorios.php"><i class="fas fa-chart-bar me-2"></i> Relatórios BI</a>
            <a class="nav-link" href="#"><i class="fas fa-file-invoice me-2"></i> Notas Fiscais</a>

        <?php elseif ($contexto == 'pecuaria'): ?>
            <div class="menu-label">Pecuária Leiteira</div>
            <a class="nav-link active" href="index.php"><i class="fas fa-cow me-2"></i> Controle de Ordenha</a>
            <a class="nav-link" href="#"><i class="fas fa-clipboard-list me-2"></i> Gestão do Rebanho</a>
            <a class="nav-link" href="#"><i class="fas fa-Syringe me-2"></i> Vacinação/Saúde</a>

        <?php elseif ($contexto == 'monitoramento'): ?>
            <div class="menu-label">Monitoramento</div>
            <a class="nav-link active" href="index.php"><i class="fas fa-map-marked-alt me-2"></i> Meus Talhões</a>
            <a class="nav-link" href="#"><i class="fas fa-seedling me-2"></i> Safras</a>
        <?php endif; ?>

        <hr class="mx-3 opacity-25">
        <a class="nav-link" href="../../portal.php"><i class="fas fa-th me-2"></i> Workspace</a>
        <a class="nav-link text-danger mt-auto" href="../../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </nav>
</div>