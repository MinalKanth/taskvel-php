<?php
/**
 * Admin gate + shared helpers for /admin.
 * require_admin() must be the first call on every admin page — it layers a
 * role check on top of the normal session auth, and logs every denial to
 * the security audit trail.
 *
 * Note: current_user_role() now lives in auth.php (it's needed by
 * non-admin pages too, e.g. login.php's post-login redirect), so it's
 * no longer defined here — this file just require_once's auth.php and
 * uses it like everything else does.
 */
require_once __DIR__ . '/auth.php';

function require_admin(): void
{
    if (!current_user_id()) {
        header('Location: ../login.php');
        exit;
    }
    if (current_user_role() !== 'admin') {
        audit_log(current_user_id(), 'admin_access_denied', ['path' => $_SERVER['REQUEST_URI'] ?? '']);
        http_response_code(403);
        echo '<!DOCTYPE html><meta charset="utf-8"><title>403</title>
              <body style="font-family:Inter,sans-serif;background:#0A1128;color:#FAF8F3;display:grid;place-items:center;min-height:100vh;margin:0">
              <div style="text-align:center"><h1 style="font-size:56px;margin:0">403</h1>
              <p style="opacity:.7">This area is for administrators only.</p>
              <a href="../taskvel-pro.php" style="color:#E8C766">← Back to Taskvel</a></div>';
        exit;
    }
}

/** htmlspecialchars shorthand used across admin views */
function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** "2 hours ago" style timestamps for tables */
function time_ago(?string $dt): string
{
    if (!$dt) return '—';
    $diff = time() - strtotime($dt);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('j M Y', strtotime($dt));
}

/** Standard pagination: returns [limit, offset, page] from ?page= */
function paginate(int $perPage = 20): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    return [$perPage, ($page - 1) * $perPage, $page];
}