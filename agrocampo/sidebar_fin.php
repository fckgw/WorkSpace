<?php
/**
 * SIDEBAR EXCLUSIVA - FINANCEIRO
 */
$pagina_viana = basename($_SERVER['PHP_SELF']);
?>
<style>
    :root { --fin-dark: #1a1c23; --fin-primary: #1a73e8; --sidebar-w: 260px; }
    body { display: flex; min-height: 100vh; background-color: #f8f9fa; margin: 0; font-family: 'Segoe UI', sans-serif; }
    .sidebar-fin { width: var(--sidebar-w); background: var(--fin-dark); color: white; position: fixed; left: 0; top: 0; height: 100vh; z-index: 1050; display: flex; flex-direction: column; }
    .main-wrapper { flex: 1; margin-left: var(--sidebar-w); padding: 40px; width: calc(100% - var(--sidebar-w)); }
    .sidebar-fin .nav-link { color: rgba(255,255,255,0.6); padding: 15px 25px; display: flex; align-items: center; text-decoration: none; transition: 0.2s; }
    .sidebar-fin .nav-link:hover { background: rgba(255,255,255,0.05); color: white; }
    .sidebar-fin .nav-link.active { background: var(--fin-primary); color: white; border-left: 5px solid #fff; }
    .menu-header { padding: 20px 25px 5px; font-size: 10px; font-weight: 800; color: #6c757d; text-transform: uppercase; letter-spacing: 1px; }
    @media (max-width: 991px) { .sidebar-fin { left: -260px; } .main-wrapper { margin-left: 0; width: 100%; } }
</style>
<div class="sidebar-fin shadow">
    <div class="p-4 text-center border-bottom border-secondary">
        <h4 class="fw-bold mb-0 text-white">Financeiro</h4>
        <small class="opacity-50">AgroCampo</small>
    </div>
    <nav class="nav flex-column mt-3">
        <a class="nav-link <?php echo ($pagina_viana == 'index.php') ? 'active' : ''; ?>" href="index.php"><i class="fas fa-hand-holding-usd me-3"></i> Fluxo de Caixa</a>
        <a class="nav-link <?php echo ($pagina_viana == 'provisoes.php') ? 'active' : ''; ?>" href="provisoes.php"><i class="fas fa-calendar-alt me-3"></i> Provisionamento</a>
        <a class="nav-link" href="../relatorio_financeiro.php"><i class="fas fa-chart-bar me-3"></i> Relatórios BI</a>
        <hr class="mx-3 opacity-25">
        <a class="nav-link" href="../index.php"><i class="fas fa-arrow-left me-3"></i> Painel Agro</a>
        <a class="nav-link text-danger mt-auto" href="../../logout.php"><i class="fas fa-sign-out-alt me-3"></i> Sair</a>
    </nav>
</div>