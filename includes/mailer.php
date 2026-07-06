<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Sends an HTML email.
 * If SMTP_HOST is set, uses a minimal raw SMTP client (no libraries needed).
 * Otherwise falls back to PHP's built-in mail().
 *
 * For production, swapping this for PHPMailer is recommended:
 *   composer require phpmailer/phpmailer
 * and replacing the body of this function with PHPMailer's send() call —
 * the function signature below can stay identical so nothing else changes.
 */
function send_mail(string $to, string $subject, string $htmlBody): bool
{
    if (SMTP_HOST !== '') {
        return smtp_send($to, $subject, $htmlBody);
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM . ">\r\n";

    return mail($to, $subject, $htmlBody, $headers);
}

function smtp_send(string $to, string $subject, string $htmlBody): bool
{
    $fp = @fsockopen((strpos(SMTP_HOST, 'ssl://') === 0 ? '' : 'tcp://') . SMTP_HOST, (int)SMTP_PORT, $errno, $errstr, 10);
    if (!$fp) return false;

    $read  = fn() => fgets($fp, 512);
    $write = function ($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };

    $read();
    $write('EHLO taskvel.app'); $read();
    $write('STARTTLS'); $read();
    stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    $write('EHLO taskvel.app'); $read();
    $write('AUTH LOGIN'); $read();
    $write(base64_encode(SMTP_USER)); $read();
    $write(base64_encode(SMTP_PASS)); $read();
    $write('MAIL FROM: <' . SMTP_FROM . '>'); $read();
    $write('RCPT TO: <' . $to . '>'); $read();
    $write('DATA'); $read();

    $msg  = "Subject: $subject\r\n";
    $msg .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $msg .= "To: <$to>\r\n";
    $msg .= "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n\r\n";
    $msg .= $htmlBody . "\r\n.";

    $write($msg); $read();
    $write('QUIT');
    fclose($fp);
    return true;
}

function send_invite_email(string $to, string $inviterName, string $taskTitle, string $token): bool
{
    $link = APP_URL . '/invite-accept.php?token=' . urlencode($token);
    $subject = "$inviterName invited you to collaborate on \"$taskTitle\" — Taskvel";
    $html = <<<HTML
    <div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:24px;border:1px solid #eee;border-radius:12px">
      <h2 style="margin:0 0 12px">You've been invited to Taskvel</h2>
      <p><strong>$inviterName</strong> shared the task <strong>"$taskTitle"</strong> with you.</p>
      <p style="margin:24px 0">
        <a href="$link" style="background:#0a0a0a;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;display:inline-block">
          Accept invite
        </a>
      </p>
      <p style="color:#888;font-size:13px">If you don't have a Taskvel account yet, you'll be able to create one from this link.</p>
    </div>
    HTML;

    return send_mail($to, $subject, $html);
}
