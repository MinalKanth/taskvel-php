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

/**
 * Shared HTML shell for all Taskvel emails.
 * Table-based layout with inline styles for maximum email-client
 * compatibility (Outlook, Gmail, Apple Mail, etc. all strip <style> blocks
 * or mangle flexbox/grid, so everything here is deliberately old-school).
 *
 * $accentFrom / $accentTo control the header gradient so each email type
 * (invite vs. reset) gets its own subtle identity while staying on-brand.
 */
function email_shell(string $preheader, string $badgeLabel, string $heading, string $bodyHtml, string $ctaLabel, string $ctaLink, string $footerNote, string $accentFrom = '#6366f1', string $accentTo = '#8b5cf6'): string
{
    $year = date('Y');
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Taskvel</title>
    </head>
    <body style="margin:0;padding:0;background-color:#f4f4f8;-webkit-text-size-adjust:100%;">
      <!-- Preheader (hidden preview text) -->
      <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;color:#f4f4f8;line-height:1px;">
        {$preheader}
      </div>

      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f8;padding:32px 16px;">
        <tr>
          <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(20,20,43,0.08);">

              <!-- Gradient header -->
              <tr>
                <td style="background:linear-gradient(135deg,{$accentFrom} 0%,{$accentTo} 100%);padding:36px 40px;text-align:center;">
                  <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 16px;">
                    <tr>
                      <td style="width:48px;height:48px;background-color:rgba(255,255,255,0.18);border-radius:12px;text-align:center;vertical-align:middle;font-size:24px;line-height:48px;">
                        ✅
                      </td>
                    </tr>
                  </table>
                  <div style="font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#ffffff;font-size:20px;font-weight:700;letter-spacing:0.3px;">
                    Taskvel
                  </div>
                </td>
              </tr>

              <!-- Body -->
              <tr>
                <td style="padding:40px 40px 8px;">
                  <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                    <tr>
                      <td style="background-color:#f0f0ff;color:{$accentFrom};font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.6px;text-transform:uppercase;padding:6px 12px;border-radius:999px;">
                        {$badgeLabel}
                      </td>
                    </tr>
                  </table>

                  <h1 style="margin:0 0 16px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:24px;line-height:1.3;color:#141425;font-weight:700;">
                    {$heading}
                  </h1>

                  <div style="font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;color:#4b4b5c;">
                    {$bodyHtml}
                  </div>

                  <!-- CTA button -->
                  <table role="presentation" cellpadding="0" cellspacing="0" style="margin:32px 0 8px;">
                    <tr>
                      <td style="border-radius:10px;background:linear-gradient(135deg,{$accentFrom} 0%,{$accentTo} 100%);box-shadow:0 6px 16px rgba(99,102,241,0.35);">
                        <a href="{$ctaLink}" target="_blank" style="display:inline-block;padding:14px 32px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">
                          {$ctaLabel} &rarr;
                        </a>
                      </td>
                    </tr>
                  </table>

                  <!-- Fallback link -->
                  <p style="margin:20px 0 0;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.5;color:#a0a0b0;word-break:break-all;">
                    Button not working? Copy and paste this link into your browser:<br>
                    <a href="{$ctaLink}" style="color:{$accentFrom};text-decoration:underline;">{$ctaLink}</a>
                  </p>
                </td>
              </tr>

              <tr>
                <td style="padding:8px 40px 32px;">
                  <div style="border-top:1px solid #eeeef2;padding-top:20px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.6;color:#9a9aab;">
                    {$footerNote}
                  </div>
                </td>
              </tr>

              <!-- Footer -->
              <tr>
                <td style="background-color:#fafafc;padding:24px 40px;text-align:center;">
                  <p style="margin:0;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;color:#b0b0c0;">
                    &copy; {$year} Taskvel &middot; Made for teams who get things done
                  </p>
                </td>
              </tr>

            </table>
          </td>
        </tr>
      </table>
    </body>
    </html>
    HTML;
}

function send_invite_email(string $to, string $inviterName, string $taskTitle, string $token): bool
{
    $link = APP_URL . '/invite-accept.php?token=' . urlencode($token);
    $subject = "$inviterName invited you to collaborate on \"$taskTitle\" — Taskvel";

    $body = <<<HTML
      <p style="margin:0 0 14px;"><strong style="color:#141425;">{$inviterName}</strong> just invited you to collaborate on:</p>
      <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f7f7fb;border-left:3px solid #6366f1;border-radius:8px;margin:0 0 4px;">
        <tr>
          <td style="padding:14px 18px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;font-weight:600;color:#141425;">
            📋 {$taskTitle}
          </td>
        </tr>
      </table>
    HTML;

    $html = email_shell(
        preheader: "$inviterName invited you to collaborate on \"$taskTitle\" on Taskvel",
        badgeLabel: 'Team Invite',
        heading: "You've been invited 🎉",
        bodyHtml: $body,
        ctaLabel: 'Accept invite',
        ctaLink: $link,
        footerNote: "Don't have a Taskvel account yet? No problem — you'll be able to create one right from this link. If you weren't expecting this invite, you can safely ignore this email.",
        accentFrom: '#6366f1',
        accentTo: '#8b5cf6'
    );

    return send_mail($to, $subject, $html);
}

/**
 * Sent right after a successful registration. Uses a slightly richer shell
 * than email_shell() (feature grid + bigger hero) since this is the very
 * first impression a new user gets of Taskvel Pro.
 */
function send_welcome_email(string $to, string $name): bool
{
    $link = rtrim(APP_URL, '/') . '/taskvel-pro.php';
    $subject = "Welcome to Taskvel Pro, $name! 🎉";
    $year = date('Y');
    $firstName = trim(explode(' ', $name)[0] ?? $name) ?: $name;

    $features = [
        ['icon' => '⚡', 'title' => 'Smart task ranking', 'desc' => 'Set urgency & impact — Taskvel ranks what matters most, automatically.'],
        ['icon' => '⏱️', 'title' => 'Pomodoro focus timer', 'desc' => 'Stay locked in with built-in focus sessions and daily focus history.'],
        ['icon' => '📊', 'title' => 'Premium analytics', 'desc' => 'Track streaks, productivity score, and weekly reviews at a glance.'],
        ['icon' => '👥', 'title' => 'Team collaboration', 'desc' => 'Share tasks, assign projects, and plan events with your team.'],
        ['icon' => '🔄', 'title' => 'Recurring & synced', 'desc' => 'Repeatable tasks, tags, deadlines — synced across every device.'],
        ['icon' => '📁', 'title' => 'Compliance tools', 'desc' => 'GST, EPF, ESIC trackers, payroll & invoicing, built right in.'],
    ];

    $featureRows = '';
    foreach ($features as $f) {
        $featureRows .= <<<HTML
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
              <tr>
                <td style="width:44px;vertical-align:top;padding-top:2px;">
                  <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="width:36px;height:36px;background-color:#f0f0ff;border-radius:10px;text-align:center;vertical-align:middle;font-size:17px;line-height:36px;">
                        {$f['icon']}
                      </td>
                    </tr>
                  </table>
                </td>
                <td style="vertical-align:top;padding-left:14px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">
                  <div style="font-size:14px;font-weight:700;color:#141425;margin-bottom:2px;">{$f['title']}</div>
                  <div style="font-size:13px;line-height:1.5;color:#6b6b7c;">{$f['desc']}</div>
                </td>
              </tr>
            </table>
        HTML;
    }

    $html = <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Welcome to Taskvel Pro</title>
    </head>
    <body style="margin:0;padding:0;background-color:#f4f4f8;-webkit-text-size-adjust:100%;">
      <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;color:#f4f4f8;line-height:1px;">
        Welcome to Taskvel Pro, {$firstName}! Here's what you can do now.
      </div>

      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f8;padding:32px 16px;">
        <tr>
          <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:540px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(20,20,43,0.08);">

              <!-- Hero -->
              <tr>
                <td style="background:linear-gradient(135deg,#0A1128 0%,#0F4436 60%,#0A1128 100%);padding:44px 40px;text-align:center;">
                  <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 18px;">
                    <tr>
                      <td style="width:56px;height:56px;background:linear-gradient(135deg,#E8C766,#C9A227);border-radius:14px;text-align:center;vertical-align:middle;font-size:26px;font-weight:800;line-height:56px;color:#0A1128;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">
                        T
                      </td>
                    </tr>
                  </table>
                  <div style="font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#ffffff;font-size:24px;font-weight:800;letter-spacing:0.2px;margin-bottom:6px;">
                    Welcome to Taskvel Pro 🎉
                  </div>
                  <div style="font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#C9A227;font-size:13px;font-weight:600;letter-spacing:0.4px;">
                    by Samal Consultancy · Est. 1993
                  </div>
                </td>
              </tr>

              <!-- Greeting -->
              <tr>
                <td style="padding:36px 40px 6px;">
                  <p style="margin:0 0 8px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.6;color:#141425;">
                    Hi <strong>{$firstName}</strong>,
                  </p>
                  <p style="margin:0 0 26px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.65;color:#4b4b5c;">
                    Your account is live! Taskvel Pro is your fast, focused workspace for ranking tasks, running focus sessions, and getting things done — free to start, premium features included from day one. Here's a quick look at what's waiting for you:
                  </p>
                </td>
              </tr>

              <!-- Feature grid -->
              <tr>
                <td style="padding:0 40px 10px;">
                  {$featureRows}
                </td>
              </tr>

              <!-- CTA -->
              <tr>
                <td style="padding:14px 40px 8px;text-align:center;">
                  <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                    <tr>
                      <td style="border-radius:10px;background:linear-gradient(135deg,#C9A227,#E8C766);box-shadow:0 6px 16px rgba(201,162,39,0.35);">
                        <a href="{$link}" target="_blank" style="display:inline-block;padding:14px 34px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;font-weight:700;color:#0A1128;text-decoration:none;border-radius:10px;">
                          Open Taskvel Pro &rarr;
                        </a>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>

              <tr>
                <td style="padding:8px 40px 32px;">
                  <p style="margin:20px 0 0;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.5;color:#a0a0b0;text-align:center;">
                    Button not working? Paste this into your browser:<br>
                    <a href="{$link}" style="color:#C9A227;text-decoration:underline;">{$link}</a>
                  </p>
                </td>
              </tr>

              <!-- Footer -->
              <tr>
                <td style="background-color:#fafafc;padding:24px 40px;text-align:center;">
                  <p style="margin:0 0 4px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;color:#b0b0c0;">
                    &copy; {$year} Taskvel &middot; A product of Samal Consultancy
                  </p>
                  <p style="margin:0;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:11px;color:#c4c4d0;">
                    Questions? Just reply to this email — a real person reads it.
                  </p>
                </td>
              </tr>

            </table>
          </td>
        </tr>
      </table>
    </body>
    </html>
    HTML;

    return send_mail($to, $subject, $html);
}

function send_password_reset_email(string $to, string $name, string $token): bool
{
    $link = APP_URL . '/reset-password.php?token=' . urlencode($token);
    $subject = 'Reset your Taskvel password';

    $body = <<<HTML
      <p style="margin:0 0 14px;">Hi <strong style="color:#141425;">{$name}</strong>, we received a request to reset your Taskvel password.</p>
      <p style="margin:0;">Click the button below to choose a new one. For your security, this link will expire soon.</p>
    HTML;

    $html = email_shell(
        preheader: 'Reset your Taskvel password — this link expires in 1 hour',
        badgeLabel: 'Security',
        heading: 'Reset your password 🔒',
        bodyHtml: $body,
        ctaLabel: 'Reset password',
        ctaLink: $link,
        footerNote: '⏱️ This link expires in <strong>1 hour</strong>. If you didn\'t request a password reset, you can safely ignore this email — your password will stay the same.',
        accentFrom: '#f97316',
        accentTo: '#ec4899'
    );

    return send_mail($to, $subject, $html);
}

function send_contact_notification_email(string $to, string $name, string $phone, string $email, string $message): bool
{
    $subject = "New website enquiry from $name";
 
    $safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safePhone   = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $safeEmail   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
 
    $body = <<<HTML
      <p style="margin:0 0 14px;">You've received a new enquiry through the Samal Consultancy website contact form:</p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f7fb;border-left:3px solid #C9A227;border-radius:8px;margin:0 0 16px;">
        <tr>
          <td style="padding:14px 18px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.7;color:#141425;">
            <strong>Name:</strong> {$safeName}<br>
            <strong>Phone:</strong> {$safePhone}<br>
            <strong>Email:</strong> {$safeEmail}
          </td>
        </tr>
      </table>
      <p style="margin:0 0 6px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-weight:700;color:#141425;">Message:</p>
      <p style="margin:0;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">{$safeMessage}</p>
    HTML;
 
    $html = email_shell(
        preheader: "New website enquiry from $name",
        badgeLabel: 'Website Enquiry',
        heading: 'New contact form submission 📩',
        bodyHtml: $body,
        ctaLabel: 'Reply by email',
        ctaLink: 'mailto:' . $email,
        footerNote: 'This message was submitted through the "Get in Touch" form on samalconsultancy.com.',
        accentFrom: '#0F4436',
        accentTo: '#C9A227'
    );
 
    return send_mail($to, $subject, $html);
}
/**
 * Sent to a task's "senior" (creator, or the team owner if the creator is
 * no longer reachable) when a team task's assignee submits a progress
 * update. Respects the recipient's notify_task_updates_email preference —
 * callers should check that before calling this (kept as a plain send
 * function here so it stays easy to reuse for a future digest/batch mode).
 */
function send_task_update_email(string $to, string $recipientName, string $updaterName, string $taskTitle, string $statusTo, int $progressTo, ?string $notes, string $link): bool
{
    $subject = "$updaterName updated \"$taskTitle\" — Taskvel";
    $safeNotes = $notes ? nl2br(htmlspecialchars($notes)) : '<em style="color:#a0a0b0;">No notes added.</em>';
    $statusLabel = ucwords(str_replace('_', ' ', $statusTo));

    $body = <<<HTML
      <p style="margin:0 0 14px;"><strong style="color:#141425;">{$updaterName}</strong> posted a progress update on:</p>
      <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f7f7fb;border-left:3px solid #6366f1;border-radius:8px;margin:0 0 16px;">
        <tr>
          <td style="padding:14px 18px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">
            <div style="font-size:15px;font-weight:600;color:#141425;margin-bottom:8px;">📋 {$taskTitle}</div>
            <div style="font-size:13px;color:#4b4b5c;">Status: <strong>{$statusLabel}</strong> &middot; Progress: <strong>{$progressTo}%</strong></div>
          </td>
        </tr>
      </table>
      <p style="margin:0 0 6px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-weight:700;color:#141425;">Notes:</p>
      <p style="margin:0;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">{$safeNotes}</p>
    HTML;

    $html = email_shell(
        preheader: "$updaterName updated \"$taskTitle\" to $progressTo% complete",
        badgeLabel: 'Task Update',
        heading: "Progress update: $statusLabel",
        bodyHtml: $body,
        ctaLabel: 'View task',
        ctaLink: $link,
        footerNote: 'You\'re receiving this because you created this task or own the team. You can turn off task-update emails anytime in Settings.',
        accentFrom: '#0ea5a4',
        accentTo: '#22c55e'
    );

    return send_mail($to, $subject, $html);
}

/**
 * Sent to a brand-new account Taskvel just created on an organization's
 * behalf (Feature 4, Part B — "Employee Provisioning: New Users").
 */
function send_org_onboarding_email(string $to, string $name, string $orgName, string $loginUrl, string $tempPassword): bool
{
    $subject = "Welcome to Taskvel Pro — you've been added to $orgName";
    $safeName = htmlspecialchars($name);
    $safeOrg = htmlspecialchars($orgName);
    $safePw = htmlspecialchars($tempPassword);

    $body = <<<HTML
      <p style="margin:0 0 14px;">Hi {$safeName},</p>
      <p style="margin:0 0 14px;">Your organization, <strong>{$safeOrg}</strong>, has created a Taskvel Pro account for you.</p>
      <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f7f7fb;border-radius:8px;margin:0 0 16px;">
        <tr><td style="padding:16px 18px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:14px;">
          <div style="margin-bottom:8px;"><strong>Login email:</strong> {$to}</div>
          <div><strong>Temporary password:</strong> <code style="background:#fff;padding:2px 8px;border-radius:4px;">{$safePw}</code></div>
        </td></tr>
      </table>
      <p style="margin:0 0 14px;">For security reasons, we recommend changing your password after your first login. You may continue using the temporary password if you prefer, but updating it is strongly recommended.</p>
    HTML;

    $html = email_shell(
        preheader: "$orgName added you to Taskvel Pro",
        badgeLabel: 'Welcome',
        heading: 'Welcome to Taskvel Pro 🎉',
        bodyHtml: $body,
        ctaLabel: 'Log in now',
        ctaLink: $loginUrl,
        footerNote: 'This account was created by an administrator at ' . $orgName . '.',
        accentFrom: '#6366f1',
        accentTo: '#8b5cf6'
    );

    return send_mail($to, $subject, $html);
}

/**
 * Sent to an *existing* Taskvel user when an organization assigns them a
 * seat (Feature 4, Part B — "Existing Users").
 */
function send_org_added_email(string $to, string $name, string $orgName, string $link): bool
{
    $subject = "You've been added to $orgName on Taskvel Pro";
    $safeName = htmlspecialchars($name);
    $safeOrg = htmlspecialchars($orgName);

    $body = <<<HTML
      <p style="margin:0 0 14px;">Hi {$safeName},</p>
      <p style="margin:0;"><strong>{$safeOrg}</strong> has added your existing Taskvel account to their organization and upgraded it to Taskvel Pro — no action needed on your part.</p>
    HTML;

    $html = email_shell(
        preheader: "$orgName upgraded your account to Taskvel Pro",
        badgeLabel: 'Organization',
        heading: "You're now on $orgName's Taskvel Pro plan",
        bodyHtml: $body,
        ctaLabel: 'Open Taskvel',
        ctaLink: $link,
        footerNote: 'You can see your organization membership anytime from Billing & Plan.',
        accentFrom: '#6366f1',
        accentTo: '#8b5cf6'
    );

    return send_mail($to, $subject, $html);
}

/**
 * Feature 4, Part A trial reminders — 7/3/1 days before expiry, and on
 * expiry itself.
 */
function send_trial_reminder_email(string $to, string $name, string $milestone, string $link): bool
{
    $safeName = htmlspecialchars($name);
    $copy = [
        '7d' => ['subject' => 'Your Taskvel Pro trial ends in 7 days', 'heading' => '7 days left on your trial', 'body' => 'Your 30-day Taskvel Pro trial wraps up in a week. Upgrade any time to keep unlimited teams, seats, and projects.'],
        '3d' => ['subject' => 'Your Taskvel Pro trial ends in 3 days', 'heading' => '3 days left on your trial', 'body' => 'Just a heads-up — your trial ends in 3 days. Upgrade now so nothing interrupts your teams.'],
        '1d' => ['subject' => 'Your Taskvel Pro trial ends tomorrow', 'heading' => 'Your trial ends tomorrow', 'body' => 'Last call — your Taskvel Pro trial ends tomorrow. After that, free-plan limits apply until you upgrade.'],
        'expired' => ['subject' => 'Your Taskvel Pro trial has ended', 'heading' => 'Your trial has ended', 'body' => 'Your 30-day Taskvel Pro trial has ended and your account is now on the free plan. Upgrade any time to get Pro features back.'],
    ][$milestone];

    $body = <<<HTML
      <p style="margin:0 0 14px;">Hi {$safeName},</p>
      <p style="margin:0;">{$copy['body']}</p>
    HTML;

    $html = email_shell(
        preheader: $copy['heading'],
        badgeLabel: 'Trial',
        heading: $copy['heading'],
        bodyHtml: $body,
        ctaLabel: 'Upgrade to Pro',
        ctaLink: $link,
        footerNote: 'This is an automated reminder about your Taskvel Pro trial.',
        accentFrom: '#f59e0b',
        accentTo: '#ef4444'
    );

    return send_mail($to, $copy['subject'], $html);
}

/**
 * The "send my end-of-day report" email — sent to a manager, any number of
 * teammates, and/or arbitrary custom addresses selected at checkout.
 * Deliberately built on the same email_shell() design system as every other
 * Taskvel email (Task Update, Welcome, etc.) rather than the older ad-hoc
 * inline-HTML the daily Check-in summary used previously, so it reads as
 * part of one consistent, premium product rather than a bolted-on extra.
 */
function send_daily_report_email(string $to, string $userName, string $dateLabel, array $summary, array $tasks, ?string $notes, string $link): bool
{
    $subject = "$userName's daily report — $dateLabel";

    $rows = '';
    foreach ($tasks as $t) {
        $statusLabel = ['pending' => 'Pending', 'in_progress' => 'In progress', 'pending_approval' => 'Awaiting approval', 'done' => 'Done'][$t['status']] ?? ucfirst($t['status']);
        $dotColor = ['pending' => '#a0a0b0', 'in_progress' => '#f59e0b', 'pending_approval' => '#6366f1', 'done' => '#22c55e'][$t['status']] ?? '#a0a0b0';
        $durText = $t['duration_seconds'] ? gmdate('H\h i\m', (int)$t['duration_seconds']) : '—';
        $safeTitle = htmlspecialchars($t['title']);
        $rows .= <<<HTML
          <tr>
            <td style="padding:10px 8px;border-bottom:1px solid #eee;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:13.5px;">
              <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{$dotColor};margin-right:8px;"></span>{$safeTitle}
            </td>
            <td style="padding:10px 8px;border-bottom:1px solid #eee;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:13px;color:#4b4b5c;">{$statusLabel}</td>
            <td style="padding:10px 8px;border-bottom:1px solid #eee;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:13px;color:#4b4b5c;text-align:right;">{$durText}</td>
          </tr>
        HTML;
    }
    if ($rows === '') {
        $rows = '<tr><td colspan="3" style="padding:14px 8px;color:#a0a0b0;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:13px;">No tasks logged today.</td></tr>';
    }

    $overtimeChip = $summary['overtime_text']
        ? "<span style=\"background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;margin-left:6px;\">+{$summary['overtime_text']} overtime</span>"
        : '';
    $notesHtml = $notes
        ? '<p style="margin:16px 0 0;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:13.5px;"><strong>Notes:</strong> ' . nl2br(htmlspecialchars($notes)) . '</p>'
        : '';

    $body = <<<HTML
      <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 18px;">
        <tr>
          <td style="text-align:center;padding:10px;background:#f7f7fb;border-radius:10px;">
            <div style="font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:22px;font-weight:800;color:#141425;">{$summary['done']}</div>
            <div style="font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:11px;color:#8a8a9a;text-transform:uppercase;letter-spacing:.03em;">Completed</div>
          </td>
          <td style="width:10px;"></td>
          <td style="text-align:center;padding:10px;background:#f7f7fb;border-radius:10px;">
            <div style="font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:22px;font-weight:800;color:#141425;">{$summary['worked_text']}</div>
            <div style="font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:11px;color:#8a8a9a;text-transform:uppercase;letter-spacing:.03em;">Worked{$overtimeChip}</div>
          </td>
          <td style="width:10px;"></td>
          <td style="text-align:center;padding:10px;background:#f7f7fb;border-radius:10px;">
            <div style="font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:22px;font-weight:800;color:#141425;">{$summary['pending_approval']}</div>
            <div style="font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:11px;color:#8a8a9a;text-transform:uppercase;letter-spacing:.03em;">Awaiting approval</div>
          </td>
        </tr>
      </table>
      <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
        <tr style="text-align:left;">
          <th style="padding:8px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:11px;color:#8a8a9a;text-transform:uppercase;letter-spacing:.03em;border-bottom:2px solid #eee;">Task</th>
          <th style="padding:8px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:11px;color:#8a8a9a;text-transform:uppercase;letter-spacing:.03em;border-bottom:2px solid #eee;">Status</th>
          <th style="padding:8px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:11px;color:#8a8a9a;text-transform:uppercase;letter-spacing:.03em;border-bottom:2px solid #eee;text-align:right;">Time</th>
        </tr>
        {$rows}
      </table>
      {$notesHtml}
    HTML;

    $html = email_shell(
        preheader: "{$summary['done']} done · {$summary['worked_text']} worked — {$userName}'s report for {$dateLabel}",
        badgeLabel: 'Daily Report',
        heading: "{$userName}'s report — {$dateLabel}",
        bodyHtml: $body,
        ctaLabel: 'Open Manager Dashboard',
        ctaLink: $link,
        footerNote: "Sent from Taskvel's Daily Check-in — {$userName} chose to send you this end-of-day report.",
        accentFrom: '#6366f1',
        accentTo: '#22c55e'
    );

    return send_mail($to, $subject, $html);
}