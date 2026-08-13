<?php
require_once __DIR__ . '/includes/auth.php';

$error = null;
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please go back and try again.';
    } else {
        $email = $_POST['email'] ?? '';
        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            $res = resend_verification_email($email);
            if ($res['ok']) { $sent = true; } else { $error = $res['error']; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Resend verification — Taskvel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{font-family:-apple-system,Segoe UI,Arial,sans-serif;background:#f6f6f4;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
  .card{background:#fff;padding:32px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:380px;width:100%;text-align:center}
  a.btn{display:inline-block;margin-top:14px;padding:12px 20px;background:#0a0a0a;color:#fff;border-radius:8px;text-decoration:none;font-weight:600}
  .err{color:#dc2626;font-size:14px}
</style>
</head>
<body>
<div class="card">
  <?php if ($sent): ?>
    <h2>📧 Check your inbox</h2>
    <p>If that account needs verifying, a new link is on its way.</p>
  <?php else: ?>
    <h2>Couldn't resend</h2>
    <p class="err"><?= htmlspecialchars($error ?? 'Something went wrong.') ?></p>
  <?php endif; ?>
  <a class="btn" href="login.php">Back to log in</a>
</div>
</body>
</html>