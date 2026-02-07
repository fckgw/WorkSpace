<?php
/**
 * BDSoft Workspace - SIDEBAR CONTEXTUAL AGRO
 */
$pagina_atual = basename($_SERVER['PHP_SELF']);

// Definir o contexto do menu
$contexto = 'geral';
if (in_array($pagina_atual, ['financeiro.php', 'provisoes.php', 'relatorio_financeiro.php', 'detalhe_provisao.php'])) {
    $contexto = 'financeiro';
} elseif (in_array($pagina_atual, ['ordenha.php'])) {
    $contexto = 'ordenha';
} elseif (in_array($pagina_atual, ['talhoes.php'])) {
    $contexto = 'monitoramento';
}
?>
<style>
    :root { --agro-dark: #1a3317; --agro-main: #2d5a27; --sidebar-w: 280px; }
    .sidebar-agro { width: var(--sidebar-w); background: var(--agro-dark); color: white; position: fixed; left: 0; top: 0; height: 100vh; z-index: 1050; display: flex; flex-direction: column; overflow-y: auto; box-shadow: 4px 0 10px rgba(0,0,0,0.2); }
    .main-wrapper { flex: 1; margin-left: var(--sidebar-w); padding: 40px; width: calc(100% - var(--sidebar-w)); min-height: 100vh; transition: 0.3s; }
    .sidebar-agro .nav-link { color: rgba(255,255,255,0.6); padding: 15px 25px; display: flex; align-items: center; text-decoration: none; transition: 0.2; }
    .sidebar-agro .nav-link:hover { background: rgba(255,255,255,0.05); color: white; }
    .sidebar-agro .nav-link.active { background: var(--agro-main); color: white; border-left: 5px solid #8bc34a; }
    .menu-header { padding: 20px 25px 5px; font-size: 11px; font-weight: 800; color: #8bc34a; text-transform: uppercase; letter-spacing: 1px; }
    @media (max-width: 991px) { .sidebar-agro { left: -280px; } .main-wrapper { margin-left: 0; width: 100%; } }
</style>

<div class="sidebar-agro no-print">
    <div class="p-4 text-center border-bottom border-secondary">
        <h4 class="fw-bold mb-0 text-success">AgroCampo</h4>
        <small class="opacity-50">Menu <?php echo ucfirst($contexto); ?></small>
    </div>
    
    <nav class="nav flex-column mt-2">
        <a class="nav-link" href="index.php"><i class="fas fa-th-large me-2"></i> Painel Agro</a>

        <?php if ($contexto == 'financeiro'): ?>
            <div class="menu-header">Controladoria</div>
            <a class="nav-link <?php echo ($pagina_atual == 'financeiro.php')?'active':''; ?>" href="financeiro.php"><i class="fas fa-hand-holding-usd me-2"></i> Fluxo de Caixa</a>
            <a class="nav-link <?php echo ($pagina_atual == 'provisoes.php')?'active':''; ?>" href="provisoes.php"><i class="fas fa-calendar-alt me-2"></i> Provisionamento</a>
            <a class="nav-link <?php echo ($pagina_atual == 'relatorio_financeiro.php')?'active':''; ?>" href="relatorio_financeiro.php"><i class="fas fa-file-invoice-dollar me-2"></i> Relatórios BI</a>

        <?php elseif ($contexto == 'ordenha'): ?>
            <div class="menu-header">Pecuária</div>
            <a class="nav-link active" href="ordenha.php"><i class="fas fa-cow me-2"></i> Ordenha Prática</a>

        <?php elseif ($contexto == 'monitoramento'): ?>
            <div class="menu-header">Agricultura</div>
            <a class="nav-link active" href="talhoes.php"><i class="fas fa-map-marked-alt me-2"></i> Meus Talhões</a>
        <?php endif; ?>

        <hr class="mx-3 opacity-25">
        <a class="nav-link" href="../portal.php"><i class="fas fa-arrow-left me-2"></i> Workspace</a>
        <a class="nav-link text-danger mt-auto" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </nav>
</div>