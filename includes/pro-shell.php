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
        // Same theme + accent bootstrap as taskvel-pro.php — these pages
        // follow whatever theme AND colour accent the person picked in the
        // main app, read from the exact same localStorage keys.
        (function() {
            try {
                var savedTheme = localStorage.getItem('taskvel_theme_v1');
                var theme = savedTheme || (window.matchMedia && window.matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
                var accent = localStorage.getItem('taskvel_accent_v1') || 'indigo';
                document.documentElement.setAttribute('data-accent', accent);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
                document.documentElement.setAttribute('data-accent', 'indigo');
            }
        })();
    </script>
    <style>
        /* ═══════ SHARED DESIGN TOKENS (mirrors taskvel-pro.php) ═══════ */
        :root {
            --bg:#f7f8fa; --bg-elev:#ffffff; --bg-sunk:#eef0f3;
            --ink:#14161a; --ink2:#4b5563; --ink3:#8a8f98; --ink4:#c1c4cb;
            --line:#e4e6ea; --line2:#d3d6dc;
            --accent:#4f46e5; --accent-2:#6366f1;
            --accent-soft:rgba(79,70,229,.08); --accent-glow:rgba(79,70,229,.18);
            --on-accent:#ffffff;
            --good:#16a34a; --good-soft:rgba(22,163,74,.10);
            --warn:#d97706; --warn-soft:rgba(217,119,6,.10);
            --bad:#dc2626; --bad-soft:rgba(220,38,38,.10);
            --shadow-sm:0 1px 2px rgba(15,23,42,.05);
            --shadow:0 6px 20px rgba(15,23,42,.08); --shadow-lg:0 20px 48px rgba(15,23,42,.14);
            --ring:rgba(79,70,229,.15);
            --r:10px; --r-lg:14px; --r-sm:8px;
            --ease:cubic-bezier(.22,1,.36,1);
            --font-display:'Space Grotesk',sans-serif; --font-body:'Sora',-apple-system,'Segoe UI',sans-serif;
        }
        :root[data-theme="dark"] {
            --bg:#0e0f13; --bg-elev:#17181d; --bg-sunk:#0a0b0e;
            --ink:#f2f3f5; --ink2:#b4b8c0; --ink3:#7c8087; --ink4:#4b4e56;
            --line:#24262c; --line2:#313339;
            --accent:#818cf8; --accent-2:#a5b4fc;
            --accent-soft:rgba(129,140,248,.12); --accent-glow:rgba(129,140,248,.22);
            --on-accent:#0e0f13;
            --shadow-sm:0 1px 2px rgba(0,0,0,.4);
            --shadow:0 6px 20px rgba(0,0,0,.45); --shadow-lg:0 20px 48px rgba(0,0,0,.6);
            --ring:rgba(129,140,248,.25);
        }

        /* Accent variants — must stay byte-for-byte in sync with the same
           blocks in taskvel-pro.php's <style>, otherwise the colour theme
           you pick on the main app won't carry over to these pages. */
        :root[data-accent="mono"] { --accent:#14161a; --accent-2:#4b5563; --accent-soft:rgba(20,22,26,.06); --accent-glow:rgba(20,22,26,.14); --on-accent:#ffffff; --ring:rgba(20,22,26,.12); }
        :root[data-accent="mono"][data-theme="dark"] { --accent:#f2f3f5; --accent-2:#b4b8c0; --accent-soft:rgba(242,243,245,.10); --accent-glow:rgba(242,243,245,.16); --on-accent:#0e0f13; --ring:rgba(242,243,245,.16); }

        :root[data-accent="indigo"] { --accent:#4f46e5; --accent-2:#6366f1; --accent-soft:rgba(79,70,229,.08); --accent-glow:rgba(79,70,229,.18); --on-accent:#ffffff; --ring:rgba(79,70,229,.15); }
        :root[data-accent="indigo"][data-theme="dark"] { --accent:#818cf8; --accent-2:#a5b4fc; --accent-soft:rgba(129,140,248,.12); --accent-glow:rgba(129,140,248,.22); --on-accent:#0e0f13; --ring:rgba(129,140,248,.25); }

        :root[data-accent="emerald"] { --accent:#059669; --accent-2:#0d9488; --accent-soft:rgba(5,150,105,.08); --accent-glow:rgba(5,150,105,.18); --on-accent:#ffffff; --ring:rgba(5,150,105,.16); }
        :root[data-accent="emerald"][data-theme="dark"] { --accent:#34d399; --accent-2:#2dd4bf; --accent-soft:rgba(52,211,153,.12); --accent-glow:rgba(52,211,153,.22); --on-accent:#0e0f13; --ring:rgba(52,211,153,.22); }

        :root[data-accent="amber"] { --accent:#d97706; --accent-2:#b45309; --accent-soft:rgba(217,119,6,.08); --accent-glow:rgba(217,119,6,.18); --on-accent:#ffffff; --ring:rgba(217,119,6,.16); }
        :root[data-accent="amber"][data-theme="dark"] { --accent:#fbbf24; --accent-2:#fb923c; --accent-soft:rgba(251,191,36,.12); --accent-glow:rgba(251,191,36,.22); --on-accent:#0e0f13; --ring:rgba(251,191,36,.22); }

        :root[data-accent="samal"] { --accent:#a3811f; --accent-2:#0f4436; --accent-soft:rgba(163,129,31,.08); --accent-glow:rgba(163,129,31,.18); --on-accent:#ffffff; --ring:rgba(163,129,31,.16); }
        :root[data-accent="samal"][data-theme="dark"] { --accent:#e8c766; --accent-2:#8fa0e8; --accent-soft:rgba(232,199,102,.12); --accent-glow:rgba(232,199,102,.22); --on-accent:#0e0f13; --ring:rgba(232,199,102,.22); }

        html { color-scheme: light; }
        html[data-theme="dark"] { color-scheme: dark; }
        * { box-sizing:border-box; margin:0; padding:0; -webkit-tap-highlight-color:transparent; }
        body { font-family:var(--font-body); background:var(--bg); color:var(--ink); min-height:100vh;
               -webkit-font-smoothing:antialiased; transition:background .2s, color .2s; }
        a { color:inherit; }

        .aurora { display:none; }

        .wrap { max-width:560px; margin:0 auto; padding:22px 18px 90px; }

            @media (min-width: 720px) {
                .wrap { max-width:660px; padding:22px 24px 90px; }
            }

            @media (min-width: 980px) {
                .wrap { max-width:760px; }
            }

        /* ═══════ HEADER ═══════ */
        .pro-header { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px; }
        .brand { display:flex; align-items:center; gap:11px; text-decoration:none; }
        .brand .logo { width:36px; height:36px; border-radius:var(--r-sm); background:var(--ink); color:var(--bg);
            display:flex; align-items:center; justify-content:center; font-family:var(--font-display); font-weight:700; font-size:16px; }
        :root[data-theme="dark"] .brand .logo { background:var(--accent); color:var(--on-accent); }
        .brand h1 { font-family:var(--font-display); font-size:18px; font-weight:700; letter-spacing:-.2px; }
        .brand h1 span { color:var(--ink3); font-weight:400; }
        .brand .tag { font-size:10px; color:var(--ink3); letter-spacing:.3px; }
        .head-right { display:flex; align-items:center; gap:8px; }
        .icon-btn { width:36px; height:36px; border-radius:var(--r-sm); border:1px solid var(--line); background:var(--bg-elev);
            color:var(--ink2); font-size:15px; cursor:pointer; display:flex; align-items:center; justify-content:center;
            transition:background .15s, border-color .15s, color .15s; }
        .icon-btn:hover { background:var(--bg-sunk); border-color:var(--line2); color:var(--ink); }
        .user-chip { font-size:11.5px; color:var(--ink3); padding:7px 11px; border:1px solid var(--line); border-radius:var(--r-sm);
            background:var(--bg-elev); max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

        /* Nav pills */
        .pro-nav { display:flex; gap:6px; margin:14px 0 24px; flex-wrap:wrap; }
        .pro-nav a { text-decoration:none; font-family:var(--font-display); font-size:12.5px; font-weight:600;
            padding:8px 14px; border-radius:var(--r-sm); border:1px solid var(--line); background:var(--bg-elev); color:var(--ink2);
            transition:background .15s, border-color .15s, color .15s; }
        .pro-nav a:hover { background:var(--bg-sunk); border-color:var(--line2); color:var(--ink); }
        .pro-nav a.active { background:var(--ink); color:var(--bg); border-color:var(--ink); }
        :root[data-theme="dark"] .pro-nav a.active { background:var(--accent); color:var(--on-accent); border-color:var(--accent); }
        .crumb { font-size:12px; color:var(--ink3); margin-bottom:6px; }
        .crumb a { color:var(--ink3); text-decoration:none; font-weight:600; }
        .crumb a:hover { color:var(--ink); }

        /* ═══════ SHARED COMPONENTS ═══════ */
        h1.page-title { font-family:var(--font-display); font-size:22px; font-weight:700; letter-spacing:-.2px; margin-bottom:4px;
            display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .sub { color:var(--ink3); font-size:13.5px; margin-bottom:20px; line-height:1.5; }
        section { margin-top:30px; }
        section > h2 { font-family:var(--font-display); font-size:12.5px; font-weight:700; text-transform:uppercase;
            letter-spacing:.5px; color:var(--ink3); margin-bottom:12px; }

        .btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; font-family:var(--font-display);
            font-size:13px; font-weight:600; padding:9px 16px; border-radius:var(--r-sm); border:1px solid var(--accent);
            background:var(--accent); color:var(--on-accent); cursor:pointer; text-decoration:none;
            transition:background .15s, border-color .15s; }
        .btn:hover { background:var(--accent-2); border-color:var(--accent-2); }
        .btn.ghost { background:var(--bg-elev); color:var(--ink); border-color:var(--line2); }
        .btn.ghost:hover { background:var(--bg-sunk); }
        .btn.danger { background:var(--bad); border-color:var(--bad); }
        .btn.danger:hover { background:#b91c1c; border-color:#b91c1c; }
        .btn.warn { background:var(--warn); border-color:var(--warn); }
        .btn.sm { padding:6px 11px; font-size:11.5px; border-radius:var(--r-sm); }

        .card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r); padding:15px 16px;
            transition:border-color .15s ease, box-shadow .15s ease; }
        a.card { text-decoration:none; color:inherit; display:block; cursor:pointer; }
        a.card:hover, .card.hover:hover { border-color:var(--line2); box-shadow:var(--shadow-sm); }
        .card-list { display:flex; flex-direction:column; gap:8px; }

        .role-badge { font-family:var(--font-display); font-size:9.5px; font-weight:700; padding:3px 10px; border-radius:999px;
            text-transform:uppercase; letter-spacing:.5px; }
        .role-owner { background:var(--accent-soft); color:var(--accent); border:1px solid transparent; }
        .role-manager { background:var(--bg-sunk); color:var(--ink2); border:1px solid var(--line2); }
        .role-member { background:var(--bg-sunk); color:var(--ink3); border:1px solid var(--line); }

        .avatar { width:24px; height:24px; border-radius:50%; background:var(--ink); color:var(--bg); font-size:9.5px;
            font-family:var(--font-display); font-weight:700; display:inline-flex; align-items:center; justify-content:center;
            border:2px solid var(--bg-elev); flex-shrink:0; }
        :root[data-theme="dark"] .avatar { background:var(--accent); color:var(--on-accent); }
        .avatar-stack { display:flex; align-items:center; }
        .avatar-stack .avatar { margin-left:-8px; }
        .avatar-stack .avatar:first-child { margin-left:0; }

        .empty { text-align:center; padding:44px 20px; color:var(--ink3); font-size:13.5px;
            background:var(--bg-sunk); border:1px dashed var(--line2); border-radius:var(--r); }
        .empty .ic { font-size:32px; margin-bottom:10px; opacity:.5; display:block; }

        /* ═══════ MODALS ═══════ */
        .modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.45); backdrop-filter:blur(2px);
            display:none; align-items:center; justify-content:center; z-index:100; padding:16px; }
        .modal-overlay.open { display:flex; animation:fadeIn .15s var(--ease); }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        .modal { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:24px;
            width:min(480px,94vw); max-height:88vh; overflow-y:auto; box-shadow:var(--shadow-lg); animation:pop .15s var(--ease); }
        @keyframes pop { from { transform:translateY(8px); opacity:0; } to { transform:none; opacity:1; } }
        .modal h2 { font-family:var(--font-display); font-size:18px; font-weight:700; margin-bottom:18px; }
        .modal label { font-family:var(--font-display); font-size:10.5px; color:var(--ink3); text-transform:uppercase;
            letter-spacing:.6px; display:block; margin-bottom:6px; font-weight:600; }
        .fg { margin-bottom:14px; }
        .modal input, .modal select, .modal textarea { width:100%; padding:10px 12px; border:1px solid var(--line2);
            border-radius:var(--r-sm); font-size:14px; font-family:var(--font-body); background:var(--bg); color:var(--ink); }
        .modal input:focus, .modal select:focus, .modal textarea:focus { outline:none; border-color:var(--accent);
            box-shadow:0 0 0 3px var(--ring); }
        .modal textarea { resize:vertical; min-height:70px; }
        .modal-actions { display:flex; gap:8px; margin-top:8px; }
        .modal-actions .btn { flex:1; justify-content:center; }
        .row2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        @media (max-width:480px) { .row2 { grid-template-columns:1fr; } }

        /* Attendee picker chips */
        .chip-picker { display:flex; flex-wrap:wrap; gap:7px; }
        .chip-picker .chip { font-family:var(--font-display); font-size:12px; font-weight:600; padding:7px 12px;
            border-radius:999px; border:1px solid var(--line2); background:var(--bg); color:var(--ink2); cursor:pointer;
            transition:border-color .15s, background .15s; user-select:none; }
        .chip-picker .chip:hover { border-color:var(--line2); background:var(--bg-sunk); }
        .chip-picker .chip.on { background:var(--accent); color:var(--on-accent); border-color:var(--accent); }
        .chip-picker .chip.locked { opacity:.65; cursor:default; }

        .toast { position:fixed; left:50%; bottom:26px; transform:translateX(-50%) translateY(60px); opacity:0;
            background:var(--ink); color:var(--bg); font-family:var(--font-display); font-size:13px; font-weight:600;
            padding:11px 18px; border-radius:var(--r-sm); box-shadow:var(--shadow-lg); z-index:200; transition:all .2s var(--ease); }
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
        <a href="checkin.php" class="<?= $active === 'checkin' ? 'active' : '' ?>">📍 Check-in</a>
        <a href="billing.php" class="<?= $active === 'billing' ? 'active' : '' ?>">💳 Billing</a>
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

function pro_footer(array $user): void
{
    ?>
    <div class="foot">
        <div class="n">Taskvel</div>
        <div class="d">Focus · Rank · Ship</div>
        <div class="k">Signed in as <b><?= htmlspecialchars($user['email']) ?></b> · <a href="#" onclick="logoutUser();return false;" style="color:var(--accent);font-weight:600">Log out</a></div>
    </div>
    <?php
}