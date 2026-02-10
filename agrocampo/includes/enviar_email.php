<?php
/**
 * BDSoft Workspace - ROBÔ DE E-MAIL SMTP (Office 365 / Locaweb)
 */
function dispararEmailWorkspace($para, $assunto, $mensagem_html) {
    $remetente = "souzafelipe@bdsoft.com.br";
    $senha = "BDSoft@2020";
    $host = "email-ssl.com.br";
    $porta = 465;

    $header = "MIME-Version: 1.0\r\n";
    $header .= "Content-type: text/html; charset=utf-8\r\n";
    $header .= "From: BDSoft Workspace <$remetente>\r\n";
    $header .= "Reply-To: $remetente\r\n";
    
    // Na Locaweb, usamos o parâmetro -f para autenticar o envelope
    return mail($para, $assunto, $mensagem_html, $header, "-f" . $remetente);
}
?>