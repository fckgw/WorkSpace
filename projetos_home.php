<?php
/**
 * MÓDULO GESTÃO DE PROJETOS (MONDAY CLONE)
 */
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }
require_once 'config.php';

$user_id = $_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Projetos - BDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .monday-sidebar { width: 240px; background: #292f4c; height: 100vh; position: fixed; color: #fff; }
        .monday-content { margin-left: 240px; padding: 30px; background: #fff; min-height: 100vh; }
        .nav-link { color: #b5b5b5; padding: 15px 20px; }
        .nav-link:hover { color: #fff; background: rgba(255,255,255,0.1); }
        .nav-link.active { color: #fff; border-left: 4px solid #00ca72; }
    </style>
</head>
<body>

<div class="monday-sidebar">
    <div class="p-4"><h5 class="fw-bold">BDS Projects</h5></div>
    <nav class="nav flex-column">
        <a class="nav-link active" href="#"><i class="fas fa-home me-2"></i> Áreas de Trabalho</a>
        <a class="nav-link" href="portal.php"><i class="fas fa-arrow-left me-2"></i> Sair para o Portal</a>
    </nav>
</div>

<div class="monday-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold">Meus Quadros</h2>
        <button class="btn btn-success rounded-pill px-4"><i class="fas fa-plus me-2"></i>Novo Projeto</button>
    </div>

    <!-- Interface estilo Monday iniciará aqui -->
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-light border p-5 text-center">
                <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
                <h4>Bem-vindo à Gestão de Projetos</h4>
                <p>Aqui você poderá criar colunas de status, prioridades e prazos como no Monday.com.</p>
                <button class="btn btn-outline-primary mt-2">Criar primeiro quadro</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>