<?php
/**
 * Public, token-based approve/reject link — clicked from the "task awaiting
 * your approval" email. Deliberately does NOT require login, since the
 * report-to person might not even have a Taskvel account; the random
 * 24-byte token is the only credential, matching the pattern used by
 * invite-accept.php elsewhere in the app.
 *
 * IMPORTANT: the action only happens on POST (a real button click). GET
 * only ever renders a confirmation page — it never mutates anything. This
 * matters because GET requests to this URL can fire without the person
 * ever consciously clicking: email link-scanners (Outlook Safe Links,
 * corporate mail gateways), image/link preview fetchers, and chat-app
 * link unfurling all issue a GET the moment the email/message is
 * received or opened, not when a human clicks.
 */
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/security.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$decision = $_GET['decision'] ?? $_POST['decision'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($decision, ['approve', 'reject']) || !preg_match('/^[a-f0-9]{48}$/', $token)) {
    http_response_code(400);
    echo 'Invalid link.';
    exit;
}

// Cheap brute-force throttle per token — the 192-bit token already makes
// guessing infeasible, but this caps any single token from being hammered.
if (!rate_limit_check("approvetask:$token", 10, 3600)) {
    http_response_code(429);
    echo 'Too many attempts on this link. Please try again later.';
    exit;
}
rate_limit_hit("approvetask:$token", 3600);

$pdo = db();
$stmt = $pdo->prepare('SELECT wt.*, w.user_id, u.name AS employee_name, u.email AS employee_email
                        FROM workday_tasks wt
                        JOIN workdays w ON w.id = wt.workday_id
                        JOIN users u ON u.id = w.user_id
                        WHERE wt.approval_token = ?');
$stmt->execute([$token]);
$task = $stmt->fetch();

if (!$task) {
    http_response_code(404);
    echo 'This approval link is invalid or has already been used.';
    exit;
}

if ($task['approval_status'] !== 'pending') {
    echo '<div style="font-family:Arial,sans-serif;max-width:420px;margin:60px auto;text-align:center;color:#555">'
       . 'This task was already ' . htmlspecialchars($task['approval_status']) . '.</div>';
    exit;
}

// ---- GET: show a confirmation page only. No database writes happen here. ----
if ($method !== 'POST') {
    $verb = $decision === 'approve' ? 'approve' : 'send back';
    $color = $decision === 'approve' ? '#059669' : '#dc2626';
    ?>
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><title>Taskvel</title>
    <style>body{font-family:-apple-system,Arial,sans-serif;background:#f6f6f4;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
    .card{background:#fff;padding:36px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:400px;text-align:center}
    h1{font-size:19px;margin:0 0 10px}p{color:#666;font-size:14px;line-height:1.5}
    button{margin-top:18px;padding:12px 26px;border:none;border-radius:10px;background:<?= $color ?>;color:#fff;font-weight:700;font-size:14.5px;cursor:pointer}
    button:hover{opacity:.92}</style></head>
    <body><div class="card">
    <h1>Confirm: <?= $decision === 'approve' ? '✓ Approve task' : '↩ Send task back' ?></h1>
    <p>You're about to <strong><?= htmlspecialchars($verb) ?></strong> "<strong><?= htmlspecialchars($task['title']) ?></strong>" from <?= htmlspecialchars($task['employee_name']) ?>.</p>
    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <input type="hidden" name="decision" value="<?= htmlspecialchars($decision) ?>">
        <button type="submit"><?= $decision === 'approve' ? 'Confirm approval' : 'Confirm send back' ?></button>
    </form>
    </div></body></html>
    <?php
    exit;
}

// ---- POST: this is a real, deliberate click. Perform the action. ----
if ($decision === 'approve') {
    $pdo->prepare("UPDATE workday_tasks SET status = 'done', approval_status = 'approved' WHERE id = ?")->execute([$task['id']]);
    $message = 'approved ✓';
} else {
    $pdo->prepare("UPDATE workday_tasks SET status = 'in_progress', approval_status = 'rejected', completed_at = NULL WHERE id = ?")->execute([$task['id']]);
    $message = 'sent back for more work';
}

// Let the employee know the outcome.
$subject = "Your task was $message — \"{$task['title']}\"";
$html = "<div style=\"font-family:Arial,sans-serif;max-width:460px;margin:auto;padding:24px;border:1px solid #eee;border-radius:12px\">"
      . "<h2 style=\"margin:0 0 12px\">Task $message</h2>"
      . "<p>\"{$task['title']}\" was just <strong>$message</strong>.</p></div>";
try { send_mail($task['employee_email'], $subject, $html); } catch (Throwable $e) {}

?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Taskvel</title>
<style>body{font-family:-apple-system,Arial,sans-serif;background:#f6f6f4;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.card{background:#fff;padding:36px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:380px;text-align:center}
h1{font-size:20px;margin:0 0 10px}p{color:#666;font-size:14px}</style></head>
<body><div class="card">
<h1><?= $decision === 'approve' ? '✓ Task approved' : '↩ Sent back to ' . htmlspecialchars($task['employee_name']) ?></h1>
<p>"<?= htmlspecialchars($task['title']) ?>" has been <?= htmlspecialchars($message) ?>.</p>
</div></body></html>