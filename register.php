<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user_id()) { header('Location: index.php'); exit; }
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try again.';
    } else {
        $res = register_user($_POST['name'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($res['ok']) {
            session_regenerate_id(true); // was missing — prevented a fixed pre-auth session ID from carrying over
            $_SESSION['user_id'] = $res['user_id'];
            $_SESSION['session_started_at'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['last_rotated_at'] = time();
            unset($_SESSION['csrf_token']);
            header('Location: index.php'); exit;
        }
        $error = $res['error'];
    }
}
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Taskvel — Sign up</title>
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
  <h1>Create your Taskvel account</h1>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="text" name="name" placeholder="Full name" required maxlength="190" autocomplete="name">
    <input type="email" name="email" placeholder="Email" required maxlength="190" autocomplete="email">
    <input type="password" name="password" placeholder="Password (min 8 chars)" required minlength="8" maxlength="200" autocomplete="new-password">
    <button type="submit">Sign up</button>
  </form>
  <p style="text-align:center;margin-top:14px;font-size:14px">Already have an account? <a href="login.php">Log in</a></p>
</div>
</body>
</html>
