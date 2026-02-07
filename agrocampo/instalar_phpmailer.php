<?php
/**
 * INSTALADOR AUTOMÁTICO PHPMAILER - BDSoft Workspace
 */

$dir = 'includes/PHPMailer/';

// Criar a pasta se não existir
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// URLs dos arquivos originais no GitHub (Versão estável 6.9.1)
$files = [
    'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php',
    'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
    'SMTP.php'      => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php'
];

echo "<h2>Instalando PHPMailer no BDSoft Workspace...</h2>";

foreach ($files as $name => $url) {
    echo "Baixando $name... ";
    $content = file_get_contents($url);
    if ($content) {
        file_put_contents($dir . $name, $content);
        echo "<span style='color:green;'>OK!</span><br>";
    } else {
        echo "<span style='color:red;'>FALHOU!</span> (Verifique se o seu servidor permite conexões externas)<br>";
    }
}

echo "<br><b>Instalação concluída!</b> Agora você pode usar o registro.php com envio de e-mail.";
echo "<br><br><a href='registro.php'>Ir para a Tela de Registro</a>";
?>