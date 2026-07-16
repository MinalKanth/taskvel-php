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

// function smtp_send(string $to, string $subject, string $htmlBody): bool
// {
//     $fp = @fsockopen((strpos(SMTP_HOST, 'ssl://') === 0 ? '' : 'tcp://') . SMTP_HOST, (int)SMTP_PORT, $errno, $errstr, 10);
//     if (!$fp) return false;

//     $read  = fn() => fgets($fp, 512);
//     $write = function ($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };

//     $read();
//     $write('EHLO taskvel.app'); $read();
//     $write('STARTTLS'); $read();
//     stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
//     $write('EHLO taskvel.app'); $read();
//     $write('AUTH LOGIN'); $read();
//     $write(base64_encode(SMTP_USER)); $read();
//     $write(base64_encode(SMTP_PASS)); $read();
//     $write('MAIL FROM: <' . SMTP_FROM . '>'); $read();
//     $write('RCPT TO: <' . $to . '>'); $read();
//     $write('DATA'); $read();

//     $msg  = "Subject: $subject\r\n";
//     $msg .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
//     $msg .= "To: <$to>\r\n";
//     $msg .= "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n\r\n";
//     $msg .= $htmlBody . "\r\n.";

//     $write($msg); $read();
//     $write('QUIT');
//     fclose($fp);
//     return true;
// }
// function smtp_send(string $to, string $subject, string $htmlBody): bool
// {

// echo "i am sending mail";
// exit();
//     $fp = @fsockopen((strpos(SMTP_HOST, 'ssl://') === 0 ? '' : 'tcp://') . SMTP_HOST, (int)SMTP_PORT, $errno, $errstr, 10);
//     if (!$fp) {
//         error_log("SMTP connect failed: $errstr ($errno)");
//         return false;
//     }

//     $read  = fn() => fgets($fp, 512);
//     // Checks the SMTP reply code; logs and bails out on unexpected codes.
//     $expect = function ($code, $label) use ($read) {
//         $resp = $read();
//         if (strpos($resp, (string)$code) !== 0) {
//             error_log("SMTP step '$label' failed: $resp");
//             return false;
//         }
//         return true;
//     };
//     $write = function ($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };

//     $read(); // greeting (220)
//     $write('EHLO taskvel.app');
//     if (!$expect(250, 'EHLO')) { fclose($fp); return false; }

//     $write('STARTTLS');
//     if (!$expect(220, 'STARTTLS')) { fclose($fp); return false; }
//     if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
//         error_log('SMTP TLS handshake failed');
//         fclose($fp); return false;
//     }

//     $write('EHLO taskvel.app');
//     if (!$expect(250, 'EHLO2')) { fclose($fp); return false; }

//     $write('AUTH LOGIN');
//     if (!$expect(334, 'AUTH LOGIN')) { fclose($fp); return false; }

//     $write(base64_encode(SMTP_USER));
//     if (!$expect(334, 'AUTH USER')) { fclose($fp); return false; }

//     $write(base64_encode(SMTP_PASS));
//     if (!$expect(235, 'AUTH PASS')) { fclose($fp); return false; } // this is the one that fails on a bad/revoked app password

//     $write('MAIL FROM: <' . SMTP_FROM . '>');
//     if (!$expect(250, 'MAIL FROM')) { fclose($fp); return false; }

//     $write('RCPT TO: <' . $to . '>');
//     if (!$expect(250, 'RCPT TO')) { fclose($fp); return false; }

//     $write('DATA');
//     if (!$expect(354, 'DATA')) { fclose($fp); return false; }

//     $msg  = "Subject: $subject\r\n";
//     $msg .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
//     $msg .= "To: <$to>\r\n";
//     $msg .= "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n\r\n";
//     $msg .= $htmlBody . "\r\n.";

//     $write($msg);
//     $ok = $expect(250, 'DATA body');

//     $write('QUIT');
//     fclose($fp);
//     return $ok;
// }
function smtp_send(string $to, string $subject, string $htmlBody): bool
{
    $fp = @fsockopen((strpos(SMTP_HOST, 'ssl://') === 0 ? '' : 'tcp://') . SMTP_HOST, (int)SMTP_PORT, $errno, $errstr, 10);
    if (!$fp) {
        error_log("SMTP connect failed: $errstr ($errno)");
        return false;
    }

    // Reads a FULL response, including multi-line replies (e.g. EHLO's
    // capability list). SMTP marks the last line of a multi-line reply with
    // a space after the code ("250 ...") instead of a dash ("250-...").
    // Reading only one line, as the original code did, leaves trailing
    // lines buffered and desyncs every command that follows.
    $readFull = function () use ($fp) {
        $data = '';
        do {
            $line = fgets($fp, 512);
            if ($line === false) break;
            $data .= $line;
        } while (isset($line[3]) && $line[3] === '-');
        return $data;
    };

    $expect = function ($code, $label) use ($readFull) {
        $resp = $readFull();
        if (strpos($resp, (string)$code) !== 0) {
            error_log("SMTP step '$label' failed: $resp");
            return false;
        }
        return true;
    };
    $write = function ($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };

    $readFull(); // greeting (220)

    $write('EHLO taskvel.app');
    if (!$expect(250, 'EHLO')) { fclose($fp); return false; }

    $write('STARTTLS');
    if (!$expect(220, 'STARTTLS')) { fclose($fp); return false; }

    if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        error_log('SMTP TLS handshake failed');
        fclose($fp); return false;
    }

    $write('EHLO taskvel.app');
    if (!$expect(250, 'EHLO2')) { fclose($fp); return false; }

    $write('AUTH LOGIN');
    if (!$expect(334, 'AUTH LOGIN')) { fclose($fp); return false; }

    $write(base64_encode(SMTP_USER));
    if (!$expect(334, 'AUTH USER')) { fclose($fp); return false; }

    $write(base64_encode(SMTP_PASS));
    if (!$expect(235, 'AUTH PASS')) { fclose($fp); return false; }

    $write('MAIL FROM: <' . SMTP_FROM . '>');
    if (!$expect(250, 'MAIL FROM')) { fclose($fp); return false; }

    $write('RCPT TO: <' . $to . '>');
    if (!$expect(250, 'RCPT TO')) { fclose($fp); return false; }

    $write('DATA');
    if (!$expect(354, 'DATA')) { fclose($fp); return false; }

    $msg  = "Subject: $subject\r\n";
    $msg .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $msg .= "To: <$to>\r\n";
    $msg .= "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n\r\n";
    $msg .= $htmlBody . "\r\n.";

    $write($msg);
    $ok = $expect(250, 'DATA body');

    $write('QUIT');
    fclose($fp);
    return $ok;
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

function send_password_reset_email(string $to, string $name, string $token): bool
{
    $link = APP_URL . '/reset-password.php?token=' . urlencode($token);
    $subject = 'Reset your Taskvel password';
    $html = <<<HTML
    <div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:24px;border:1px solid #eee;border-radius:12px">
      <h2 style="margin:0 0 12px">Reset your password</h2>
      <p>Hi $name, we received a request to reset your Taskvel password.</p>
      <p style="margin:24px 0">
        <a href="$link" style="background:#0a0a0a;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;display:inline-block">
          Reset password
        </a>
      </p>
      <p style="color:#888;font-size:13px">This link expires in 1 hour. If you didn't request this, you can safely ignore this email.</p>
    </div>
    HTML;

    return send_mail($to, $subject, $html);
}