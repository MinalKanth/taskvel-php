<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user_id()) { header('Location: index.php'); exit; }
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try again.';
    } else {
        $res = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($res['ok']) { header('Location: index.php'); exit; }
        $error = $res['error'];
    }
}
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Taskvel — Log in</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{font-family:-apple-system,Segoe UI,Arial,sans-serif;background:#f6f6f4;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
  .card{background:#fff;padding:32px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:360px;width:100%}
  h1{font-size:22px;margin:0 0 20px}
  input{width:100%;padding:10px;margin:6px 0 14px;border:1px solid #ddd;border-radius:8px;box-sizing:border-box}
  button{width:100%;padding:12px;background:#0a0a0a;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer}
  .err{color:#dc2626;font-size:14px;margin-bottom:10px}
  a{color:#2563eb;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <h1>Log in to Taskvel</h1>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="email" name="email" placeholder="Email" required maxlength="190" autocomplete="username">
    <input type="password" name="password" placeholder="Password" required maxlength="200" autocomplete="current-password">
    <button type="submit">Log in</button>
  </form>
  <p style="text-align:center;margin-top:14px;font-size:14px">No account? <a href="register.php">Sign up</a></p>
</div>
</body>
</html>
