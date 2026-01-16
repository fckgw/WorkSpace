<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>


<?php

// Se o usuário já está logado, ele SEMPRE cai no Portal de Aplicativos

/**
 * ROTEADOR PRINCIPAL - CENTRAL DE ACESSO
 */
session_start();

// Se o usuário estiver logado, ele é enviado para o Portal de Aplicativos
if (isset($_SESSION['usuario_id'])) {
    header("Location: portal.php");
    exit;
} else {
    // Caso contrário, ele deve realizar o login
    header("Location: login.php");
    exit;
}

?>