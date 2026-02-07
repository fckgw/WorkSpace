<?php
/**
 * BDSoft Workspace - GESTÃO DE USUÁRIOS E MÓDULOS
 * Localização: public_html/admin_usuarios.php
 */
session_start();
require_once 'config.php';

// 1. Segurança: Somente Admin acessa
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$mensagem = "";

// --- 2. PROCESSAR ATRIBUIÇÃO DE MÓDULOS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_salvar_modulos'])) {
    $uid_alvo = (int)$_POST['user_id'];
    $mods_selecionados = $_POST['modulos'] ?? [];

    try {
        $pdo->beginTransaction();
        // Remove permissões antigas
        $pdo->prepare("DELETE FROM usuarios_modulos WHERE usuario_id = ?")->execute([$uid_alvo]);
        
        // Insere as novas
        $stmt_ins = $pdo->prepare("INSERT INTO usuarios_modulos (usuario_id, modulo_id) VALUES (?, ?)");
        foreach ($mods_selecionados as $mid) {
            $stmt_ins->execute([$uid_alvo, $mid]);
        }
        $pdo->commit();
        $mensagem = "<div class='alert alert-success'>Permissões do usuário atualizadas!</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='alert alert-danger'>Erro ao salvar: " . $e->getMessage() . "</div>";
    }
}

// --- 3. BUSCAR DADOS ---
$usuarios = $pdo->query("SELECT *, 
    (SELECT COUNT(*) FROM usuarios_modulos WHERE usuario_id = usuarios.id) as total_mods 
    FROM usuarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$todos_modulos = $pdo->query("SELECT * FROM modulos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Usuários - BDSoft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .card-user { border: none; border-radius: 15px; transition: 0.3s; background: #fff; }
        .card-user:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .badge-trial { background: #e8f0fe; color: #1a73e8; }
        .badge-expired { background: #fce8e6; color: #d93025; }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark">Gestão de Usuários</h2>
            <p class="text-muted">Controle de acessos e planos do BDSoft Workspace</p>
        </div>
        <a href="portal.php" class="btn btn-outline-secondary rounded-pill px-4">Voltar ao Portal</a>
    </div>

    <?php echo $mensagem; ?>

    <div class="row g-4">
        <?php foreach ($usuarios as $u): 
            $data_c = new DateTime($u['data_criacao']);
            $dias = (new DateTime())->diff($data_c)->days;
            $restantes = 14 + (int)$u['dias_bonus_cupom'] - $dias;
            $is_trial = ($restantes >= 0);
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card card-user p-4 shadow-sm h-100">
                <div class="d-flex justify-content-between mb-3">
                    <span class="badge rounded-pill <?php echo $is_trial ? 'badge-trial' : 'badge-expired'; ?>">
                        <?php echo $is_trial ? "Trial: $restantes dias" : "Expirado"; ?>
                    </span>
                    <span class="small text-muted">ID #<?php echo $u['id']; ?></span>
                </div>
                
                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($u['nome']); ?></h5>
                <p class="small text-muted mb-3"><?php echo htmlspecialchars($u['usuario']); ?></p>
                
                <div class="bg-light p-3 rounded-3 mb-4">
                    <div class="d-flex justify-content-between small">
                        <span>Módulos Ativos:</span>
                        <b class="text-primary"><?php echo $u['nivel'] == 'admin' ? 'ILIMITADO' : $u['total_mods']; ?></b>
                    </div>
                </div>

                <div class="mt-auto d-flex gap-2">
                    <button class="btn btn-primary btn-sm rounded-pill flex-grow-1 fw-bold" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalMods<?php echo $u['id']; ?>">
                        <i class="fas fa-th me-2"></i>MÓDULOS
                    </button>
                    <button class="btn btn-light border btn-sm rounded-pill px-3"><i class="fas fa-edit"></i></button>
                </div>
            </div>
        </div>

        <!-- MODAL DE ATRIBUIÇÃO DE MÓDULOS -->
        <div class="modal fade" id="modalMods<?php echo $u['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:20px;">
                    <div class="modal-header border-0 p-4 pb-0">
                        <h5 class="fw-bold">Liberar Módulos: <?php echo explode(' ', $u['nome'])[0]; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <p class="text-muted small mb-4">Selecione quais tecnologias este usuário poderá acessar após o período de 14 dias.</p>
                        
                        <?php 
                        // Buscar módulos que o usuário já tem
                        $my_m = $pdo->prepare("SELECT modulo_id FROM usuarios_modulos WHERE usuario_id = ?");
                        $my_m->execute([$u['id']]);
                        $my_m_ids = $my_m->fetchAll(PDO::FETCH_COLUMN);
                        
                        foreach($todos_modulos as $mod): 
                        ?>
                        <label class="list-group-item d-flex align-items-center py-3 border rounded-3 mb-2 cursor-pointer">
                            <input class="form-check-input me-3" type="checkbox" name="modulos[]" value="<?php echo $mod['id']; ?>" 
                                <?php echo in_array($mod['id'], $my_m_ids) ? 'checked' : ''; ?>
                                style="width: 20px; height: 20px;">
                            <div>
                                <div class="fw-bold"><i class="fas <?php echo $mod['icone']; ?> me-2 text-primary"></i><?php echo $mod['nome']; ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" name="btn_salvar_modulos" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">SALVAR PERMISSÕES</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>