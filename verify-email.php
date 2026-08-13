<?php
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';
$res = verify_email_token($token);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify email — Taskvel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{font-family:-apple-system,Segoe UI,Arial,sans-serif;background:#f6f6f4;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
  .card{background:#fff;padding:32px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:380px;width:100%;text-align:center}
  a.btn{display:inline-block;margin-top:14px;padding:12px 20px;background:#0a0a0a;color:#fff;border-radius:8px;text-decoration:none;font-weight:600}
</style>
</head>
<body>
<div class="card">
  <?php if ($res['ok']): ?>
    <h2>✅ Email verified</h2>
    <p><?= !empty($res['already_verified']) ? 'This email was already verified.' : 'Your email has been confirmed.' ?></p>
    <a class="btn" href="login.php">Log in</a>
  <?php else: ?>
    <h2>Verification failed</h2>
    <p><?= htmlspecialchars($res['error']) ?></p>
    <a class="btn" href="login.php">Back to log in</a>
  <?php endif; ?>
</div>
</body>
</html>