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
               -webkit-font-smoothing:antialiased; transition:background .2s, color .2s;
               overflow-x:hidden; position:relative; }
        a { color:inherit; }

        .aurora { display:none; }

                .wrap { max-width:560px; margin:0 auto; padding:0 18px 120px; }
            @media (min-width: 720px) {
                .wrap { max-width:660px; padding:0 24px 120px; }
            }
            @media (min-width: 980px) {
                .wrap { max-width:760px; }
            }
            @media (min-width: 1040px) {
                .wrap { max-width:1040px; }
            }
            @media (max-width: 380px) {
                .wrap { padding:0 14px 120px; }
            }

        /* ═══════ HEADER — kept byte-for-byte visually identical to
           taskvel-pro.php's own header CSS, including its responsive
           breakpoints, so the app doesn't visibly "reset" size/style
           when you navigate from My Tasks to any other page. ═══════ */
        .header { padding:24px 0 8px; position:sticky; top:0; z-index:40; background:color-mix(in srgb, var(--bg) 88%, transparent); backdrop-filter:blur(14px) saturate(1.4); -webkit-backdrop-filter:blur(14px) saturate(1.4); border-bottom:1px solid transparent; }
        .brand-row { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
        .brand { display:flex; align-items:center; gap:12px; text-decoration:none; }
        .logo { width:40px; height:40px; border-radius:12px; background:linear-gradient(150deg, var(--accent), var(--accent-2));
            display:flex; align-items:center; justify-content:center; font-family:var(--font-display); font-weight:700; font-size:17px;
            color:var(--on-accent); flex-shrink:0; position:relative; overflow:hidden;
            box-shadow:0 1px 0 rgba(255,255,255,.22) inset, 0 8px 20px -8px var(--accent-glow); transition:box-shadow .3s var(--ease), transform .3s var(--ease); }
        .brand:hover .logo { transform:translateY(-1px) rotate(-2deg); box-shadow:0 1px 0 rgba(255,255,255,.22) inset, 0 10px 24px -6px var(--accent-glow); }
        .logo::after { content:''; position:absolute; inset:0; border-radius:inherit; border:1px solid rgba(255,255,255,.14); pointer-events:none; }
        .brand-txt h1 { font-family:'Sora'; font-size:19px; font-weight:700; letter-spacing:-.3px; line-height:1; color:var(--ink); }
        .brand-txt h1 span { font-weight:400; color:var(--ink3); }
        .brand-txt .tag { font-family:'JetBrains Mono',monospace; font-size:9.5px; color:var(--ink3); letter-spacing:1.2px; text-transform:uppercase; margin-top:4px; }

        .clock-chip { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-sm); padding:7px 12px;
            text-align:right; min-width:104px; }
        .clock-time { font-family:'JetBrains Mono',monospace; font-size:16px; font-weight:600; line-height:1; color:var(--ink); }
        .clock-time .sec { font-size:12px; color:var(--ink3); }
        .clock-date { font-size:9.5px; color:var(--ink3); margin-top:4px; font-family:'JetBrains Mono',monospace; letter-spacing:.3px; }

        .head-right { display:flex; align-items:stretch; gap:6px; flex-wrap:wrap; justify-content:flex-end; }

        /* Grouped utility icons — one rounded pill container, matching
           the .icon-toolbar grouping on taskvel-pro.php, instead of each
           icon being its own separate bordered box. */
        .icon-toolbar { display:flex; align-items:center; gap:2px; padding:4px; background:var(--bg-elev);
            border:1px solid var(--line); border-radius:12px; flex-shrink:0; }
        .icon-toolbar-div { width:1px; height:18px; background:var(--line); margin:0 4px; flex-shrink:0; }
        .icon-btn { width:34px; height:34px; border:1px solid transparent; border-radius:9px; background:transparent;
            color:var(--ink3); cursor:pointer; display:flex; align-items:center; justify-content:center;
            transition:background .18s var(--ease), color .18s var(--ease); flex-shrink:0; position:relative; overflow:hidden; text-decoration:none; }
        .icon-btn:hover { background:var(--bg-sunk); color:var(--ink); }
        .icon-btn:active { transform:scale(.96); }
        .tt-icon { font-size:16px; line-height:1; display:block; }
        .icon-btn .badge-dot { position:absolute; top:6px; right:6px; width:6px; height:6px; border-radius:50%; background:var(--bad); display:none; }
        .icon-btn .badge-dot.show { display:block; }

        /* Premium Explore Features Button — must stay byte-for-byte
           identical to the .features-btn block in taskvel-pro.php's own
           <style>, otherwise Explore renders as a plain white pill here
           instead of the accent gradient pill it is on the main page.
           NOTE: this block must come AFTER .icon-btn above — the button
           carries both classes ("icon-btn features-btn"), and CSS gives
           the later rule priority when specificity is equal. */
        .features-btn {
            position: relative; width: auto; min-width: 0; height: 34px; padding: 0 13px;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px; overflow: hidden;
            border: 1px solid var(--accent); border-radius: 9px; background: var(--accent); color: var(--on-accent);
            box-shadow: 0 4px 14px -6px var(--accent-glow); cursor: pointer; text-decoration:none;
            transition: transform .18s var(--ease), box-shadow .18s var(--ease), background .18s var(--ease);
        }
        .features-spark { position: relative; font-size: 12px; line-height: 1; color: var(--on-accent); }
        .features-label { position: relative; font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--on-accent); }
        .features-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px -8px var(--accent-glow); }
        .features-btn:active { transform: translateY(0) scale(.97); }
        @media (max-width: 700px) {
            .features-btn { width: 42px; min-width: 42px; padding: 0; }
            .features-label { display: none; }
        }

        /* Labeled primary navigation — must stay identical to the
           .main-nav block in taskvel-pro.php's <style>. */
        .main-nav { display:flex; gap:3px; margin:16px 0 22px; flex-wrap:wrap; padding:4px; background:var(--bg-sunk);
            border:1px solid var(--line); border-radius:14px; width:fit-content; max-width:100%; overflow-x:auto; scrollbar-width:none; }
        .main-nav::-webkit-scrollbar { display:none; }
        .main-nav a { text-decoration:none; font-family:var(--font-display); font-size:12.5px; font-weight:600;
            padding:8px 14px; border-radius:10px; border:1px solid transparent; background:transparent; color:var(--ink2); white-space:nowrap;
            transition:background .18s var(--ease), color .18s var(--ease), box-shadow .18s var(--ease); }
        .main-nav a:hover { background:var(--bg-elev); color:var(--ink); }
        .main-nav a.active { background:var(--bg-elev); color:var(--accent); border-color:var(--line); box-shadow:var(--shadow-sm); }
        .main-nav details.nav-group { position:relative; }
        .main-nav details.nav-group summary { list-style:none; cursor:pointer; text-decoration:none; font-family:var(--font-display);
            font-size:12.5px; font-weight:600; padding:8px 14px; border-radius:10px; border:1px solid transparent; background:transparent;
            color:var(--ink2); white-space:nowrap; transition:background .18s var(--ease), color .18s var(--ease), box-shadow .18s var(--ease); }
        .main-nav details.nav-group summary::-webkit-details-marker { display:none; }
        .main-nav details.nav-group summary::marker { content:''; }
        .main-nav details.nav-group summary::after { content:'▾'; margin-left:6px; font-size:9px; opacity:.65; }
        .main-nav details.nav-group summary:hover { background:var(--bg-elev); color:var(--ink); }
        .main-nav details.nav-group[open] summary, .main-nav details.nav-group.nav-group-active summary {
            background:var(--bg-elev); color:var(--accent); border-color:var(--line); box-shadow:var(--shadow-sm); }
        .main-nav details.nav-group .nav-group-menu { display:none !important; /* replaced by #nav-dropdown-portal below —
            .header's backdrop-filter creates a containing block that breaks fixed/absolute positioning for descendants */ }
        #nav-dropdown-portal { position:fixed; min-width:200px; background:var(--bg-elev); border:1px solid var(--line);
            border-radius:14px; box-shadow:var(--shadow-lg); padding:6px; display:none; flex-direction:column; gap:2px; z-index:9999; }
        #nav-dropdown-portal a { text-decoration:none; font-family:var(--font-display); font-size:12.5px; font-weight:600;
            padding:9px 12px; border-radius:8px; border:1px solid transparent; background:transparent; color:var(--ink2); white-space:nowrap; }
        #nav-dropdown-portal a:hover { background:var(--bg-sunk); color:var(--ink); }
        #nav-dropdown-portal a.active { color:var(--accent); background:var(--accent-soft); }

        /* ── Responsive — mirrors taskvel-pro.php's breakpoints exactly,
           so the header scales the same way on every page. ── */
        @media (max-width: 380px) {
            .header { padding:18px 0 6px; }
            .brand-row { flex-wrap:wrap; }
            .logo { width:38px; height:38px; font-size:18px; border-radius:10px; }
            .brand-txt h1 { font-size:20px; }
            .head-right { gap:7px; }
            .tt-icon { font-size:16px; }
            .clock-chip { min-width:0; padding:7px 11px; flex-grow:1; }
            .clock-time { font-size:16px; }
        }
        @media (max-width: 340px) {
            .brand-txt .tag { display:none; }
            .clock-date { display:none; }
        }
        @media (min-width: 720px) {
            .header { padding:32px 0 10px; }
            .brand-txt h1 { font-size:25px; }
        }

        .crumb { font-size:12px; color:var(--ink3); margin:10px 0 6px; }
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

        /* ── Global search ── */
        .gs-modal { width:min(560px,94vw); padding:16px; max-height:74vh; display:flex; flex-direction:column; }
        .gs-input { width:100%; padding:13px 14px; border:1px solid var(--line2); border-radius:var(--r-sm); font-size:15px;
            background:var(--bg-sunk); color:var(--ink); margin-bottom:12px; }
        .gs-input:focus { outline:none; border-color:var(--accent); background:var(--bg-elev); }
        .gs-results { overflow-y:auto; flex:1; }
        .gs-hint { font-size:12.5px; color:var(--ink3); padding:16px 6px; text-align:center; }
        .gs-group { margin-bottom:10px; }
        .gs-group-title { font-family:var(--font-display); font-size:10px; text-transform:uppercase; letter-spacing:.6px;
            color:var(--ink3); font-weight:700; padding:4px 8px; }
        .gs-row { display:flex; align-items:center; gap:9px; padding:9px 8px; border-radius:8px; text-decoration:none;
            color:var(--ink); transition:background .12s var(--ease); }
        .gs-row:hover { background:var(--bg-sunk); }
        .gs-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; background:var(--ink4); }
        .gs-dot.todo { background:var(--ink4); }
        .gs-dot.in_progress { background:var(--warn); }
        .gs-dot.done { background:var(--good); }
        .gs-t { flex:1; font-size:13.5px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .gs-sub { font-size:11px; color:var(--ink3); font-family:var(--font-display); flex-shrink:0; }
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

        /* Header gets its own fixed-width shell, deliberately decoupled
           from each page's .wrap — some pages (My Work's table, the
           project board) widen .wrap for their content, and if the
           header lived inside that same element it would get a
           different amount of room on every page and wrap differently.
           This keeps the header pixel-identical everywhere regardless
           of what width a given page's content needs. */
        .header-shell { max-width:560px; margin:0 auto; }
        @media (min-width: 720px) { .header-shell { max-width:660px; } }
        @media (min-width: 980px) { .header-shell { max-width:760px; } }
        @media (min-width: 1040px) { .header-shell { max-width:1040px; } }

        /* ═══════ FOOTER — mirrors taskvel-pro.php's .foot block ═══════ */
        .foot { text-align:center; padding:42px 20px 10px; color:var(--ink3); }
        .foot .n { font-family:var(--font-body); font-size:16px; font-weight:700; color:var(--accent); letter-spacing:-.3px; }
        .foot .d { font-size:10px; font-family:'JetBrains Mono',monospace; letter-spacing:2px; margin-top:6px; text-transform:uppercase; }
        .foot .k { font-size:10px; font-family:'JetBrains Mono',monospace; margin-top:16px; color:var(--ink3); }
    </style>
    <?php
}

function pro_header(array $user, string $active = 'teams', string $crumbHtml = ''): void
{
    ?>
    <div class="header-shell">
    <div class="aurora"><span class="a1"></span><span class="a2"></span></div>
    <div class="header">
        <div class="brand-row">
            <a class="brand" href="taskvel-pro.php">
                <div class="logo" id="brand-logo">T</div>
                <div class="brand-txt">
                    <h1>Task<span>vel</span> Pro</h1>
                    <div class="tag">by Samal Consultancy</div>
                </div>
            </a>
            <div class="head-right">
                <a class="icon-btn features-btn" href="features.php" aria-label="View all features" title="Explore everything Taskvel can do">
                    <span class="features-spark">✦</span>
                    <span class="features-label">Explore</span>
                </a>
                <div class="icon-toolbar">
                    <a class="icon-btn" href="taskvel-pro.php#notif" aria-label="Notifications" title="Notifications">
                        <span class="tt-icon">◔</span><span class="badge-dot" id="pro-notif-dot"></span>
                    </a>
                    <a class="icon-btn" href="taskvel-pro.php#hist" aria-label="Focus history" title="Focus history">
                        <span class="tt-icon">▤</span>
                    </a>
                    <a class="icon-btn" href="taskvel-pro.php#export" aria-label="Export" title="Export">
                        <span class="tt-icon">↓</span>
                    </a>
                    <a class="icon-btn" href="taskvel-pro.php#tmpl" aria-label="Templates" title="Templates">
                        <span class="tt-icon">▧</span>
                    </a>
                    <span class="icon-toolbar-div"></span>
                    <a class="icon-btn" href="taskvel-pro.php#palette" aria-label="Choose colour theme" title="Colour theme">
                        <span class="tt-icon">◑</span>
                    </a>
                    <button class="icon-btn" id="pro-mute-toggle" onclick="proToggleMute()" aria-label="Mute or unmute sounds" title="Mute completion chime">
                        <span class="tt-icon" id="pro-mute-icon">🔊</span>
                    </button>
                    <button class="icon-btn" onclick="proToggleTheme()" title="Light / dark" aria-label="Toggle light or dark mode"><span class="tt-icon" id="pro-tt">☾</span></button>
                </div>
                <div class="clock-chip">
                    <div class="clock-time" id="pro-clock"><span>--:--</span><span class="sec">:--</span></div>
                    <div class="clock-date" id="pro-clock-date">—</div>
                </div>
            </div>
        </div>
        <div class="main-nav">
            <a href="taskvel-pro.php" class="<?= $active === 'tasks' ? 'active' : '' ?>">✓ My Tasks</a>
            <a href="my-work.php" class="<?= $active === 'mywork' ? 'active' : '' ?>">🗂 My Work</a>
            <details class="nav-group<?= in_array($active, ['teams', 'checkin'], true) ? ' nav-group-active' : '' ?>">
                <summary>🏢 Enterprise</summary>
                <div class="nav-group-menu">
                    <a href="teams.php" class="<?= $active === 'teams' ? 'active' : '' ?>">👥 My Teams</a>
                    <a href="checkin.php" class="<?= $active === 'checkin' ? 'active' : '' ?>">📍 Check-in</a>
                </div>
            </details>
            <details class="nav-group<?= in_array($active, ['journal', 'calendar'], true) ? ' nav-group-active' : '' ?>">
                <summary>📈 Trading</summary>
                <div class="nav-group-menu">
                    <a href="trading-journal.php" class="<?= $active === 'journal' ? 'active' : '' ?>">📈 Trading Journal</a>
                    <a href="trading-calendar.php" class="<?= $active === 'calendar' ? 'active' : '' ?>">🗓️ Trading Calendar</a>
                </div>
            </details>
            <a href="billing.php" class="<?= $active === 'billing' ? 'active' : '' ?>">💳 Billing</a>
        </div>
    </div>
    </div>
    <div id="nav-dropdown-portal"></div>
    <?php if ($crumbHtml !== ''): ?><div class="crumb"><?= $crumbHtml ?></div><?php endif; ?>
    <div class="toast" id="pro-toast"></div>

    <!-- Global search -->
    <div class="modal-overlay" id="gs-overlay" onclick="if(event.target===this)closeGlobalSearch()">
        <div class="modal gs-modal">
            <input type="text" id="gs-input" class="gs-input" placeholder="Search tasks, projects, teams…" autocomplete="off"
                oninput="gsDebouncedSearch()" onkeydown="if(event.key==='Escape')closeGlobalSearch()" />
            <div id="gs-results" class="gs-results"><div class="gs-hint">Type at least 2 characters — searches your personal tasks, project tasks, team tasks, projects, and teams.</div></div>
        </div>
    </div>

    <script>
        function proToggleTheme() {
            var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try {
                localStorage.setItem('taskvel_theme_v1', next);
                // Must stay in sync with taskvel-pro.php's LS_THEME_TS — that
                // timestamp is what stops an incoming server pull from
                // overwriting a just-made pick with an older saved value.
                // Without stamping it here too, a theme change made on this
                // page looks "stale" the moment you navigate to My Tasks.
                localStorage.setItem('taskvel_theme_accent_ts_v1', String(Date.now()));
            } catch (e) {}
            document.getElementById('pro-tt').textContent = next === 'dark' ? '☀' : '☾';
        }
        (function(){ var t=document.documentElement.getAttribute('data-theme');
            document.getElementById('pro-tt').textContent = t === 'dark' ? '☀' : '☾'; })();
        // Same mute flag/key as taskvel-pro.php — chimes only ever play
        // there, but the on/off state must stay in sync everywhere.
        function proToggleMute() {
            var next = localStorage.getItem('taskvel_muted_v1') !== '1';
            try { localStorage.setItem('taskvel_muted_v1', next ? '1' : '0'); } catch (e) {}
            document.getElementById('pro-mute-icon').textContent = next ? '🔇' : '🔊';
        }
        (function(){ if (localStorage.getItem('taskvel_muted_v1') === '1')
            document.getElementById('pro-mute-icon').textContent = '🔇'; })();
        function toast(msg) {
            var el = document.getElementById('pro-toast');
            el.textContent = msg; el.classList.add('show');
            clearTimeout(el._t); el._t = setTimeout(() => el.classList.remove('show'), 2600);
        }
        function proTickClock() {
            var d = new Date(),
                h = String(d.getHours()).padStart(2, '0'),
                m = String(d.getMinutes()).padStart(2, '0'),
                s = String(d.getSeconds()).padStart(2, '0');
            var t = document.getElementById('pro-clock');
            if (t) t.innerHTML = '<span>' + h + ':' + m + '</span><span class="sec">:' + s + '</span>';
            var dt = document.getElementById('pro-clock-date');
            if (dt) dt.textContent = d.toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' }).toUpperCase();
        }
        proTickClock();
        setInterval(proTickClock, 1000);

        // ═══════ ORG BRANDING — must mirror taskvel-pro.php's applyOrgBranding()
        // exactly, otherwise a member of an org with a custom brand colour
        // sees the org colour on My Tasks but their own picked theme colour
        // on every other page (Teams, My Work, Billing, etc.), since only
        // My Tasks was applying this override before. ═══════
        function proHexToRgb(hex) {
            const h = hex.replace('#', '');
            return [0, 2, 4].map(i => parseInt(h.substr(i, 2), 16));
        }
        function proEsc(s) {
            return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
        (async function applyOrgBranding() {
            try {
                const res = await fetch('api/organizations.php?action=mine', { credentials: 'same-origin' });
                const { membership } = await res.json();
                if (!membership) return;

                if (membership.org_logo_url) {
                    const logoEl = document.getElementById('brand-logo');
                    if (logoEl) logoEl.innerHTML = `<img src="${proEsc(membership.org_logo_url)}" alt="" style="width:100%;height:100%;object-fit:cover" onerror="this.remove();this.parentElement.textContent='${proEsc(membership.org_name || 'T').charAt(0)}'">`;
                }
                // Only use organization branding when the user has NOT selected
                // a personal accent theme. Personal theme must remain consistent
                // across every Pro Shell page.
                const hasPersonalAccent = (() => {
                    try {
                        return localStorage.getItem('taskvel_accent_v1') !== null;
                    } catch (e) {
                        return false;
                    }
                })();

                if (!hasPersonalAccent && membership.org_brand_color) {
                    const [r, g, b] = proHexToRgb(membership.org_brand_color);
                    const root = document.documentElement.style;

                    root.setProperty('--accent', membership.org_brand_color);
                    root.setProperty('--accent-soft', `rgba(${r},${g},${b},.10)`);
                    root.setProperty('--accent-glow', `rgba(${r},${g},${b},.28)`);
                }
            } catch (e) { /* not in an org — keep the person's own theme */ }
        })();

        // Enterprise / Trading dropdowns — rendered via a fixed-position
        // portal, since .header's backdrop-filter breaks fixed/absolute
        // positioning for elements still nested inside it.
        (function() {
            const portal = document.getElementById('nav-dropdown-portal');
            if (!portal) return;
            let openDetails = null;

            function closeNav() {
                if (openDetails) openDetails.removeAttribute('open');
                openDetails = null;
                portal.style.display = 'none';
                portal.innerHTML = '';
            }
            function openNav(details) {
                const summary = details.querySelector('summary');
                const menu = details.querySelector('.nav-group-menu');
                if (!summary || !menu) return;
                document.querySelectorAll('.main-nav details.nav-group[open]').forEach(d => {
                    if (d !== details) d.removeAttribute('open');
                });
                openDetails = details;
                portal.innerHTML = menu.innerHTML;
                portal.style.display = 'flex';
                const rect = summary.getBoundingClientRect();
                const portalWidth = Math.max(portal.offsetWidth, 200);
                let left = rect.left;
                const maxLeft = window.innerWidth - portalWidth - 8;
                if (left > maxLeft) left = Math.max(8, maxLeft);
                portal.style.top = (rect.bottom + 6) + 'px';
                portal.style.left = left + 'px';
            }
            document.querySelectorAll('.main-nav details.nav-group').forEach(details => {
                details.addEventListener('toggle', () => {
                    if (details.open) openNav(details);
                    else if (openDetails === details) closeNav();
                });
            });
            document.addEventListener('click', e => {
                if (portal.contains(e.target)) return;
                if (e.target.closest && e.target.closest('.main-nav details.nav-group')) return;
                closeNav();
            });
            window.addEventListener('resize', closeNav);
            window.addEventListener('scroll', closeNav, true);
        })();

        // ═══════════════════════ GLOBAL SEARCH ═══════════════════════
        let gsTimer = null;
        function openGlobalSearch() {
            document.getElementById('gs-overlay').classList.add('open');
            const inp = document.getElementById('gs-input');
            inp.value = '';
            document.getElementById('gs-results').innerHTML = '<div class="gs-hint">Type at least 2 characters — searches your personal tasks, project tasks, team tasks, projects, and teams.</div>';
            setTimeout(() => inp.focus(), 50);
        }
        function closeGlobalSearch() {
            document.getElementById('gs-overlay').classList.remove('open');
        }
        function gsDebouncedSearch() {
            clearTimeout(gsTimer);
            gsTimer = setTimeout(gsRunSearch, 220);
        }
        function gsEsc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
        async function gsRunSearch() {
            const q = document.getElementById('gs-input').value.trim();
            const results = document.getElementById('gs-results');
            if (q.length < 2) {
                results.innerHTML = '<div class="gs-hint">Type at least 2 characters — searches your personal tasks, project tasks, team tasks, projects, and teams.</div>';
                return;
            }
            results.innerHTML = '<div class="gs-hint">Searching…</div>';
            try {
                const res = await fetch('api/search.php?q=' + encodeURIComponent(q), { credentials: 'same-origin' });
                const data = await res.json();
                renderGlobalSearch(data, q);
            } catch (e) {
                results.innerHTML = '<div class="gs-hint">Search failed — try again.</div>';
            }
        }
        function gsGroup(title, items, renderRow) {
            if (!items || !items.length) return '';
            return `<div class="gs-group"><div class="gs-group-title">${title}</div>${items.map(renderRow).join('')}</div>`;
        }
        function renderGlobalSearch(data, q) {
            const results = document.getElementById('gs-results');
            const statusDot = s => `<span class="gs-dot ${s}"></span>`;
            const html =
                gsGroup('Personal tasks', data.personal_tasks, t =>
                    `<a class="gs-row" href="taskvel-pro.php">${statusDot(t.status)}<span class="gs-t">${gsEsc(t.title)}</span><span class="gs-sub">Personal</span></a>`) +
                gsGroup('Project tasks', data.project_tasks, t =>
                    `<a class="gs-row" href="project.php?id=${t.project_id}">${statusDot(t.status)}<span class="gs-t">${gsEsc(t.title)}</span><span class="gs-sub">${gsEsc(t.project_name)}</span></a>`) +
                gsGroup('Team tasks', data.team_tasks, t =>
                    `<a class="gs-row" href="team.php?id=${t.team_id}">${statusDot(t.status)}<span class="gs-t">${gsEsc(t.title)}</span><span class="gs-sub">${gsEsc(t.team_name)}</span></a>`) +
                gsGroup('Projects', data.projects, p =>
                    `<a class="gs-row" href="project.php?id=${p.id}"><span class="gs-dot" style="background:${p.color || '#4f46e5'}"></span><span class="gs-t">${gsEsc(p.name)}</span><span class="gs-sub">${gsEsc(p.team_name)}</span></a>`) +
                gsGroup('Teams', data.teams, t =>
                    `<a class="gs-row" href="team.php?id=${t.id}"><span class="gs-dot" style="background:var(--accent)"></span><span class="gs-t">${gsEsc(t.name)}</span><span class="gs-sub">${gsEsc(t.role)}</span></a>`);
            results.innerHTML = html || `<div class="gs-hint">No results for "${gsEsc(q)}".</div>`;
        }
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                openGlobalSearch();
            }
        });
    </script>
    <?php
}

function pro_footer(array $user): void
{
    ?>
    <div class="foot">
        <div class="n">Taskvel</div>
        <div class="d">Focus · Rank · Ship</div>
        <div class="k">Signed in as <b><?= htmlspecialchars($user['email']) ?></b> · <a href="terms.php" style="color:var(--accent);font-weight:600">Terms</a> · <a href="privacy.php" style="color:var(--accent);font-weight:600">Privacy</a> · <a href="#" onclick="logoutUser();return false;" style="color:var(--accent);font-weight:600">Log out</a></div>
    </div>
    <?php
}