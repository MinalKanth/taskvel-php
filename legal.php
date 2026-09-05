<!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Terms &amp; Conditions and Privacy Policy | Samal Consultancy &amp; TaskVel Pro</title>
<meta name="description" content="Terms of Service and Privacy Policy covering Samal Consultancy's advisory services and the TaskVel Pro application.">
<link rel="canonical" href="https://www.samalconsultancy.com/legal.php">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#0A1128">
<meta name="author" content="Samal Consultancy">
<link rel="icon" href="images/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
<link rel="apple-touch-icon" href="images/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600&display=swap" rel="stylesheet">
<style>
/* ============================= TOKENS — identical to index.php ============================= */
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
  --mesh-1: rgba(232,199,102,0.10);
  --mesh-2: rgba(143,160,232,0.12);
}
*{box-sizing:border-box;}
html{scroll-behavior:smooth;}
@media (prefers-reduced-motion: reduce){
  html{scroll-behavior:auto;}
  *{animation-duration:0.001ms !important; animation-iteration-count:1 !important; transition-duration:0.001ms !important;}
}
body{
  margin:0;
  font-family:var(--font-body);
  background:var(--ivory);
  color:var(--charcoal);
  -webkit-font-smoothing:antialiased;
  overflow-x:hidden;
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
  font-family:var(--font-eyebrow);
  font-size:12px;
  font-weight:600;
  letter-spacing:.12em;
  text-transform:uppercase;
  display:inline-flex;
  align-items:center;
  gap:8px;
  color:var(--teal-deep);
}
.eyebrow::before{
  content:'';
  width:18px;height:1px;
  background:var(--amber);
}
h1,h2,h3,h4{font-family:var(--font-display); margin:0; letter-spacing:-0.01em; color:var(--navy-ink);}
p{line-height:1.7; color:#54514C; margin:0;}

/* ============================= BUTTONS — identical to index.php ============================= */
.btn{
  position:relative; overflow:hidden;
  display:inline-flex; align-items:center; justify-content:center; gap:10px;
  padding:15px 26px; font-weight:600; font-size:14.5px;
  border-radius:100px; text-decoration:none; cursor:pointer;
  border:1px solid transparent; transition:transform .35s var(--ease), box-shadow .35s var(--ease), background .3s ease, color .3s ease;
  white-space:nowrap; -webkit-tap-highlight-color:transparent;
}
@media (min-width:640px){ .btn{padding:16px 30px; font-size:15px;} }
.btn:active{transform:scale(.96);}
.btn-gold{ background:linear-gradient(135deg,var(--amber-2),var(--amber)); color:#fff; box-shadow:0 14px 30px -10px rgba(224,108,31,0.55); }
.btn-gold:hover{transform:translateY(-3px); box-shadow:0 20px 40px -12px rgba(224,108,31,0.6);}
.btn-outline-dark{ background:transparent;color:var(--teal-deep);border-color:rgba(14,124,140,0.3); padding:12px 22px; font-size:13.5px; }
.btn-outline-dark:hover{background:var(--teal-deep); color:var(--ivory); border-color:var(--teal-deep);}

/* ============================= NAVBAR — identical structure to index.php,
   rendered permanently in its "scrolled" state since this inner page has
   no hero image behind it for the transparent variant to sit on. ============================= */
.navbar{
  position:fixed; top:0; left:0; right:0; z-index:999;
  padding:10px 0;
  background:rgba(250,247,242,0.92);
  backdrop-filter:blur(16px) saturate(160%);
  box-shadow:0 8px 30px -14px rgba(27,31,39,0.18);
}
.nav-inner{display:flex; align-items:center; justify-content:space-between; gap:16px;}
.brand{display:flex; align-items:center; gap:10px; text-decoration:none; min-width:0;}
.brand-logo{height:36px; width:auto; max-width:150px; object-fit:contain; flex-shrink:0;}
.brand-mark{ width:36px;height:36px;border-radius:10px; flex-shrink:0; background:linear-gradient(145deg,var(--amber),var(--teal-deep));
  display:none; align-items:center;justify-content:center; color:#fff; font-family:var(--font-display); font-weight:700; font-size:15px;
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.15); }
.brand-name{font-family:var(--font-display); font-weight:700; font-size:16.5px; color:#E06C1F; letter-spacing:-.01em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
@media (max-width: 768px){ .brand-name{display:none;} }
@media (min-width:480px){ .brand-name{font-size:18px;} }

.nav-links{list-style:none; display:flex; align-items:center; gap:30px;}
.nav-links a{ text-decoration:none; font-size:14.5px; font-weight:600; letter-spacing:.01em; position:relative; padding:6px 0; color:var(--navy-ink); }
.nav-links a::after{ content:''; position:absolute; left:0; bottom:0; height:1.5px; width:0%; background:var(--amber); transition:width .35s var(--ease); }
.nav-links a:hover::after{width:100%;}
.nav-cta{display:flex; align-items:center; gap:14px;}
.menu-toggle{display:flex; flex-direction:column; gap:5px; cursor:pointer; background:none;border:none; padding:8px; position:relative; z-index:1000; color:var(--navy-ink); -webkit-tap-highlight-color:transparent;}
.menu-toggle span{width:22px;height:2px;background:currentColor;border-radius:2px;transition:all .3s ease; transform-origin:left center;}
.menu-toggle.open span:nth-child(1){transform:rotate(45deg);}
.menu-toggle.open span:nth-child(2){opacity:0; transform:translateX(-8px);}
.menu-toggle.open span:nth-child(3){transform:rotate(-45deg);}
.menu-toggle.open{color:var(--ivory) !important;}

.nav-links{
  position:fixed; inset:0 0 0 auto; width:min(320px,82%); height:100dvh;
  background:linear-gradient(180deg,var(--navy-ink),var(--teal-deep));
  flex-direction:column; justify-content:center; align-items:flex-start;
  padding:40px; gap:26px; transform:translateX(100%); transition:transform .45s var(--ease);
  box-shadow:-20px 0 60px rgba(0,0,0,0.25);
}
.nav-links.open{transform:translateX(0);}
.nav-links a{color:var(--ivory) !important; font-size:19px;}
@media (min-width:901px){
  .nav-links{ position:static; width:auto; height:auto; background:none; flex-direction:row; padding:0; gap:34px; transform:none; box-shadow:none; }
  .nav-links a{color:var(--navy-ink) !important; font-size:14.5px;}
  .menu-toggle{display:none;}
}

/* ============================= PAGE HEADER ============================= */
.legal-hero{ padding:118px 0 40px; background:linear-gradient(180deg,var(--navy-ink),#0d1638 60%,var(--ivory)); }
@media (min-width:860px){ .legal-hero{padding:150px 0 56px;} }
.legal-hero .eyebrow{color:var(--amber-2);}
.legal-hero .eyebrow::before{background:var(--amber-2);}
.legal-hero h1{ color:var(--ivory); font-size:clamp(28px,5vw,44px); line-height:1.15; margin-top:14px; max-width:760px; }
.legal-hero p{ color:rgba(250,247,242,0.72); margin-top:16px; max-width:640px; font-size:15.5px; }
.legal-meta{ display:flex; flex-wrap:wrap; gap:10px 22px; margin-top:22px; font-family:var(--font-eyebrow); font-size:12.5px; color:rgba(250,247,242,0.6); }
.legal-meta b{ color:var(--amber-2); font-weight:600; }

/* ============================= TABLE OF CONTENTS ============================= */
.legal-toc{ margin-top:-34px; position:relative; z-index:2; }
.legal-toc-card{
  background:var(--paper); border:1px solid var(--line); border-radius:var(--radius-lg);
  box-shadow:var(--shadow-lg); padding:22px; display:grid; grid-template-columns:1fr; gap:8px;
}
@media (min-width:640px){ .legal-toc-card{grid-template-columns:1fr 1fr; padding:26px 30px;} }
.legal-toc-card a{
  display:flex; align-items:center; gap:10px; text-decoration:none; color:var(--navy-ink);
  font-family:var(--font-display); font-weight:600; font-size:14px; padding:10px 12px; border-radius:10px;
  transition:background .25s var(--ease), color .25s var(--ease);
}
.legal-toc-card a:hover{ background:rgba(201,162,39,0.10); color:var(--amber-deep); }
.legal-toc-card a span.n{
  width:22px; height:22px; border-radius:7px; background:var(--navy-ink); color:var(--ivory);
  display:inline-flex; align-items:center; justify-content:center; font-size:11.5px; flex-shrink:0;
}

/* ============================= CONTENT ============================= */
.legal-body{ padding:56px 0 20px; }
.legal-part{ padding:32px 0 8px; }
.legal-part > .eyebrow{ margin-bottom:6px; }
.legal-part h2.part-title{ font-size:clamp(24px,4vw,34px); margin-top:8px; }
.legal-part > p.part-lead{ margin-top:12px; max-width:720px; font-size:15.5px; }

.legal-section{ padding:26px 0; border-top:1px solid var(--line); scroll-margin-top:96px; }
.legal-section:first-of-type{ border-top:none; }
.legal-section h3{ font-size:19px; display:flex; align-items:baseline; gap:10px; }
.legal-section h3 .num{ font-family:var(--font-eyebrow); font-size:12px; color:var(--amber-deep); font-weight:700; }
.legal-section h4{ font-size:15px; margin-top:18px; color:var(--teal-deep); }
.legal-section p{ margin-top:12px; font-size:14.8px; }
.legal-section p + p{ margin-top:12px; }
.legal-section ul{ margin-top:12px; display:grid; gap:9px; padding-left:0; }
.legal-section ul li{ list-style:none; display:flex; gap:10px; font-size:14.8px; line-height:1.65; color:#54514C; }
.legal-section ul li::before{ content:'—'; color:var(--amber); flex-shrink:0; font-weight:700; }

.legal-note{
  margin-top:16px; padding:16px 18px; border-radius:var(--radius-md); border:1px solid rgba(201,162,39,0.35);
  background:rgba(201,162,39,0.08);
}
.legal-note p{ color:var(--navy-ink); font-size:14px; margin-top:0; }
.legal-note strong{ color:var(--amber-deep); }

.legal-divider{
  margin:44px 0; height:1px; background:linear-gradient(90deg,transparent,var(--line) 20%,var(--line) 80%,transparent);
}

.legal-contact-card{
  margin-top:20px; padding:22px 24px; border-radius:var(--radius-lg); background:var(--navy-ink); color:var(--ivory);
  display:grid; gap:6px; box-shadow:var(--shadow-sm);
}
.legal-contact-card a{ color:var(--amber-2); text-decoration:none; font-weight:600; }
.legal-contact-card a:hover{ text-decoration:underline; }

/* ============================= FOOTER — identical to index.php ============================= */
footer{ position:relative; overflow:hidden; background:var(--navy-ink); padding-top:10px; margin-top:60px; }
.footer-orb{position:absolute; border-radius:50%; filter:blur(80px); opacity:.20; pointer-events:none; z-index:0;}
.footer-orb-1{width:320px;height:320px; background:var(--amber-2); top:-100px; left:-60px; animation:orbFloat 20s ease-in-out infinite;}
.footer-orb-2{width:260px;height:260px; background:var(--teal-2); bottom:-80px; right:-40px; animation:orbFloat 24s ease-in-out infinite; animation-delay:3s;}
@keyframes orbFloat{0%,100%{transform:translate(0,0) scale(1);} 50%{transform:translate(28px,-26px) scale(1.14);}}
@media (prefers-reduced-motion: reduce){ .footer-orb{animation:none;} }
footer .wrap{ position:relative; z-index:2; }

.footer-cta{ display:flex; flex-direction:column; gap:20px; padding:40px 0 32px; border-bottom:1px solid rgba(255,255,255,0.08); }
@media (min-width:860px){ .footer-cta{ flex-direction:row; align-items:center; justify-content:space-between; padding:56px 0 48px; } }
.footer-cta-text h3{ color:var(--ivory); font-size:clamp(20px,4vw,30px); margin-top:10px; line-height:1.25; max-width:560px; }
.footer-cta .btn{ flex-shrink:0; }

.footer-grid{display:grid; grid-template-columns:1fr; gap:36px; padding:48px 0 40px; border-bottom:1px solid rgba(255,255,255,0.08);}
@media (min-width:640px){ .footer-grid{grid-template-columns:1fr 1fr; row-gap:40px;} }
@media (min-width:1024px){ .footer-grid{grid-template-columns:1.4fr 1fr 1fr 1.2fr; gap:50px; padding:60px 0 52px;} }
.footer-brand{display:flex; flex-direction:column; align-items:flex-start;}
.footer-logo{height:84px; width:auto; max-width:170px; object-fit:contain; margin-bottom:4px; filter:brightness(0) invert(1); transition:transform .4s var(--ease);}
.footer-brand:hover .footer-logo{ transform:translateY(-3px) scale(1.04); }
.footer-brand p{color:rgba(250,247,242,0.55); font-size:14px; margin-top:14px; max-width:280px;}
.footer-est{ display:inline-flex; align-items:center; gap:8px; margin-top:14px; font-family:var(--font-eyebrow); font-size:11.5px; letter-spacing:.05em; color:var(--amber-2); }
.footer-est::before{ content:'◆'; font-size:7px; }
.footer-mini-contact{ display:grid; gap:10px; margin-top:20px; }
.footer-mini-contact a{ display:flex; align-items:center; gap:8px; color:rgba(250,247,242,0.75); text-decoration:none; font-size:14px; transition:color .3s ease, transform .25s var(--ease); }
.footer-mini-contact a:hover{ color:var(--teal-2); transform:translateX(3px); }
.social-row{ display:flex; gap:10px; margin-top:20px; }
.social-row a{ width:36px;height:36px;border-radius:50%; background:rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:center; color:var(--ivory); text-decoration:none; font-size:13px; transition:background .25s ease, transform .25s var(--ease); }
.social-row a:hover{ background:var(--amber); transform:translateY(-3px); }
.footer-col h5{color:var(--ivory); font-size:13px; letter-spacing:.06em; text-transform:uppercase; margin-bottom:20px; font-family:var(--font-eyebrow); font-weight:600; position:relative; padding-bottom:12px;}
.footer-col h5::after{ content:''; position:absolute; left:0; bottom:0; width:26px; height:2px; background:linear-gradient(90deg,var(--amber),var(--amber-2)); border-radius:2px; }
.footer-col ul{display:grid; gap:12px; list-style:none;}
.footer-col a{color:rgba(250,247,242,0.62); text-decoration:none; font-size:14px; position:relative; display:inline-flex; align-items:center; gap:8px; transition:color .3s ease, transform .25s var(--ease);}
.footer-col a::before{ content:'→'; font-size:11px; color:var(--amber-2); opacity:0; transform:translateX(-6px); transition:opacity .25s ease, transform .25s var(--ease); }
.footer-col a:hover{ color:var(--ivory); transform:translateX(4px); }
.footer-col a:hover::before{ opacity:1; transform:translateX(0); }
.footer-bottom{display:flex; justify-content:space-between; align-items:center; padding:24px 0; font-size:12.5px; color:rgba(250,247,242,0.42); flex-wrap:wrap; gap:10px; text-align:center;}
@media (min-width:768px){ .footer-bottom{padding:28px 0; font-size:13px;} }
.footer-bottom a{ color:rgba(250,247,242,0.62); text-decoration:none; }
.footer-bottom a:hover{ color:var(--ivory); text-decoration:underline; }
@media (max-width:639px){
  .footer-cta{ text-align:center; align-items:center; }
  .footer-cta-text{ margin-left:auto; margin-right:auto; }
  .footer-grid{ text-align:center; }
  .footer-brand{ align-items:center; }
  .footer-brand p{ margin-left:auto; margin-right:auto; }
  .footer-mini-contact{ justify-items:center; }
}

/* Back to top — identical to index.php */
.back-to-top{
  position:fixed; right:18px; bottom:28px; width:46px; height:46px; border-radius:50%;
  background:rgba(27,31,39,0.85); border:1px solid rgba(255,255,255,0.14); backdrop-filter:blur(10px);
  color:var(--teal-2); display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:900;
  opacity:0; visibility:hidden; transform:translateY(16px);
  transition:opacity .4s var(--ease), transform .4s var(--ease), background .3s ease, border-color .3s ease;
  box-shadow:var(--shadow-sm); -webkit-tap-highlight-color:transparent;
}
.back-to-top.show{opacity:1; visibility:visible; transform:translateY(0);}
.back-to-top:hover{background:linear-gradient(135deg,var(--amber-2),var(--amber)); color:#fff; border-color:var(--amber); transform:translateY(-4px);}
.back-to-top svg{transition:transform .3s var(--ease);}
.back-to-top:hover svg{transform:translateY(-2px);}

.scroll-progress{
  position:fixed; top:0; left:0; height:3px; width:0%; z-index:1200;
  background:linear-gradient(90deg,var(--amber),var(--amber-2),var(--teal-2));
  box-shadow:0 0 14px rgba(232,199,102,0.55);
  transition:width .12s linear;
}
</style>
</head>
<body>

<div class="scroll-progress" id="scrollProgress" aria-hidden="true"></div>

<!-- ============================= NAVBAR ============================= -->
<nav class="navbar" id="navbar">
  <div class="wrap nav-inner">
    <a href="index.php" class="brand">
        <img src="images/3.png"
          alt="Samal Consultancy Logo"
          class="brand-logo"
          id="navLogoImg"
          onerror="this.style.display='none'; document.getElementById('navLogoFallback').style.display='flex';">
        <span class="brand-mark" id="navLogoFallback" style="display:none;">SC</span>
        <span class="brand-name">Samal Consultancy</span>
    </a>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php#about">About</a></li>
      <li><a href="index.php#services">Services</a></li>
      <li><a href="index.php#products">TaskVel Pro</a></li>
      <li><a href="index.php#contact">Contact</a></li>
      <li><a href="#terms">Terms</a></li>
      <li><a href="#privacy">Privacy</a></li>
    </ul>
    <div class="nav-cta">
      <a href="index.php#contact" class="btn btn-gold" data-ripple style="padding:12px 20px; font-size:13px;">Book Consultation</a>
      <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu"><span></span><span></span><span></span></button>
    </div>
  </div>
</nav>

<!-- ============================= PAGE HEADER ============================= -->
<header class="legal-hero">
  <div class="wrap">
    <span class="eyebrow">Legal</span>
    <h1>Terms &amp; Conditions and Privacy Policy</h1>
    <p>This single page covers both Samal Consultancy's professional advisory services and the TaskVel Pro application (web, mobile and desktop). Please read it before using either.</p>
    <div class="legal-meta">
      <span><b>Effective date:</b> 5 September 2026</span>
      <span><b>Entity:</b> Samal Consultancy, Nagaon, Assam, India</span>
      <span><b>Applies to:</b> samalconsultancy.com &amp; TaskVel Pro</span>
    </div>
  </div>
</header>

<!-- ============================= TABLE OF CONTENTS ============================= -->
<div class="legal-toc">
  <div class="wrap">
    <div class="legal-toc-card">
      <a href="#terms"><span class="n">1</span> Terms &amp; Conditions</a>
      <a href="#privacy"><span class="n">2</span> Privacy Policy</a>
      <a href="#terms-taskvel-billing"><span class="n">1.4</span> TaskVel Pro plans &amp; free trials</a>
      <a href="#terms-trading"><span class="n">1.5</span> Trading Journal disclaimer</a>
      <a href="#privacy-data"><span class="n">2.2</span> Information we collect</a>
      <a href="#legal-contact"><span class="n">✉</span> Contact us about this page</a>
    </div>
  </div>
</div>

<!-- ============================= CONTENT ============================= -->
<main class="legal-body">
  <div class="wrap">

    <!-- ═══════════════════════════ PART 1 — TERMS & CONDITIONS ═══════════════════════════ -->
    <section class="legal-part" id="terms">
      <span class="eyebrow">Part One</span>
      <h2 class="part-title">Terms &amp; Conditions</h2>
      <p class="part-lead">These Terms &amp; Conditions ("Terms") are a legal agreement between you and Samal Consultancy ("Samal Consultancy", "the Company", "we", "us", "our"), a professional advisory practice based in Nagaon, Assam, India, and cover both (a) the offline and online consultancy services described on samalconsultancy.com, and (b) TaskVel Pro, the task-management application we build and operate. By engaging our consultancy services or by creating a TaskVel Pro account, you agree to these Terms.</p>

      <div class="legal-section" id="terms-definitions">
        <h3><span class="num">1.1</span> Definitions &amp; acceptance</h3>
        <p>"Services" means, collectively, our consultancy engagements (GST registration and filing, EPFO/ESIC compliance, company and trademark registration, tax advisory, payroll and related work) and the TaskVel Pro application. "You" or "User" means any individual or business using either. "Content" means anything you upload, enter or generate inside TaskVel Pro — tasks, notes, check-ins, trading journal entries, attachments and messages.</p>
        <p>You must be at least 18 years old, or the age of majority in your state, and able to enter a binding contract to use our Services. If you use TaskVel Pro on behalf of an organization (an Enterprise/Team account), you confirm you are authorised to bind that organization to these Terms, and "you" will refer to that organization as well as you personally.</p>
      </div>

      <div class="legal-section" id="terms-consultancy">
        <h3><span class="num">1.2</span> Consultancy engagements</h3>
        <p>Where you engage Samal Consultancy for GST, EPFO/ESIC, company registration, trademark or tax-advisory work, the following applies in addition to the rest of this Part:</p>
        <ul>
          <li>Our advice is based on the facts, documents and instructions you provide; you are responsible for the accuracy and completeness of what you share with us.</li>
          <li>We do not control, and cannot guarantee, the processing time, outcome or approval of any filing made with a government department, portal or authority.</li>
          <li>Professional fees for a specific filing or engagement are payable as agreed at the time of engagement (invoice, quotation or WhatsApp/email confirmation) and, once we have submitted work to a third-party portal or authority on your behalf, are non-refundable for the effort already performed.</li>
          <li>You remain responsible under law for your own statutory compliance; our role is advisory and administrative support, not a substitute for your own legal or statutory obligations as a business owner.</li>
        </ul>
      </div>

      <div class="legal-section" id="terms-taskvel-overview">
        <h3><span class="num">1.3</span> TaskVel Pro — what it is</h3>
        <p>TaskVel Pro is a task and productivity application offered by Samal Consultancy, providing: personal task management ("My Tasks", "My Work"), a focus timer and productivity tracking, an Enterprise workspace (Teams, Check-in, Manager Dashboard) for organizations that purchase seats, and an optional Trading Journal and Trading Calendar for personal trade record-keeping. You are responsible for the accuracy of the information you enter, and for keeping your login credentials confidential.</p>
      </div>

      <div class="legal-section" id="terms-taskvel-billing">
        <h3><span class="num">1.4</span> TaskVel Pro plans, free trials &amp; billing</h3>
        <p>New TaskVel Pro accounts receive full access to paid features, including Enterprise-tier limits, free for 30 days from the date of registration. No payment is required to use this initial period. After the 30-day period ends, your account automatically moves to the Free plan unless you have an active paid subscription or an organization seat; the Free plan remains fully usable, with certain limits (for example, on the number of teams, members or projects) rather than a full lock-out.</p>
        <ul>
          <li>The Trading Journal and Trading Calendar have a separate, shorter free period: 10 days, which begins the first time you log a trading entry (not on the date you registered).</li>
          <li>Paid subscriptions are billed as shown on the Billing page at the time of purchase, and are processed through our payment processor (Stripe); we do not store your full card details.</li>
          <li>Enterprise/Team plans are billed per seat to the organization owner; suspending or removing a seat ends that member's paid access but does not delete their account or personal data.</li>
          <li>You may cancel a paid subscription at any time from the Billing page; access continues until the end of the paid period already billed. Fees already charged are non-refundable except where required by law or expressly stated at the time of purchase.</li>
          <li>We may change plan pricing or limits for future billing periods; where reasonably practicable we will give notice before a change takes effect for existing subscribers.</li>
        </ul>
      </div>

      <div class="legal-section" id="terms-trading">
        <h3><span class="num">1.5</span> Trading Journal — important disclaimer</h3>
        <div class="legal-note">
          <p><strong>The Trading Journal and Trading Calendar are personal record-keeping tools only.</strong> Nothing in TaskVel Pro constitutes financial, investment, tax or trading advice, and Samal Consultancy is not a registered investment adviser or broker. Any figures, statistics, streaks or analytics shown are calculated purely from the data you enter and may not be accurate if your entries are incomplete or incorrect. You are solely responsible for your own trading and investment decisions, and should consult a licensed financial adviser before making them.</p>
        </div>
        <p>After your 10-day Trading Journal free period ends without an active paid plan, the Trading section becomes read-only: you can continue to view all your existing entries, journal notes, goals and calendar history, but cannot create or edit new records until you subscribe. We do not delete or hide your existing trading data because your free period has ended.</p>
      </div>

      <div class="legal-section" id="terms-acceptable-use">
        <h3><span class="num">1.6</span> Acceptable use</h3>
        <ul>
          <li>Do not use the Services for anything unlawful, fraudulent, or that infringes another person's rights.</li>
          <li>Do not attempt to reverse-engineer, scrape, resell or sub-license access to TaskVel Pro without our written permission.</li>
          <li>Do not attempt to bypass usage limits, free-trial logic, or subscription/paywall checks, whether through the interface or by calling the underlying API directly.</li>
          <li>Within a Team or Organization, only enter or share data you are authorised to share with your teammates or organization administrators.</li>
          <li>We may suspend or terminate accounts that violate these Terms, engage in abuse of other users, or pose a security risk to the platform.</li>
        </ul>
      </div>

      <div class="legal-section" id="terms-ip">
        <h3><span class="num">1.7</span> Intellectual property &amp; your content</h3>
        <p>Samal Consultancy owns TaskVel Pro's software, design, branding and underlying technology. You retain ownership of the Content you create inside TaskVel Pro (tasks, notes, journal entries, uploaded files). By using the Services you grant us a limited licence to host, process and display that Content solely to operate and improve the Services for you and, where applicable, your Team or Organization.</p>
      </div>

      <div class="legal-section" id="terms-org-data">
        <h3><span class="num">1.8</span> Teams &amp; organization access</h3>
        <p>If you join a Team or an Enterprise Organization inside TaskVel Pro, the owners/admins of that Team or Organization may be able to see work-related data associated with your membership (for example, shared project tasks, Check-in records addressed to them, or a Manager Dashboard view of tasks reported to their email) for the purpose of running that workspace. Your strictly personal task lists outside of shared projects remain private to you.</p>
      </div>

      <div class="legal-section" id="terms-termination">
        <h3><span class="num">1.9</span> Suspension &amp; termination</h3>
        <p>You may stop using the Services and delete your account at any time. We may suspend or terminate your access if you breach these Terms, if required by law, or if we discontinue a Service, with notice where reasonably possible. On termination, your right to use the Services ends immediately; certain provisions (intellectual property, disclaimers, limitation of liability, governing law) survive termination.</p>
      </div>

      <div class="legal-section" id="terms-disclaimer">
        <h3><span class="num">1.10</span> Disclaimers &amp; limitation of liability</h3>
        <p>The Services are provided "as is" and "as available". To the maximum extent permitted by law, Samal Consultancy disclaims all warranties, express or implied, regarding the Services, including uninterrupted availability, error-free operation, or fitness for a particular purpose. To the maximum extent permitted by law, our total liability arising out of or relating to the Services is limited to the amount you paid us for the relevant Service in the 12 months preceding the claim; we are not liable for indirect, incidental or consequential losses, including lost profits or lost trading gains.</p>
      </div>

      <div class="legal-section" id="terms-law">
        <h3><span class="num">1.11</span> Governing law &amp; changes</h3>
        <p>These Terms are governed by the laws of India, and any dispute will be subject to the exclusive jurisdiction of the courts at Nagaon, Assam. We may update these Terms from time to time; the "Effective date" at the top of this page shows when they last changed. Continuing to use the Services after an update means you accept the revised Terms.</p>
      </div>
    </section>

    <div class="legal-divider"></div>

    <!-- ═══════════════════════════ PART 2 — PRIVACY POLICY ═══════════════════════════ -->
    <section class="legal-part" id="privacy">
      <span class="eyebrow">Part Two</span>
      <h2 class="part-title">Privacy Policy</h2>
      <p class="part-lead">This Privacy Policy explains what personal data Samal Consultancy collects across our consultancy services and TaskVel Pro, why we collect it, and the choices you have.</p>

      <div class="legal-section" id="privacy-scope">
        <h3><span class="num">2.1</span> Scope</h3>
        <p>This Policy applies to samalconsultancy.com, our consultancy engagements, and the TaskVel Pro application (web, and any mobile or desktop client we offer). Where our consultancy services require you to submit statutory documents (for example, for GST or company registration), those documents are handled under this Policy plus any specific confidentiality terms of your engagement.</p>
      </div>

      <div class="legal-section" id="privacy-data">
        <h3><span class="num">2.2</span> Information we collect</h3>
        <h4>Account &amp; profile</h4>
        <ul>
          <li>Name, email address, password (stored as a secure hash, never in plain text), timezone, theme/accent preference and avatar, if you set one.</li>
        </ul>
        <h4>Product usage data</h4>
        <ul>
          <li>Tasks, notes, projects, focus-timer sessions, streaks and productivity stats you create in TaskVel Pro.</li>
          <li>Check-in records (check-in/check-out times, work-day tasks, and the "report to" email you configure for Check-in and Manager Dashboard).</li>
          <li>Team and Organization membership, roles, and shared project/task data within those workspaces.</li>
          <li>Trading Journal and Trading Calendar entries, goals and notes you choose to record — this data is used only to operate the feature for you and is never used to generate financial advice.</li>
        </ul>
        <h4>Billing data</h4>
        <ul>
          <li>Subscription plan, billing history and organization seat counts. Card and payment details are collected and processed directly by our payment processor (Stripe); we receive confirmation of payment, not your full card number.</li>
        </ul>
        <h4>Consultancy engagement data</h4>
        <ul>
          <li>Where you engage our advisory services, documents such as PAN, Aadhaar, business registration papers, bank statements or similar, submitted specifically for the filing or registration you asked us to complete.</li>
        </ul>
        <h4>Technical data</h4>
        <ul>
          <li>IP address, browser/device type, and basic log data used for security (rate-limiting, fraud prevention) and diagnosing issues.</li>
        </ul>
      </div>

      <div class="legal-section" id="privacy-use">
        <h3><span class="num">2.3</span> How we use this information</h3>
        <ul>
          <li>To provide, maintain and improve the Services, including syncing your data across devices.</li>
          <li>To process payments and manage subscriptions, trials and Enterprise seats.</li>
          <li>To complete the specific filings or advisory work you have engaged us for.</li>
          <li>To send you service-related emails (verification, reminders, trial/renewal notices) and, where you opt in, product updates.</li>
          <li>To detect, prevent and investigate fraud, abuse or security incidents.</li>
          <li>To comply with applicable tax, accounting and other legal obligations.</li>
        </ul>
      </div>

      <div class="legal-section" id="privacy-cookies">
        <h3><span class="num">2.4</span> Cookies &amp; local storage</h3>
        <p>We use browser local storage (not third-party ad-tracking cookies) to remember your theme, colour accent and session preferences inside TaskVel Pro, and session cookies to keep you securely logged in. You can clear these at any time from your browser settings; doing so may sign you out or reset display preferences.</p>
      </div>

      <div class="legal-section" id="privacy-sharing">
        <h3><span class="num">2.5</span> Who we share data with</h3>
        <ul>
          <li>Our payment processor (Stripe), solely to process payments and manage subscriptions.</li>
          <li>Government portals or authorities, only where necessary to complete a filing or registration you have specifically asked us to make on your behalf.</li>
          <li>Email delivery providers, solely to send account, verification and notification emails.</li>
          <li>Other members of your Team or Organization, limited to the shared/work-related data described in Terms §1.8.</li>
        </ul>
        <p>We do not sell your personal data to third parties, and do not share it for third-party advertising purposes.</p>
      </div>

      <div class="legal-section" id="privacy-security">
        <h3><span class="num">2.6</span> Data security &amp; retention</h3>
        <p>We use industry-standard measures to protect your data, including password hashing, encrypted connections, and access controls that limit which staff or systems can see which data. We retain your account and usage data for as long as your account is active, and afterward only as long as needed for legitimate business or legal purposes (for example, statutory record-keeping for consultancy filings, or fraud/dispute records), after which it is deleted or anonymised.</p>
      </div>

      <div class="legal-section" id="privacy-rights">
        <h3><span class="num">2.7</span> Your rights &amp; choices</h3>
        <ul>
          <li>You can review and update most of your profile information directly inside TaskVel Pro.</li>
          <li>You can request a copy of your personal data, or ask us to correct or delete it, by contacting us using the details below — subject to any records we are legally required to keep (for example, billing and tax records).</li>
          <li>You can opt out of non-essential marketing emails using the unsubscribe link in those emails; you will still receive essential account and billing notices.</li>
        </ul>
      </div>

      <div class="legal-section" id="privacy-children">
        <h3><span class="num">2.8</span> Children's privacy</h3>
        <p>Our Services are intended for people aged 18 and over, or the age of majority in their jurisdiction, and are not directed at children. We do not knowingly collect personal data from children. If you believe a child has provided us with personal data, please contact us and we will take steps to delete it.</p>
      </div>

      <div class="legal-section" id="privacy-international">
        <h3><span class="num">2.9</span> Where your data is processed</h3>
        <p>Our servers and primary operations are based in India, and your data is generally processed there. If you access the Services from outside India, you understand your data will be transferred to and processed in India, subject to this Policy.</p>
      </div>

      <div class="legal-section" id="privacy-changes">
        <h3><span class="num">2.10</span> Changes to this Policy</h3>
        <p>We may update this Privacy Policy from time to time to reflect changes to our Services or legal requirements. We will update the "Effective date" at the top of this page when we do, and, for material changes, will make reasonable efforts to notify you directly (for example, by email or an in-app notice).</p>
      </div>
    </section>

    <div class="legal-contact-card" id="legal-contact">
      <span class="eyebrow" style="color:var(--amber-2);">Questions about this page</span>
      <p style="color:rgba(250,247,242,0.8); margin-top:8px;">For anything relating to these Terms, this Privacy Policy, a data request, or a TaskVel Pro billing question, reach us at:</p>
      <p><a href="mailto:info@samalconsultancy.com">info@samalconsultancy.com</a> &nbsp;·&nbsp; <a href="tel:+917002050242">+91 70020 50242</a></p>
      <p style="color:rgba(250,247,242,0.55); font-size:13px; margin-top:4px;">Samal Consultancy, Subarna Path, Near Railway Gate, Lakhinagar, Khutikatia, Nagaon, Assam 782002, India</p>
    </div>

  </div>
</main>

<!-- ============================= FOOTER — identical to index.php ============================= -->
<footer id="footer">
  <span class="footer-orb footer-orb-1" aria-hidden="true"></span>
  <span class="footer-orb footer-orb-2" aria-hidden="true"></span>

  <div class="footer-cta wrap">
    <div class="footer-cta-text">
      <span class="eyebrow" style="color:var(--amber-2);">Let's Talk Compliance</span>
      <h3>Ready to hand off your GST, EPFO &amp; ROC filings?</h3>
    </div>
    <a href="index.php#contact" class="btn btn-gold" data-ripple>Book Free Consultation</a>
  </div>

  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="index.php" class="brand" style="margin-bottom:6px;">
            <img src="images/3.png" alt="Samal Consultancy Logo" class="footer-logo" id="footerLogoImg"
                onerror="this.style.display='none'; document.getElementById('footerLogoFallback').style.display='flex';">
            <span class="brand-mark" id="footerLogoFallback" style="display:none; align-items:center; justify-content:center;">SC</span>
        </a>
        <span class="brand-name" style="color:var(--ivory);">Samal Consultancy</span>
        <p>End-to-end tax, registration and labour-law compliance for growing businesses — and the team behind TaskVel Pro.</p>
        <div class="footer-est">Providing trusted compliance services since 1993</div>
        <div class="footer-mini-contact">
          <a href="tel:+917002050242"><span class="fmc-icon">☎</span> +91 70020 50242</a>
          <a href="mailto:info@samalconsultancy.com"><span class="fmc-icon">✉</span> info@samalconsultancy.com</a>
        </div>
        <div class="social-row">
          <a href="#" aria-label="LinkedIn"><span>in</span></a>
          <a href="#" aria-label="Twitter"><span>𝕏</span></a>
          <a href="#" aria-label="Instagram"><span>◎</span></a>
        </div>
      </div>
      <div class="footer-col">
        <h5>Company</h5>
        <ul>
          <li><a href="index.php#about">About Us</a></li>
          <li><a href="index.php#products">Products</a></li>
          <li><a href="index.php#services">Services</a></li>
          <li><a href="index.php#contact">Contact</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>TaskVel Pro</h5>
        <ul>
          <li><a href="taskvel-pro.php">Open App</a></li>
          <li><a href="billing.php">Plans &amp; Billing</a></li>
          <li><a href="teams.php">Enterprise / Teams</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Legal</h5>
        <ul>
          <li><a href="#terms">Terms &amp; Conditions</a></li>
          <li><a href="#privacy">Privacy Policy</a></li>
          <li><a href="#legal-contact">Contact about privacy</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <span id="year"></span> Samal Consultancy. All rights reserved.</span>
      <span><a href="legal.php#terms">Terms of Service</a> · <a href="legal.php#privacy">Privacy Policy</a></span>
    </div>
  </div>
</footer>

<button id="backToTop" class="back-to-top" aria-label="Back to top">
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 19V5"/><path d="M6 11l6-6 6 6"/>
  </svg>
</button>

<script>
document.getElementById('year').textContent = new Date().getFullYear();

const navbar = document.getElementById('navbar');
const backToTop = document.getElementById('backToTop');
const scrollProgress = document.getElementById('scrollProgress');
window.addEventListener('scroll', () => {
  const y = window.scrollY;
  backToTop.classList.toggle('show', y > 600);
  const max = document.documentElement.scrollHeight - window.innerHeight;
  scrollProgress.style.width = (max > 0 ? (y / max) * 100 : 0) + '%';
}, { passive:true });

const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');
menuToggle.addEventListener('click', () => {
  navLinks.classList.toggle('open');
  menuToggle.classList.toggle('open');
});
navLinks.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
  navLinks.classList.remove('open');
  menuToggle.classList.remove('open');
}));

backToTop.addEventListener('click', () => window.scrollTo({ top:0, behavior:'smooth' }));
</script>
</body>
</html>
