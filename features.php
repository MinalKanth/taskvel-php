<?php
require_once __DIR__ . '/includes/auth.php';
if (!current_user_id()) { header('Location: login.php'); exit; }
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Everything Taskvel does · Taskvel</title>
<meta name="description" content="Every Taskvel feature in one place — capture, focus, organize, and work with a team, all inside one fast task OS.">
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta name="apple-mobile-web-app-title" content="Taskvel" />
<meta name="theme-color" content="#FAF8F3" media="(prefers-color-scheme: light)" />
<meta name="theme-color" content="#0A1128" media="(prefers-color-scheme: dark)" />
<link rel="icon"
    href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230a0a0a'/%3E%3Ctext x='50' y='72' font-family='Arial,sans-serif' font-size='62' font-weight='800' fill='%23ffffff' text-anchor='middle'%3ET%3C/text%3E%3C/svg%3E" />
<link rel="apple-touch-icon"
    href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230a0a0a'/%3E%3Ctext x='50' y='72' font-family='Arial,sans-serif' font-size='62' font-weight='800' fill='%23ffffff' text-anchor='middle'%3ET%3C/text%3E%3C/svg%3E" />
<link rel="manifest" href="manifest.json" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Sora:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" media="print" onload="this.media='all'">
<style>
/* ═══════ Same token system as taskvel-pro.php, so this page is never a foreign body ═══════ */
:root{
  --bg:#f6f6f4; --bg-elev:#ffffff; --bg-sunk:#ededea; --ink:#0a0a0a; --ink2:#3d3d3b; --ink3:#7c7c78; --ink4:#b4b4ae;
  --line:#e6e5e0; --line2:#d4d3cd; --paper:#ffffff; --accent:#0a0a0a; --accent-2:#3d3d3b;
  --accent-soft:rgba(10,10,10,.08); --accent-glow:rgba(10,10,10,.14); --on-accent:#ffffff;
  --good:#16a34a; --good-soft:rgba(22,163,74,.12); --warn:#d97706; --warn-soft:rgba(217,119,6,.12);
  --bad:#dc2626; --bad-soft:rgba(220,38,38,.12);
  --r:14px; --r-lg:20px; --shadow-sm:0 1px 2px rgba(10,10,10,.06); --shadow:0 10px 34px rgba(10,10,10,.10);
  --shadow-lg:0 24px 60px rgba(10,10,10,.16); --ease:cubic-bezier(.22,1,.36,1); --spring:cubic-bezier(.34,1.56,.64,1);
}
:root[data-theme="dark"]{
  --bg:#0b0b0b; --bg-elev:#161615; --bg-sunk:#070707; --ink:#f7f6f2; --ink2:#bcbbb3; --ink3:#84837c; --ink4:#56554f;
  --line:#262624; --line2:#393834; --paper:#1a1a18; --accent:#f7f6f2; --accent-2:#bcbbb3;
  --accent-soft:rgba(247,246,242,.10); --accent-glow:rgba(247,246,242,.16); --on-accent:#0a0a0a;
  --good:#34d399; --good-soft:rgba(52,211,153,.14); --warn:#fbbf24; --warn-soft:rgba(251,191,36,.14);
  --bad:#f87171; --bad-soft:rgba(248,113,113,.14);
  --shadow-sm:0 1px 2px rgba(0,0,0,.5); --shadow:0 10px 34px rgba(0,0,0,.55); --shadow-lg:0 24px 60px rgba(0,0,0,.7);
}
:root[data-accent="samal"]{ --bg:#FAF8F3; --bg-elev:#fff; --bg-sunk:#F3F1E9; --ink:#0A1128; --ink2:#3C4258; --ink3:#7A7F90; --ink4:#B9BCC6; --line:#EAE7DD; --line2:#D8D6CE; --paper:#fff; --accent:#C9A227; --accent-2:#0F4436; --accent-soft:rgba(201,162,39,.12); --accent-glow:rgba(201,162,39,.30); --on-accent:#fff; }
:root[data-accent="samal"][data-theme="dark"]{ --bg:#0A1128; --bg-elev:#121A36; --bg-sunk:#060B1C; --ink:#FAF8F3; --ink2:#C3C8DC; --ink3:#8990AC; --ink4:#525A78; --line:#1E2745; --line2:#2C365A; --paper:#121A36; --accent:#E8C766; --accent-2:#8FA0E8; --accent-soft:rgba(232,199,102,.14); --accent-glow:rgba(232,199,102,.34); --on-accent:#0A1128; }
:root[data-accent="indigo"]{ --bg:#f5f6fb; --bg-elev:#fff; --bg-sunk:#e9ebf6; --ink:#1a1d3a; --ink2:#474c70; --ink3:#8086a8; --ink4:#b6bad3; --line:#e3e6f2; --line2:#d0d4ea; --paper:#fff; --accent:#4f46e5; --accent-2:#6d28d9; --accent-soft:rgba(79,70,229,.10); --accent-glow:rgba(79,70,229,.28); --on-accent:#fff; }
:root[data-accent="indigo"][data-theme="dark"]{ --bg:#0c0e1c; --bg-elev:#161930; --bg-sunk:#080a16; --ink:#eef0ff; --ink2:#b3b8e0; --ink3:#7d83b0; --ink4:#4e5482; --line:#232745; --line2:#343a5e; --paper:#161930; --accent:#818cf8; --accent-2:#a78bfa; --accent-soft:rgba(129,140,248,.14); --accent-glow:rgba(129,140,248,.35); --on-accent:#0c0e1c; }
:root[data-accent="emerald"]{ --bg:#f3f8f5; --bg-elev:#fff; --bg-sunk:#e6f1ea; --ink:#0e2a20; --ink2:#3a5a4b; --ink3:#759084; --ink4:#aecabd; --line:#dcede4; --line2:#c6e0d2; --paper:#fff; --accent:#059669; --accent-2:#0d9488; --accent-soft:rgba(5,150,105,.10); --accent-glow:rgba(5,150,105,.26); --on-accent:#fff; }
:root[data-accent="emerald"][data-theme="dark"]{ --bg:#07140f; --bg-elev:#0f221b; --bg-sunk:#050f0b; --ink:#e6fff4; --ink2:#a7d6c2; --ink3:#6fa28d; --ink4:#44685a; --line:#1b3329; --line2:#294a3b; --paper:#0f221b; --accent:#34d399; --accent-2:#2dd4bf; --accent-soft:rgba(52,211,153,.14); --accent-glow:rgba(52,211,153,.32); --on-accent:#07140f; }
:root[data-accent="amber"]{ --bg:#fbf7f1; --bg-elev:#fff; --bg-sunk:#f4ece0; --ink:#2e1f0c; --ink2:#6a5232; --ink3:#a08560; --ink4:#d0bca0; --line:#efe5d6; --line2:#e2d3bd; --paper:#fff; --accent:#ea580c; --accent-2:#d97706; --accent-soft:rgba(234,88,12,.10); --accent-glow:rgba(234,88,12,.26); --on-accent:#fff; }
:root[data-accent="amber"][data-theme="dark"]{ --bg:#160e05; --bg-elev:#241809; --bg-sunk:#100a03; --ink:#fff2e0; --ink2:#e0c4a0; --ink3:#ad9170; --ink4:#6e5942; --line:#34230f; --line2:#4a3318; --paper:#241809; --accent:#fb923c; --accent-2:#fbbf24; --accent-soft:rgba(251,146,60,.14); --accent-glow:rgba(251,146,60,.34); --on-accent:#160e05; }

html{color-scheme:light;} html[data-theme="dark"]{color-scheme:dark;}
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent;}
html{scroll-behavior:smooth;}
body{font-family:'Sora',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);color:var(--ink);min-height:100vh;-webkit-font-smoothing:antialiased;transition:background .5s var(--ease),color .5s var(--ease);overflow-x:hidden;}
.wrap{max-width:1080px;margin:0 auto;padding:0 20px;}
@media (min-width:720px){ .wrap{padding:0 32px;} }

/* topbar */
.topbar{position:sticky;top:0;z-index:50;display:flex;align-items:center;justify-content:space-between;padding:16px 0;background:linear-gradient(180deg,var(--bg) 70%,transparent);backdrop-filter:blur(8px);}
.tb-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--ink);}
.tb-logo{width:34px;height:34px;border-radius:10px;background:var(--accent);color:var(--on-accent);display:flex;align-items:center;justify-content:center;font-family:'Sora';font-weight:800;font-size:16px;flex-shrink:0;}
.tb-brand span{font-family:'Sora';font-weight:800;font-size:17px;letter-spacing:-.4px;}
.tb-back{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border:1px solid var(--line);border-radius:100px;background:var(--bg-elev);color:var(--ink);text-decoration:none;font-family:'Space Grotesk';font-size:13px;font-weight:600;transition:all .25s var(--spring);box-shadow:var(--shadow-sm);}
.tb-back:hover{border-color:var(--accent);color:var(--accent);transform:translateY(-2px);}

/* ═══════ HERO — a real Taskvel card animating through its own lifecycle ═══════ */
.hero{padding:56px 0 40px;position:relative;}
@media (min-width:860px){ .hero{padding:80px 0 56px;} }
.hero-grid{display:grid;grid-template-columns:1fr;gap:44px;align-items:center;}
@media (min-width:860px){ .hero-grid{grid-template-columns:1.05fr .95fr;gap:56px;} }
.hero-eyebrow{display:inline-flex;align-items:center;gap:8px;font-family:'JetBrains Mono';font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--accent);}
.hero-eyebrow::before{content:'';width:16px;height:1px;background:var(--accent);}
.hero h1{font-family:'Sora';font-size:clamp(30px,5.5vw,50px);font-weight:800;letter-spacing:-1.2px;line-height:1.06;margin-top:16px;}
.hero h1 em{font-style:normal;color:var(--accent);}
.hero p.lead{font-size:16px;color:var(--ink3);line-height:1.65;margin-top:18px;max-width:480px;}
.hero-cta{display:flex;gap:12px;margin-top:30px;flex-wrap:wrap;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:14px 24px;border-radius:13px;border:none;font-family:'Sora';font-size:14.5px;font-weight:700;cursor:pointer;text-decoration:none;transition:transform .3s var(--spring),box-shadow .3s var(--ease);}
.btn-primary{background:var(--accent);color:var(--on-accent);box-shadow:0 10px 26px -8px var(--accent-glow);}
.btn-primary:hover{transform:translateY(-3px);box-shadow:0 16px 34px -8px var(--accent-glow);}
.btn-ghost{background:var(--bg-elev);color:var(--ink);border:1px solid var(--line);}
.btn-ghost:hover{border-color:var(--accent);color:var(--accent);transform:translateY(-2px);}

/* hero card demo — literally the app's .card/.step markup, animated */
.demo-stage{position:relative;perspective:1000px;}
.demo-card{background:var(--bg-elev);border:1px solid var(--line);border-radius:var(--r-lg);padding:20px;box-shadow:var(--shadow-lg);transition:transform .5s var(--spring);}
.demo-badges{display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap;}
.demo-badge{font-family:'JetBrains Mono';font-size:9.5px;font-weight:700;padding:4px 10px;border-radius:6px;letter-spacing:.5px;text-transform:uppercase;border:1px solid var(--line2);background:var(--bg-sunk);color:var(--ink2);}
.demo-badge.hot{background:var(--accent);color:var(--on-accent);border-color:var(--accent);}
.demo-title{font-family:'Space Grotesk';font-size:18px;font-weight:600;margin-bottom:6px;transition:all .3s;}
.demo-title.struck{text-decoration:line-through;color:var(--ink3);}
.demo-meta{font-size:12px;color:var(--ink3);margin-bottom:14px;}
.demo-prog{height:6px;border-radius:5px;background:var(--bg-sunk);margin-bottom:14px;overflow:hidden;}
.demo-prog i{display:block;height:100%;background:var(--accent);border-radius:5px;width:0%;transition:width .7s var(--ease);}
.demo-steps{display:flex;flex-direction:column;gap:9px;border-top:1px solid var(--line);padding-top:12px;}
.demo-step{display:flex;gap:10px;align-items:center;font-size:13px;color:var(--ink2);}
.demo-box{width:18px;height:18px;border:1.5px solid var(--line2);border-radius:6px;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .3s var(--spring);}
.demo-box.on{background:var(--accent);border-color:var(--accent);}
.demo-box.on::after{content:'✓';color:var(--on-accent);font-size:11px;font-weight:800;}
.demo-step-t.struck{text-decoration:line-through;color:var(--ink3);}
.demo-ring-wrap{position:absolute;top:-16px;right:-16px;width:64px;height:64px;background:var(--bg-elev);border:1px solid var(--line);border-radius:50%;box-shadow:var(--shadow);display:flex;align-items:center;justify-content:center;}
.demo-ring-wrap svg{transform:rotate(-90deg);}
.demo-ring-fg{fill:none;stroke:var(--accent);stroke-width:5;stroke-linecap:round;transition:stroke-dashoffset .4s linear;}
.demo-ring-time{position:absolute;font-family:'JetBrains Mono';font-size:11px;font-weight:700;}

/* ═══════ CAPABILITY RAILS — grouped by what you're doing, not a generic grid ═══════ */
.rail-section{padding:24px 0;}
.rail-head{margin-bottom:22px;}
.rail-eyebrow{font-family:'JetBrains Mono';font-size:10.5px;letter-spacing:2px;text-transform:uppercase;color:var(--ink3);}
.rail-head h2{font-family:'Sora';font-size:clamp(21px,3.4vw,28px);font-weight:800;letter-spacing:-.6px;margin-top:6px;}
.rail-head p{font-size:13.5px;color:var(--ink3);margin-top:6px;max-width:520px;line-height:1.55;}

.feat-grid{display:grid;grid-template-columns:1fr;gap:12px;}
@media (min-width:640px){ .feat-grid{grid-template-columns:1fr 1fr;} }
@media (min-width:980px){ .feat-grid{grid-template-columns:1fr 1fr 1fr;} }

.feat-card{background:var(--bg-elev);border:1px solid var(--line);border-radius:var(--r);padding:20px;position:relative;overflow:hidden;box-shadow:var(--shadow-sm);transition:transform .3s var(--spring),border-color .3s,box-shadow .35s;opacity:0;transform:translateY(16px);}
.feat-card.in{opacity:1;transform:translateY(0);transition:transform .6s var(--ease),opacity .6s var(--ease),border-color .3s,box-shadow .35s;}
.feat-card:hover{transform:translateY(-5px);border-color:var(--accent);box-shadow:var(--shadow);}
.feat-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--accent);opacity:0;transition:opacity .3s;}
.feat-card:hover::before{opacity:1;}
.feat-ic{width:38px;height:38px;border-radius:11px;background:var(--accent-soft);border:1px solid var(--accent-glow);display:flex;align-items:center;justify-content:center;font-size:17px;color:var(--accent);margin-bottom:14px;}
.feat-card h3{font-family:'Space Grotesk';font-size:15px;font-weight:700;margin-bottom:7px;}
.feat-card p{font-size:12.8px;color:var(--ink3);line-height:1.55;}
.feat-tag{position:absolute;top:16px;right:16px;font-family:'JetBrains Mono';font-size:8.5px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;padding:3px 8px;border-radius:6px;background:var(--good-soft);color:var(--good);}
.feat-tag.pro{background:var(--accent-soft);color:var(--accent);}

/* keyboard shortcuts strip */
.kbd-strip{display:flex;flex-wrap:wrap;gap:10px;margin-top:8px;}
.kbd-chip{display:flex;align-items:center;gap:8px;padding:9px 14px;border:1px solid var(--line);border-radius:10px;background:var(--bg-elev);font-size:12px;color:var(--ink2);}
.kbd-chip kbd{background:var(--bg-sunk);border:1px solid var(--line2);border-radius:5px;padding:2px 7px;font-family:'JetBrains Mono';font-size:10.5px;color:var(--ink);}

/* theme swatch preview row */
.theme-row{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;}
.theme-dot{width:38px;height:38px;border-radius:11px;flex-shrink:0;box-shadow:var(--shadow-sm);border:2px solid var(--line);transition:transform .3s var(--spring);}
.theme-dot:hover{transform:scale(1.12) rotate(-6deg);}
.theme-dot.samal{background:linear-gradient(135deg,#E8C766,#C9A227 55%,#0F4436);}
.theme-dot.mono{background:linear-gradient(135deg,#2a2a2a,#0a0a0a);}
.theme-dot.indigo{background:linear-gradient(135deg,#818cf8,#4f46e5);}
.theme-dot.emerald{background:linear-gradient(135deg,#34d399,#059669);}
.theme-dot.amber{background:linear-gradient(135deg,#fbbf24,#ea580c);}

/* final CTA */
.final-cta{margin:60px 0 40px;padding:44px 30px;border-radius:24px;background:linear-gradient(160deg,var(--accent-soft),transparent);border:1px solid var(--accent-glow);text-align:center;position:relative;overflow:hidden;}
.final-cta::before{content:'';position:absolute;top:-40%;left:50%;width:60%;padding-bottom:60%;transform:translateX(-50%);background:radial-gradient(circle,var(--accent-soft),transparent 70%);pointer-events:none;}
.final-cta h2{font-family:'Sora';font-size:clamp(22px,4vw,32px);font-weight:800;letter-spacing:-.6px;position:relative;}
.final-cta p{color:var(--ink3);font-size:14px;margin:10px 0 24px;position:relative;}
.final-cta .hero-cta{justify-content:center;position:relative;}

.foot{text-align:center;padding:30px 20px 50px;color:var(--ink3);font-size:11.5px;font-family:'JetBrains Mono';letter-spacing:.5px;}

@media (prefers-reduced-motion:reduce){ * { animation-duration:.001ms !important; transition-duration:.05ms !important; } }
:focus-visible{outline:2px solid var(--accent);outline-offset:2px;}

/* ═══════ PRICING ═══════ */
.pricing-section{padding:64px 0 24px;}
.pricing-head{text-align:center;max-width:560px;margin:0 auto 36px;}
.pricing-head .rail-eyebrow{justify-content:center;display:flex;}
.pricing-head h2{font-family:'Sora';font-size:clamp(24px,4.4vw,36px);font-weight:800;letter-spacing:-.7px;margin-top:10px;}
.pricing-head p{font-size:14px;color:var(--ink3);margin-top:10px;line-height:1.6;}

.plan-toggle-row{display:flex;flex-direction:column;align-items:center;gap:14px;margin-bottom:40px;}
.toggle-pill{display:inline-flex;padding:4px;border-radius:100px;background:var(--bg-sunk);border:1px solid var(--line);gap:2px;}
.toggle-pill button{
  border:none;background:transparent;padding:9px 18px;border-radius:100px;font-family:'Space Grotesk';
  font-size:12.5px;font-weight:700;color:var(--ink3);cursor:pointer;transition:all .3s var(--spring);
  display:inline-flex;align-items:center;gap:6px;white-space:nowrap;
}
.toggle-pill button.active{background:var(--accent);color:var(--on-accent);box-shadow:0 8px 20px -8px var(--accent-glow);}
.toggle-pill .save-tag{font-family:'JetBrains Mono';font-size:8.5px;background:var(--good-soft);color:var(--good);padding:1px 6px;border-radius:5px;letter-spacing:.3px;}
.toggle-pill button.active .save-tag{background:rgba(255,255,255,.22);color:var(--on-accent);}

.price-cards{display:grid;grid-template-columns:1fr;gap:16px;max-width:820px;margin:0 auto;}
@media (min-width:720px){ .price-cards{grid-template-columns:1fr 1fr;} }

.price-card{
  position:relative;background:var(--bg-elev);border:1px solid var(--line);border-radius:var(--r-lg);
  padding:28px 24px;box-shadow:var(--shadow-sm);transition:transform .35s var(--spring),box-shadow .35s var(--ease),border-color .3s;
  display:flex;flex-direction:column;overflow:hidden;
}
.price-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-lg);}
.price-card.hi{border-color:var(--accent);background:linear-gradient(165deg,var(--accent-soft),var(--bg-elev) 55%);}
.price-card.hi::before{
  content:'MOST POPULAR';position:absolute;top:0;right:0;font-family:'JetBrains Mono';font-size:9px;font-weight:700;
  letter-spacing:1px;background:var(--accent);color:var(--on-accent);padding:6px 14px;border-radius:0 0 0 12px;
}
.price-tier{font-family:'JetBrains Mono';font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--accent);font-weight:700;}
.price-amt{font-family:'Sora';font-size:38px;font-weight:800;letter-spacing:-1px;margin-top:10px;color:var(--ink);}
.price-amt span{font-family:'Space Grotesk';font-size:13px;font-weight:500;color:var(--ink3);}
.price-note{font-family:'JetBrains Mono';font-size:10.5px;color:var(--ink3);margin-top:4px;}
.price-desc{font-size:13px;color:var(--ink3);margin-top:12px;line-height:1.55;}
.price-feats{list-style:none;margin-top:20px;display:flex;flex-direction:column;gap:10px;flex:1;}
.price-feats li{display:flex;gap:9px;align-items:flex-start;font-size:12.8px;color:var(--ink2);line-height:1.5;}
.price-feats li::before{
  content:'✓';flex-shrink:0;width:17px;height:17px;border-radius:50%;margin-top:1px;
  display:flex;align-items:center;justify-content:center;background:var(--accent-soft);color:var(--accent);
  font-size:9px;font-weight:800;
}
.price-card .btn{margin-top:22px;width:100%;justify-content:center;}

/* ═══════ TRIAL EXPIRY NOTICE ═══════ */
.trial-notice{
  max-width:820px;margin:28px auto 0;border-radius:18px;padding:24px 26px;
  background:linear-gradient(150deg,var(--warn-soft),transparent 70%);
  border:1px solid rgba(217,119,6,.28);position:relative;overflow:hidden;
}
:root[data-theme="dark"] .trial-notice{border-color:rgba(251,191,36,.28);}
.trial-notice-head{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.trial-notice-ic{
  width:30px;height:30px;border-radius:9px;background:var(--warn);color:#fff;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;
}
.trial-notice h4{font-family:'Space Grotesk';font-size:14.5px;font-weight:700;}
.trial-notice p{font-size:12.8px;color:var(--ink2);line-height:1.65;}
.trial-notice p strong{color:var(--ink);font-weight:700;}
.trial-notice-list{list-style:none;margin-top:12px;display:flex;flex-direction:column;gap:7px;}
.trial-notice-list li{display:flex;gap:8px;font-size:12.3px;color:var(--ink3);align-items:flex-start;}
.trial-notice-list li::before{content:'—';color:var(--warn);flex-shrink:0;}

</style>
<script>
(function(){
  try{
    var t = localStorage.getItem('taskvel_theme_v1') || (window.matchMedia && matchMedia('(prefers-color-scheme:dark)').matches ? 'dark':'light');
    document.documentElement.setAttribute('data-theme', t);
    document.documentElement.setAttribute('data-accent', localStorage.getItem('taskvel_accent_v1') || 'samal');
  }catch(e){
    document.documentElement.setAttribute('data-theme','light');
    document.documentElement.setAttribute('data-accent','samal');
  }
})();
</script>
</head>
<body>

<div class="wrap">
  <div class="topbar">
    <a class="tb-brand" href="taskvel-pro.php"><div class="tb-logo">T</div><span>Taskvel</span></a>
    <a class="tb-back" href="taskvel-pro.php">← Back to your tasks</a>
  </div>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-grid">
      <div>
        <span class="hero-eyebrow">Everything, in one place</span>
        <h1>One task app.<br>No feature <em>you'll outgrow.</em></h1>
        <p class="lead">Taskvel ranks what matters, keeps you focused, remembers what you tell it, and stays in sync everywhere you work — solo or with a team. Here's the whole thing, laid out properly.</p>
        <div class="hero-cta">
          <a class="btn btn-primary" href="taskvel-pro.php">Open Taskvel →</a>
          <button class="btn btn-ghost" id="replay-tour-btn">↻ Replay the quick tour</button>
        </div>
      </div>

      <div class="demo-stage">
        <div class="demo-card" id="demo-card">
          <div class="demo-badges">
            <span class="demo-badge hot" id="demo-rank">◆ High</span>
            <span class="demo-badge" id="demo-due">2d left</span>
          </div>
          <div class="demo-title" id="demo-title">Send the client proposal</div>
          <div class="demo-meta">◴ Waiting on: You · ↯ score 8</div>
          <div class="demo-prog"><i id="demo-prog-fill"></i></div>
          <div class="demo-steps" id="demo-steps">
            <div class="demo-step"><div class="demo-box" id="db-0"></div><span class="demo-step-t" id="dt-0">Draft the outline</span></div>
            <div class="demo-step"><div class="demo-box" id="db-1"></div><span class="demo-step-t" id="dt-1">Get pricing signed off</span></div>
            <div class="demo-step"><div class="demo-box" id="db-2"></div><span class="demo-step-t" id="dt-2">Send for review</span></div>
          </div>
        </div>
        <div class="demo-ring-wrap">
          <svg width="64" height="64" viewBox="0 0 64 64">
            <circle cx="32" cy="32" r="26" fill="none" stroke="var(--bg-sunk)" stroke-width="5"/>
            <circle class="demo-ring-fg" id="demo-ring" cx="32" cy="32" r="26" stroke-dasharray="163.4" stroke-dashoffset="0"/>
          </svg>
          <div class="demo-ring-time" id="demo-ring-time">25:00</div>
        </div>
      </div>
    </div>
  </section>

  <!-- CAPTURE -->
  <section class="rail-section">
    <div class="rail-head">
      <span class="rail-eyebrow">01 — Capture</span>
      <h2>Get it out of your head, instantly</h2>
      <p>Adding a task should take seconds, not a form. Type naturally and Taskvel figures out the rest.</p>
    </div>
    <div class="feat-grid" data-stagger>
      <div class="feat-card"><div class="feat-ic">✨</div><h3>Smart Quick-Add</h3><p>Type "Call the client tomorrow #work !high" and it's parsed into a due date, tag, and urgency automatically — no dropdowns.</p></div>
      <div class="feat-card"><div class="feat-ic">↯</div><h3>Auto-ranking</h3><p>Set urgency × impact once and Taskvel scores and sorts your list — the right task always floats to the top.</p></div>
      <div class="feat-card"><div class="feat-ic">▧</div><h3>Templates</h3><p>Save any task's structure — steps, tags, priority — and reuse it for recurring workflows in one tap.</p></div>
      <div class="feat-card"><div class="feat-ic">⌘</div><h3>Command palette</h3><p>Press <kbd style="font-family:'JetBrains Mono';font-size:11px;background:var(--bg-sunk);border:1px solid var(--line2);border-radius:4px;padding:1px 6px">⌘K</kbd> and jump to any action — add, export, switch theme — without touching the mouse.</p></div>
      <div class="feat-card"><div class="feat-ic">↻</div><h3>Recurring tasks</h3><p>Daily, weekly, monthly, or a custom RRULE — completing one spawns the next occurrence automatically.</p></div>
      <div class="feat-card"><div class="feat-ic">🔗</div><h3>Links &amp; resources</h3><p>Attach reference links directly to a task or a single step, so context lives right where the work happens.</p></div>
    </div>
  </section>

  <!-- FOCUS -->
  <section class="rail-section">
    <div class="rail-head">
      <span class="rail-eyebrow">02 — Focus</span>
      <h2>Deep work, without losing the rest of your list</h2>
      <p>A Pomodoro timer that stays out of your way — floats as a mini pill so you can keep working while it runs.</p>
    </div>
    <div class="feat-grid" data-stagger>
      <div class="feat-card"><div class="feat-ic">◉</div><h3>Focus timer</h3><p>Classic 25/5, longer 50/10, or set your own custom lengths — every session logs itself automatically.</p></div>
      <div class="feat-card"><div class="feat-ic">◔</div><h3>Floating mini-timer</h3><p>A draggable pill keeps your session visible and controllable while you browse, edit, and add tasks elsewhere.</p></div>
      <div class="feat-card"><div class="feat-ic">▤</div><h3>Focus history</h3><p>Today, this week, and your daily average — a 7-day chart shows exactly where your attention went.</p></div>
      <div class="feat-card"><div class="feat-ic">⏱</div><h3>Time tracking</h3><p>Start/stop a stopwatch on any task independent of the Pomodoro timer, then see time spent per tag in one report.</p></div>
      <div class="feat-card"><div class="feat-ic">🔥</div><h3>Streaks</h3><p>Every day you show up counts. Miss a day and it resets — a small, honest nudge to keep momentum.</p></div>
      <div class="feat-card"><div class="feat-ic">🎯</div><h3>Daily goal &amp; score</h3><p>Set a tasks-per-day target and watch a live productivity score blend completion rate, streak, and focus minutes.</p></div>
    </div>
  </section>

  <!-- ORGANIZE -->
  <section class="rail-section">
    <div class="rail-head">
      <span class="rail-eyebrow">03 — Organize</span>
      <h2>Everything findable, nothing forgotten</h2>
      <p>Deadlines that escalate themselves. Views that actually answer "what should I do right now."</p>
    </div>
    <div class="feat-grid" data-stagger>
      <div class="feat-card"><div class="feat-ic">📅</div><h3>Deadlines that escalate</h3><p>A task's priority automatically climbs as its deadline approaches — no manual re-prioritizing required.</p></div>
      <div class="feat-card"><div class="feat-ic">◫</div><h3>Eisenhower Matrix</h3><p>Every open task sorted into Do First, Schedule, Quick Wins, and Later — a real decision tool, not just a list.</p></div>
      <div class="feat-card"><div class="feat-ic">🏷</div><h3>Tags &amp; search</h3><p>Tag freely, filter instantly, and search across names, people, notes, and steps all at once.</p></div>
      <div class="feat-card"><div class="feat-ic">❝</div><h3>Remarks</h3><p>Log context, updates, and follow-ups against any task — a running notebook attached to the work itself.</p></div>
      <div class="feat-card"><div class="feat-ic">📊</div><h3>Weekly Review</h3><p>A standing summary of focus minutes, tasks closed, streak, and your most-used tags for the last 7 days.</p></div>
      <div class="feat-card"><div class="feat-ic">↓</div><h3>Export anywhere</h3><p>CSV, print-ready PDF, or a .ics calendar file of every deadline — filterable by person, tag, status, or date range.</p></div>
    </div>
  </section>

  <!-- TOGETHER -->
  <section class="rail-section">
    <div class="rail-head">
      <span class="rail-eyebrow">04 — Together</span>
      <h2>Built for teams, not just to-do lists</h2>
      <p>Assign work, plan events, and see what a whole team is actually getting done.</p>
    </div>
    <div class="feat-grid" data-stagger>
      <div class="feat-card"><span class="feat-tag pro">Teams</span><div class="feat-ic">👥</div><h3>Teams &amp; roles</h3><p>Owners, managers, and members — invite coworkers and control who can assign, edit, and delete.</p></div>
      <div class="feat-card"><span class="feat-tag pro">Teams</span><div class="feat-ic">▦</div><h3>Projects &amp; Kanban</h3><p>A full to-do / in-progress / done board per project, with comments and per-person progress tracking.</p></div>
      <div class="feat-card"><span class="feat-tag pro">Teams</span><div class="feat-ic">📆</div><h3>Team events</h3><p>Plan meetings and deadlines with attendees, RSVPs, and a live "who's actually going" view.</p></div>
      <div class="feat-card"><span class="feat-tag pro">Office</span><div class="feat-ic">📍</div><h3>Daily Check-in</h3><p>Check in, log tasks with report-to emails, take tracked breaks, and check out with an auto-generated summary.</p></div>
      <div class="feat-card"><span class="feat-tag pro">Office</span><div class="feat-ic">📈</div><h3>Manager Dashboard</h3><p>Per-person productivity, a 7-day completion trend, late check-ins, overtime, and overdue flags — all in one view.</p></div>
      <div class="feat-card"><span class="feat-tag pro">Teams</span><div class="feat-ic">🔔</div><h3>Task assignment alerts</h3><p>Assignees get notified the moment work lands on them, and creators hear back the moment it's done.</p></div>
    </div>
  </section>

  <!-- YOURS -->
  <section class="rail-section">
    <div class="rail-head">
      <span class="rail-eyebrow">05 — Yours</span>
      <h2>It looks and works the way you want</h2>
      <p>Every device stays in sync. Every color, every keystroke, tuned to you.</p>
    </div>
    <div class="feat-grid" data-stagger>
      <div class="feat-card"><div class="feat-ic">☁</div><h3>Multi-device sync</h3><p>Add a task on your phone, finish it on your laptop — everything reconciles automatically, even offline.</p></div>
      <div class="feat-card"><div class="feat-ic">🔔</div><h3>Push notifications</h3><p>Real OS-level alerts for assignments and deadlines, delivered even when the Taskvel tab is closed.</p></div>
      <div class="feat-card"><div class="feat-ic">⇩</div><h3>Full backup &amp; restore</h3><p>Download every task, remark, and setting as one JSON file — restore it on any device, any time.</p></div>
      <div class="feat-card"><div class="feat-ic">☾</div><h3>Light &amp; dark mode</h3><p>Switch instantly, or let it follow your system setting automatically.</p></div>
      <div class="feat-card"><div class="feat-ic">◑</div><h3>5 color themes</h3><p>Samal, Mono, Indigo, Emerald, Amber — pick the palette that feels like you.
        <div class="theme-row"><span class="theme-dot samal"></span><span class="theme-dot mono"></span><span class="theme-dot indigo"></span><span class="theme-dot emerald"></span><span class="theme-dot amber"></span></div>
      </p></div>
      <div class="feat-card"><div class="feat-ic">⌨</div><h3>Keyboard-first</h3><p>Fly through Taskvel without a mouse.
        <div class="kbd-strip">
          <span class="kbd-chip"><kbd>⌘K</kbd> commands</span>
          <span class="kbd-chip"><kbd>N</kbd> new task</span>
          <span class="kbd-chip"><kbd>/</kbd> search</span>
          <span class="kbd-chip"><kbd>Space</kbd> timer</span>
          <span class="kbd-chip"><kbd>D</kbd> mark done</span>
        </div>
      </p></div>
    </div>
  </section>

  <!-- PRICING -->
  <section class="rail-section pricing-section" id="pricing">
    <div class="pricing-head">
      <span class="rail-eyebrow">06 — Pricing</span>
      <h2>Simple, honest pricing.</h2>
      <p>Every plan starts with a 14-day free trial — every premium feature unlocked, no card required to begin.</p>
    </div>

    <div class="plan-toggle-row">
      <div class="toggle-pill" id="typeToggle">
        <button class="active" data-type="individual">Individual</button>
        <button data-type="enterprise">Enterprise / Team</button>
      </div>
      <div class="toggle-pill" id="cycleToggle">
        <button class="active" data-cycle="monthly">Monthly</button>
        <button data-cycle="six">6 Months <span class="save-tag">Save 15%</span></button>
        <button data-cycle="yearly">Yearly <span class="save-tag">Save 30%</span></button>
      </div>
    </div>

    <div class="price-cards" id="priceCards"></div>

    <div class="trial-notice">
      <div class="trial-notice-head">
        <div class="trial-notice-ic">!</div>
        <h4>What happens after your free trial</h4>
      </div>
      <p>Your first <strong>14 days are fully free</strong> — every premium feature is unlocked, exactly as shown on this page. If you started on a <strong>1-month free promotional plan</strong>, here's exactly what happens the moment it ends:</p>
      <ul class="trial-notice-list">
        <li>Your account automatically reverts to the <strong>Free tier</strong> — no charge is ever made without your consent.</li>
        <li>Premium features are locked: <strong>team collaboration, analytics dashboard, multi-device sync, manager dashboard, compliance tracker, and push notifications</strong> will stop working.</li>
        <li>Your existing tasks, remarks, and data are <strong>never deleted</strong> — everything is preserved and waiting the moment you subscribe.</li>
        <li>Re-activate instantly at any time by choosing a plan above — there's no re-onboarding, you pick up exactly where you left off.</li>
      </ul>
    </div>
  </section>

  <!-- FINAL CTA -->
  <div class="final-cta">
    <h2>That's the whole toolkit.</h2>
    <p>No upsells buried behind this page — everything shown here is already in your account.</p>
    <div class="hero-cta">
      <a class="btn btn-primary" href="taskvel-pro.php">Back to your tasks →</a>
    </div>
  </div>

  <div class="foot">TASKVEL · BY SAMAL CONSULTANCY</div>
</div>

<script>
const TV_UID = <?= (int)current_user_id() ?>;

// ── Replay tour: clears this user's onboarding flag, then sends them back
// so the original first-run carousel in taskvel-pro.php shows again. ──
document.getElementById('replay-tour-btn').addEventListener('click', function(){
  try { localStorage.setItem('taskvel_u' + TV_UID + '_onboarded_v1', '0'); } catch(e){}
  window.location.href = 'taskvel-pro.php';
});

// ── Hero demo: a real task card cycling through its own lifecycle on loop ──
(function(){
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const steps = [0,1,2];
  const dbEls = steps.map(i => document.getElementById('db-'+i));
  const dtEls = steps.map(i => document.getElementById('dt-'+i));
  const progFill = document.getElementById('demo-prog-fill');
  const titleEl = document.getElementById('demo-title');
  const rankEl = document.getElementById('demo-rank');
  const dueEl = document.getElementById('demo-due');

  function setProgress(n){
    dbEls.forEach((el,i) => el.classList.toggle('on', i < n));
    dtEls.forEach((el,i) => el.classList.toggle('struck', i < n));
    progFill.style.width = Math.round(n/3*100) + '%';
  }

  let cycle = 0;
  function runCycle(){
    setProgress(0);
    titleEl.classList.remove('struck');
    rankEl.textContent = '◆ High';
    rankEl.classList.add('hot');
    dueEl.textContent = '2d left';
    if (prefersReduced) return;
    setTimeout(() => setProgress(1), 900);
    setTimeout(() => setProgress(2), 2000);
    setTimeout(() => { setProgress(3); titleEl.classList.add('struck'); rankEl.textContent='✓ Done'; dueEl.textContent='Completed'; }, 3300);
    setTimeout(runCycle, 5600);
  }
  runCycle();

  // Mini focus ring ticking down, purely decorative
  const RING_LEN = 163.4;
  let remaining = 25*60, total = 25*60;
  const ringEl = document.getElementById('demo-ring');
  const timeEl = document.getElementById('demo-ring-time');
  if (!prefersReduced){
    setInterval(() => {
      remaining -= 6; // accelerated for demo purposes
      if (remaining <= 0) remaining = total;
      const m = String(Math.floor(remaining/60)).padStart(2,'0');
      const s = String(remaining%60).padStart(2,'0');
      timeEl.textContent = m+':'+s;
      ringEl.style.strokeDashoffset = RING_LEN * (1 - remaining/total);
    }, 400);
  }
})();

// ── Scroll-reveal for feature cards ──
(function(){
  const cards = document.querySelectorAll('.feat-card');
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e,) => {
      if (e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); }
    });
  }, { threshold: 0.15 });
  cards.forEach((c,i) => { c.style.transitionDelay = (i % 3 * 60) + 'ms'; io.observe(c); });
})();
// ── Pricing: type + cycle toggle ──
(function(){
  const planData = {
    individual: {
      monthly: { price: 49, unit: '/mo', note: 'Billed monthly' },
      six:     { price: 42, unit: '/mo', note: 'Billed ₹252 every 6 months' },
      yearly:  { price: 34, unit: '/mo', note: 'Billed ₹408 per year' }
    },
    enterprise: {
      monthly: { price: 99, unit: '/user/mo', note: 'Billed monthly · per user' },
      six:     { price: 84, unit: '/user/mo', note: 'Billed ₹504/user every 6 months' },
      yearly:  { price: 69, unit: '/user/mo', note: 'Billed ₹828/user per year' }
    }
  };
  const planFeats = {
    individual: [
      'Everything in Free',
      'Multi-device sync & personal analytics',
      'Custom Pomodoro focus sessions',
      'Compliance & client tracker',
      'Real push notifications, even offline',
      'Priority email support'
    ],
    enterprise: [
      'Everything in Individual',
      'Team collaboration & task assignment',
      'Role & permission control',
      'Daily check-in & manager dashboard',
      'Team-wide analytics dashboard',
      'Dedicated priority support & onboarding'
    ]
  };

  let curType = 'individual', curCycle = 'monthly';
  const cardsEl = document.getElementById('priceCards');

  function render(){
    const p = planData[curType][curCycle];
    const feats = planFeats[curType];
    cardsEl.innerHTML = `
      <div class="price-card">
        <span class="price-tier">Free</span>
        <div class="price-amt">₹0<span>/forever</span></div>
        <p class="price-desc">Solo task tracking with the essentials — always free, no trial needed.</p>
        <ul class="price-feats">
          <li>Task ranking & Pomodoro timer</li>
          <li>Tags, deadlines, recurring tasks</li>
          <li>CSV / PDF / calendar export</li>
          <li>Works offline, installs like an app</li>
        </ul>
        <a href="taskvel-pro.php" class="btn btn-ghost">Continue on Free</a>
      </div>
      <div class="price-card hi">
        <span class="price-tier">${curType === 'enterprise' ? 'Enterprise' : 'Premium'}</span>
        <div class="price-amt">₹${p.price}<span>${p.unit}</span></div>
        <div class="price-note">${p.note}</div>
        <p class="price-desc">${curType === 'enterprise'
          ? 'For anyone using team features. Every member is billed individually at this rate.'
          : 'For professionals who need the full toolkit, every day.'}</p>
        <ul class="price-feats">${feats.map(f => `<li>${f}</li>`).join('')}</ul>
        <a href="register.php?plan=${curType}&cycle=${curCycle}" class="btn btn-primary">Start 14-day free trial →</a>
      </div>
    `;
  }

  document.querySelectorAll('#typeToggle button').forEach(b=>{
    b.addEventListener('click', ()=>{
      document.querySelectorAll('#typeToggle button').forEach(x=>x.classList.remove('active'));
      b.classList.add('active'); curType = b.dataset.type; render();
    });
  });
  document.querySelectorAll('#cycleToggle button').forEach(b=>{
    b.addEventListener('click', ()=>{
      document.querySelectorAll('#cycleToggle button').forEach(x=>x.classList.remove('active'));
      b.classList.add('active'); curCycle = b.dataset.cycle; render();
    });
  });

  render();
})();
</script>
</body>
</html>