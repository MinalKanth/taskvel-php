<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/db.php';

$q         = trim((string) ($_GET['q'] ?? ''));
$fDate     = trim((string) ($_GET['date'] ?? ''));
$fLocation = trim((string) ($_GET['location'] ?? ''));

$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(e.title LIKE :q OR e.location LIKE :q OR e.short_description LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($fDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fDate)) {
    $where[] = 'e.event_date = :date';
    $params[':date'] = $fDate;
}
if ($fLocation !== '') {
    $where[] = 'e.location = :location';
    $params[':location'] = $fLocation;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$events = [];
$locations = [];
$nextEvent = null;
$dbError = null;

try {
    $stmt = db()->prepare(
        "SELECT e.*, c.name AS category_name,
                (SELECT COUNT(*) FROM event_images ei WHERE ei.event_id = e.id) AS photo_count
         FROM events e
         LEFT JOIN event_categories c ON c.id = e.category_id
         $whereSql
         ORDER BY e.event_date ASC, e.id ASC"
    );
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    $locations = db()->query('SELECT DISTINCT location FROM events ORDER BY location ASC')->fetchAll(PDO::FETCH_COLUMN);

    // Spotlight: the soonest upcoming/ongoing event, regardless of filters
    $nextStmt = db()->query(
        "SELECT e.*, c.name AS category_name
         FROM events e
         LEFT JOIN event_categories c ON c.id = e.category_id
         WHERE e.status IN ('upcoming','ongoing')
         ORDER BY e.event_date ASC, e.id ASC
         LIMIT 1"
    );
    $nextEvent = $nextStmt->fetch();
} catch (Throwable $e) {
    $dbError = 'Events could not be loaded right now. Please check the database connection.';
}

$STATUS_LABELS = ['upcoming' => 'Upcoming', 'ongoing' => 'Happening Now', 'completed' => 'Completed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Events Near You | Samal Consultancy</title>
<meta name="description" content="Discover curated events near you — with dates, venues, galleries, nearby hotels and direct booking links. Brought to you by Samal Consultancy.">
<link rel="icon" href="images/favicon.ico" type="image/x-icon">
<meta name="theme-color" content="#0A1128">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600&display=swap" rel="stylesheet">
<style>
/* ============================= TOKENS (same as homepage) ============================= */
:root{
  --amber:#C9A227;
  --amber-2:#E8C766;
  --amber-deep:#8F7112;
  --teal:#1B2A6B;
  --teal-2:#8FA0E8;
  --teal-deep:#12513E;
  --ivory:#FAF8F3;
  --paper:#F3F1E9;
  --charcoal:#1C1C1A;
  --navy-ink:#0A1128;
  --navy-2:#0F4436;
  --silver:#D8D6CE;
  --line:rgba(28,28,26,0.10);
  --line-invert:rgba(255,255,255,0.14);
  --shadow-lg: 0 30px 80px -30px rgba(10,17,40,0.35);
  --shadow-sm: 0 10px 30px -12px rgba(10,17,40,0.18);
  --radius-lg: 22px;
  --radius-md: 14px;
  --ease: cubic-bezier(.22,1,.36,1);
  --font-display:'Space Grotesk', sans-serif;
  --font-body:'Inter', sans-serif;
  --font-eyebrow:'Plus Jakarta Sans', sans-serif;
}
*{box-sizing:border-box;}
html{scroll-behavior:smooth;}
@media (prefers-reduced-motion: reduce){
  html{scroll-behavior:auto;}
  *{animation-duration:0.001ms !important; animation-iteration-count:1 !important; transition-duration:0.001ms !important;}
}
body{
  margin:0; font-family:var(--font-body); background:var(--ivory); color:var(--charcoal);
  -webkit-font-smoothing:antialiased; overflow-x:hidden;
}
img{max-width:100%;display:block;}
a{color:inherit;}
ul{margin:0;padding:0;}
button{font-family:inherit;}
::selection{background:var(--teal-2); color:var(--navy-ink);}
.wrap{max-width:1240px;margin:0 auto;padding:0 20px;}
@media (min-width:640px){ .wrap{padding:0 28px;} }
@media (min-width:1024px){ .wrap{padding:0 32px;} }

.eyebrow{
  font-family:var(--font-eyebrow); font-size:12px; font-weight:600; letter-spacing:.12em; text-transform:uppercase;
  display:inline-flex; align-items:center; gap:8px; color:var(--teal-deep);
}
.eyebrow::before{content:''; width:18px;height:1px; background:var(--amber);}
h1,h2,h3,h4{font-family:var(--font-display); margin:0; letter-spacing:-0.01em; color:var(--navy-ink);}
p{line-height:1.7; color:#54514C; margin:0;}
section{position:relative;}
.pad{padding:64px 0;}
@media (min-width:768px){ .pad{padding:100px 0;} }

.btn{
  position:relative; overflow:hidden; display:inline-flex; align-items:center; justify-content:center; gap:10px;
  padding:15px 26px; font-weight:600; font-size:14.5px; border-radius:100px; text-decoration:none; cursor:pointer;
  border:1px solid transparent; transition:transform .35s var(--ease), box-shadow .35s var(--ease), background .3s ease, color .3s ease;
  white-space:nowrap; -webkit-tap-highlight-color:transparent;
}
@media (min-width:640px){ .btn{padding:16px 30px; font-size:15px;} }
.btn:active{transform:scale(.96);}
.btn-gold{ background:linear-gradient(135deg,var(--amber-2),var(--amber)); color:#fff; box-shadow:0 14px 30px -10px rgba(224,108,31,0.55); }
.btn-gold:hover{transform:translateY(-3px); box-shadow:0 20px 40px -12px rgba(224,108,31,0.6);}
.btn-ghost{ background:transparent; color:var(--ivory); border-color:rgba(255,255,255,0.35); }
.btn-ghost:hover{background:rgba(255,255,255,0.10); border-color:rgba(255,255,255,0.6);}
.btn-outline-dark{ background:transparent;color:var(--teal-deep);border-color:rgba(14,124,140,0.3); }
.btn-outline-dark:hover{background:var(--teal-deep); color:var(--ivory); border-color:var(--teal-deep);}
.btn-sm{padding:10px 18px; font-size:12.5px;}

.ripple-span{ position:absolute; border-radius:50%; background:rgba(255,255,255,0.55); transform:scale(0); animation:rippleAnim .6s ease-out forwards; pointer-events:none; }
@keyframes rippleAnim{ to{ transform:scale(2.6); opacity:0; } }

/* ============================= NAV (same as homepage) ============================= */
.navbar{ position:fixed; top:0; left:0; right:0; z-index:999; padding:16px 0; transition:padding .35s var(--ease), background .35s var(--ease), box-shadow .35s var(--ease); }
.navbar.scrolled{ padding:10px 0; background:rgba(250,247,242,0.85); backdrop-filter:blur(16px) saturate(160%); box-shadow:0 8px 30px -14px rgba(27,31,39,0.18); }
.nav-inner{display:flex; align-items:center; justify-content:space-between; gap:16px;}
.brand{display:flex; align-items:center; gap:10px; text-decoration:none; min-width:0;}
.brand-logo{ height:auto; width:auto; max-width:100%; max-height:clamp(42px,6vw,60px); object-fit:contain; transition:transform .3s ease; }
.brand-logo:hover{transform:scale(1.03);}
.brand-mark{
  width:42px;height:42px;border-radius:12px; display:flex; align-items:center;justify-content:center;
  background:linear-gradient(135deg,#ff8c00,#ff6a00); color:#fff; font-weight:700; font-size:1rem;
}
.brand-name{color:#ff7a00; font-size:1.2rem; font-weight:700; margin-left:10px; font-family:var(--font-display); white-space:nowrap;}
#navLogoFallback{display:none; align-items:center; justify-content:center;}
@media (max-width:768px){ .brand-name{display:none;} .brand-logo{max-height:46px;} }
@media (max-width:480px){ .brand-logo{max-height:40px;} }

.nav-links{list-style:none; display:flex; align-items:center; gap:30px;}
.nav-links a{ text-decoration:none; font-size:14.5px; font-weight:600; letter-spacing:.01em; position:relative; padding:6px 0; }
.nav-links a::after{ content:''; position:absolute; left:0; bottom:0; height:1.5px; width:0%; background:var(--amber); transition:width .35s var(--ease); }
.nav-links a:hover::after, .nav-links a.current::after{width:100%;}
.nav-cta{display:flex; align-items:center; gap:14px;}
.menu-toggle{display:flex; flex-direction:column; gap:5px; cursor:pointer; background:none;border:none; padding:8px; position:relative; z-index:1000; -webkit-tap-highlight-color:transparent;}
.menu-toggle span{width:22px;height:2px;background:currentColor;border-radius:2px;transition:all .3s ease; transform-origin:left center;}
.menu-toggle.open span:nth-child(1){transform:rotate(45deg);}
.menu-toggle.open span:nth-child(2){opacity:0; transform:translateX(-8px);}
.menu-toggle.open span:nth-child(3){transform:rotate(-45deg);}
.menu-toggle.open{color:var(--ivory) !important;}
.navbar:not(.scrolled) .nav-links a, .navbar:not(.scrolled) .menu-toggle{color:var(--ivory);}
.navbar.scrolled .nav-links a, .navbar.scrolled .menu-toggle{color:var(--navy-ink);}
.nav-links{
  position:fixed; inset:0 0 0 auto; width:min(320px,82%); height:100dvh;
  background:linear-gradient(180deg,var(--navy-ink),var(--teal-deep));
  flex-direction:column; justify-content:center; align-items:flex-start;
  padding:40px; gap:26px; transform:translateX(100%); transition:transform .45s var(--ease);
  box-shadow:-20px 0 60px rgba(0,0,0,0.25);
}
.nav-links.open{transform:translateX(0);}
.nav-links a{color:var(--ivory) !important; font-size:19px;}
.nav-cta .btn-gold{padding:12px 20px; font-size:13px;}
@media (min-width:901px){
  .nav-links{ position:static; width:auto; height:auto; background:none; flex-direction:row; padding:0; gap:34px; transform:none; box-shadow:none; }
  .nav-links a{font-size:14.5px !important;}
  .menu-toggle{display:none;}
  .navbar:not(.scrolled) .nav-links a{color:var(--ivory) !important;}
  .navbar.scrolled .nav-links a{color:var(--navy-ink) !important;}
  .nav-cta .btn-gold{padding:14px 26px; font-size:14px;}
}
.mobile-cta-bar{
  position:fixed; left:0; right:0; bottom:0; z-index:850; display:flex; gap:10px;
  padding:12px 16px calc(12px + env(safe-area-inset-bottom));
  background:rgba(250,247,242,0.92); backdrop-filter:blur(14px); border-top:1px solid var(--line);
  transform:translateY(100%); transition:transform .4s var(--ease);
}
.mobile-cta-bar.show{transform:translateY(0);}
.mobile-cta-bar a{flex:1; text-align:center;}
@media (min-width:901px){ .mobile-cta-bar{display:none;} }
.back-to-top{
  position:fixed; right:18px; bottom:80px; width:46px; height:46px; border-radius:50%;
  background:rgba(27,31,39,0.85); border:1px solid rgba(255,255,255,0.14); backdrop-filter:blur(10px);
  color:var(--teal-2); display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:900;
  opacity:0; visibility:hidden; transform:translateY(16px);
  transition:opacity .4s var(--ease), transform .4s var(--ease), background .3s ease, border-color .3s ease;
  box-shadow:var(--shadow-sm); -webkit-tap-highlight-color:transparent;
}
@media (min-width:901px){ .back-to-top{right:28px; bottom:28px; width:52px; height:52px;} }
.back-to-top.show{opacity:1; visibility:visible; transform:translateY(0);}
.back-to-top:hover{background:linear-gradient(135deg,var(--amber-2),var(--amber)); color:#fff; border-color:var(--amber); transform:translateY(-4px);}
.scroll-progress{
  position:fixed; top:0; left:0; height:3px; width:0%; z-index:1200;
  background:linear-gradient(90deg,var(--amber),var(--amber-2),var(--teal-2));
  box-shadow:0 0 14px rgba(232,199,102,0.55); transition:width .12s linear;
}

/* ============================= EVENTS HERO ============================= */
.ev-hero{
  position:relative; min-height:78vh; display:flex; align-items:center;
  background:
    radial-gradient(1100px 560px at 88% -10%, rgba(232,199,102,0.14), transparent 60%),
    radial-gradient(900px 500px at -10% 110%, rgba(27,42,107,0.35), transparent 60%),
    linear-gradient(160deg, var(--navy-ink) 0%, var(--teal-deep) 65%, #0F4436 100%);
  padding:130px 0 90px; overflow:hidden;
}
.ev-hero-orbs{position:absolute; inset:0; overflow:hidden; pointer-events:none; z-index:0;}
.orb{position:absolute; border-radius:50%; filter:blur(70px); opacity:.32; animation:orbFloat 16s ease-in-out infinite;}
.orb-1{width:300px;height:300px; background:var(--amber-2); top:-70px; right:6%; animation-duration:17s;}
.orb-2{width:230px;height:230px; background:var(--teal-2); bottom:-50px; left:4%; animation-duration:20s; animation-delay:2s;}
@keyframes orbFloat{0%,100%{transform:translate(0,0) scale(1);} 50%{transform:translate(28px,-26px) scale(1.14);}}
@media (prefers-reduced-motion: reduce){ .orb{animation:none;} }

.ev-hero-grid{position:relative; z-index:1; display:grid; grid-template-columns:1fr; gap:44px; align-items:center;}
@media (min-width:980px){ .ev-hero-grid{grid-template-columns:1.05fr .95fr; gap:60px;} }

.est-badge{
  display:inline-flex; align-items:center; gap:8px; margin-bottom:14px;
  background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.18);
  padding:7px 14px; border-radius:100px; font-family:var(--font-eyebrow);
  font-size:11.5px; font-weight:600; letter-spacing:.05em; color:var(--teal-2); text-transform:uppercase;
}
.est-badge b{color:var(--amber-2);}
.ev-hero-content .eyebrow{color:var(--amber-2);}
.ev-hero-content .eyebrow::before{background:var(--amber-2);}
.ev-hero-content h1{ color:var(--ivory); font-size:clamp(32px,7vw,60px); line-height:1.08; margin-top:16px; letter-spacing:-0.02em; }
.ev-hero-content h1 em{
  font-style:normal; background:linear-gradient(120deg,var(--amber-2),var(--amber));
  -webkit-background-clip:text; background-clip:text; color:transparent;
}
.ev-hero-content p.lead{ color:rgba(250,247,242,0.78); font-size:16.5px; max-width:520px; margin-top:18px; }
@media (min-width:640px){ .ev-hero-content p.lead{font-size:18px;} }
.ev-hero-actions{display:flex; gap:14px; margin-top:32px; flex-wrap:wrap;}
.ev-hero-actions .btn{flex:1 1 auto; min-width:150px;}
@media (min-width:480px){ .ev-hero-actions .btn{flex:0 1 auto;} }
.ev-trust-row{
  display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-top:44px;
  border-top:1px solid rgba(255,255,255,0.14); padding-top:24px;
}
.ev-trust-row .t-num{font-family:var(--font-display); font-size:22px; font-weight:700; color:var(--ivory);}
@media (min-width:480px){ .ev-trust-row .t-num{font-size:28px;} }
.ev-trust-row .t-label{font-size:11.5px; color:rgba(250,247,242,0.6); margin-top:4px; letter-spacing:.02em;}

/* Signature element: Live Event Countdown (echoes homepage's Live Ledger) */
.hero-visual{perspective:1200px;}
.ledger-card{
  background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.16); border-radius:var(--radius-lg);
  backdrop-filter:blur(18px); padding:22px 18px 20px; box-shadow:var(--shadow-lg); position:relative;
  transform-style:preserve-3d; transition:transform .35s var(--ease); will-change:transform;
}
@media (min-width:640px){ .ledger-card{padding:26px 24px 22px;} }
.ledger-card::before{
  content:''; position:absolute; inset:-1px; border-radius:inherit; padding:1px;
  background:linear-gradient(140deg, rgba(95,211,227,0.55), transparent 40%, rgba(255,255,255,0.08));
  -webkit-mask:linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
  -webkit-mask-composite:xor; mask-composite:exclude; pointer-events:none;
}
.ledger-top{display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;}
.ledger-title{color:var(--ivory); font-family:var(--font-display); font-weight:600; font-size:14px; display:flex; gap:9px; align-items:center;}
.ledger-dot{width:8px;height:8px;border-radius:50%;background:#4ADE80; box-shadow:0 0 0 4px rgba(74,222,128,0.18); animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}
.ledger-clock{color:var(--amber-2); font-size:13px; font-weight:700; font-variant-numeric:tabular-nums; letter-spacing:.03em;}
.ev-spot-banner{ width:100%; height:150px; object-fit:cover; border-radius:14px; margin-bottom:16px; }
.ev-spot-title{ color:var(--ivory); font-family:var(--font-display); font-weight:700; font-size:19px; line-height:1.3; margin-bottom:14px; }
.ledger-rows{display:flex; flex-direction:column; gap:9px;}
.ledger-row{
  display:grid; grid-template-columns:auto 1fr auto; gap:10px; align-items:center;
  background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);
  border-radius:12px; padding:11px 12px; opacity:0; transform:translateY(8px); animation:rowIn .6s var(--ease) forwards;
}
@keyframes rowIn{to{opacity:1; transform:translateY(0);}}
.ledger-check{
  width:26px;height:26px;border-radius:8px; display:flex;align-items:center;justify-content:center;
  background:rgba(232,199,102,0.15); color:var(--amber-2); font-size:12.5px; flex-shrink:0;
}
.ledger-name{color:var(--ivory); font-size:13px; font-weight:500; min-width:0;}
.ledger-status{font-size:11px; font-weight:600; letter-spacing:.02em; color:rgba(250,247,242,0.55); text-align:right;}
.ledger-foot{
  margin-top:16px; padding-top:14px; border-top:1px dashed rgba(255,255,255,0.14);
  display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;
}
.ledger-foot .score{color:var(--amber-2); font-family:var(--font-display); font-weight:700; font-size:14px;}
.ledger-foot .score-label{color:rgba(250,247,242,0.55); font-size:10.5px;}
.ev-spot-empty{ color:rgba(250,247,242,0.65); font-size:13.5px; text-align:center; padding:30px 10px; }

/* ============================= FILTER PANEL (overlaps hero) ============================= */
.filter-section{ position:relative; z-index:5; margin-top:-56px; }
.filter-panel{
  background:var(--ivory); border:1px solid var(--line); border-radius:var(--radius-lg);
  box-shadow:var(--shadow-lg); padding:22px 22px 20px;
  display:grid; grid-template-columns:1fr; gap:14px;
}
@media (min-width:640px){ .filter-panel{padding:26px 30px 24px;} }
@media (min-width:900px){ .filter-panel{grid-template-columns:1.6fr 1fr 1fr auto; gap:16px; align-items:end;} }
.filter-field{display:flex; flex-direction:column; gap:6px;}
.filter-field label{
  font-family:var(--font-eyebrow); font-size:10.5px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:#8F8B80;
}
.filter-field .input-wrap{position:relative;}
.filter-field .input-wrap .ficon{ position:absolute; left:15px; top:50%; transform:translateY(-50%); color:var(--teal-deep); font-size:14px; pointer-events:none; }
.filter-field input, .filter-field select{
  width:100%; padding:13px 14px 13px 38px; border-radius:12px; border:1px solid var(--line2, var(--line));
  background:var(--paper); font-family:inherit; font-size:14px; color:var(--navy-ink); outline:none;
  transition:border-color .3s ease, background .3s ease, box-shadow .3s ease;
}
.filter-field select{padding-left:14px; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='9'%3E%3Cpath d='M1 1l6 6 6-6' stroke='%2354514C' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 14px center; padding-right:32px;}
.filter-field input:focus, .filter-field select:focus{ border-color:var(--teal-deep); background:#fff; box-shadow:0 0 0 4px rgba(18,81,62,0.08); }
.filter-actions{display:flex; gap:10px;}
.filter-actions .btn{padding:13px 22px; font-size:13.5px;}
@media (max-width:899px){ .filter-actions{flex-wrap:wrap;} .filter-actions .btn{flex:1;} }

/* ============================= EVENTS GRID ============================= */
.ev-results-bar{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin:38px 0 26px; }
.ev-results-count{ font-family:var(--font-eyebrow); font-size:12.5px; letter-spacing:.04em; color:#8F8B80; }
.ev-results-count strong{color:var(--navy-ink);}

.ev-grid{display:grid; grid-template-columns:1fr; gap:26px;}
@media (min-width:640px){ .ev-grid{grid-template-columns:repeat(2,1fr); gap:24px;} }
@media (min-width:1100px){ .ev-grid{grid-template-columns:repeat(3,1fr);} }

.ev-card{
  background:var(--ivory); border:1px solid var(--line); border-radius:var(--radius-md); overflow:hidden;
  display:flex; flex-direction:column; text-decoration:none; color:inherit; position:relative;
  transition:transform .4s var(--ease), box-shadow .4s var(--ease), border-color .3s ease;
}
.ev-card::before{
  content:''; position:absolute; top:0; left:0; right:0; height:3px; z-index:2;
  background:linear-gradient(90deg,var(--amber),var(--teal)); transform:scaleX(0); transform-origin:left; transition:transform .5s var(--ease);
}
.ev-card:hover{transform:translateY(-7px); box-shadow:var(--shadow-lg); border-color:transparent;}
.ev-card:hover::before{transform:scaleX(1);}
.ev-media{position:relative; width:100%; height:190px; overflow:hidden; background:var(--paper);}
.ev-media img{width:100%; height:100%; object-fit:cover; transition:transform .6s var(--ease);}
.ev-card:hover .ev-media img{transform:scale(1.08);}
.ev-media-overlay{ position:absolute; inset:0; background:linear-gradient(180deg, rgba(10,17,40,0) 45%, rgba(10,17,40,0.55) 100%); }
.ev-badges-float{ position:absolute; top:12px; left:12px; right:12px; display:flex; justify-content:space-between; align-items:flex-start; gap:8px; z-index:1; }
.ev-date-chip{
  background:rgba(250,247,242,0.95); backdrop-filter:blur(6px); border-radius:10px; padding:7px 10px; text-align:center;
  font-family:var(--font-display); line-height:1.05; box-shadow:var(--shadow-sm); flex-shrink:0;
}
.ev-date-chip .d{font-size:16px; font-weight:700; color:var(--navy-ink);}
.ev-date-chip .m{font-size:9.5px; font-weight:600; letter-spacing:.05em; text-transform:uppercase; color:var(--amber-deep);}
.ev-photo-count{
  background:rgba(10,17,40,0.55); backdrop-filter:blur(6px); color:var(--ivory); font-size:11px; font-weight:600;
  padding:6px 10px; border-radius:100px; display:inline-flex; align-items:center; gap:5px;
}

.ev-status{ font-size:9.5px; font-weight:700; padding:4px 10px; border-radius:100px; text-transform:uppercase; letter-spacing:.05em; }
.ev-status-upcoming{ background:rgba(143,160,232,0.16); color:var(--teal-2); border:1px solid rgba(143,160,232,0.4); }
.ev-status-ongoing{ background:linear-gradient(135deg,var(--amber-2),var(--amber)); color:#1C1400; box-shadow:0 6px 16px -6px rgba(201,162,39,0.6); }
.ev-status-completed{ background:rgba(255,255,255,0.65); color:#6b6a63; border:1px solid rgba(0,0,0,0.08); }

.ev-body{padding:18px 20px 20px; display:flex; flex-direction:column; gap:10px; flex:1;}
.ev-category{ font-family:var(--font-eyebrow); font-size:10.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--teal-deep); }
.ev-title{ font-size:17.5px; font-weight:700; line-height:1.3; color:var(--navy-ink); }
.ev-meta-list{display:flex; flex-direction:column; gap:6px; margin-top:2px;}
.ev-meta-row{ display:flex; align-items:center; gap:8px; font-size:12.5px; color:#6b6a63; }
.ev-meta-row .mi{ color:var(--amber); flex-shrink:0; }
.ev-desc{font-size:13px; color:#6b6a63; margin-top:4px; line-height:1.55;}
.ev-footer-row{ margin-top:auto; padding-top:14px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.ev-view-link{
  font-size:13px; font-weight:700; color:var(--teal-deep); text-decoration:none; display:inline-flex; align-items:center; gap:6px;
}
.ev-view-link .arrow{transition:transform .3s var(--ease);}
.ev-card:hover .ev-view-link .arrow{transform:translateX(4px);}

/* Empty state */
.ev-empty{
  text-align:center; padding:70px 24px; color:#8F8B80; font-size:14.5px;
  background:var(--paper); border:1px dashed var(--silver); border-radius:var(--radius-lg);
}
.ev-empty .eyebrow{justify-content:center; margin-bottom:10px;}
.ev-empty h3{color:var(--navy-ink); font-size:19px; margin-bottom:8px;}

/* Scroll reveal */
.reveal{opacity:0; transform:translateY(22px); transition:opacity .8s var(--ease), transform .8s var(--ease);}
.reveal.in{opacity:1; transform:translateY(0);}
.stagger.in > *{transition-delay:calc(var(--i,0) * 70ms);}
a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible{ outline:2px solid var(--amber); outline-offset:3px; }

/* ============================= FOOTER (same as homepage) ============================= */
footer{background:var(--charcoal); color:rgba(250,247,242,0.7); padding:60px 0 0; margin-top:40px;}
@media (min-width:768px){ footer{padding:80px 0 0;} }
.footer-grid{display:grid; grid-template-columns:1fr; gap:36px; padding-bottom:44px; border-bottom:1px solid rgba(255,255,255,0.08);}
@media (min-width:640px){ .footer-grid{grid-template-columns:1fr 1fr; row-gap:40px;} }
@media (min-width:1024px){ .footer-grid{grid-template-columns:1.4fr 1fr 1fr 1.2fr; gap:50px; padding-bottom:60px;} }
.footer-brand{display:flex; flex-direction:column; align-items:flex-start;}
.footer-logo{height:64px; width:auto; max-width:170px; object-fit:contain; margin-bottom:4px; filter:brightness(0) invert(1);}
.footer-brand p{color:rgba(250,247,242,0.55); font-size:14px; margin-top:14px; max-width:280px;}
.footer-est{font-family:var(--font-eyebrow); font-size:11.5px; letter-spacing:.06em; text-transform:uppercase; color:var(--teal-2); margin-top:10px;}
.footer-col h5{color:var(--ivory); font-size:13px; letter-spacing:.06em; text-transform:uppercase; margin-bottom:18px; font-family:var(--font-eyebrow); font-weight:600;}
.footer-col ul{display:grid; gap:11px; list-style:none;}
.footer-col a{color:rgba(250,247,242,0.6); text-decoration:none; font-size:14px; transition:color .3s ease;}
.footer-col a:hover{color:var(--teal-2);}
.social-row{display:flex; gap:12px; margin-top:18px;}
.social-row a{ width:36px;height:36px;border-radius:50%; border:1px solid rgba(255,255,255,0.15); display:flex;align-items:center;justify-content:center; font-size:13px; transition:all .3s ease; }
.social-row a:hover{background:var(--amber); color:#fff; border-color:var(--amber);}
.footer-bottom{display:flex; justify-content:space-between; align-items:center; padding:22px 0; font-size:12.5px; color:rgba(250,247,242,0.45); flex-wrap:wrap; gap:10px; text-align:center;}
@media (min-width:768px){ .footer-bottom{padding:26px 0; font-size:13px;} }
</style>
</head>
<body>

<div class="scroll-progress" id="scrollProgress" aria-hidden="true"></div>

<!-- ============================= NAVBAR ============================= -->
<nav class="navbar" id="navbar">
  <div class="wrap nav-inner">
    <a href="index.php#top" class="brand">
        <img src="images/3.png" alt="Samal Consultancy Logo" class="brand-logo" id="navLogoImg"
          onerror="this.style.display='none'; document.getElementById('navLogoFallback').style.display='flex';">
        <span class="brand-mark" id="navLogoFallback" style="display:none;">SC</span>
        <span class="brand-name">Samal Consultancy</span>
    </a>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php#about">About</a></li>
      <li><a href="index.php#services">Services</a></li>
      <li><a href="index.php#registry">Registry</a></li>
      <li><a href="index.php#process">Process</a></li>
      <li><a href="index.php#clients">Clients</a></li>
      <li><a href="index.php#contact">Contact</a></li>
      <li><a href="index.php#products">Products</a></li>
      <li><a href="events.php" class="current">Events</a></li>
    </ul>
    <div class="nav-cta">
      <a href="index.php#contact" class="btn btn-gold" style="padding:12px 20px; font-size:13px;">Book Consultation</a>
      <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu"><span></span><span></span><span></span></button>
    </div>
  </div>
</nav>

<!-- ============================= EVENTS HERO ============================= -->
<header class="ev-hero" id="top">
  <div class="ev-hero-orbs" aria-hidden="true">
    <span class="orb orb-1"></span>
    <span class="orb orb-2"></span>
  </div>
  <div class="wrap ev-hero-grid">
    <div class="ev-hero-content reveal in">
      <div class="est-badge">Curated <b>Locally</b> &nbsp;·&nbsp; Updated Weekly</div>
      <span class="eyebrow">Events Near You</span>
      <h1>Discover experiences, <em>booked</em> effortlessly.</h1>
      <p class="lead">Browse curated events around you — with galleries, nearby hotels, and a direct booking link for every listing. No clutter, just what's happening and how to be there.</p>
      <div class="ev-hero-actions">
        <a href="#browse" class="btn btn-gold" data-ripple>Browse Events</a>
        <a href="index.php#contact" class="btn btn-ghost" data-ripple>List Your Event</a>
      </div>
      <div class="ev-trust-row">
        <div><div class="t-num"><?= count($events) ?: '—' ?></div><div class="t-label">Events listed</div></div>
        <div><div class="t-num"><?= count($locations) ?: '—' ?></div><div class="t-label">Cities covered</div></div>
        <div><div class="t-num">100%</div><div class="t-label">Verified listings</div></div>
      </div>
    </div>

    <div class="hero-visual reveal in" style="transition-delay:.15s;">
      <div class="ledger-card">
        <div class="ledger-top">
          <div class="ledger-title"><span class="ledger-dot"></span> Up Next</div>
          <div class="ledger-clock" id="countdownClock" data-target="<?= $nextEvent ? htmlspecialchars($nextEvent['event_date'] . 'T' . ($nextEvent['event_time'] ?: '00:00:00')) : '' ?>">--:--:--</div>
        </div>

        <?php if ($nextEvent): ?>
          <img class="ev-spot-banner" src="<?= htmlspecialchars($nextEvent['banner_image'] ?: 'https://placehold.co/500x300?text=Event') ?>" alt="<?= htmlspecialchars($nextEvent['title']) ?>">
          <div class="ev-spot-title"><?= htmlspecialchars($nextEvent['title']) ?></div>
          <div class="ledger-rows">
            <div class="ledger-row" style="animation-delay:.05s">
              <div class="ledger-check">📅</div>
              <div class="ledger-name">Date &amp; time</div>
              <div class="ledger-status"><?= htmlspecialchars(date('d M Y', strtotime($nextEvent['event_date']))) ?><?= $nextEvent['event_time'] ? ' · ' . htmlspecialchars(date('h:i A', strtotime($nextEvent['event_time']))) : '' ?></div>
            </div>
            <div class="ledger-row" style="animation-delay:.12s">
              <div class="ledger-check">📍</div>
              <div class="ledger-name">Location</div>
              <div class="ledger-status"><?= htmlspecialchars($nextEvent['location']) ?></div>
            </div>
            <?php if ($nextEvent['category_name']): ?>
            <div class="ledger-row" style="animation-delay:.19s">
              <div class="ledger-check">◈</div>
              <div class="ledger-name">Category</div>
              <div class="ledger-status"><?= htmlspecialchars($nextEvent['category_name']) ?></div>
            </div>
            <?php endif; ?>
          </div>
          <div class="ledger-foot">
            <div><div class="score">Time-sensitive</div><div class="score-label">Booking recommended early</div></div>
            <a href="event.php?slug=<?= urlencode($nextEvent['slug']) ?>" class="btn btn-gold btn-sm" data-ripple>View Event</a>
          </div>
        <?php else: ?>
          <div class="ev-spot-empty"><?= $dbError ? htmlspecialchars($dbError) : 'No upcoming events right now — check back soon.' ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>

<!-- ============================= FILTER PANEL ============================= -->
<div class="wrap filter-section" id="browse">
  <form method="get" action="events.php" class="filter-panel reveal in">
    <div class="filter-field" style="grid-column:span 1;">
      <label>Search</label>
      <div class="input-wrap">
        <span class="ficon">⌕</span>
        <input type="text" name="q" placeholder="Search events, venues…" value="<?= htmlspecialchars($q) ?>" maxlength="150">
      </div>
    </div>
    <div class="filter-field">
      <label>Date</label>
      <div class="input-wrap">
        <span class="ficon">📅</span>
        <input type="date" name="date" value="<?= htmlspecialchars($fDate) ?>">
      </div>
    </div>
    <div class="filter-field">
      <label>Location</label>
      <div class="input-wrap">
        <span class="ficon">📍</span>
        <select name="location">
          <option value="">All locations</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?= htmlspecialchars($loc) ?>" <?= $fLocation === $loc ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="filter-actions">
      <button type="submit" class="btn btn-gold" data-ripple>Search</button>
      <?php if ($q !== '' || $fDate !== '' || $fLocation !== ''): ?>
        <a href="events.php" class="btn btn-outline-dark">Clear</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- ============================= EVENTS GRID ============================= -->
<section class="pad" style="padding-top:40px;">
  <div class="wrap">
    <div class="ev-results-bar reveal">
      <div class="ev-results-count">
        <?php if ($q !== '' || $fDate !== '' || $fLocation !== ''): ?>
          <strong><?= count($events) ?></strong> event<?= count($events) === 1 ? '' : 's' ?> match your search
        <?php else: ?>
          Showing <strong><?= count($events) ?></strong> event<?= count($events) === 1 ? '' : 's' ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($dbError): ?>
      <div class="ev-empty">
        <span class="eyebrow">Connection issue</span>
        <h3>Events could not be loaded</h3>
        <p><?= htmlspecialchars($dbError) ?></p>
      </div>
    <?php elseif (!$events): ?>
      <div class="ev-empty">
        <span class="eyebrow">No results</span>
        <h3>No events match your search</h3>
        <p>Try a different date, location, or clear your filters to see everything on offer.</p>
      </div>
    <?php else: ?>
      <div class="ev-grid stagger reveal in">
        <?php foreach ($events as $i => $ev): ?>
          <?php
            $statusClass = 'ev-status-' . $ev['status'];
            $dateObj = strtotime($ev['event_date']);
          ?>
          <a href="event.php?slug=<?= urlencode($ev['slug']) ?>" class="ev-card" style="--i:<?= $i ?>">
            <div class="ev-media">
              <img src="<?= htmlspecialchars($ev['banner_image'] ?: 'https://placehold.co/600x400?text=Event') ?>" alt="<?= htmlspecialchars($ev['title']) ?>" loading="lazy">
              <div class="ev-media-overlay"></div>
              <div class="ev-badges-float">
                <div class="ev-date-chip">
                  <div class="d"><?= htmlspecialchars(date('d', $dateObj)) ?></div>
                  <div class="m"><?= htmlspecialchars(date('M', $dateObj)) ?></div>
                </div>
                <?php if ((int) $ev['photo_count'] > 0): ?>
                  <span class="ev-photo-count">🖼 <?= (int) $ev['photo_count'] ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="ev-body">
              <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                <?php if ($ev['category_name']): ?><span class="ev-category"><?= htmlspecialchars($ev['category_name']) ?></span><?php else: ?><span></span><?php endif; ?>
                <span class="ev-status <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($STATUS_LABELS[$ev['status']] ?? ucfirst($ev['status'])) ?></span>
              </div>
              <div class="ev-title"><?= htmlspecialchars($ev['title']) ?></div>
              <div class="ev-meta-list">
                <div class="ev-meta-row"><span class="mi">◷</span><?= htmlspecialchars(date('d M Y', $dateObj)) ?><?= $ev['event_time'] ? ' · ' . htmlspecialchars(date('h:i A', strtotime($ev['event_time']))) : '' ?></div>
                <div class="ev-meta-row"><span class="mi">⌂</span><?= htmlspecialchars($ev['location']) ?></div>
              </div>
              <?php if ($ev['short_description']): ?><div class="ev-desc"><?= htmlspecialchars($ev['short_description']) ?></div><?php endif; ?>
              <div class="ev-footer-row">
                <span class="ev-view-link">View details <span class="arrow">→</span></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============================= FOOTER ============================= -->
<footer>
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="index.php#top" class="brand" style="margin-bottom:6px;">
            <img src="images/3.png" alt="Samal Consultancy Logo" class="footer-logo" id="footerLogoImg"
                onerror="this.style.display='none'; document.getElementById('footerLogoFallback').style.display='flex';">
            <span class="brand-mark" id="footerLogoFallback" style="display:none; align-items:center; justify-content:center;">SC</span>
        </a>
        <span class="brand-name" style="color:var(--ivory);">Samal Consultancy</span>
        <p>End-to-end tax, registration and labour-law compliance for growing businesses.</p>
        <div class="footer-est">Providing trusted compliance services since 1993</div>
        <div class="social-row">
          <a href="#" aria-label="LinkedIn">in</a>
          <a href="#" aria-label="Twitter">𝕏</a>
          <a href="#" aria-label="Instagram">◎</a>
        </div>
      </div>
      <div class="footer-col">
        <h5>Company</h5>
        <ul><li><a href="index.php#about">About Us</a></li><li><a href="index.php#products">Products</a></li><li><a href="index.php#services">Services</a></li><li><a href="index.php#contact">Contact</a></li></ul>
      </div>
      <div class="footer-col">
        <h5>Explore</h5>
        <ul><li><a href="events.php">Events Near You</a></li><li><a href="index.php#registry">Full Service Registry</a></li><li><a href="index.php#process">Our Process</a></li></ul>
      </div>
      <div class="footer-col">
        <h5>Stay Updated</h5>
        <p style="font-size:14px; margin-bottom:4px;">New events and compliance deadlines, in your inbox.</p>
        <form class="sub-form" onsubmit="event.preventDefault();this.reset();" style="display:flex; margin-top:14px; border:1px solid rgba(255,255,255,0.15); border-radius:100px; overflow:hidden; padding:4px;">
          <input type="email" placeholder="Your email" required style="flex:1; min-width:0; background:none; border:none; padding:10px 14px; color:var(--ivory); outline:none; font-family:inherit; font-size:13.5px;">
          <button type="submit" style="background:var(--amber); border:none; color:#fff; font-weight:700; padding:10px 18px; border-radius:100px; cursor:pointer; font-size:12.5px; flex-shrink:0;">Join</button>
        </form>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <span id="year"></span> Samal Consultancy. All rights reserved.</span>
      <span>Privacy Policy · Terms of Service</span>
    </div>
  </div>
</footer>

<button id="backToTop" class="back-to-top" aria-label="Back to top">
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 19V5"/><path d="M6 11l6-6 6 6"/>
  </svg>
</button>
<div class="mobile-cta-bar" id="mobileCtaBar">
  <a href="tel:+919876543210" class="btn btn-outline-dark" data-ripple>Call Now</a>
  <a href="#browse" class="btn btn-gold" data-ripple>Browse Events</a>
</div>

<script>
const navbar = document.getElementById('navbar');
const backToTop = document.getElementById('backToTop');
const mobileCtaBar = document.getElementById('mobileCtaBar');
const scrollProgress = document.getElementById('scrollProgress');
window.addEventListener('scroll', () => {
  const y = window.scrollY;
  navbar.classList.toggle('scrolled', y > 40);
  backToTop.classList.toggle('show', y > 600);
  mobileCtaBar.classList.toggle('show', y > 500);
  const max = document.documentElement.scrollHeight - window.innerHeight;
  scrollProgress.style.width = (max > 0 ? (y / max) * 100 : 0) + '%';
}, { passive:true });

const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');
menuToggle.addEventListener('click', () => { navLinks.classList.toggle('open'); menuToggle.classList.toggle('open'); });
navLinks.querySelectorAll('a').forEach(a => a.addEventListener('click', () => { navLinks.classList.remove('open'); menuToggle.classList.remove('open'); }));
backToTop.addEventListener('click', () => window.scrollTo({ top:0, behavior:'smooth' }));

document.querySelectorAll('[data-ripple]').forEach(btn=>{
  btn.addEventListener('click', function(e){
    const rect = this.getBoundingClientRect();
    const span = document.createElement('span');
    const size = Math.max(rect.width, rect.height);
    span.className = 'ripple-span';
    span.style.width = span.style.height = size + 'px';
    span.style.left = (e.clientX - rect.left - size/2) + 'px';
    span.style.top = (e.clientY - rect.top - size/2) + 'px';
    this.appendChild(span);
    setTimeout(()=> span.remove(), 650);
  });
});

/* Live countdown to the spotlighted event */
const countdownEl = document.getElementById('countdownClock');
const target = countdownEl.dataset.target ? new Date(countdownEl.dataset.target).getTime() : null;
function tickCountdown(){
  if (!target) { countdownEl.textContent = ''; return; }
  const now = Date.now();
  let diff = Math.max(0, target - now);
  const days = Math.floor(diff / 86400000); diff -= days * 86400000;
  const hrs  = Math.floor(diff / 3600000); diff -= hrs * 3600000;
  const mins = Math.floor(diff / 60000);
  countdownEl.textContent = days > 0 ? `${days}d ${hrs}h ${mins}m` : `${hrs}h ${mins}m`;
}
tickCountdown();
setInterval(tickCountdown, 30000);

/* Hero tilt (desktop only, matches homepage interaction) */
const heroEl = document.querySelector('.ev-hero');
const ledgerCard = document.querySelector('.ledger-card');
const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const isCoarsePointer = window.matchMedia('(pointer: coarse)').matches;
if (heroEl && ledgerCard && !prefersReduced && !isCoarsePointer) {
  heroEl.addEventListener('mousemove', (e)=>{
    const cardRect = ledgerCard.getBoundingClientRect();
    const cx = cardRect.left + cardRect.width/2;
    const cy = cardRect.top + cardRect.height/2;
    const dx = Math.max(-1, Math.min(1, (e.clientX - cx) / (cardRect.width/2)));
    const dy = Math.max(-1, Math.min(1, (e.clientY - cy) / (cardRect.height/2)));
    ledgerCard.style.transform = `rotateY(${dx*7}deg) rotateX(${-dy*7}deg)`;
  });
  heroEl.addEventListener('mouseleave', ()=>{ ledgerCard.style.transform = 'rotateY(0deg) rotateX(0deg)'; });
}

/* Scroll reveal */
const revealEls = document.querySelectorAll('.reveal');
const revealObserver = new IntersectionObserver((entries)=>{
  entries.forEach(e=>{ if (e.isIntersecting){ e.target.classList.add('in'); revealObserver.unobserve(e.target); } });
}, { threshold:0.1 });
revealEls.forEach(el=>revealObserver.observe(el));

document.getElementById('year').textContent = new Date().getFullYear();
</script>
</body>
</html>