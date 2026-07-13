<?php
/**
 * Shared admin chrome. Usage in every page:
 *
 *   require_once __DIR__ . '/../includes/admin.php';
 *   require_admin();
 *   ...handle POST / load data...
 *   admin_header('Users', 'users');   // (page title, active nav key)
 *   ...page HTML...
 *   admin_footer();
 */

function admin_nav_items(): array
{
    return [
        'dashboard' => ['index.php',      '◈', 'Dashboard'],
        'users'     => ['users.php',      '◔', 'Users'],
        'events'    => ['events.php',     '✦', 'Events'],
        'enquiries' => ['enquiries.php',  '✉', 'Enquiries'],
        'audit'     => ['audit.php',      '☰', 'Audit log'],
    ];
}

function admin_header(string $title, string $active): void
{
    $user = current_user();
    $csrf = csrf_token();

    // Unseen enquiry count for the sidebar badge
    $newEnq = 0;
    try { $newEnq = (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn(); }
    catch (Throwable $e) { /* table not migrated yet */ }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= h($title) ?> — Taskvel Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= h($csrf) ?>">
<meta name="theme-color" content="#0A1128">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
  --gold:#C9A227; --gold-2:#E8C766; --teal-deep:#0F4436; --lav:#8FA0E8;
  --navy:#0A1128; --navy-2:#0E1735; --ivory:#FAF8F3;
  --ink-70:rgba(250,248,243,.7); --ink-45:rgba(250,248,243,.45);
  --line:rgba(250,248,243,.09); --glass:rgba(255,255,255,.045);
  --ok:#5FC98E; --warn:#E8C766; --bad:#E86A6A;
  --ease:cubic-bezier(.22,1,.36,1); --side:236px;
}
*{box-sizing:border-box} html{scrollbar-color:rgba(250,248,243,.2) transparent}
body{
  margin:0;min-height:100vh;font-family:'Inter',sans-serif;color:var(--ivory);font-size:14px;
  background:
    radial-gradient(900px 480px at 100% -8%, rgba(232,199,102,.10), transparent 60%),
    radial-gradient(720px 460px at -8% 108%, rgba(143,160,232,.12), transparent 60%),
    linear-gradient(158deg, var(--navy) 0%, #0C1B30 55%, var(--teal-deep) 130%);
}
body::before{content:'';position:fixed;inset:0;pointer-events:none;opacity:.4;
  background:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),
             linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);
  background-size:54px 54px;
  mask-image:radial-gradient(ellipse 90% 80% at 50% 30%,#000 25%,transparent 100%);
  -webkit-mask-image:radial-gradient(ellipse 90% 80% at 50% 30%,#000 25%,transparent 100%);}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}

/* ── SIDEBAR — the "gold thread" ledger ─────────────────── */
.side{position:fixed;inset:0 auto 0 0;width:var(--side);z-index:20;display:flex;flex-direction:column;
  padding:22px 0 18px;background:rgba(10,17,40,.72);backdrop-filter:blur(18px);
  border-right:1px solid var(--line);}
.brand{display:flex;align-items:center;gap:12px;padding:0 22px 22px;border-bottom:1px solid var(--line);}
.brand .mark{width:40px;height:40px;border-radius:11px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
  font-family:'Space Grotesk';font-weight:700;font-size:16px;color:#fff;
  background:linear-gradient(145deg,var(--gold-2),var(--gold) 55%,var(--teal-deep));
  box-shadow:inset 0 0 0 1px rgba(255,255,255,.18),0 12px 26px -12px rgba(201,162,39,.7);}
.brand h1{font-family:'Space Grotesk';font-size:16px;margin:0;letter-spacing:-.01em;line-height:1.15;}
.brand span{display:block;font-size:9.5px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--gold-2);margin-top:3px;}
nav{flex:1;padding:16px 12px;position:relative;}
nav a{display:flex;align-items:center;gap:12px;padding:10px 12px;margin:2px 0;border-radius:10px;
  color:var(--ink-70);text-decoration:none;font-weight:500;position:relative;transition:all .25s var(--ease);}
nav a i{font-style:normal;width:20px;text-align:center;font-size:15px;opacity:.85;}
nav a:hover{color:var(--ivory);background:var(--glass);}
nav a.on{color:var(--ivory);background:linear-gradient(120deg,rgba(232,199,102,.13),rgba(232,199,102,.04));}
nav a.on::before{content:'';position:absolute;left:-12px;top:9px;bottom:9px;width:2px;border-radius:2px;
  background:linear-gradient(var(--gold-2),var(--gold));box-shadow:0 0 12px rgba(232,199,102,.8);}
nav a .pill{margin-left:auto;background:var(--bad);color:#fff;font-size:10px;font-weight:700;
  border-radius:99px;padding:2px 7px;line-height:1.4;}
.side-foot{padding:14px 18px 0;border-top:1px solid var(--line);display:flex;align-items:center;gap:10px;}
.avatar{width:34px;height:34px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:13px;background:linear-gradient(140deg,var(--lav),var(--teal-deep));color:#fff;}
.side-foot .who{min-width:0;flex:1;}
.side-foot .who b{display:block;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.side-foot .who span{font-size:10.5px;color:var(--gold-2);font-weight:600;letter-spacing:.08em;text-transform:uppercase;}
.side-foot a{color:var(--ink-45);text-decoration:none;font-size:16px;padding:6px;border-radius:8px;}
.side-foot a:hover{color:var(--bad);background:var(--glass);}

/* ── MAIN ────────────────────────────────────────────────── */
main{margin-left:var(--side);padding:28px 34px 60px;min-height:100vh;position:relative;z-index:1;}
.tophead{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:26px;flex-wrap:wrap;}
.tophead h2{font-family:'Space Grotesk';font-size:clamp(22px,3vw,30px);margin:0;letter-spacing:-.02em;}
.tophead h2 em{font-style:normal;background:linear-gradient(120deg,var(--gold-2),var(--gold));
  -webkit-background-clip:text;background-clip:text;color:transparent;}
.tophead .sub{color:var(--ink-45);font-size:13px;margin-top:5px;}

/* Cards, tables, forms */
.card{background:var(--glass);border:1px solid var(--line);border-radius:16px;padding:20px 22px;
  backdrop-filter:blur(14px);box-shadow:0 24px 50px -30px rgba(0,0,0,.6);}
.grid{display:grid;gap:16px;}
.g4{grid-template-columns:repeat(auto-fit,minmax(190px,1fr));}
.g2{grid-template-columns:repeat(auto-fit,minmax(340px,1fr));}
.kpi .lbl{font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-45);}
.kpi .num{font-family:'Space Grotesk';font-size:34px;font-weight:700;margin:8px 0 2px;letter-spacing:-.02em;}
.kpi .hint{font-size:12px;color:var(--ink-45);}
.kpi .hint b{color:var(--ok);font-weight:600;}

table{width:100%;border-collapse:collapse;font-size:13.5px;}
th{font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-45);
  text-align:left;padding:0 12px 10px;border-bottom:1px solid var(--line);white-space:nowrap;}
td{padding:12px;border-bottom:1px solid rgba(250,248,243,.05);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(255,255,255,.02);}
td .muted, .muted{color:var(--ink-45);font-size:12px;}
.mono{font-family:'JetBrains Mono',monospace;font-size:12px;}

.badge{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;border-radius:99px;padding:3.5px 10px;
  border:1px solid transparent;white-space:nowrap;}
.badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;}
.b-ok{color:var(--ok);background:rgba(95,201,142,.1);border-color:rgba(95,201,142,.25);}
.b-warn{color:var(--warn);background:rgba(232,199,102,.1);border-color:rgba(232,199,102,.28);}
.b-bad{color:var(--bad);background:rgba(232,106,106,.1);border-color:rgba(232,106,106,.28);}
.b-mut{color:var(--ink-45);background:rgba(250,248,243,.05);border-color:var(--line);}
.b-lav{color:var(--lav);background:rgba(143,160,232,.1);border-color:rgba(143,160,232,.3);}

.btn{display:inline-flex;align-items:center;gap:7px;border:none;cursor:pointer;text-decoration:none;
  font-family:'Inter';font-weight:600;font-size:13px;border-radius:10px;padding:9px 16px;
  color:#1a1405;background:linear-gradient(120deg,var(--gold-2),var(--gold));
  box-shadow:0 14px 28px -14px rgba(201,162,39,.8);transition:transform .2s var(--ease),box-shadow .2s;}
.btn:hover{transform:translateY(-1px);box-shadow:0 18px 32px -14px rgba(201,162,39,.9);}
.btn.ghost{background:var(--glass);color:var(--ivory);border:1px solid var(--line);box-shadow:none;}
.btn.ghost:hover{border-color:rgba(232,199,102,.4);}
.btn.danger{background:rgba(232,106,106,.14);color:var(--bad);border:1px solid rgba(232,106,106,.3);box-shadow:none;}
.btn.sm{padding:6px 11px;font-size:12px;border-radius:8px;}

input[type=text],input[type=email],input[type=url],input[type=date],input[type=search],input[type=tel],select,textarea{
  width:100%;background:rgba(10,17,40,.5);border:1px solid var(--line);border-radius:10px;
  color:var(--ivory);font-family:'Inter';font-size:13.5px;padding:10px 13px;outline:none;
  transition:border-color .2s,box-shadow .2s;}
input:focus,select:focus,textarea:focus{border-color:rgba(232,199,102,.55);box-shadow:0 0 0 3px rgba(232,199,102,.12);}
select option{background:var(--navy-2);}
label{display:block;font-size:11.5px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;
  color:var(--ink-45);margin:0 0 7px;}
.field{margin-bottom:16px;}
textarea{min-height:110px;resize:vertical;}

.toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:16px;}
.toolbar form{display:flex;gap:10px;flex:1;min-width:220px;}
.toolbar input[type=search]{flex:1;}

.pager{display:flex;gap:8px;justify-content:flex-end;margin-top:16px;}
.pager a,.pager span{padding:7px 13px;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;
  color:var(--ink-70);border:1px solid var(--line);background:var(--glass);}
.pager .cur{color:#1a1405;background:linear-gradient(120deg,var(--gold-2),var(--gold));border-color:transparent;}

.empty{text-align:center;padding:52px 20px;color:var(--ink-45);}
.empty .ic{font-size:30px;margin-bottom:10px;opacity:.6;}

#toast{position:fixed;bottom:26px;left:50%;transform:translate(-50%,16px);opacity:0;z-index:99;
  background:rgba(14,23,53,.95);border:1px solid rgba(232,199,102,.35);color:var(--ivory);
  padding:11px 20px;border-radius:12px;font-size:13.5px;font-weight:500;pointer-events:none;
  backdrop-filter:blur(12px);transition:all .3s var(--ease);box-shadow:0 20px 44px -18px rgba(0,0,0,.7);}
#toast.show{opacity:1;transform:translate(-50%,0);}

@media (max-width:860px){
  :root{--side:0px;}
  .side{position:sticky;top:0;width:100%;height:auto;flex-direction:row;align-items:center;padding:10px 14px;inset:auto;}
  .brand{padding:0 14px 0 0;border:none;} .brand h1 span, .brand h1{display:none;}
  nav{display:flex;padding:0;overflow-x:auto;} nav a{white-space:nowrap;padding:8px 11px;}
  nav a.on::before{left:0;right:0;top:auto;bottom:-10px;width:auto;height:2px;}
  .side-foot{border:none;padding:0 0 0 8px;} .side-foot .who{display:none;}
  main{margin-left:0;padding:20px 16px 50px;}
}
</style>
</head>
<body>
<aside class="side">
  <div class="brand">
    <div class="mark">T</div>
    <h1>Taskvel <span>Admin · Samal Consultancy</span></h1>
  </div>
  <nav>
    <?php foreach (admin_nav_items() as $key => [$href, $icon, $label]): ?>
      <a href="<?= h($href) ?>" class="<?= $key === $active ? 'on' : '' ?>">
        <i><?= $icon ?></i><?= h($label) ?>
        <?php if ($key === 'enquiries' && $newEnq > 0): ?><span class="pill"><?= $newEnq ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="side-foot">
    <div class="avatar"><?= h(strtoupper(mb_substr($user['name'] ?? 'A', 0, 1))) ?></div>
    <div class="who"><b><?= h($user['name'] ?? '') ?></b><span>Administrator</span></div>
    <a href="../taskvel-pro.php" title="Back to app">↩</a>
  </div>
</aside>
<main>
<div id="toast"></div>
<?php }

function admin_footer(): void
{ ?>
<script>
// Shared admin JS: CSRF-aware POST helper + toast. Every state change in the
// panel goes through post() so the X-CSRF-Token header is never forgotten.
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function post(url, data) {
    const body = new URLSearchParams(data);
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-Token': CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
        body, credentials: 'same-origin',
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(json.error || 'Request failed');
    return json;
}

let _toastT;
function toast(msg) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(_toastT);
    _toastT = setTimeout(() => el.classList.remove('show'), 2600);
}

// Animated KPI counters (dashboard)
document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count, 10) || 0;
    if (matchMedia('(prefers-reduced-motion: reduce)').matches || target === 0) { el.textContent = target.toLocaleString(); return; }
    const t0 = performance.now(), dur = 800;
    (function tick(t) {
        const p = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(target * e).toLocaleString();
        if (p < 1) requestAnimationFrame(tick);
    })(t0);
});
</script>
</main>
</body>
</html>
<?php }
