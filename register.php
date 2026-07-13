<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user_id()) { header('Location: taskvel-pro.php'); exit; }if (current_user_id()) {
    header('Location: ' . (current_user_role() === 'admin' ? 'admin/index.php' : 'taskvel-pro.php'));
    exit;
}
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
            header('Location: taskvel-pro.php'); exit;
        }
        $error = $res['error'];
    }
}
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign up — Taskvel by Samal Consultancy</title>
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
body::before{
  content:''; position:fixed; inset:0; pointer-events:none; opacity:.45;
  background:
    linear-gradient(rgba(255,255,255,0.035) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.035) 1px, transparent 1px);
  background-size:54px 54px;
  mask-image:radial-gradient(ellipse 80% 70% at 50% 40%, #000 30%, transparent 100%);
  -webkit-mask-image:radial-gradient(ellipse 80% 70% at 50% 40%, #000 30%, transparent 100%);
}
.orb{position:fixed; border-radius:50%; filter:blur(80px); opacity:.26; pointer-events:none; animation:drift 18s ease-in-out infinite;}
.o1{width:340px;height:340px;background:var(--gold-2);top:-90px;right:8%;}
.o2{width:260px;height:260px;background:var(--teal-2);bottom:-70px;left:4%;animation-delay:3s;animation-duration:23s;}
@keyframes drift{0%,100%{transform:translate(0,0) scale(1);}50%{transform:translate(26px,-24px) scale(1.12);}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important;}}

.shell{
  position:relative; z-index:1; width:100%; max-width:1060px;
  display:grid; grid-template-columns:1fr; gap:28px; align-items:center;
}
@media (min-width:920px){ .shell{grid-template-columns:1.1fr .9fr; gap:56px;} }

/* ===== BRAND / INFO PANEL ===== */
.wordmark{display:flex; align-items:center; gap:14px; margin-bottom:26px;}
.mark{
  width:52px;height:52px;border-radius:14px; flex-shrink:0;
  background:linear-gradient(145deg,var(--gold-2),var(--gold) 55%,var(--teal-deep));
  display:flex;align-items:center;justify-content:center;
  font-family:'Space Grotesk'; font-weight:700; font-size:20px; color:#fff;
  box-shadow:inset 0 0 0 1px rgba(255,255,255,0.18), 0 16px 34px -14px rgba(201,162,39,0.7);
}
.wm-txt h1{font-family:'Space Grotesk'; font-size:26px; margin:0; letter-spacing:-.02em; line-height:1;}
.wm-txt h1 span{
  background:linear-gradient(120deg,var(--gold-2),var(--gold));
  -webkit-background-clip:text; background-clip:text; color:transparent;
}
.wm-txt .by{
  font-size:11px; font-weight:600; letter-spacing:.14em; text-transform:uppercase;
  color:rgba(250,248,243,0.6); margin-top:6px;
}
.wm-txt .by b{color:var(--gold-2); font-weight:700;}

.info h2{font-family:'Space Grotesk'; font-size:clamp(24px,4.5vw,36px); line-height:1.15; margin:0 0 12px; letter-spacing:-.02em;}
.info h2 em{font-style:normal; background:linear-gradient(120deg,var(--gold-2),var(--gold)); -webkit-background-clip:text; background-clip:text; color:transparent;}
.info p.lead{color:rgba(250,248,243,0.72); font-size:15px; line-height:1.7; margin:0; max-width:480px;}

.feats{list-style:none; padding:0; margin:26px 0 0; display:grid; gap:11px;}
.feats li{
  display:flex; gap:11px; align-items:flex-start; font-size:13.5px; line-height:1.5;
  color:rgba(250,248,243,0.85); opacity:0; transform:translateY(10px);
  animation:rise .6s var(--ease) forwards; animation-delay:calc(var(--i) * 90ms + .2s);
}
@keyframes rise{to{opacity:1; transform:translateY(0);}}
.feats li::before{
  content:'✓'; flex-shrink:0; width:19px;height:19px;border-radius:50%; margin-top:1px;
  display:inline-flex;align-items:center;justify-content:center;
  background:rgba(232,199,102,0.16); color:var(--gold-2); font-size:10px; font-weight:700;
}
.feats li b{color:var(--ivory);}

.trust{
  display:flex; gap:26px; margin-top:30px; padding-top:22px;
  border-top:1px solid rgba(255,255,255,0.14); flex-wrap:wrap;
}
.trust div{min-width:90px;}
.t-num{font-family:'Space Grotesk'; font-size:21px; font-weight:700; color:var(--gold-2);}
.t-label{font-size:11px; color:rgba(250,248,243,0.55); margin-top:3px; letter-spacing:.02em;}

/* ===== 3D FORM CARD ===== */
.card-zone{perspective:1400px;}
.card{
  position:relative; border-radius:24px; padding:34px 28px 30px;
  background:linear-gradient(165deg, rgba(255,255,255,0.09), rgba(255,255,255,0.03));
  border:1px solid rgba(255,255,255,0.15); backdrop-filter:blur(18px);
  box-shadow:0 40px 90px -30px rgba(0,0,0,0.65);
  transform-style:preserve-3d; will-change:transform;
  transition:transform .35s var(--ease); overflow:hidden;
  --mx:50%; --my:35%;
}
@property --ang{syntax:'<angle>'; initial-value:0deg; inherits:false;}
.card::before{
  content:''; position:absolute; inset:-1px; border-radius:inherit; padding:1.5px;
  background:conic-gradient(from var(--ang), var(--gold-2), transparent 28%, var(--gold) 50%, transparent 72%, var(--gold-2));
  -webkit-mask:linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
  -webkit-mask-composite:xor; mask-composite:exclude;
  animation:spin 7s linear infinite; pointer-events:none;
}
@keyframes spin{to{--ang:360deg;}}
.glare{
  position:absolute; inset:0; pointer-events:none; opacity:0; transition:opacity .4s ease;
  background:radial-gradient(380px circle at var(--mx) var(--my), rgba(255,255,255,0.12), transparent 55%);
}
.card:hover .glare{opacity:1;}
.card > *:not(.glare){position:relative; z-index:1;}

.card h3{font-family:'Space Grotesk'; font-size:22px; margin:0 0 4px; letter-spacing:-.01em; transform:translateZ(26px);}
.card .sub{font-size:13px; color:rgba(250,248,243,0.6); margin:0 0 22px;}
.err{
  background:rgba(220,38,38,0.14); border:1px solid rgba(248,113,113,0.4); color:#FCA5A5;
  border-radius:12px; padding:11px 14px; font-size:13px; margin-bottom:16px;
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
  position:relative; overflow:hidden; width:100%; padding:15px 22px; border:none; cursor:pointer;
  border-radius:100px; font-family:'Inter'; font-weight:700; font-size:14.5px; color:#fff;
  background:linear-gradient(135deg,var(--gold-2),var(--gold));
  box-shadow:0 16px 34px -12px rgba(201,162,39,0.65);
  transition:transform .3s var(--ease), box-shadow .3s var(--ease);
  transform:translateZ(20px); -webkit-tap-highlight-color:transparent;
}
.btn:hover{transform:translateZ(20px) translateY(-2px); box-shadow:0 22px 44px -14px rgba(201,162,39,0.75);}
.btn:active{transform:translateZ(20px) scale(.97);}
.ripple{position:absolute; border-radius:50%; background:rgba(255,255,255,0.5); transform:scale(0); animation:rip .6s ease-out forwards; pointer-events:none;}
@keyframes rip{to{transform:scale(2.6); opacity:0;}}

.alt{text-align:center; margin:18px 0 0; font-size:13.5px; color:rgba(250,248,243,0.6);}
.alt a{color:var(--gold-2); text-decoration:none; font-weight:600;}
.alt a:hover{text-decoration:underline;}
.card-foot{
  margin-top:20px; padding-top:16px; border-top:1px dashed rgba(255,255,255,0.14);
  font-size:11px; color:rgba(250,248,243,0.45); text-align:center; letter-spacing:.03em;
}
.card-foot a{color:rgba(250,248,243,0.65); text-decoration:none;}
.card-foot a:hover{color:var(--gold-2);}

@media (max-width:919px){
  .info p.lead{font-size:14px;}
  .feats li:nth-child(n+5){display:none;} /* compact on mobile */
  .shell{gap:34px;}
}
input:focus-visible, button:focus-visible, a:focus-visible{outline:2px solid var(--gold); outline-offset:3px;}
</style>
</head>
<body>
<span class="orb o1" aria-hidden="true"></span>
<span class="orb o2" aria-hidden="true"></span>

<div class="shell">

  <!-- BRANDING + PREMIUM FEATURES -->
  <div class="info">
    <div class="wordmark">
      <div class="mark">SC</div>
      <div class="wm-txt">
        <h1>Task<span>vel</span></h1>
        <div class="by">by <b>Samal Consultancy</b> · Est. 1993</div>
      </div>
    </div>
    <h2>One platform. <em>Every workflow.</em></h2>
    <p class="lead">From Samal Consultancy — trusted by 1,500+ businesses for tax &amp; compliance since 1993 — Taskvel brings the same discipline to your daily work. Sign in to unlock the full premium suite:</p>
    <ul class="feats">
      <li style="--i:0"><span><b>Secure accounts</b> with role &amp; permission management</span></li>
      <li style="--i:1"><span><b>Multi-device sync</b> — your workspace on every screen</span></li>
      <li style="--i:2"><span><b>Premium analytics dashboard</b> with focus reports &amp; productivity trends</span></li>
      <li style="--i:3"><span><b>Team collaboration</b>, calendar planning &amp; smart notifications</span></li>
      <li style="--i:4"><span><b>Pomodoro focus system</b> with custom sessions &amp; session-level stats</span></li>
      <li style="--i:5"><span><b>Compliance &amp; client management</b> — GST, EPF, ESIC trackers, payroll &amp; invoicing</span></li>
    </ul>
    <div class="trust">
      <div><div class="t-num">1,500+</div><div class="t-label">Businesses served</div></div>
      <div><div class="t-num">30+ yrs</div><div class="t-label">In practice</div></div>
      <div><div class="t-num">99.2%</div><div class="t-label">On-time delivery</div></div>
    </div>
  </div>

  <!-- 3D LOGIN CARD -->
  <div class="card-zone">
    <div class="card" id="card">
      <span class="glare" aria-hidden="true"></span>
      <h3>Create your account</h3>
      <p class="sub">Free to start — premium features included.</p>
      <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <div class="field">
          <input type="text" name="name" placeholder=" " required maxlength="190" autocomplete="name" id="name">
          <label for="name">Full name</label>
        </div>
        <div class="field">
          <input type="email" name="email" placeholder=" " required maxlength="190" autocomplete="email" id="email">
          <label for="email">Email address</label>
        </div>
        <div class="field">
          <input type="password" name="password" placeholder=" " required minlength="8" maxlength="200" autocomplete="new-password" id="password">
          <label for="password">Password (min 8 characters)</label>
        </div>
        <button type="submit" class="btn" id="submitBtn">Create account</button>
      </form>
      <p class="alt">Already have an account? <a href="login.php">Log in</a></p>
      <div class="card-foot">A product of <a href="https://www.samalconsultancy.com" target="_blank" rel="noopener">Samal Consultancy</a> · Guwahati, Assam · Trusted compliance since 1993</div>
    </div>
  </div>

</div>

<script>
(function(){
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var coarse  = window.matchMedia('(pointer: coarse)').matches;
  var card = document.getElementById('card');

  /* 3D tilt + glare follow */
  if(card && !reduced && !coarse){
    card.addEventListener('mousemove', function(e){
      var r = card.getBoundingClientRect();
      var px = (e.clientX - r.left) / r.width;
      var py = (e.clientY - r.top) / r.height;
      card.style.transform = 'rotateY(' + ((px - 0.5) * 8) + 'deg) rotateX(' + ((0.5 - py) * 8) + 'deg)';
      card.style.setProperty('--mx', (px * 100) + '%');
      card.style.setProperty('--my', (py * 100) + '%');
    });
    card.addEventListener('mouseleave', function(){ card.style.transform = ''; });
  }

  /* Button ripple */
  var btn = document.getElementById('submitBtn');
  if(btn){
    btn.addEventListener('click', function(e){
      var rect = this.getBoundingClientRect();
      var s = document.createElement('span');
      var size = Math.max(rect.width, rect.height);
      s.className = 'ripple';
      s.style.width = s.style.height = size + 'px';
      s.style.left = (e.clientX - rect.left - size/2) + 'px';
      s.style.top  = (e.clientY - rect.top  - size/2) + 'px';
      this.appendChild(s);
      setTimeout(function(){ s.remove(); }, 650);
    });
  }
})();
</script>
</body>
</html>