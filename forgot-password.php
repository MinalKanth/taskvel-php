<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user_id()) {
    header('Location: ' . (current_user_role() === 'admin' ? 'admin/index.php' : 'taskvel-pro.php'));
    exit;
}
$error = null;
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = $_POST['email'] ?? '';
        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $res = request_password_reset($email);
            if ($res['ok']) {
                $sent = true;
            } else {
                $error = $res['error'];
            }
        }
    }
}
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot password — Taskvel by Samal Consultancy</title>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0A1128">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --gold:#C9A227; --gold-2:#E8C766; --teal-deep:#0F4436; --teal-2:#8FA0E8;
  --navy:#0A1128; --ivory:#FAF8F3;
  --ease:cubic-bezier(.22,1,.36,1);
}
*{box-sizing:border-box;}
body{
  margin:0; min-height:100dvh; font-family:'Inter',sans-serif; color:var(--ivory);
  background:
    radial-gradient(1000px 520px at 90% -10%, rgba(232,199,102,0.14), transparent 60%),
    radial-gradient(800px 480px at -10% 110%, rgba(143,160,232,0.16), transparent 60%),
    linear-gradient(160deg, var(--navy) 0%, var(--teal-deep) 70%, #0F4436 100%);
  display:flex; align-items:center; justify-content:center; padding:24px; overflow-x:hidden;
}
.orb{position:fixed; border-radius:50%; filter:blur(80px); opacity:.26; pointer-events:none; animation:drift 18s ease-in-out infinite;}
.o1{width:340px;height:340px;background:var(--gold-2);top:-90px;right:8%;}
.o2{width:260px;height:260px;background:var(--teal-2);bottom:-70px;left:4%;animation-delay:3s;animation-duration:23s;}
@keyframes drift{0%,100%{transform:translate(0,0) scale(1);}50%{transform:translate(26px,-24px) scale(1.12);}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important;}}

.card-zone{width:100%; max-width:440px; position:relative; z-index:1;}
.card{
  position:relative; border-radius:24px; padding:34px 28px 30px;
  background:linear-gradient(165deg, rgba(255,255,255,0.09), rgba(255,255,255,0.03));
  border:1px solid rgba(255,255,255,0.15); backdrop-filter:blur(18px);
  box-shadow:0 40px 90px -30px rgba(0,0,0,0.65); overflow:hidden;
}
.wordmark{display:flex; align-items:center; gap:12px; margin-bottom:22px;}
.mark{
  width:42px;height:42px;border-radius:12px; flex-shrink:0;
  background:linear-gradient(145deg,var(--gold-2),var(--gold) 55%,var(--teal-deep));
  display:flex;align-items:center;justify-content:center;
  font-family:'Space Grotesk'; font-weight:700; font-size:16px; color:#fff;
}
.wordmark h1{font-family:'Space Grotesk'; font-size:20px; margin:0; letter-spacing:-.02em;}
.wordmark h1 span{background:linear-gradient(120deg,var(--gold-2),var(--gold)); -webkit-background-clip:text; background-clip:text; color:transparent;}

.card h3{font-family:'Space Grotesk'; font-size:22px; margin:0 0 4px; letter-spacing:-.01em;}
.card .sub{font-size:13px; color:rgba(250,248,243,0.6); margin:0 0 22px; line-height:1.5;}
.err{
  background:rgba(220,38,38,0.14); border:1px solid rgba(248,113,113,0.4); color:#FCA5A5;
  border-radius:12px; padding:11px 14px; font-size:13px; margin-bottom:16px;
}
.ok{
  background:rgba(15,68,54,0.35); border:1px solid rgba(143,160,232,0.4); color:var(--ivory);
  border-radius:12px; padding:14px 16px; font-size:13.5px; margin-bottom:16px; line-height:1.5;
}
.field{position:relative; margin-bottom:16px;}
.field input{
  width:100%; background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.18);
  border-radius:12px; padding:20px 16px 8px; color:var(--ivory);
  font-family:inherit; font-size:15px; outline:none;
  transition:border-color .3s ease, background .3s ease, box-shadow .3s ease;
}
.field label{
  position:absolute; left:17px; top:15px; color:rgba(250,248,243,0.5); font-size:14.5px;
  pointer-events:none; transition:all .25s var(--ease);
}
.field input:focus{border-color:var(--gold); background:rgba(255,255,255,0.11); box-shadow:0 0 0 4px rgba(201,162,39,0.14);}
.field input:focus + label, .field input:not(:placeholder-shown) + label{top:6px; font-size:10.5px; color:var(--gold-2); letter-spacing:.04em;}

.btn{
  width:100%; padding:15px 22px; border:none; cursor:pointer;
  border-radius:100px; font-family:'Inter'; font-weight:700; font-size:14.5px; color:#fff;
  background:linear-gradient(135deg,var(--gold-2),var(--gold));
  box-shadow:0 16px 34px -12px rgba(201,162,39,0.65);
  transition:transform .3s var(--ease), box-shadow .3s var(--ease);
}
.btn:hover{transform:translateY(-2px); box-shadow:0 22px 44px -14px rgba(201,162,39,0.75);}
.btn:active{transform:scale(.97);}

.alt{text-align:center; margin:18px 0 0; font-size:13.5px; color:rgba(250,248,243,0.6);}
.alt a{color:var(--gold-2); text-decoration:none; font-weight:600;}
.alt a:hover{text-decoration:underline;}
input:focus-visible, button:focus-visible, a:focus-visible{outline:2px solid var(--gold); outline-offset:3px;}
</style>
</head>
<body>
<span class="orb o1" aria-hidden="true"></span>
<span class="orb o2" aria-hidden="true"></span>

<div class="card-zone">
  <div class="card">
    <div class="wordmark">
      <div class="mark">SC</div>
      <h1>Task<span>vel</span></h1>
    </div>
    <h3>Forgot your password?</h3>
    <p class="sub">Enter the email on your account and we'll send you a link to reset it.</p>

    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if ($sent): ?>
      <div class="ok">If an account exists for that email, a reset link is on its way. Check your inbox (and spam folder) — the link expires in 1 hour.</div>
      <p class="alt"><a href="login.php">Back to log in</a></p>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <div class="field">
          <input type="email" name="email" placeholder=" " required maxlength="190" autocomplete="username" id="email">
          <label for="email">Email address</label>
        </div>
        <button type="submit" class="btn">Send reset link</button>
      </form>
      <p class="alt">Remembered it? <a href="login.php">Log in</a></p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>