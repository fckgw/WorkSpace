<?php
/**
 * BDSoft Workspace - SIDEBAR AGRO DINÂMICA (CONTEXTUAL)
 * Local: agrocampo/includes/sidebar_agro.php
 */
$usuario_id_sid = $_SESSION['usuario_id'];
$nivel_sid = $_SESSION['usuario_nivel'];
$uri_sid = $_SERVER['REQUEST_URI'];

// Identificar em qual submódulo estamos
$contexto = 'portal';
if (strpos($uri_sid, 'monitoramento') !== false) { $contexto = 'monitoramento'; }
elseif (strpos($uri_sid, 'financeiro') !== false) { $contexto = 'financeiro'; }
elseif (strpos($uri_sid, 'pecuariaLeiteira') !== false) { $contexto = 'leite'; }
elseif (strpos($uri_sid, 'laticinios') !== false) { $contexto = 'laticinios'; }
elseif (strpos($uri_sid, 'gadoCorte') !== false) { $contexto = 'corte'; }

// Função de validação
if (!function_exists('validaAcessoAgro')) {
    function validaAcessoAgro($pdo, $uid, $slug_parcial, $nivel) {
        if ($nivel === 'admin') return true;
        $stmt = $pdo->prepare("SELECT 1 FROM usuarios_agro_permissões up 
                               INNER JOIN agro_submodulos s ON up.submodulo_id = s.id 
                               WHERE up.usuario_id = ? AND s.slug LIKE ?");
        $stmt->execute([$uid, "%$slug_parcial%"]);
        return $stmt->fetch() ? true : false;
    }
}
?>
<style>
    :root { --agro-dark: #1a3317; --agro-main: #2d5a27; --agro-accent: #8bc34a; --sidebar-w: 280px; }
    body { display: flex; min-height: 100vh; background-color: #f4f7f4; margin: 0; font-family: 'Segoe UI', sans-serif; }
    .sidebar-agro { width: var(--sidebar-w); background: var(--agro-dark); color: white; position: fixed; left: 0; top: 0; height: 100vh; z-index: 1050; display: flex; flex-direction: column; box-shadow: 4px 0 10px rgba(0,0,0,0.1); }
    .main-wrapper { flex: 1; margin-left: var(--sidebar-w); padding: 40px; width: calc(100% - var(--sidebar-w)); transition: 0.3s; }
    .sidebar-agro .nav-link { color: rgba(255,255,255,0.6); padding: 14px 25px; font-weight: 500; border: none; display: flex; align-items: center; transition: 0.2s; }
    .sidebar-agro .nav-link:hover { background: rgba(255,255,255,0.05); color: white; }
    .sidebar-agro .nav-link.active { background: var(--agro-main); color: white; border-left: 5px solid var(--agro-accent); }
    .menu-header { padding: 20px 25px 5px; font-size: 10px; font-weight: 800; color: var(--agro-accent); text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
    @media (max-width: 991px) { .sidebar-agro { left: -280px; } .main-wrapper { margin-left: 0; width: 100%; } }
</style>

<div class="sidebar-agro shadow">
    <div class="p-4 text-center border-bottom border-secondary">
        <h4 class="fw-bold mb-0 text-success"><i class="fas fa-seedling me-2"></i>AgroCampo</h4>
    </div>
    
    <nav class="nav flex-column mt-2">
        <a class="nav-link" href="../index.php"><i class="fas fa-th-large me-3"></i> Painel Agro</a>

        <!-- MONITORAMENTO -->
        <?php if (validaAcessoAgro($pdo, $usuario_id_sid, 'monitoramento', $nivel_sid)): ?>
            <div class="menu-header">Agricultura</div>
            <a class="nav-link <?php echo ($contexto == 'monitoramento') ? 'active' : ''; ?>" href="../monitoramento/index.php"><i class="fas fa-map-marked-alt me-3"></i> Talhões</a>
        <?php endif; ?>

        <!-- FINANCEIRO -->
        <?php if (validaAcessoAgro($pdo, $usuario_id_sid, 'financeiro', $nivel_sid)): ?>
            <div class="menu-header">Controladoria</div>
            <a class="nav-link <?php echo ($contexto == 'financeiro') ? 'active' : ''; ?>" href="../financeiro/index.php"><i class="fas fa-hand-holding-usd me-3"></i> Financeiro</a>
        <?php endif; ?>

        <!-- PECUÁRIA LEITEIRA -->
        <?php if (validaAcessoAgro($pdo, $usuario_id_sid, 'pecuariaLeiteira', $nivel_sid)): ?>
            <div class="menu-header">Pecuária Leite</div>
            <a class="nav-link <?php echo ($contexto == 'leite') ? 'active' : ''; ?>" href="../pecuariaLeiteira/index.php"><i class="fas fa-cow me-3"></i> Ordenha</a>
        <?php endif; ?>

        <!-- LATICÍNIOS -->
        <?php if (validaAcessoAgro($pdo, $usuario_id_sid, 'laticinios', $nivel_sid)): ?>
            <div class="menu-header">Indústria</div>
            <a class="nav-link <?php echo ($contexto == 'laticinios') ? 'active' : ''; ?>" href="../laticinios/index.php"><i class="fas fa-industry me-3"></i> Laticínios</a>
        <?php endif; ?>

        <!-- GADO DE CORTE -->
        <?php if (validaAcessoAgro($pdo, $usuario_id_sid, 'gadoCorte', $nivel_sid)): ?>
            <div class="menu-header">Pecuária Corte</div>
            <a class="nav-link <?php echo ($contexto == 'corte') ? 'active' : ''; ?>" href="../gadoCorte/index.php"><i class="fas fa-drumstick-bite me-3"></i> Gado de Corte</a>
        <?php endif; ?>

        <hr class="mx-3 opacity-25">
        <a class="nav-link mt-2" href="../../portal.php"><i class="fas fa-arrow-left me-3"></i> Workspace</a>
        <a class="nav-link text-danger mt-auto" href="../../logout.php"><i class="fas fa-sign-out-alt me-3"></i> Sair</a>
    </nav>
</div>