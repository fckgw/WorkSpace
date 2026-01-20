<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

$user_id = $_SESSION['usuario_id'];

// Consultas rápidas para o Dashboard
$total_talhoes = $pdo->prepare("SELECT COUNT(*) FROM agro_talhoes WHERE usuario_id = ?");
$total_talhoes->execute([$user_id]);
$qtd_talhoes = $total_talhoes->fetchColumn();

$financeiro = $pdo->prepare("SELECT SUM(CASE WHEN tipo='Entrada' THEN valor ELSE -valor END) FROM agro_financeiro WHERE usuario_id = ? AND status = 'Pago'");
$financeiro->execute([$user_id]);
$saldo = $financeiro->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>AgroCampo - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --agro-primary: #2d5a27; --agro-sidebar: #1e3d1a; }
        body { background: #f4f7f4; display: flex; min-height: 100vh; font-family: 'Inter', sans-serif; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--agro-sidebar); color: white; position: fixed; height: 100vh; display: flex; flex-direction: column; }
        .sidebar .nav-link { color: rgba(255,255,255,0.7); padding: 15px 25px; font-weight: 500; border-radius: 0; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link.active { background: var(--agro-primary); color: white; border-left: 5px solid #8bc34a; }
        
        .main-content { flex: 1; margin-left: 260px; padding: 40px; }
        .card-stat { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: 0.3s; }
    </style>
</head>
<body>

<!-- SIDEBAR AGRO -->
<div class="sidebar shadow">
    <div class="p-4 text-center">
        <h4 class="fw-bold"><i class="fas fa-seedling me-2 text-success"></i>AgroCampo</h4>
        <small class="opacity-50">Gestão Rural Integrada</small>
    </div>
    
    <nav class="nav flex-column mt-3">
        <a class="nav-link active" href="index.php"><i class="fas fa-chart-line me-3"></i> Visão Geral</a>
        <a class="nav-link" href="talhoes.php"><i class="fas fa-map-marked-alt me-3"></i> Talhões e Áreas</a>
        <a class="nav-link" href="financeiro.php"><i class="fas fa-hand-holding-usd me-3"></i> Financeiro / Fluxo</a>
        <a class="nav-link" href="ordenha.php"><i class="fas fa-cow me-3"></i> Ordenha Fácil</a>
        <hr class="mx-3 opacity-25">
        <a class="nav-link" href="../portal.php"><i class="fas fa-th me-3"></i> Workspace</a>
        <a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-3"></i> Sair</a>
    </nav>
</div>

<!-- CONTEÚDO -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold">Olá, <?php echo explode(' ', $_SESSION['usuario_nome'])[0]; ?></h2>
            <p class="text-muted">Aqui está o resumo da sua fazenda hoje.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Card Financeiro -->
        <div class="col-md-4">
            <div class="card card-stat p-4 bg-white">
                <div class="small text-muted fw-bold">SALDO EM CAIXA</div>
                <h2 class="fw-bold <?php echo $saldo >= 0 ? 'text-success' : 'text-danger'; ?>">R$ <?php echo number_format($saldo, 2, ',', '.'); ?></h2>
                <a href="financeiro.php" class="small text-decoration-none">Ver fluxo completo →</a>
            </div>
        </div>
        <!-- Card Áreas -->
        <div class="col-md-4">
            <div class="card card-stat p-4 bg-white border-start border-success border-5">
                <div class="small text-muted fw-bold">ÁREAS MAPEADAS</div>
                <h2 class="fw-bold"><?php echo $qtd_talhoes; ?> <small class="h6">talhões</small></h2>
                <a href="index.php" class="small text-decoration-none text-success">Gerenciar mapas →</a>
            </div>
        </div>
        <!-- Card Ordenha -->
        <div class="col-md-4">
            <div class="card card-stat p-4 bg-white border-start border-primary border-5">
                <div class="small text-muted fw-bold">PRODUÇÃO DE LEITE</div>
                <h2 class="fw-bold">0.00 <small class="h6">litros hoje</small></h2>
                <a href="ordenha.php" class="small text-decoration-none text-primary">Abrir Ordenha Fácil →</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>