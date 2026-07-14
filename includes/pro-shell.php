<?php
// ────────────────────────────────────────────────────────────
// PRO SHELL — shared look & feel for the Teams pages so they are
// visually part of Taskvel Pro (same Samal gold/navy tokens, same
// fonts, same light/dark theme keys in localStorage) instead of
// the old generic indigo styling.
//
// Usage in a page:
//   require_once __DIR__ . '/includes/pro-shell.php';
//   pro_head('Teams');            // inside <head>
//   pro_header($user, $crumbs);   // right after <body>
// ────────────────────────────────────────────────────────────

function pro_head(string $title): void
{
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FAF8F3" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0A1128" media="(prefers-color-scheme: dark)">
    <title><?= htmlspecialchars($title) ?> · Taskvel Pro</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230A1128'/%3E%3Ctext x='50' y='72' font-family='Arial,sans-serif' font-size='62' font-weight='800' fill='%23C9A227' text-anchor='middle'%3ET%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Sora:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap">
    <script>
        // Same theme bootstrap as taskvel-pro.php — the Teams pages follow
        // whatever theme the person picked in the main app.
        (function() {
            try {
                var savedTheme = localStorage.getItem('taskvel_theme_v1');
                var theme = savedTheme || (window.matchMedia && window.matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) { document.documentElement.setAttribute('data-theme', 'light'); }
        })();
    </script>
    <style>
        /* ═══════ SAMAL DESIGN TOKENS (mirrors taskvel-pro.php) ═══════ */
        :root {
            --bg:#FAF8F3; --bg-elev:#ffffff; --bg-sunk:#F3F1E9;
            --ink:#0A1128; --ink2:#3C4258; --ink3:#7A7F90; --ink4:#B9BCC6;
            --line:#EAE7DD; --line2:#D8D6CE;
            --accent:#C9A227; --accent-2:#0F4436;
            --accent-soft:rgba(201,162,39,.12); --accent-glow:rgba(201,162,39,.30);
            --on-accent:#ffffff;
            --good:#16a34a; --good-soft:rgba(22,163,74,.12);
            --warn:#d97706; --warn-soft:rgba(217,119,6,.12);
            --bad:#dc2626; --bad-soft:rgba(220,38,38,.12);
            --shadow:0 12px 38px rgba(10,17,40,.12); --shadow-lg:0 24px 60px rgba(10,17,40,.18);
            --ring:rgba(201,162,39,.25);
            --r:16px; --r-lg:20px; --r-sm:10px;
            --ease:cubic-bezier(.22,1,.36,1);
            --font-display:'Space Grotesk',sans-serif; --font-body:'Sora',-apple-system,'Segoe UI',sans-serif;
        }
        :root[data-theme="dark"] {
            --bg:#0A1128; --bg-elev:#121A36; --bg-sunk:#060B1C;
            --ink:#FAF8F3; --ink2:#C3C8DC; --ink3:#8990AC; --ink4:#525A78;
            --line:#1E2745; --line2:#2C365A;
            --accent:#E8C766; --accent-2:#8FA0E8;
            --accent-soft:rgba(232,199,102,.14); --accent-glow:rgba(232,199,102,.34);
            --on-accent:#0A1128;
            --shadow:0 12px 40px rgba(0,0,0,.6); --shadow-lg:0 24px 64px rgba(0,0,0,.72);
            --ring:rgba(232,199,102,.3);
        }
        html { color-scheme: light; }
        html[data-theme="dark"] { color-scheme: dark; }
        * { box-sizing:border-box; margin:0; padding:0; -webkit-tap-highlight-color:transparent; }
        body { font-family:var(--font-body); background:var(--bg); color:var(--ink); min-height:100vh;
               -webkit-font-smoothing:antialiased; transition:background .3s, color .3s; }
        a { color:inherit; }

        /* Aurora backdrop — same signature glow as taskvel-pro */
        .aurora { position:fixed; inset:0; z-index:-1; overflow:hidden; pointer-events:none; }
        .aurora span { position:absolute; border-radius:50%; filter:blur(90px); opacity:.5; }
        .aurora .a1 { width:520px; height:520px; top:-180px; right:-120px; background:var(--accent-soft); }
        .aurora .a2 { width:420px; height:420px; bottom:-160px; left:-140px; background:rgba(15,68,54,.10); }
        :root[data-theme="dark"] .aurora .a2 { background:rgba(143,160,232,.10); }

        .wrap { max-width:900px; margin:0 auto; padding:22px 18px 90px; }

        /* ═══════ HEADER ═══════ */
        .pro-header { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px; }
        .brand { display:flex; align-items:center; gap:11px; text-decoration:none; }
        .brand .logo { width:42px; height:42px; border-radius:12px; background:var(--ink); color:var(--accent);
            display:flex; align-items:center; justify-content:center; font-family:var(--font-display); font-weight:700; font-size:22px;
            box-shadow:var(--shadow); }
        :root[data-theme="dark"] .brand .logo { background:var(--accent); color:var(--on-accent); }
        .brand h1 { font-family:var(--font-display); font-size:20px; font-weight:700; letter-spacing:-.3px; }
        .brand h1 span { color:var(--accent); }
        .brand .tag { font-size:10.5px; color:var(--ink3); letter-spacing:.3px; }
        .head-right { display:flex; align-items:center; gap:8px; }
        .icon-btn { width:38px; height:38px; border-radius:11px; border:1px solid var(--line2); background:var(--bg-elev);
            color:var(--ink2); font-size:16px; cursor:pointer; display:flex; align-items:center; justify-content:center;
            transition:transform .2s var(--ease), border-color .2s; }
        .icon-btn:hover { transform:translateY(-2px); border-color:var(--accent); color:var(--accent); }
        .user-chip { font-size:11.5px; color:var(--ink3); padding:8px 12px; border:1px solid var(--line); border-radius:11px;
            background:var(--bg-elev); max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

        /* Nav pills — makes Teams feel like a section OF Taskvel Pro */
        .pro-nav { display:flex; gap:8px; margin:14px 0 24px; flex-wrap:wrap; }
        .pro-nav a { text-decoration:none; font-family:var(--font-display); font-size:12.5px; font-weight:600;
            padding:9px 16px; border-radius:999px; border:1px solid var(--line2); background:var(--bg-elev); color:var(--ink2);
            transition:all .2s var(--ease); }
        .pro-nav a:hover { border-color:var(--accent); color:var(--accent); transform:translateY(-1px); }
        .pro-nav a.active { background:var(--ink); color:var(--bg); border-color:var(--ink); }
        :root[data-theme="dark"] .pro-nav a.active { background:var(--accent); color:var(--on-accent); border-color:var(--accent); }
        .crumb { font-size:12px; color:var(--ink3); margin-bottom:6px; }
        .crumb a { color:var(--ink3); text-decoration:none; font-weight:600; }
        .crumb a:hover { color:var(--accent); }

        /* ═══════ SHARED COMPONENTS ═══════ */
        h1.page-title { font-family:var(--font-display); font-size:26px; font-weight:700; letter-spacing:-.4px; margin-bottom:4px;
            display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .sub { color:var(--ink3); font-size:13.5px; margin-bottom:20px; line-height:1.5; }
        section { margin-top:30px; }
        section > h2 { font-family:var(--font-display); font-size:14px; font-weight:700; text-transform:uppercase;
            letter-spacing:1px; color:var(--ink3); margin-bottom:13px; display:flex; justify-content:space-between; align-items:center; }
        .btn { display:inline-flex; align-items:center; gap:6px; padding:11px 18px; border-radius:12px; border:none;
            background:var(--accent); color:var(--on-accent); font-family:var(--font-display); font-weight:700; font-size:13.5px;
            cursor:pointer; box-shadow:0 8px 22px -8px var(--accent-glow); transition:transform .2s var(--ease), box-shadow .2s; }
        .btn:hover { transform:translateY(-2px); box-shadow:0 12px 28px -8px var(--accent-glow); }
        .btn.ghost { background:var(--bg-elev); color:var(--ink); border:1px solid var(--line2); box-shadow:none; }
        .btn.ghost:hover { border-color:var(--accent); color:var(--accent); }
        .btn.danger { background:var(--bad); box-shadow:0 8px 22px -8px rgba(220,38,38,.4); }
        .btn.sm { padding:7px 12px; font-size:11.5px; border-radius:9px; }

        .card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r); padding:16px 18px;
            transition:transform .22s var(--ease), box-shadow .22s, border-color .22s; }
        a.card { text-decoration:none; color:inherit; display:block; cursor:pointer; }
        a.card:hover, .card.hover:hover { transform:translateY(-3px); box-shadow:var(--shadow); border-color:var(--accent); }
        .card-list { display:flex; flex-direction:column; gap:11px; }

        .role-badge { font-family:var(--font-display); font-size:10px; font-weight:700; padding:4px 11px; border-radius:999px;
            text-transform:uppercase; letter-spacing:.6px; }
        .role-owner { background:var(--accent-soft); color:var(--accent); border:1px solid var(--accent-glow); }
        .role-manager { background:rgba(15,68,54,.10); color:var(--accent-2); border:1px solid rgba(15,68,54,.22); }
        :root[data-theme="dark"] .role-manager { background:rgba(143,160,232,.12); border-color:rgba(143,160,232,.3); }
        .role-member { background:var(--bg-sunk); color:var(--ink3); border:1px solid var(--line); }

        .avatar { width:26px; height:26px; border-radius:50%; background:var(--ink); color:var(--accent); font-size:10px;
            font-family:var(--font-display); font-weight:700; display:inline-flex; align-items:center; justify-content:center;
            border:2px solid var(--bg-elev); flex-shrink:0; }
        :root[data-theme="dark"] .avatar { background:var(--accent); color:var(--on-accent); }
        .avatar-stack { display:flex; align-items:center; }
        .avatar-stack .avatar { margin-left:-8px; }
        .avatar-stack .avatar:first-child { margin-left:0; }

        .empty { text-align:center; padding:44px 20px; color:var(--ink3); font-size:13.5px;
            background:var(--bg-sunk); border:1px dashed var(--line2); border-radius:var(--r); }
        .empty .ic { font-size:38px; margin-bottom:10px; opacity:.55; display:block; }

        /* ═══════ MODALS ═══════ */
        .modal-overlay { position:fixed; inset:0; background:rgba(10,17,40,.55); backdrop-filter:blur(4px);
            display:none; align-items:center; justify-content:center; z-index:100; padding:16px; }
        .modal-overlay.open { display:flex; animation:fadeIn .18s var(--ease); }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        .modal { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:26px;
            width:min(480px,94vw); max-height:88vh; overflow-y:auto; box-shadow:var(--shadow-lg); animation:pop .22s var(--ease); }
        @keyframes pop { from { transform:translateY(14px) scale(.98); opacity:0; } to { transform:none; opacity:1; } }
        .modal h2 { font-family:var(--font-display); font-size:19px; font-weight:700; margin-bottom:18px; }
        .modal label { font-family:var(--font-display); font-size:10.5px; color:var(--ink3); text-transform:uppercase;
            letter-spacing:.8px; display:block; margin-bottom:6px; font-weight:600; }
        .fg { margin-bottom:14px; }
        .modal input, .modal select, .modal textarea { width:100%; padding:11px 13px; border:1px solid var(--line2);
            border-radius:11px; font-size:14px; font-family:var(--font-body); background:var(--bg); color:var(--ink); }
        .modal input:focus, .modal select:focus, .modal textarea:focus { outline:none; border-color:var(--accent);
            box-shadow:0 0 0 3px var(--ring); }
        .modal textarea { resize:vertical; min-height:70px; }
        .modal-actions { display:flex; gap:8px; margin-top:8px; }
        .modal-actions .btn { flex:1; justify-content:center; }
        .row2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        @media (max-width:480px) { .row2 { grid-template-columns:1fr; } }

        /* Attendee picker chips */
        .chip-picker { display:flex; flex-wrap:wrap; gap:7px; }
        .chip-picker .chip { font-family:var(--font-display); font-size:12px; font-weight:600; padding:8px 13px;
            border-radius:999px; border:1px solid var(--line2); background:var(--bg); color:var(--ink2); cursor:pointer;
            transition:all .18s var(--ease); user-select:none; }
        .chip-picker .chip:hover { border-color:var(--accent); }
        .chip-picker .chip.on { background:var(--accent); color:var(--on-accent); border-color:var(--accent);
            box-shadow:0 6px 16px -6px var(--accent-glow); }
        .chip-picker .chip.locked { opacity:.75; cursor:default; }

        .toast { position:fixed; left:50%; bottom:26px; transform:translateX(-50%) translateY(80px); opacity:0;
            background:var(--ink); color:var(--bg); font-family:var(--font-display); font-size:13px; font-weight:600;
            padding:12px 20px; border-radius:12px; box-shadow:var(--shadow-lg); z-index:200; transition:all .3s var(--ease); }
        .toast.show { transform:translateX(-50%); opacity:1; }
    </style>
    <?php
}

function pro_header(array $user, string $active = 'teams', string $crumbHtml = ''): void
{
    ?>
    <div class="aurora"><span class="a1"></span><span class="a2"></span></div>
    <div class="pro-header">
        <a class="brand" href="taskvel-pro.php">
            <div class="logo">T</div>
            <div>
                <h1>Task<span>vel</span> Pro</h1>
                <div class="tag">by Samal Consultancy</div>
            </div>
        </a>
        <div class="head-right">
            <span class="user-chip"><?= htmlspecialchars($user['email']) ?></span>
            <button class="icon-btn" onclick="proToggleTheme()" title="Light / dark" aria-label="Toggle light or dark mode"><span id="pro-tt">☾</span></button>
        </div>
    </div>
    <div class="pro-nav">
        <a href="taskvel-pro.php" class="<?= $active === 'tasks' ? 'active' : '' ?>">✓ My Tasks</a>
        <a href="teams.php" class="<?= $active === 'teams' ? 'active' : '' ?>">👥 Teams</a>
        <a href="checkin.php">📍 Check-in</a>
    </div>
    <?php if ($crumbHtml !== ''): ?><div class="crumb"><?= $crumbHtml ?></div><?php endif; ?>
    <div class="toast" id="pro-toast"></div>
    <script>
        function proToggleTheme() {
            var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('taskvel_theme_v1', next); } catch (e) {}
            document.getElementById('pro-tt').textContent = next === 'dark' ? '☀' : '☾';
        }
        (function(){ var t=document.documentElement.getAttribute('data-theme');
            document.getElementById('pro-tt').textContent = t === 'dark' ? '☀' : '☾'; })();
        function toast(msg) {
            var el = document.getElementById('pro-toast');
            el.textContent = msg; el.classList.add('show');
            clearTimeout(el._t); el._t = setTimeout(() => el.classList.remove('show'), 2600);
        }
    </script>
    <?php
}
