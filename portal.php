<?php
/**
 * BDSoft Workspace - PORTAL CENTRAL DE SELEÇÃO DE TECNOLOGIAS
 * Localização: public_html/portal.php
 * 
 * Este arquivo atua como o Hub principal. Ele decide quais módulos exibir
 * baseando-se no nível do usuário e no período de trial (14 dias).
 */

// 1. Configurações Iniciais e Sessão
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 2. Verificação de Segurança: O usuário está autenticado?
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config.php';

$usuario_id    = $_SESSION['usuario_id'];
$usuario_nivel = $_SESSION['usuario_nivel']; // 'admin' ou 'usuario'
$usuario_nome  = $_SESSION['usuario_nome'];
$ultimo_acesso = isset($_SESSION['ultimo_acesso_info']) ? $_SESSION['ultimo_acesso_info'] : 'Recente';

// Extrair primeiro nome para saudação
$partes_nome = explode(' ', trim($usuario_nome));
$primeiro_nome = $partes_nome[0];

try {
    // 3. Buscar dados de cadastro do usuário para calcular o período de Trial
    $stmt_user = $pdo->prepare("SELECT data_criacao, dias_bonus_cupom FROM usuarios WHERE id = :uid LIMIT 1");
    $stmt_user->execute([':uid' => $usuario_id]);
    $dados_cadastro = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$dados_cadastro) {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    // --- LÓGICA DE TRIAL (14 DIAS + CUPOM) ---
    $data_criacao = new DateTime($dados_cadastro['data_criacao']);
    $data_hoje    = new DateTime();
    $dias_desde_cadastro = $data_hoje->diff($data_criacao)->days;
    $dias_bonus   = (int)$dados_cadastro['dias_bonus_cupom'];
    
    $prazo_total_teste = 14 + $dias_bonus;
    $está_no_periodo_teste = ($dias_desde_cadastro <= $prazo_total_teste);

    /**
     * 4. LÓGICA DE CARREGAMENTO DOS MÓDULOS (APPS)
     * - Se for ADMINISTRADOR: Vê todos os módulos da tabela 'modulos'.
     * - Se for USUÁRIO EM TRIAL: Vê todos os módulos.
     * - Se for USUÁRIO PÓS-TRIAL: Vê apenas os módulos vinculados em 'usuarios_modulos'.
     */
    if (trim(strtolower($usuario_nivel)) === 'admin' || $está_no_periodo_teste) {
        // Acesso total
        $query_modulos = "SELECT * FROM modulos ORDER BY nome ASC";
        $stmt_exec = $pdo->query($query_modulos);
        $meus_modulos = $stmt_exec->fetchAll(PDO::FETCH_ASSOC);
        $modo_acesso = (trim(strtolower($usuario_nivel)) === 'admin') ? "Administrador" : "Período de Teste (Trial)";
    } else {
        // Acesso restrito via tabela de permissões
        $query_restrita = "SELECT m.id, m.nome, m.slug, m.icone, m.descricao 
                           FROM modulos m 
                           INNER JOIN usuarios_modulos um ON m.id = um.modulo_id 
                           WHERE um.usuario_id = :uid 
                           ORDER BY m.nome ASC";
        $stmt_exec = $pdo->prepare($query_restrita);
        $stmt_exec->execute([':uid' => $usuario_id]);
        $meus_modulos = $stmt_exec->fetchAll(PDO::FETCH_ASSOC);
        $modo_acesso = "Plano Contratado";
    }

} catch (PDOException $e) {
    die("Erro interno ao processar permissões: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace - BDSoft Cloud</title>
    
    <!-- CSS: Bootstrap 5, FontAwesome 6 e Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-blue: #1a73e8;
            --dark-text: #202124;
            --muted-text: #5f6368;
            --bg-body: #f8f9fa;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Inter', sans-serif;
            color: var(--dark-text);
            margin: 0;
            padding: 0;
        }

        /* Topbar */
        .navbar-top {
            background-color: #ffffff;
            border-bottom: 1px solid #e0e0e0;
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-blue) !important;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }

        /* Hero */
        .hero-title {
            padding: 70px 0 40px;
            text-align: center;
        }

        .hero-title h1 {
            font-weight: 700;
            font-size: 2.3rem;
            margin-bottom: 10px;
        }

        /* Estilo dos Quadrinhos de Tecnologia (Apps) */
        .app-card {
            background: #ffffff;
            border: 1px solid #e0e6ed;
            border-radius: 28px;
            padding: 45px 25px;
            text-align: center;
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            position: relative;
        }

        .app-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }

        .app-icon-box {
            font-size: 3.5rem;
            margin-bottom: 25px;
            width: 100px;
            height: 100px;
            line-height: 100px;
            background-color: #f8fafc;
            border-radius: 24px;
            transition: 0.3s;
        }

        .app-card:hover .app-icon-box {
            background-color: #e8f0fe;
        }

        .app-title {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 10px;
            color: var(--dark-text);
        }

        .app-card:hover .app-title {
            color: var(--primary-blue);
        }

        .app-desc {
            font-size: 0.9rem;
            color: var(--muted-text);
            line-height: 1.5;
        }

        /* Selos de Status */
        .badge-access {
            font-size: 0.7rem;
            padding: 5px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        .badge-trial { background-color: #e8f0fe; color: #1a73e8; }
        .badge-admin { background-color: #fce8e6; color: #d93025; }

        .btn-logout {
            font-weight: 600;
            border-radius: 50px;
            padding: 8px 25px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar-top shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-th-large me-2"></i>BDSoft Workspace
        </a>
        <div class="d-flex align-items-center">
            <div class="text-end me-4 d-none d-md-block">
                <div class="small fw-bold text-dark"><?php echo htmlspecialchars($usuario_nome); ?></div>
                <div class="text-muted" style="font-size: 11px;">Último acesso: <?php echo $ultimo_acesso; ?></div>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm btn-logout fw-bold">SAIR</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    
    <div class="hero-title">
        <span class="badge badge-access <?php echo ($usuario_nivel === 'admin') ? 'badge-admin' : 'badge-trial'; ?> mb-3">
            Acesso: <?php echo $modo_acesso; ?>
        </span>
        <h1>Olá, <?php echo htmlspecialchars($primeiro_nome); ?>!</h1>
        <p class="text-muted fs-5">Selecione uma de suas tecnologias disponíveis para começar.</p>
    </div>

    <div class="row g-4 justify-content-center">
        
        <?php if (empty($meus_modulos)): ?>
            <!-- Alerta: Nenhum módulo encontrado -->
            <div class="col-md-7">
                <div class="card p-5 border-0 shadow-sm rounded-4 text-center">
                    <i class="fas fa-user-lock fa-4x text-warning mb-4 opacity-50"></i>
                    <h4 class="fw-bold">Acesso em processamento</h4>
                    <p class="text-muted">Seu período de teste expirou e você não possui módulos contratados ativos.<br>Por favor, entre em contato com o suporte para liberar seu acesso.</p>
                    <div class="mt-3">
                        <a href="mailto:suporte@bdsoft.com.br" class="btn btn-primary rounded-pill px-4">CONTATO SUPORTE</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            
            <!-- LISTAGEM DE MÓDULOS (DINÂMICA) -->
            <?php foreach ($meus_modulos as $mod): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                    <a href="<?php echo htmlspecialchars($mod['slug']); ?>" class="app-card">
                        <div class="app-icon-box text-primary">
                            <i class="fas <?php echo htmlspecialchars($mod['icone']); ?>"></i>
                        </div>
                        <div class="app-title"><?php echo htmlspecialchars($mod['nome']); ?></div>
                        <div class="app-desc"><?php echo htmlspecialchars($mod['descricao']); ?></div>
                    </a>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

        <!-- CARD EXCLUSIVO DE ADMINISTRAÇÃO -->
        <?php if (trim(strtolower($usuario_nivel)) === 'admin'): ?>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                <a href="admin_usuarios.php" class="app-card border-danger border-opacity-25 bg-light bg-opacity-50">
                    <div class="app-icon-box text-danger">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="app-title text-danger">Painel Admin</div>
                    <div class="app-desc">Gestão global de usuários, liberação de planos, auditoria de logs e cupons.</div>
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<footer class="text-center mt-5 py-5 text-muted small border-top bg-white">
    <div class="container">
        <p class="mb-1 fw-bold">BDSoft Workspace &copy; <?php echo date('Y'); ?></p>
        <p class="mb-0">Tecnologia Cloud para Pecuária e Gestão de Projetos</p>
        <p class="mt-2" style="font-size: 10px;">Ambiente de Produção Seguro - IP: <?php echo $_SERVER['REMOTE_ADDR']; ?></p>
    </div>
</footer>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>