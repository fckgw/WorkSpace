<?php
/**
 * BDSoft Workspace - SIDEBAR GADO DE CORTE (ATUALIZADA)
 * Local: agrocampo/gadoCorte/sidebar.php
 */
$pagina_ativa = basename($_SERVER['PHP_SELF']);
?>
<style>
    :root {
        --corte-dark: #2c1b0e;
        --corte-primary: #5d4037;
        --corte-accent: #ff9800;
        --sidebar-w: 280px;
    }

    body { display: flex; min-height: 100vh; background-color: #f4f1ea; margin: 0; font-family: 'Segoe UI', sans-serif; }

    .sidebar-corte {
        width: var(--sidebar-w);
        background: var(--corte-dark);
        color: white;
        position: fixed;
        left: 0; top: 0; height: 100vh;
        z-index: 1050;
        display: flex; flex-direction: column;
        box-shadow: 4px 0 10px rgba(0,0,0,0.2);
    }

    .main-wrapper {
        flex: 1;
        margin-left: var(--sidebar-w);
        padding: 40px;
        width: calc(100% - var(--sidebar-w));
        transition: 0.3s;
    }

    .sidebar-corte .nav-link {
        color: rgba(255,255,255,0.6);
        padding: 15px 25px;
        font-weight: 500;
        border: none;
        display: flex; align-items: center;
        transition: 0.2s;
        text-decoration: none;
    }

    .sidebar-corte .nav-link i { width: 25px; margin-right: 10px; font-size: 1.1rem; }
    .sidebar-corte .nav-link:hover { background: rgba(255,255,255,0.05); color: white; }
    .sidebar-corte .nav-link.active {
        background: var(--corte-primary);
        color: white;
        border-left: 5px solid var(--corte-accent);
    }

    .menu-header { padding: 20px 25px 5px; font-size: 10px; font-weight: 800; color: var(--corte-accent); text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }

    @media (max-width: 991px) {
        .sidebar-corte { left: calc(-1 * var(--sidebar-w)); }
        .sidebar-corte.active { left: 0; }
        .main-wrapper { margin-left: 0; width: 100%; padding: 20px; }
    }
</style>

<div class="sidebar-corte shadow" id="sidebar">
    <div class="p-4 text-center border-bottom border-secondary">
        <h4 class="fw-bold mb-0 text-white"><i class="fas fa-drumstick-bite me-2"></i>Gado de Corte</h4>
        <small class="opacity-50">Gestão de Performance</small>
    </div>
    
    <nav class="nav flex-column mt-3">
        <a class="nav-link <?php echo ($pagina_ativa == 'index.php') ? 'active' : ''; ?>" href="index.php">
            <i class="fas fa-chart-pie"></i> Dashboard BI
        </a>

        <div class="menu-header">Estrutura</div>
        <a class="nav-link <?php echo ($pagina_ativa == 'fazendas.php') ? 'active' : ''; ?>" href="fazendas.php">
            <i class="fas fa-map-signs"></i> Gestão de Fazendas
        </a>
        <a class="nav-link <?php echo ($pagina_ativa == 'lotes.php') ? 'active' : ''; ?>" href="lotes.php">
            <i class="fas fa-layer-group"></i> Gestão de Lotes
        </a>

        <div class="menu-header">Manejo Animal</div>
        <a class="nav-link <?php echo ($pagina_ativa == 'manejo_individual.php') ? 'active' : ''; ?>" href="manejo_individual.php">
            <i class="fas fa-tags"></i> Manejo Gado a Gado
        </a>
        <a class="nav-link <?php echo ($pagina_ativa == 'compras.php') ? 'active' : ''; ?>" href="compras.php">
            <i class="fas fa-shopping-cart"></i> Compras / Entradas
        </a>

        <div class="menu-header">Financeiro</div>
        <a class="nav-link <?php echo ($pagina_ativa == 'custos.php') ? 'active' : ''; ?>" href="custos.php">
            <i class="fas fa-money-bill-wave"></i> Custos de Produção
        </a>
        <a class="nav-link <?php echo ($pagina_ativa == 'vendas.php') ? 'active' : ''; ?>" href="vendas.php">
            <i class="fas fa-handshake"></i> Vendas / Abate
        </a>

        <hr class="mx-3 opacity-25">
        
        <a class="nav-link" href="../index.php">
            <i class="fas fa-arrow-left"></i> Painel Agro
        </a>
        
        <div class="mt-auto p-4 border-top border-secondary">
            <a href="../../logout.php" class="btn btn-sm btn-outline-danger w-100 rounded-pill fw-bold">SAIR</a>
        </div>
    </nav>
</div>