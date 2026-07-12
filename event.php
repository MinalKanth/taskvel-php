<?php
require_once __DIR__ . '/includes/auth.php';

function youtube_embed_url(?string $url): ?string
{
    if (!$url) return null;
    $id = null;
    if (preg_match('~youtu\.be/([A-Za-z0-9_\-]+)~', $url, $m)) $id = $m[1];
    elseif (preg_match('~[?&]v=([A-Za-z0-9_\-]+)~', $url, $m)) $id = $m[1];
    elseif (preg_match('~youtube\.com/embed/([A-Za-z0-9_\-]+)~', $url, $m)) $id = $m[1];
    return $id ? 'https://www.youtube.com/embed/' . $id : null;
}

$slug = trim((string) ($_GET['slug'] ?? ''));

$stmt = db()->prepare(
    "SELECT e.*, c.name AS category_name
     FROM events e
     LEFT JOIN event_categories c ON c.id = e.category_id
     WHERE e.slug = :slug
     LIMIT 1"
);
$stmt->execute([':slug' => $slug]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
}

$images = [];
$hotels = [];
if ($event) {
    $imgStmt = db()->prepare('SELECT image_url FROM event_images WHERE event_id = :id ORDER BY position ASC, id ASC');
    $imgStmt->execute([':id' => $event['id']]);
    $images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

    $hotelStmt = db()->prepare('SELECT * FROM event_hotels WHERE event_id = :id ORDER BY position ASC, id ASC');
    $hotelStmt->execute([':id' => $event['id']]);
    $hotels = $hotelStmt->fetchAll();
}

$STATUS_LABELS = ['upcoming' => 'Upcoming', 'ongoing' => 'Happening Now', 'completed' => 'Completed'];
$embedUrl = $event ? youtube_embed_url($event['youtube_url']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= $event ? htmlspecialchars($event['title']) . ' | Events | Samal Consultancy' : 'Event Not Found | Samal Consultancy' ?></title>
<meta name="description" content="<?= $event ? htmlspecialchars($event['short_description'] ?: $event['title']) : 'Event not found.' ?>">
<link rel="icon" href="images/favicon.ico" type="image/x-icon">
<meta name="theme-color" content="#0A1128">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600&display=swap" rel="stylesheet">
<style>
/* ============================= TOKENS (same as events.php) ============================= */
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
.wrap-narrow{max-width:900px;margin:0 auto;padding:0 20px;}
@media (min-width:640px){ .wrap-narrow{padding:0 28px;} }

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

/* ============================= NAV (same as events.php) ============================= */
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

/* ============================= EVENT HERO ============================= */
.det-hero{
  position:relative; display:flex; align-items:flex-end;
  background:
    radial-gradient(1100px 560px at 88% -10%, rgba(232,199,102,0.14), transparent 60%),
    radial-gradient(900px 500px at -10% 110%, rgba(27,42,107,0.35), transparent 60%),
    linear-gradient(160deg, var(--navy-ink) 0%, var(--teal-deep) 65%, #0F4436 100%);
  padding:120px 0 100px; overflow:hidden;
}
.det-hero-orbs{position:absolute; inset:0; overflow:hidden; pointer-events:none; z-index:0;}
.orb{position:absolute; border-radius:50%; filter:blur(70px); opacity:.32; animation:orbFloat 16s ease-in-out infinite;}
.orb-1{width:300px;height:300px; background:var(--amber-2); top:-70px; right:6%; animation-duration:17s;}
.orb-2{width:230px;height:230px; background:var(--teal-2); bottom:-50px; left:4%; animation-duration:20s; animation-delay:2s;}
@keyframes orbFloat{0%,100%{transform:translate(0,0) scale(1);} 50%{transform:translate(28px,-26px) scale(1.14);}}
@media (prefers-reduced-motion: reduce){ .orb{animation:none;} }

.back-link{
  position:relative; z-index:1; display:inline-flex; align-items:center; gap:8px; text-decoration:none;
  color:rgba(250,247,242,0.75); font-size:13.5px; font-weight:600; margin-bottom:22px; transition:color .3s ease, transform .3s var(--ease);
}
.back-link:hover{color:var(--amber-2); transform:translateX(-3px);}

.det-hero-content{position:relative; z-index:1;}
.det-badges{display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;}
.det-status{ font-size:9.5px; font-weight:700; padding:5px 12px; border-radius:100px; text-transform:uppercase; letter-spacing:.05em; }
.det-status-upcoming{ background:rgba(143,160,232,0.16); color:var(--teal-2); border:1px solid rgba(143,160,232,0.4); }
.det-status-ongoing{ background:linear-gradient(135deg,var(--amber-2),var(--amber)); color:#1C1400; box-shadow:0 6px 16px -6px rgba(201,162,39,0.6); }
.det-status-completed{ background:rgba(255,255,255,0.14); color:rgba(250,247,242,0.75); border:1px solid rgba(255,255,255,0.2); }
.det-category-pill{ background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.18); color:var(--amber-2); font-size:9.5px; font-weight:700; padding:5px 12px; border-radius:100px; text-transform:uppercase; letter-spacing:.05em; }

.det-hero-content h1{ color:var(--ivory); font-size:clamp(28px,5.6vw,46px); line-height:1.1; letter-spacing:-0.02em; max-width:820px; }
.det-meta-row{ display:flex; gap:22px; flex-wrap:wrap; margin-top:20px; }
.det-meta-row span{ display:flex; align-items:center; gap:8px; color:rgba(250,247,242,0.78); font-size:14px; font-weight:500; }
.det-meta-row .mi{color:var(--amber-2); font-size:15px;}
.det-hero-actions{display:flex; gap:14px; margin-top:30px; flex-wrap:wrap;}

/* ============================= BANNER PANEL (overlaps hero) ============================= */
.banner-section{ position:relative; z-index:5; margin-top:-64px; }
.banner-frame{
  border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-lg); border:1px solid var(--line);
  background:var(--paper);
}
.banner-frame img{ width:100%; height:clamp(220px,42vw,440px); object-fit:cover; display:block; }

/* ============================= CONTENT SECTIONS ============================= */
.det-section{ margin-top:52px; }
.det-section h2{ font-size:13px; }
.det-panel{
  background:var(--ivory); border:1px solid var(--line); border-radius:var(--radius-lg);
  box-shadow:var(--shadow-sm); padding:28px 26px;
}
.desc-block{ font-size:15px; line-height:1.8; color:#54514C; }

.gallery{ display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; }
.gallery a{ position:relative; display:block; border-radius:var(--radius-md); overflow:hidden; border:1px solid var(--line); }
.gallery img{ width:100%; height:130px; object-fit:cover; transition:transform .5s var(--ease); }
.gallery a:hover img{ transform:scale(1.08); }

.video-wrap{ position:relative; width:100%; padding-top:56.25%; border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm); background:var(--charcoal); }
.video-wrap iframe{ position:absolute; inset:0; width:100%; height:100%; border:0; }

.hotel-card{
  background:var(--ivory); border:1px solid var(--line); border-radius:var(--radius-md);
  padding:18px 20px; margin-bottom:12px; display:flex; justify-content:space-between;
  align-items:center; gap:14px; flex-wrap:wrap; transition:box-shadow .3s ease, border-color .3s ease, transform .3s var(--ease);
}
.hotel-card:hover{ box-shadow:var(--shadow-sm); border-color:transparent; transform:translateY(-2px); }
.hotel-card:last-child{ margin-bottom:0; }
.hotel-card .h-icon{
  width:40px;height:40px;border-radius:10px; background:rgba(201,162,39,0.12); color:var(--amber-deep);
  display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0;
}
.hotel-card .h-body{ display:flex; align-items:center; gap:14px; min-width:0; }
.hotel-card .name{ font-weight:700; font-size:14.5px; color:var(--navy-ink); font-family:var(--font-display); }
.hotel-card .desc{ font-size:12.5px; color:#8F8B80; margin-top:3px; }

.organizer-card{
  background:var(--ivory); border:1px solid var(--line); border-radius:var(--radius-lg); padding:22px 24px;
  display:flex; align-items:center; gap:16px; box-shadow:var(--shadow-sm);
}
.organizer-avatar{
  width:52px;height:52px;border-radius:14px; flex-shrink:0;
  background:linear-gradient(135deg,var(--teal-deep),var(--navy-ink)); color:var(--amber-2);
  display:flex; align-items:center; justify-content:center; font-family:var(--font-display); font-weight:700; font-size:18px;
}
.organizer-card .name{ font-weight:700; font-size:15.5px; margin-bottom:6px; color:var(--navy-ink); font-family:var(--font-display); }
.organizer-card .contact{ font-size:13.5px; color:#54514C; display:flex; flex-direction:column; gap:4px; }
.organizer-card .contact a{ text-decoration:none; color:#54514C; transition:color .3s ease; }
.organizer-card .contact a:hover{ color:var(--teal-deep); }

.cta-band{
  margin-top:60px; border-radius:var(--radius-lg); padding:36px 30px; text-align:center;
  background:linear-gradient(160deg, var(--navy-ink) 0%, var(--teal-deep) 100%);
  box-shadow:var(--shadow-lg);
}
.cta-band .eyebrow{ color:var(--amber-2); justify-content:center; }
.cta-band .eyebrow::before{ background:var(--amber-2); }
.cta-band h3{ color:var(--ivory); font-size:clamp(20px,3.4vw,28px); margin-top:12px; }
.cta-band .cta-actions{ margin-top:22px; display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }

/* Not found state */
.not-found{
  text-align:center; padding:90px 24px; color:#8F8B80;
  background:var(--paper); border:1px dashed var(--silver); border-radius:var(--radius-lg);
}
.not-found .eyebrow{ justify-content:center; margin-bottom:12px; }
.not-found h1{ font-size:26px; margin-bottom:10px; }
.not-found p{ margin-bottom:24px; }

/* Scroll reveal */
.reveal{opacity:0; transform:translateY(22px); transition:opacity .8s var(--ease), transform .8s var(--ease);}
.reveal.in{opacity:1; transform:translateY(0);}
a:focus-visible, button:focus-visible{ outline:2px solid var(--amber); outline-offset:3px; }

/* ============================= FOOTER (same as events.php) ============================= */
footer{background:var(--charcoal); color:rgba(250,247,242,0.7); padding:60px 0 0; margin-top:70px;}
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

<?php if (!$event): ?>

  <!-- ============================= NOT FOUND ============================= -->
  <header class="det-hero" style="padding-top:140px; padding-bottom:60px;">
    <div class="det-hero-orbs" aria-hidden="true"><span class="orb orb-1"></span><span class="orb orb-2"></span></div>
    <div class="wrap-narrow det-hero-content" style="width:100%;">
      <a href="events.php" class="back-link"><span>&larr;</span> Back to Events</a>
    </div>
  </header>
  <div class="wrap-narrow" style="margin-top:-30px; position:relative; z-index:5;">
    <div class="not-found reveal in">
      <span class="eyebrow">Not found</span>
      <h1>Event not found</h1>
      <p>The event you're looking for doesn't exist or may have been removed.</p>
      <a href="events.php" class="btn btn-gold" data-ripple>Browse all events</a>
    </div>
  </div>

<?php else: ?>

  <?php
    $statusClass = 'det-status-' . $event['status'];
    $dateObj = strtotime($event['event_date']);
  ?>

  <!-- ============================= EVENT HERO ============================= -->
  <header class="det-hero" id="top">
    <div class="det-hero-orbs" aria-hidden="true">
      <span class="orb orb-1"></span>
      <span class="orb orb-2"></span>
    </div>
    <div class="wrap-narrow det-hero-content">
      <a href="events.php" class="back-link"><span>&larr;</span> Back to Events</a>

      <div class="det-badges">
        <span class="det-status <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($STATUS_LABELS[$event['status']] ?? ucfirst($event['status'])) ?></span>
        <?php if ($event['category_name']): ?>
          <span class="det-category-pill"><?= htmlspecialchars($event['category_name']) ?></span>
        <?php endif; ?>
      </div>

      <h1><?= htmlspecialchars($event['title']) ?></h1>

      <div class="det-meta-row">
        <span><span class="mi">◷</span> <?= htmlspecialchars(date('d M Y', $dateObj)) ?><?= $event['event_time'] ? ' · ' . htmlspecialchars(date('h:i A', strtotime($event['event_time']))) : '' ?></span>
        <span><span class="mi">⌂</span> <?= htmlspecialchars($event['location']) ?></span>
      </div>

      <?php if ($event['booking_link']): ?>
        <div class="det-hero-actions">
          <a href="<?= htmlspecialchars($event['booking_link']) ?>" class="btn btn-gold" target="_blank" rel="noopener noreferrer" data-ripple>Book this event →</a>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <!-- ============================= BANNER ============================= -->
  <div class="wrap-narrow banner-section reveal in">
    <div class="banner-frame">
      <img src="<?= htmlspecialchars($event['banner_image'] ?: 'https://placehold.co/900x400?text=Event') ?>" alt="<?= htmlspecialchars($event['title']) ?>">
    </div>
  </div>

  <div class="wrap-narrow">

    <?php if ($event['full_description'] || $event['short_description']): ?>
      <section class="det-section reveal">
        <span class="eyebrow" style="margin-bottom:14px; display:inline-flex;">About this event</span>
        <div class="det-panel">
          <div class="desc-block"><?= nl2br(htmlspecialchars($event['full_description'] ?: $event['short_description'])) ?></div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($images): ?>
      <section class="det-section reveal">
        <span class="eyebrow" style="margin-bottom:14px; display:inline-flex;">Gallery</span>
        <div class="gallery">
          <?php foreach ($images as $img): ?>
            <a href="<?= htmlspecialchars($img) ?>" target="_blank" rel="noopener noreferrer">
              <img src="<?= htmlspecialchars($img) ?>" alt="Event photo" loading="lazy">
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($embedUrl): ?>
      <section class="det-section reveal">
        <span class="eyebrow" style="margin-bottom:14px; display:inline-flex;">Video</span>
        <div class="video-wrap">
          <iframe src="<?= htmlspecialchars($embedUrl) ?>" title="Event video" allowfullscreen loading="lazy"></iframe>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($hotels): ?>
      <section class="det-section reveal">
        <span class="eyebrow" style="margin-bottom:14px; display:inline-flex;">Nearby hotels</span>
        <?php foreach ($hotels as $h): ?>
          <div class="hotel-card">
            <div class="h-body">
              <div class="h-icon">🏨</div>
              <div>
                <div class="name"><?= htmlspecialchars($h['hotel_name']) ?></div>
                <?php if ($h['description']): ?><div class="desc"><?= htmlspecialchars($h['description']) ?></div><?php endif; ?>
              </div>
            </div>
            <?php if ($h['booking_link']): ?>
              <a href="<?= htmlspecialchars($h['booking_link']) ?>" class="btn btn-outline-dark btn-sm" target="_blank" rel="noopener noreferrer" data-ripple>View →</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>

    <?php if ($event['organizer_name'] || $event['organizer_phone'] || $event['organizer_email']): ?>
      <section class="det-section reveal">
        <span class="eyebrow" style="margin-bottom:14px; display:inline-flex;">Organizer</span>
        <div class="organizer-card">
          <div class="organizer-avatar"><?= htmlspecialchars($event['organizer_name'] ? strtoupper(substr($event['organizer_name'], 0, 1)) : '◈') ?></div>
          <div>
            <?php if ($event['organizer_name']): ?><div class="name"><?= htmlspecialchars($event['organizer_name']) ?></div><?php endif; ?>
            <div class="contact">
              <?php if ($event['organizer_phone']): ?><a href="tel:<?= htmlspecialchars($event['organizer_phone']) ?>">📞 <?= htmlspecialchars($event['organizer_phone']) ?></a><?php endif; ?>
              <?php if ($event['organizer_email']): ?><a href="mailto:<?= htmlspecialchars($event['organizer_email']) ?>">✉️ <?= htmlspecialchars($event['organizer_email']) ?></a><?php endif; ?>
            </div>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <div class="cta-band reveal">
      <span class="eyebrow">Don't miss out</span>
      <h3>Ready to secure your spot?</h3>
      <div class="cta-actions">
        <?php if ($event['booking_link']): ?>
          <a href="<?= htmlspecialchars($event['booking_link']) ?>" class="btn btn-gold" target="_blank" rel="noopener noreferrer" data-ripple>Book this event →</a>
        <?php endif; ?>
        <a href="events.php" class="btn btn-ghost" data-ripple>Browse more events</a>
      </div>
    </div>

  </div>

<?php endif; ?>

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
  <a href="events.php" class="btn btn-outline-dark" data-ripple>All Events</a>
  <?php if ($event && $event['booking_link']): ?>
    <a href="<?= htmlspecialchars($event['booking_link']) ?>" class="btn btn-gold" target="_blank" rel="noopener noreferrer" data-ripple>Book Now</a>
  <?php else: ?>
    <a href="index.php#contact" class="btn btn-gold" data-ripple>Book Consultation</a>
  <?php endif; ?>
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