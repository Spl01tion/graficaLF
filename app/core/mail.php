<?php

/**
 * ============================================================
 *  mail.php — Envio de emails (PHPMailer)
 * ------------------------------------------------------------
 *  Wrapper simples sobre o PHPMailer, com as credenciais lidas
 *  do .env. É usado já no Módulo 2 (recuperação de senha) e
 *  será reaproveitado no Módulo 8 (contacto, pedidos).
 *
 *  FALLBACK DE DESENVOLVIMENTO: se o SMTP não estiver configurado
 *  (MAIL_USERNAME vazio), em vez de falhar, guardamos o email como
 *  ficheiro .html em storage/logs/emails/. Assim o fluxo completo
 *  (ex.: link de recuperação) pode ser testado sem servidor SMTP.
 * ============================================================
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Envia um email HTML. Devolve true em caso de sucesso.
 *
 * @param  string  $para     endereço do destinatário
 * @param  string  $nome     nome do destinatário
 * @param  string  $assunto  assunto
 * @param  string  $html     corpo em HTML
 * @param  string  $texto    alternativa em texto simples (opcional)
 */
function enviar_email(string $para, string $nome, string $assunto, string $html, string $texto = '', string $replyTo = ''): bool
{
    $smtpUser = env('MAIL_USERNAME');

    // ---- Fallback de desenvolvimento: guardar em ficheiro ----
    if ($smtpUser === null || $smtpUser === '') {
        return email_para_ficheiro($para, $assunto, $html);
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = env('MAIL_HOST', 'localhost');
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = (string) env('MAIL_PASSWORD', '');
        $mail->SMTPSecure = env('MAIL_ENCRYPTION', 'tls'); // tls | ssl
        $mail->Port       = (int) env('MAIL_PORT', '587');
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(
            (string) env('MAIL_FROM_ADDRESS', 'geral@graficalifei.co.mz'),
            (string) env('MAIL_FROM_NAME', APP_NAME)
        );
        $mail->addAddress($para, $nome);

        // Reply-To (ex.: no contacto, responder directamente ao cliente).
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo);
        }

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $html;
        $mail->AltBody = $texto !== '' ? $texto : strip_tags($html);

        $mail->send();

        return true;
    } catch (PHPMailerException $e) {
        registar_log('Falha ao enviar email para ' . $para . ': ' . $mail->ErrorInfo);

        return false;
    }
}

/**
 * Guarda o email como ficheiro HTML (modo de desenvolvimento).
 * Devolve sempre true — o "envio" simulado nunca deve bloquear o fluxo.
 */
function email_para_ficheiro(string $para, string $assunto, string $html): bool
{
    $pasta = BASE_PATH . '/storage/logs/emails';
    if (! is_dir($pasta)) {
        @mkdir($pasta, 0755, true);
    }

    $ficheiro = $pasta . '/' . date('Ymd_His') . '_' . str_to_url($assunto) . '.html';
    $cabecalho = "<!-- Para: {$para} | Assunto: {$assunto} | " . date('Y-m-d H:i:s') . " -->\n";

    @file_put_contents($ficheiro, $cabecalho . $html);
    registar_log("Email simulado (sem SMTP) guardado em: {$ficheiro} — destinatário: {$para}");

    return true;
}

/**
 * Envolve conteúdo num template de email com a identidade da marca.
 */
function template_email(string $titulo, string $conteudoHtml): string
{
    $ano = date('Y');

    return <<<HTML
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background:#f4f6f8; padding: 24px;">
        <div style="background:#0a1e2e; padding: 20px; text-align:center; border-radius: 10px 10px 0 0;">
            <span style="display:inline-block; width:44px; height:44px; line-height:44px; background:#e0202f; color:#fff; font-weight:bold; border-radius:10px; font-size:18px;">GL</span>
            <div style="color:#fff; font-size:18px; font-weight:bold; margin-top:8px;">Gráfica Lifei</div>
        </div>
        <div style="background:#fff; padding: 28px; border-radius: 0 0 10px 10px;">
            <h2 style="color:#0a1e2e; margin-top:0;">{$titulo}</h2>
            {$conteudoHtml}
        </div>
        <p style="text-align:center; color:#8a97a3; font-size:12px; margin-top:16px;">
            &copy; {$ano} Gráfica Lifei — Maputo, Moçambique
        </p>
    </div>
    HTML;
}
