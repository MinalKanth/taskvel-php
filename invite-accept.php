<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$token = $_GET['token'] ?? '';

if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
    http_response_code(400);
    echo '<p style="font-family:sans-serif">This invite link is invalid or has already been used.</p>';
    exit;
}
if (!rate_limit_check("invite:$token", 20, 3600)) {
    http_response_code(429);
    echo '<p style="font-family:sans-serif">Too many attempts on this invite link. Please wait a bit and try again.</p>';
    exit;
}
rate_limit_hit("invite:$token", 3600);


$stmt = $pdo->prepare("SELECT ts.*, t.title, u.name AS owner_name
                        FROM task_shares ts
                        JOIN tasks t ON t.id = ts.task_id
                        JOIN users u ON u.id = ts.owner_id
                        WHERE ts.invite_token = ? AND ts.status = 'pending'");
$stmt->execute([$token]);
$invite = $stmt->fetch();

if (!$invite) {
    http_response_code(404);
    echo '<p style="font-family:sans-serif">This invite link is invalid or has already been used.</p>';
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please reload the page and try again.';
    } else {
    $mode = $_POST['mode'] ?? 'login';

    if ($mode === 'register') {
        $res = register_user($_POST['name'] ?? '', $invite['invite_email'], $_POST['password'] ?? '');
        if (!$res['ok']) { $error = $res['error']; }
        else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $res['user_id'];
        }
    } else {
        $res = attempt_login($invite['invite_email'], $_POST['password'] ?? '');
        if (!$res['ok']) { $error = $res['error']; }
    }

    if (!$error && current_user_id()) {
        $pdo->prepare("UPDATE task_shares SET status = 'accepted', shared_with_user_id = ?, responded_at = NOW() WHERE id = ?")
            ->execute([current_user_id(), $invite['id']]);
        header('Location: ' . APP_URL . '/taskvel-pro.php');
        exit;
    }
    }
}
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Taskvel — Accept Invite</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{font-family:-apple-system,Segoe UI,Arial,sans-serif;background:#f6f6f4;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
  .card{background:#fff;padding:32px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:380px;width:100%}
  h2{margin-top:0}
  input{width:100%;padding:10px;margin:6px 0 14px;border:1px solid #ddd;border-radius:8px;box-sizing:border-box}
  button{width:100%;padding:12px;background:#0a0a0a;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer}
  .toggle{text-align:center;margin-top:12px;font-size:14px}
  .toggle a{color:#2563eb;cursor:pointer;text-decoration:none}
  .err{color:#dc2626;font-size:14px;margin-bottom:10px}
</style>
</head>
<body>
<div class="card">
  <h2><?= htmlspecialchars($invite['owner_name']) ?> invited you</h2>
  <p>to collaborate on <strong><?= htmlspecialchars($invite['title']) ?></strong> (<?= htmlspecialchars($invite['permission']) ?> access)</p>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="post" id="form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="mode" id="mode" value="login">
    <div id="nameField" style="display:none">
      <input type="text" name="name" placeholder="Your full name">
    </div>
    <input type="email" value="<?= htmlspecialchars($invite['invite_email']) ?>" disabled>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" id="submitBtn">Log in & accept</button>
  </form>
  <div class="toggle">
    <span id="toggleText">New here? <a onclick="toggleMode()">Create an account instead</a></span>
  </div>
</div>
<script>
let isRegister = false;
function toggleMode() {
  isRegister = !isRegister;
  document.getElementById('mode').value = isRegister ? 'register' : 'login';
  document.getElementById('nameField').style.display = isRegister ? 'block' : 'none';
  document.getElementById('submitBtn').textContent = isRegister ? 'Create account & accept' : 'Log in & accept';
  document.getElementById('toggleText').innerHTML = isRegister
    ? 'Already have an account? <a onclick="toggleMode()">Log in instead</a>'
    : 'New here? <a onclick="toggleMode()">Create an account instead</a>';
}
</script>
</body>
</html>
