<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-title" content="Taskvel" />
    <meta name="theme-color" content="#FAF8F3" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#0A1128" media="(prefers-color-scheme: dark)" />
    <meta name="description" content="Taskvel — a fast, focused, beautifully simple task organiser with a built-in focus timer." />
    <title>Taskvel · कार्य, done well.</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230a0a0a'/%3E%3Ctext x='50' y='72' font-family='Arial,sans-serif' font-size='62' font-weight='800' fill='%23ffffff' text-anchor='middle'%3ET%3C/text%3E%3C/svg%3E"
    />
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230a0a0a'/%3E%3Ctext x='50' y='72' font-family='Arial,sans-serif' font-size='62' font-weight='800' fill='%23ffffff' text-anchor='middle'%3ET%3C/text%3E%3C/svg%3E"
    />
    <link rel="manifest" href="manifest.json" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Sora:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" media="print" onload="this.media='all'" />
    <style>
        /* ════════════════════════════════════════════
           THEME SYSTEM
           data-theme  = light | dark   (lightness mode)
           data-accent = samal | mono | indigo | emerald | amber  (colour theme)
        ════════════════════════════════════════════ */
        
         :root {
            --bg: #f6f6f4;
            --bg-elev: #ffffff;
            --bg-sunk: #ededea;
            --ink: #0a0a0a;
            --ink2: #3d3d3b;
            --ink3: #7c7c78;
            --ink4: #b4b4ae;
            --line: #e6e5e0;
            --line2: #d4d3cd;
            --line-ink: #0a0a0a;
            --paper: #ffffff;
            --accent: #0a0a0a;
            --accent-2: #3d3d3b;
            --accent-soft: rgba(10, 10, 10, .08);
            --accent-glow: rgba(10, 10, 10, .14);
            --on-accent: #ffffff;
            --good: #16a34a;
            --good-soft: rgba(22, 163, 74, .12);
            --warn: #d97706;
            --warn-soft: rgba(217, 119, 6, .12);
            --bad: #dc2626;
            --bad-soft: rgba(220, 38, 38, .12);
            --r: 14px;
            --r-lg: 18px;
            --r-sm: 9px;
            --shadow-sm: 0 1px 2px rgba(10, 10, 10, .06);
            --shadow: 0 10px 34px rgba(10, 10, 10, .10);
            --shadow-lg: 0 24px 60px rgba(10, 10, 10, .16);
            --ring: rgba(10, 10, 10, .10);
            --ease: cubic-bezier(.22, 1, .36, 1);
            --spring: cubic-bezier(.34, 1.56, .64, 1);
        }
        
         :root[data-theme="dark"] {
            --bg: #0b0b0b;
            --bg-elev: #161615;
            --bg-sunk: #070707;
            --ink: #f7f6f2;
            --ink2: #bcbbb3;
            --ink3: #84837c;
            --ink4: #56554f;
            --line: #262624;
            --line2: #393834;
            --line-ink: #f7f6f2;
            --paper: #1a1a18;
            --accent: #f7f6f2;
            --accent-2: #bcbbb3;
            --accent-soft: rgba(247, 246, 242, .10);
            --accent-glow: rgba(247, 246, 242, .16);
            --on-accent: #0a0a0a;
            --good: #34d399;
            --good-soft: rgba(52, 211, 153, .14);
            --warn: #fbbf24;
            --warn-soft: rgba(251, 191, 36, .14);
            --bad: #f87171;
            --bad-soft: rgba(248, 113, 113, .14);
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, .5);
            --shadow: 0 10px 34px rgba(0, 0, 0, .55);
            --shadow-lg: 0 24px 60px rgba(0, 0, 0, .7);
            --ring: rgba(247, 246, 242, .14);
        }
        /* INDIGO */
        
         :root[data-accent="indigo"] {
            --bg: #f5f6fb;
            --bg-elev: #ffffff;
            --bg-sunk: #e9ebf6;
            --ink: #1a1d3a;
            --ink2: #474c70;
            --ink3: #8086a8;
            --ink4: #b6bad3;
            --line: #e3e6f2;
            --line2: #d0d4ea;
            --line-ink: #4f46e5;
            --paper: #ffffff;
            --accent: #4f46e5;
            --accent-2: #6d28d9;
            --accent-soft: rgba(79, 70, 229, .10);
            --accent-glow: rgba(79, 70, 229, .28);
            --on-accent: #ffffff;
            --shadow: 0 12px 38px rgba(79, 70, 229, .16);
            --shadow-lg: 0 24px 60px rgba(79, 70, 229, .22);
            --ring: rgba(79, 70, 229, .22);
        }
        
         :root[data-accent="indigo"][data-theme="dark"] {
            --bg: #0c0e1c;
            --bg-elev: #161930;
            --bg-sunk: #080a16;
            --ink: #eef0ff;
            --ink2: #b3b8e0;
            --ink3: #7d83b0;
            --ink4: #4e5482;
            --line: #232745;
            --line2: #343a5e;
            --line-ink: #818cf8;
            --paper: #161930;
            --accent: #818cf8;
            --accent-2: #a78bfa;
            --accent-soft: rgba(129, 140, 248, .14);
            --accent-glow: rgba(129, 140, 248, .35);
            --on-accent: #0c0e1c;
            --shadow: 0 12px 40px rgba(0, 0, 0, .6);
            --shadow-lg: 0 24px 64px rgba(0, 0, 0, .72);
            --ring: rgba(129, 140, 248, .3);
        }
        /* EMERALD */
        
         :root[data-accent="emerald"] {
            --bg: #f3f8f5;
            --bg-elev: #ffffff;
            --bg-sunk: #e6f1ea;
            --ink: #0e2a20;
            --ink2: #3a5a4b;
            --ink3: #759084;
            --ink4: #aecabd;
            --line: #dcede4;
            --line2: #c6e0d2;
            --line-ink: #059669;
            --paper: #ffffff;
            --accent: #059669;
            --accent-2: #0d9488;
            --accent-soft: rgba(5, 150, 105, .10);
            --accent-glow: rgba(5, 150, 105, .26);
            --on-accent: #ffffff;
            --shadow: 0 12px 38px rgba(5, 150, 105, .15);
            --shadow-lg: 0 24px 60px rgba(5, 150, 105, .2);
            --ring: rgba(5, 150, 105, .22);
        }
        
         :root[data-accent="emerald"][data-theme="dark"] {
            --bg: #07140f;
            --bg-elev: #0f221b;
            --bg-sunk: #050f0b;
            --ink: #e6fff4;
            --ink2: #a7d6c2;
            --ink3: #6fa28d;
            --ink4: #44685a;
            --line: #1b3329;
            --line2: #294a3b;
            --line-ink: #34d399;
            --paper: #0f221b;
            --accent: #34d399;
            --accent-2: #2dd4bf;
            --accent-soft: rgba(52, 211, 153, .14);
            --accent-glow: rgba(52, 211, 153, .32);
            --on-accent: #07140f;
            --shadow: 0 12px 40px rgba(0, 0, 0, .6);
            --shadow-lg: 0 24px 64px rgba(0, 0, 0, .72);
            --ring: rgba(52, 211, 153, .3);
        }
        /* AMBER */
        
         :root[data-accent="amber"] {
            --bg: #fbf7f1;
            --bg-elev: #ffffff;
            --bg-sunk: #f4ece0;
            --ink: #2e1f0c;
            --ink2: #6a5232;
            --ink3: #a08560;
            --ink4: #d0bca0;
            --line: #efe5d6;
            --line2: #e2d3bd;
            --line-ink: #ea580c;
            --paper: #ffffff;
            --accent: #ea580c;
            --accent-2: #d97706;
            --accent-soft: rgba(234, 88, 12, .10);
            --accent-glow: rgba(234, 88, 12, .26);
            --on-accent: #ffffff;
            --shadow: 0 12px 38px rgba(234, 88, 12, .15);
            --shadow-lg: 0 24px 60px rgba(234, 88, 12, .2);
            --ring: rgba(234, 88, 12, .22);
        }
        
         :root[data-accent="amber"][data-theme="dark"] {
            --bg: #160e05;
            --bg-elev: #241809;
            --bg-sunk: #100a03;
            --ink: #fff2e0;
            --ink2: #e0c4a0;
            --ink3: #ad9170;
            --ink4: #6e5942;
            --line: #34230f;
            --line2: #4a3318;
            --line-ink: #fb923c;
            --paper: #241809;
            --accent: #fb923c;
            --accent-2: #fbbf24;
            --accent-soft: rgba(251, 146, 60, .14);
            --accent-glow: rgba(251, 146, 60, .34);
            --on-accent: #160e05;
            --shadow: 0 12px 40px rgba(0, 0, 0, .6);
            --shadow-lg: 0 24px 64px rgba(0, 0, 0, .72);
            --ring: rgba(251, 146, 60, .3);
        }
        

        /* SAMAL — Samal Consultancy brand */

         :root[data-accent="samal"] {
            --bg: #FAF8F3;
            --bg-elev: #ffffff;
            --bg-sunk: #F3F1E9;
            --ink: #0A1128;
            --ink2: #3C4258;
            --ink3: #7A7F90;
            --ink4: #B9BCC6;
            --line: #EAE7DD;
            --line2: #D8D6CE;
            --line-ink: #C9A227;
            --paper: #ffffff;
            --accent: #C9A227;
            --accent-2: #0F4436;
            --accent-soft: rgba(201, 162, 39, .12);
            --accent-glow: rgba(201, 162, 39, .30);
            --on-accent: #ffffff;
            --shadow: 0 12px 38px rgba(10, 17, 40, .12);
            --shadow-lg: 0 24px 60px rgba(10, 17, 40, .18);
            --ring: rgba(201, 162, 39, .25);
        }

         :root[data-accent="samal"][data-theme="dark"] {
            --bg: #0A1128;
            --bg-elev: #121A36;
            --bg-sunk: #060B1C;
            --ink: #FAF8F3;
            --ink2: #C3C8DC;
            --ink3: #8990AC;
            --ink4: #525A78;
            --line: #1E2745;
            --line2: #2C365A;
            --line-ink: #E8C766;
            --paper: #121A36;
            --accent: #E8C766;
            --accent-2: #8FA0E8;
            --accent-soft: rgba(232, 199, 102, .14);
            --accent-glow: rgba(232, 199, 102, .34);
            --on-accent: #0A1128;
            --shadow: 0 12px 40px rgba(0, 0, 0, .6);
            --shadow-lg: 0 24px 64px rgba(0, 0, 0, .72);
            --ring: rgba(232, 199, 102, .3);
        }
        
        html {
            color-scheme: light;
        }
        
        html[data-theme="dark"] {
            color-scheme: dark;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Sora', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            transition: background .5s var(--ease), color .5s var(--ease);
        }
        
        .aurora {
            display: none;
            position: fixed;
            inset: -25%;
            z-index: -2;
            pointer-events: none;
            filter: blur(90px);
            opacity: .5;
        }
        
         :root[data-accent="samal"] .aurora,
         :root[data-accent="indigo"] .aurora,
         :root[data-accent="emerald"] .aurora,
         :root[data-accent="amber"] .aurora {
            display: block;
        }
        
        .aurora span {
            position: absolute;
            border-radius: 50%;
            mix-blend-mode: screen;
            background: radial-gradient(circle, var(--accent), transparent 65%);
            animation: drift 24s var(--ease) infinite alternate;
        }
        
        .aurora .a1 {
            width: 46vw;
            height: 46vw;
            left: -8vw;
            top: -10vw;
        }
        
        .aurora .a2 {
            width: 42vw;
            height: 42vw;
            right: -8vw;
            top: 12vh;
            background: radial-gradient(circle, var(--accent-2), transparent 65%);
            animation-delay: -8s;
        }
        
        .aurora .a3 {
            width: 38vw;
            height: 38vw;
            left: 22vw;
            bottom: -14vw;
            animation-delay: -14s;
            opacity: .7;
        }
        
        @keyframes drift {
            0% {
                transform: translate(0, 0) scale(1);
            }
            100% {
                transform: translate(5vw, 4vh) scale(1.16);
            }
        }
        
        .grid-overlay {
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            opacity: .5;
            background-image: radial-gradient(var(--line) .5px, transparent .5px);
            background-size: 22px 22px;
            mask-image: radial-gradient(ellipse at 50% 0%, #000 20%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse at 50% 0%, #000 20%, transparent 75%);
        }
        
        .app {
            max-width: 560px;
            margin: 0 auto;
            padding: 0 18px 120px;
            position: relative;
        }

        body.focus-mode .header, body.focus-mode .toolbar, body.focus-mode .tagrow,
        body.focus-mode .tabs, body.focus-mode .list, body.focus-mode .remarks-view,
        body.focus-mode .time-report-view, body.focus-mode .matrix-view,
        body.focus-mode .review-view, body.focus-mode .stats {
            opacity: .12;
            pointer-events: none;
            transition: opacity .4s var(--ease);
        }
        body.focus-mode .focus {
            transform: scale(1.04);
            box-shadow: var(--shadow-lg);
        }
        
        @media (min-width: 720px) {
            .app {
                max-width: 660px;
                padding: 0 24px 120px;
            }
        }
        
        @media (min-width: 980px) {
            .app {
                max-width: 760px;
            }
        }
        /* ── Header ── */
        
        .header {
            padding: 26px 0 8px;
            position: sticky;
            top: 0;
            z-index: 40;
            background: linear-gradient(180deg, var(--bg) 62%, transparent);
            backdrop-filter: blur(8px);
        }
        
        .brand-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Sora';
            font-weight: 800;
            font-size: 21px;
            color: var(--on-accent);
            flex-shrink: 0;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            transition: background .5s var(--ease), transform .4s var(--spring);
        }
        
        .logo:hover {
            transform: rotate(-6deg) scale(1.06);
        }
        
        .logo::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent 40%, rgba(255, 255, 255, .3) 50%, transparent 60%);
            transform: translateX(-120%);
            animation: sheen 5s ease-in-out infinite;
        }
        
        @keyframes sheen {
            0%,
            65% {
                transform: translateX(-120%);
            }
            100% {
                transform: translateX(120%);
            }
        }
        
        .brand-txt h1 {
            font-family: 'Sora';
            font-size: 23px;
            font-weight: 800;
            letter-spacing: -.6px;
            line-height: 1;
            color: var(--ink);
        }
        
        .brand-txt h1 span {
            font-weight: 400;
            color: var(--accent);
            transition: color .5s var(--ease);
        }
        
        .brand-txt .tag {
            font-family: 'JetBrains Mono';
            font-size: 10px;
            color: var(--ink3);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        .free-pill {
            display: inline-flex;
            align-items: center;
            vertical-align: middle;
            margin-left: 9px;
            padding: 4px 10px 3px;
            border-radius: 100px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: var(--accent);
            background: var(--accent-soft);
            border: 1px solid var(--accent-glow);
            transform: translateY(-3px);
            transition: background .5s var(--ease), color .5s var(--ease);
        }
        
        .by-samal {
            font-weight: 700;
            letter-spacing: 2px;
            text-decoration: none;
            background: linear-gradient(120deg, #E8C766, #C9A227);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            transition: opacity .3s ease;
        }
        
        .by-samal:hover {
            opacity: .75;
        }
        
        .clock-chip {
            background: var(--bg-elev);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 8px 13px;
            text-align: right;
            min-width: 116px;
            box-shadow: var(--shadow-sm);
        }
        
        .head-right {
            display: flex;
            align-items: stretch;
            gap: 9px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        
        .icon-btn {
            width: 44px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--bg-elev);
            color: var(--ink);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            transition: all .3s var(--ease);
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }
        
        .icon-btn:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            color: var(--accent);
        }
        
        .icon-btn:active {
            transform: scale(.93);
        }
        
        .icon-btn .badge-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--bad);
            display: none;
        }
        
        .icon-btn .badge-dot.show {
            display: block;
        }
        
        .tt-icon {
            font-size: 18px;
            line-height: 1;
            display: block;
            transition: transform .5s var(--spring);
        }
        
        .icon-btn:hover .tt-icon {
            transform: rotate(40deg) scale(1.12);
        }
        
        #palette-btn:hover .tt-icon {
            transform: rotate(-30deg) scale(1.12);
        }
        
        .clock-time {
            font-family: 'JetBrains Mono';
            font-size: 19px;
            font-weight: 700;
            letter-spacing: .3px;
            line-height: 1;
            color: var(--ink);
        }
        
        .clock-time .sec {
            font-size: 13px;
            color: var(--accent);
            transition: color .5s var(--ease);
        }
        
        .clock-date {
            font-size: 10px;
            color: var(--ink3);
            margin-top: 5px;
            font-family: 'JetBrains Mono';
            letter-spacing: .5px;
        }
        
        .greeting {
            margin: 20px 0 4px;
            font-family: 'Space Grotesk';
            font-size: 15px;
            color: var(--ink2);
            font-weight: 400;
        }
        
        .greeting b {
            color: var(--accent);
            font-weight: 700;
            transition: color .5s var(--ease);
        }
        /* ── Panels (palette / notifications / export / history) ── */
        
        .panel {
            display: none;
            margin: 14px 0 2px;
            padding: 14px;
            background: var(--bg-elev);
            border: 1px solid var(--line);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-sm);
            animation: paletteIn .4s var(--spring);
        }
        
        .panel.open {
            display: block;
        }
        
        @keyframes paletteIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: none;
            }
        }
        
        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .panel-title {
            font-family: 'Space Grotesk';
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        
        .panel-close {
            background: none;
            border: none;
            color: var(--ink3);
            cursor: pointer;
            font-size: 16px;
            padding: 2px 6px;
            transition: color .2s;
        }
        
        .panel-close:hover {
            color: var(--accent);
        }
        
        .palette-row {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }
        
        .swatch {
            flex: 1;
            min-width: 110px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border: 1.5px solid var(--line2);
            border-radius: 12px;
            background: var(--bg);
            cursor: pointer;
            transition: all .28s var(--spring);
            position: relative;
            overflow: hidden;
        }
        
        .swatch:hover {
            transform: translateY(-3px) scale(1.02);
            border-color: var(--sw);
            box-shadow: 0 10px 26px -8px var(--sw);
        }
        
        .swatch.active {
            border-color: var(--sw);
            box-shadow: 0 0 0 3px var(--sw-soft);
        }
        
        .swatch.active::after {
            content: '✓';
            position: absolute;
            top: 7px;
            right: 9px;
            font-size: 11px;
            font-weight: 800;
            color: var(--sw);
        }
        
        .sw-dot {
            width: 24px;
            height: 24px;
            border-radius: 8px;
            flex-shrink: 0;
            background: var(--sw-grad);
            box-shadow: 0 3px 10px -2px var(--sw);
            transition: transform .3s var(--spring);
        }
        
        .swatch:hover .sw-dot {
            transform: rotate(-8deg) scale(1.1);
        }
        
        .sw-name {
            font-family: 'Space Grotesk';
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
        }
        
        .sw-samal {
            --sw: #C9A227;
            --sw-soft: rgba(201, 162, 39, .2);
            --sw-grad: linear-gradient(135deg, #E8C766, #C9A227 55%, #0F4436);
        }
        
        .sw-mono {
            --sw: #0a0a0a;
            --sw-soft: rgba(10, 10, 10, .15);
            --sw-grad: linear-gradient(135deg, #2a2a2a, #0a0a0a);
        }
        
        .sw-indigo {
            --sw: #4f46e5;
            --sw-soft: rgba(79, 70, 229, .2);
            --sw-grad: linear-gradient(135deg, #818cf8, #4f46e5);
        }
        
        .sw-emerald {
            --sw: #059669;
            --sw-soft: rgba(5, 150, 105, .2);
            --sw-grad: linear-gradient(135deg, #34d399, #059669);
        }
        
        .sw-amber {
            --sw: #ea580c;
            --sw-soft: rgba(234, 88, 12, .2);
            --sw-grad: linear-gradient(135deg, #fbbf24, #ea580c);
        }
        /* notifications list */
        
        .notif-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 280px;
            overflow-y: auto;
        }
        
        .notif-item {
            display: flex;
            gap: 10px;
            padding: 10px 12px;
            background: var(--bg-sunk);
            border: 1px solid var(--line);
            border-radius: 10px;
            align-items: flex-start;
        }
        
        .notif-item .ic {
            font-size: 15px;
            color: var(--accent);
            flex-shrink: 0;
            margin-top: 1px;
        }
        
        .notif-item .body {
            flex: 1;
            min-width: 0;
        }
        
        .notif-item .msg {
            font-size: 12.5px;
            color: var(--ink);
            line-height: 1.4;
        }
        
        .notif-item .time {
            font-size: 10px;
            color: var(--ink3);
            font-family: 'JetBrains Mono';
            margin-top: 3px;
        }
        
        .notif-empty {
            text-align: center;
            padding: 24px 10px;
            color: var(--ink3);
            font-size: 12.5px;
        }
        /* export */
        
        .export-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .export-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 18px 10px;
            border: 1.5px dashed var(--line2);
            border-radius: 12px;
            background: var(--bg);
            cursor: pointer;
            transition: all .25s var(--spring);
        }
        
        .export-card:hover {
            border-color: var(--accent);
            border-style: solid;
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }
        
        .export-card .xic {
            font-size: 26px;
            color: var(--accent);
        }
        
        .export-card .xname {
            font-family: 'Space Grotesk';
            font-size: 13px;
            font-weight: 600;
        }
        
        .export-card .xdesc {
            font-size: 10.5px;
            color: var(--ink3);
            text-align: center;
        }
        
        .export-filters {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--line);
        }
        
        .ef-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        
        .ef-row label {
            font-size: 11.5px;
            font-family: 'Space Grotesk';
            font-weight: 500;
            color: var(--ink2);
            flex-shrink: 0;
        }
        
        .ef-row select {
            flex: 1;
            max-width: 60%;
            padding: 7px 10px;
            font-size: 12px;
            font-family: 'Sora';
            color: var(--ink);
            background: var(--bg);
            border: 1px solid var(--line2);
            border-radius: 9px;
            outline: none;
            cursor: pointer;
            transition: all .25s var(--ease);
        }
        
        .ef-row select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--ring);
        }
        
        .ef-summary {
            font-size: 11px;
            font-family: 'JetBrains Mono';
            color: var(--ink3);
            text-align: center;
            margin-bottom: 12px;
            padding: 7px;
            background: var(--bg-sunk);
            border-radius: 8px;
        }
        /* history */
        
        .hist-tot {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
        }
        
        .hist-tot .box {
            flex: 1;
            background: var(--bg-sunk);
            border-radius: 10px;
            padding: 12px;
            text-align: center;
        }
        
        .hist-tot .num {
            font-family: 'JetBrains Mono';
            font-size: 20px;
            font-weight: 700;
            color: var(--accent);
        }
        
        .hist-tot .lbl {
            font-size: 9.5px;
            color: var(--ink3);
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-top: 3px;
            font-family: 'JetBrains Mono';
        }
        
        .chart {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            height: 110px;
            padding: 0 2px;
        }
        
        .chart .col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            height: 100%;
            justify-content: flex-end;
        }
        
        .chart .barwrap {
            width: 100%;
            height: 84px;
            display: flex;
            align-items: flex-end;
            border-radius: 6px;
            overflow: hidden;
            background: var(--bg-sunk);
        }
        
        .chart .bar {
            width: 100%;
            background: var(--accent);
            border-radius: 6px 6px 0 0;
            transition: height .6s var(--spring);
            min-height: 2px;
        }
        
        .chart .dlabel {
            font-size: 9px;
            color: var(--ink3);
            font-family: 'JetBrains Mono';
        }
        
        .chart .mlabel {
            font-size: 9px;
            color: var(--ink);
            font-family: 'JetBrains Mono';
            font-weight: 700;
        }
        /* ── Onboarding ── */
        /* ── Onboarding carousel ── */
        
        .onboard-carousel {
            background: var(--bg-elev);
            border: 1.5px solid var(--line);
            border-radius: var(--r-lg);
            margin: 16px 0 18px;
            position: relative;
            overflow: hidden;
            animation: cardIn .5s var(--ease);
            box-shadow: var(--shadow-sm);
        }
        
        .onboard-carousel::before {
            content: '';
            position: absolute;
            top: -40%;
            left: 50%;
            width: 70%;
            padding-bottom: 70%;
            transform: translateX(-50%);
            background: radial-gradient(circle, var(--accent-soft), transparent 70%);
            pointer-events: none;
        }
        
        .onboard-skip {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 2;
            background: none;
            border: none;
            color: var(--ink3);
            font-family: 'Space Grotesk';
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all .25s var(--ease);
        }
        
        .onboard-skip:hover {
            color: var(--ink);
            background: var(--bg-sunk);
        }
        
        .onboard-track {
            display: flex;
            transition: transform .45s var(--ease);
            will-change: transform;
        }
        
        .onboard-slide {
            flex: 0 0 100%;
            width: 100%;
            padding: 30px 26px 14px;
            text-align: center;
            box-sizing: border-box;
            position: relative;
        }
        
        .onboard-slide .oic {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 18px;
            background: var(--accent-soft);
            border: 1px solid var(--accent-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--accent);
            position: relative;
            animation: onboardIconFloat 3.4s ease-in-out infinite;
        }
        
        @keyframes onboardIconFloat {
            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-5px) rotate(-3deg);
            }
        }
        
        .onboard-slide h3 {
            font-family: 'Space Grotesk';
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
        }
        
        .onboard-slide p {
            font-size: 13px;
            color: var(--ink3);
            line-height: 1.6;
            margin-bottom: 18px;
            max-width: 360px;
            margin-left: auto;
            margin-right: auto;
            position: relative;
            min-height: 62px;
        }
        
        .onboard-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            background: var(--accent);
            color: var(--on-accent);
            border: none;
            border-radius: 12px;
            font-family: 'Sora';
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 26px -8px var(--accent-glow);
            transition: transform .3s var(--spring), box-shadow .3s var(--ease);
            position: relative;
            margin-bottom: 6px;
        }
        
        .onboard-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 32px -8px var(--accent-glow);
        }
        
        .onboard-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            padding: 4px 22px 22px;
            position: relative;
        }
        
        .onboard-dots {
            display: flex;
            gap: 7px;
            align-items: center;
        }
        
        .onboard-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--line2);
            cursor: pointer;
            transition: all .35s var(--spring);
            padding: 0;
            border: none;
        }
        
        .onboard-dot.active {
            width: 20px;
            border-radius: 4px;
            background: var(--accent);
        }
        
        .onboard-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 10px;
        }
        
        .onboard-btn {
            flex: 1;
            padding: 11px 18px;
            border-radius: 11px;
            font-family: 'Space Grotesk';
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all .25s var(--spring);
            border: 1px solid var(--line2);
        }
        
        .onboard-btn.ghost {
            background: var(--bg);
            color: var(--ink2);
        }
        
        .onboard-btn.ghost:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-2px);
        }
        
        .onboard-btn.primary {
            background: var(--accent);
            color: var(--on-accent);
            border-color: var(--accent);
            box-shadow: 0 8px 20px -8px var(--accent-glow);
        }
        
        .onboard-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px -8px var(--accent-glow);
        }
        /* ── Stats ── */
        
        .stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin: 16px 0 18px;
        }
        
        .stat {
            background: var(--bg-elev);
            border: 1px solid var(--line);
            border-radius: var(--r);
            padding: 14px 10px 12px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: transform .35s var(--spring), border-color .3s, box-shadow .35s;
            animation: statIn .5s var(--spring) backwards;
        }
        
        @keyframes statIn {
            from {
                opacity: 0;
                transform: translateY(10px) scale(.96);
            }
            to {
                opacity: 1;
                transform: none;
            }
        }
        
        .stat:nth-child(1) {
            animation-delay: 0ms;
        }
        
        .stat:nth-child(2) {
            animation-delay: 60ms;
        }
        
        .stat:nth-child(3) {
            animation-delay: 120ms;
        }
        
        .stat:nth-child(4) {
            animation-delay: 180ms;
        }
        
        .stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--accent);
            transform: scaleX(0);
            transition: transform .35s var(--ease);
        }
        
        .stat:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: var(--shadow);
        }
        
        .stat:hover::before {
            transform: scaleX(1);
        }
        
        .stat-num {
            font-family: 'JetBrains Mono';
            font-size: 26px;
            font-weight: 700;
            line-height: 1;
            color: var(--ink);
            transition: color .3s;
        }
        
        .stat:hover .stat-num {
            color: var(--accent);
        }
        
        .stat-label {
            font-size: 9.5px;
            color: var(--ink3);
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'JetBrains Mono';
        }
        
        .stat-hint {
            font-size: 8.5px;
            color: var(--ink4);
            margin-top: 3px;
            font-family: 'JetBrains Mono';
        }
        
        .stat-prog {
            height: 3px;
            border-radius: 3px;
            background: var(--bg-sunk);
            margin-top: 9px;
            overflow: hidden;
        }
        
        .stat-prog i {
            display: block;
            height: 100%;
            background: var(--accent);
            width: 0;
            transition: width .7s var(--ease), background .5s;
            border-radius: 3px;
        }
        /* ── Focus timer ── */
        
        .focus {
            background: var(--bg-elev);
            border: 1px solid var(--line);
            border-radius: var(--r-lg);
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 18px;
            position: relative;
            overflow: hidden;
            transition: border-color .3s, box-shadow .35s;
        }
        
        .focus:hover {
            border-color: var(--accent);
            box-shadow: var(--shadow);
        }
        
        .focus::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 85% -10%, var(--accent-soft), transparent 55%);
            opacity: 0;
            transition: opacity .4s;
            pointer-events: none;
        }
        
        .focus:hover::before {
            opacity: 1;
        }
        
        .focus::after {
            content: '';
            position: absolute;
            top: 0;
            left: 18px;
            right: 18px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            opacity: .5;
        }
        
        .timer-ring {
            width: 92px;
            height: 92px;
            flex-shrink: 0;
            position: relative;
        }
        
        .timer-ring::before {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--accent-soft), transparent 70%);
            opacity: 0;
            transition: opacity .4s var(--ease);
            pointer-events: none;
        }
        
        .focus:hover .timer-ring::before {
            opacity: 1;
        }
        
        .timer-ring svg {
            transform: rotate(-90deg);
            position: relative;
        }
        
        .ring-bg {
            fill: none;
            stroke: var(--bg-sunk);
            stroke-width: 6;
        }
        
        .ring-fg {
            fill: none;
            stroke: var(--accent);
            stroke-width: 6;
            stroke-linecap: round;
            transition: stroke-dashoffset .4s linear, stroke .5s;
        }
        
        .timer-display {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .timer-time {
            font-family: 'JetBrains Mono';
            font-size: 21px;
            font-weight: 700;
            line-height: 1;
            color: var(--ink);
        }
        
        .timer-mode {
            font-size: 8px;
            color: var(--ink3);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 3px;
            font-family: 'JetBrains Mono';
        }
        
        .focus-body {
            flex: 1;
            min-width: 0;
            position: relative;
            z-index: 1;
        }
        
        .focus-title {
            font-size: 10px;
            color: var(--ink3);
            font-family: 'JetBrains Mono';
            letter-spacing: 1.5px;
            margin-bottom: 6px;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .focus-title .today-mins {
            color: var(--accent);
            font-weight: 700;
        }
        
        .focus-sub {
            font-family: 'Space Grotesk';
            font-size: 15px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .timer-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .tbtn {
            font-family: 'JetBrains Mono';
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .3px;
            padding: 8px 14px;
            border-radius: 9px;
            border: 1px solid var(--line2);
            background: var(--bg-elev);
            color: var(--ink);
            cursor: pointer;
            transition: all .25s var(--spring);
        }
        
        .tbtn:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px -6px var(--accent-glow);
        }
        
        .tbtn.primary {
            background: var(--accent);
            color: var(--on-accent);
            border-color: var(--accent);
        }
        
        .tbtn.primary:hover {
            color: var(--on-accent);
            box-shadow: 0 8px 22px -6px var(--accent-glow);
            transform: translateY(-2px) scale(1.03);
        }
        
        .pulse {
            animation: pulse 1.8s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%,
            100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
        /* ── Toolbar ── */
        
        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
        }
        
        .search {
            flex: 1;
            position: relative;
        }
        
        .search input {
            width: 100%;
            padding: 13px 14px 13px 40px;
            font-size: 14.5px;
            font-family: 'Sora';
            background: var(--bg-elev);
            border: 1px solid var(--line);
            border-radius: 12px;
            color: var(--ink);
            outline: none;
            box-shadow: var(--shadow-sm);
            transition: all .3s var(--ease);
        }
        
        .search input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--ring);
        }
        
        .search input::placeholder {
            color: var(--ink3);
        }
        
        .search .ic {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ink3);
            font-size: 15px;
            pointer-events: none;
            transition: color .3s;
        }
        
        .search input:focus~.ic {
            color: var(--accent);
        }
        
        .add-btn {
            width: 50px;
            height: 50px;
            border-radius: 13px;
            background: var(--accent);
            color: var(--on-accent);
            border: none;
            font-size: 26px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-weight: 300;
            box-shadow: var(--shadow-sm);
            transition: transform .3s var(--spring), box-shadow .3s, background .5s;
        }
        
        .add-btn:hover {
            transform: translateY(-3px) rotate(90deg) scale(1.05);
            box-shadow: 0 10px 26px -6px var(--accent-glow);
        }
        
        .add-btn:active {
            transform: scale(.92);
        }
        /* ── Tag filter row ── */
        
        .tagrow {
            display: flex;
            gap: 7px;
            margin-bottom: 12px;
            overflow-x: auto;
            scrollbar-width: none;
            padding-bottom: 2px;
        }
        
        .tagrow::-webkit-scrollbar {
            display: none;
        }
        
        .tagrow:empty {
            display: none;
        }
        
        .tagchip {
            padding: 6px 13px;
            font-size: 11.5px;
            font-weight: 500;
            font-family: 'Space Grotesk';
            border: 1px solid var(--line2);
            border-radius: 20px;
            background: var(--bg-elev);
            color: var(--ink2);
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all .25s var(--spring);
        }
        
        .tagchip:hover {
            border-color: var(--accent);
            color: var(--accent);
        }
        
        .tagchip.active {
            background: var(--accent);
            color: var(--on-accent);
            border-color: var(--accent);
        }
        /* ── Tabs ── */
        
        .tabs {
            display: flex;
            gap: 7px;
            margin-bottom: 18px;
            overflow-x: auto;
            scrollbar-width: none;
            padding-bottom: 2px;
        }
        
        .tabs::-webkit-scrollbar {
            display: none;
        }
        
        .tab {
            padding: 8px 16px;
            font-size: 12.5px;
            font-weight: 500;
            font-family: 'Space Grotesk';
            border: 1px solid var(--line);
            border-radius: 20px;
            background: var(--bg-elev);
            color: var(--ink2);
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all .28s var(--spring);
        }
        
        .tab:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-2px);
        }
        
        .tab.active {
            background: var(--accent);
            color: var(--on-accent);
            border-color: var(--accent);
            font-weight: 600;
            box-shadow: 0 6px 16px -6px var(--accent-glow);
        }
        
        .tab .cnt {
            font-family: 'JetBrains Mono';
            font-size: 10px;
            opacity: .65;
            margin-left: 5px;
        }
        /* ── Cards ── */
        
        .list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .card {
            background: var(--bg-elev);
            border: 1px solid var(--line);
            border-radius: var(--r-lg);
            padding: 17px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: transform .25s var(--spring), border-color .3s, box-shadow .4s, opacity .4s;
            animation: cardIn .5s var(--ease) backwards;
        }
        
        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: none;
            }
        }
        
        .card:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            box-shadow: var(--shadow);
        }
        
        .card.dragging {
            opacity: .4;
            transform: scale(.97);
        }
        
        .card.drag-over {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px var(--accent) inset;
        }
        
        .card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--accent);
            opacity: 0;
            transition: opacity .3s, width .3s var(--spring);
        }
        
        .card:hover::before {
            opacity: 1;
            width: 5px;
        }
        
        .card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at var(--mx, 50%) var(--my, 0%), var(--accent-soft), transparent 45%);
            opacity: 0;
            transition: opacity .4s;
            pointer-events: none;
        }
        
        .card:hover::after {
            opacity: 1;
        }
        
        .card.done {
            opacity: .55;
        }
        
        .card.done::before {
            opacity: 1;
            background: var(--ink3);
        }
        
        .card.flash {
            animation: flash .6s var(--ease);
        }
        
        @keyframes flash {
            0% {
                box-shadow: 0 0 0 0 var(--accent);
            }
            100% {
                box-shadow: 0 0 0 16px transparent;
            }
        }
        
        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .card-top-left {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        
        .drag-handle {
            cursor: grab;
            color: var(--ink4);
            font-size: 15px;
            padding: 2px 2px 2px 0;
            line-height: 1;
            margin-top: 1px;
            touch-action: none;
            user-select: none;
            transition: color .2s;
        }
        
        .drag-handle:hover {
            color: var(--accent);
        }
        
        .drag-handle:active {
            cursor: grabbing;
        }
        
        .badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .badge {
            font-family: 'JetBrains Mono';
            font-size: 9.5px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: .5px;
            text-transform: uppercase;
            border: 1px solid var(--line2);
            background: var(--bg-sunk);
            color: var(--ink2);
        }
        
        .b-critical {
            background: var(--accent);
            color: var(--on-accent);
            border-color: var(--accent);
        }
        
        .b-high {
            background: var(--accent-soft);
            color: var(--accent);
            border-color: var(--accent);
        }
        
        .b-medium {
            background: var(--bg-sunk);
            color: var(--ink2);
            border-color: var(--line2);
        }
        
        .b-low {
            background: var(--bg-sunk);
            color: var(--ink3);
            border-color: var(--line);
        }
        
        .dl {
            font-family: 'JetBrains Mono';
            font-size: 9.5px;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 6px;
            display: inline-flex;
            gap: 4px;
            align-items: center;
            border: 1px solid var(--line2);
            background: var(--bg-elev);
            color: var(--ink2);
        }
        
        .dl-overdue {
            border-color: var(--bad);
            color: var(--bad);
            font-weight: 700;
            animation: pulse 2s infinite;
        }
        
        .dl-urgent {
            border-color: var(--warn);
            color: var(--warn);
        }
        
        .dl-soon {
            color: var(--ink2);
        }
        
        .dl-safe {
            color: var(--ink3);
        }
        
        .recur-badge {
            font-family: 'JetBrains Mono';
            font-size: 9.5px;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 6px;
            display: inline-flex;
            gap: 4px;
            align-items: center;
            border: 1px solid var(--line2);
            background: var(--bg-sunk);
            color: var(--ink2);
        }
        
        .collab-badge {
            font-family: 'Space Grotesk';
            font-size: 9.5px;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 6px;
            display: inline-flex;
            gap: 4px;
            align-items: center;
            border: 1px solid var(--line2);
            background: var(--bg-sunk);
            color: var(--ink2);
        }
        
        .pin-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: var(--ink4);
            transition: transform .3s var(--spring), color .3s;
            flex-shrink: 0;
            padding: 2px;
            line-height: 1;
        }
        
        .pin-btn:hover {
            transform: scale(1.3) rotate(12deg);
            color: var(--accent);
        }
        
        .pin-btn.pinned {
            color: var(--accent);
        }
        
        .escalated {
            font-family: 'JetBrains Mono';
            font-size: 10px;
            color: var(--accent);
            margin: 2px 0 9px;
            display: flex;
            align-items: center;
            gap: 5px;
            position: relative;
            z-index: 1;
        }
        
        .task-name {
            font-family: 'Space Grotesk';
            font-size: 17px;
            font-weight: 600;
            letter-spacing: -.2px;
            line-height: 1.3;
            margin-bottom: 5px;
            color: var(--ink);
            position: relative;
            z-index: 1;
        }
        
        .task-name.struck {
            text-decoration: line-through;
            color: var(--ink3);
        }
        
        .task-meta {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            font-size: 12px;
            color: var(--ink3);
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .task-meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .tag-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        
        .tagpill {
            font-size: 10.5px;
            font-family: 'Space Grotesk';
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 20px;
            background: var(--accent-soft);
            color: var(--accent);
            border: 1px solid var(--accent-glow);
        }
        
        .prog {
            height: 6px;
            border-radius: 5px;
            background: var(--bg-sunk);
            margin-bottom: 13px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }
        
        .prog i {
            display: block;
            height: 100%;
            border-radius: 5px;
            width: 0;
            transition: width .55s var(--ease), background .4s;
        }
        
        .prog-bad i {
            background: var(--bad);
        }
        
        .prog-warn i {
            background: var(--warn);
        }
        
        .prog-good i {
            background: var(--good);
        }
        
        .prog-neutral i {
            background: var(--ink3);
        }
        
        .steps {
            display: flex;
            flex-direction: column;
            gap: 1px;
            margin-bottom: 13px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
            position: relative;
            z-index: 1;
        }
        
        .step {
            display: flex;
            gap: 11px;
            align-items: flex-start;
            padding: 6px 0;
            cursor: pointer;
            transition: padding-left .2s;
        }
        
        .step:hover {
            padding-left: 4px;
        }
        
        .box {
            width: 19px;
            height: 19px;
            border: 1.5px solid var(--line2);
            border-radius: 6px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1px;
            transition: all .3s var(--spring);
        }
        
        .step:hover .box {
            border-color: var(--accent);
        }
        
        .box.on {
            background: var(--accent);
            border-color: var(--accent);
        }
        
        .box.on::after {
            content: '✓';
            color: var(--on-accent);
            font-size: 12px;
            font-weight: 800;
        }
        
        .step-t {
            font-size: 13px;
            color: var(--ink2);
            line-height: 1.45;
            transition: all .3s;
        }
        
        .step-t.struck {
            text-decoration: line-through;
            color: var(--ink3);
        }
        
        .card-rmk {
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            position: relative;
            z-index: 1;
        }
        
        .rmk-item {
            background: var(--bg-sunk);
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 9px 11px;
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: flex-start;
            transition: border-color .25s;
        }
        
        .rmk-item:hover {
            border-color: var(--accent);
        }
        
        .rmk-item .t {
            font-size: 12px;
            color: var(--ink2);
            line-height: 1.45;
        }
        
        .rmk-item .d {
            font-size: 9.5px;
            color: var(--ink3);
            font-family: 'JetBrains Mono';
            margin-top: 4px;
        }
        
        .rmk-x {
            background: none;
            border: none;
            color: var(--ink3);
            cursor: pointer;
            font-size: 13px;
            flex-shrink: 0;
            transition: color .2s, transform .2s;
        }
        
        .rmk-x:hover {
            color: var(--accent);
            transform: scale(1.2);
        }
        
        .actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        
        .act {
            font-family: 'Space Grotesk';
            font-size: 12px;
            font-weight: 500;
            padding: 7px 13px;
            border-radius: 9px;
            border: 1px solid var(--line);
            background: var(--bg-elev);
            color: var(--ink2);
            cursor: pointer;
            transition: all .25s var(--spring);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .act:hover {
            transform: translateY(-2px);
            color: var(--accent);
            border-color: var(--accent);
            box-shadow: 0 6px 16px -8px var(--accent-glow);
        }
        
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--ink2);
        }
        
        .empty .ic {
            font-size: 46px;
            margin-bottom: 14px;
            opacity: .5;
            color: var(--accent);
        }
        
        .empty .h {
            font-family: 'Space Grotesk';
            font-size: 16px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 6px;
        }
        
        .empty .s {
            font-size: 13px;
            color: var(--ink3);
            line-height: 1.5;
        }
        
        .remarks-view {
            display: none;
            flex-direction: column;
            gap: 12px;
        }
        
        .remarks-view.active {
            display: flex;
        }
        
        .list.hidden {
            display: none;
        }
        
        .rmk-card {
            background: var(--bg-elev);
            border: 1px solid var(--line);
            border-radius: var(--r-lg);
            padding: 16px;
            box-shadow: var(--shadow-sm);
            animation: cardIn .5s var(--ease) backwards;
            transition: transform .35s var(--spring), border-color .3s, box-shadow .35s;
        }
        
        .rmk-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent);
            box-shadow: var(--shadow);
        }
        
        .rmk-link {
            font-size: 11px;
            color: var(--accent);
            margin-bottom: 8px;
            font-family: 'JetBrains Mono';
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }
        
        .rmk-gen {
            display: inline-block;
            font-size: 9.5px;
            font-family: 'JetBrains Mono';
            padding: 3px 10px;
            border-radius: 6px;
            background: var(--bg-sunk);
            color: var(--ink3);
            margin-bottom: 8px;
            border: 1px solid var(--line);
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        
        .rmk-body {
            font-size: 14px;
            color: var(--ink);
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .rmk-time {
            font-size: 10px;
            color: var(--ink3);
            margin-top: 10px;
            font-family: 'JetBrains Mono';
        }
        /* ── Sheets ── */
        
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 10, 10, .4);
            backdrop-filter: blur(3px);
            z-index: 100;
            opacity: 0;
            pointer-events: none;
            transition: opacity .35s;
        }
        
        .overlay.open {
            opacity: 1;
            pointer-events: auto;
        }
        
        .sheet {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            max-width: 560px;
            margin: 0 auto;
            z-index: 101;
            background: var(--paper);
            border: 1px solid var(--line);
            border-bottom: none;
            border-radius: 24px 24px 0 0;
            padding: 14px 22px 38px;
            max-height: 92vh;
            overflow-y: auto;
            transform: translateY(100%);
            transition: transform .42s var(--ease);
            box-shadow: var(--shadow-lg);
        }
        
        .sheet.open {
            transform: translateY(0);
        }
        
        @media (min-width: 720px) {
            .sheet {
                max-width: 560px;
                top: 50%;
                bottom: auto;
                border-radius: 24px;
                border-bottom: 1px solid var(--line);
                transform: translate(-50%, -46%) scale(.97);
                left: 50%;
                right: auto;
                opacity: 0;
                pointer-events: none;
                max-height: 86vh;
            }
            .sheet.open {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
                pointer-events: auto;
            }
            .handle {
                display: none;
            }
        }
        
        .handle {
            width: 42px;
            height: 4px;
            background: var(--line2);
            border-radius: 3px;
            margin: 0 auto 20px;
        }
        
        .sheet-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: var(--bg-elev);
            color: var(--ink3);
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .25s var(--spring);
            z-index: 2;
        }
        
        .sheet-close:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: rotate(90deg);
            background: var(--accent-soft);
        }
        
        .sheet h2 {
            font-family: 'Sora';
            font-size: 21px;
            font-weight: 700;
            letter-spacing: -.4px;
            margin-bottom: 5px;
            color: var(--ink);
        }
        
        .sheet .sub {
            font-size: 13px;
            color: var(--ink3);
            margin-bottom: 22px;
        }
        
        .fg {
            margin-bottom: 16px;
        }
        
        .fg label {
            font-size: 10.5px;
            color: var(--ink3);
            margin-bottom: 8px;
            display: block;
            font-family: 'JetBrains Mono';
            letter-spacing: .8px;
            text-transform: uppercase;
            font-weight: 500;
        }
        
        .sheet input[type=text],
        .sheet input[type=url],
        .sheet textarea,
        .sheet input[type=date] {
            width: 100%;
            padding: 13px 14px;
            font-size: 15px;
            font-family: 'Sora';
            color: var(--ink);
            background: var(--bg);
            border: 1px solid var(--line2);
            border-radius: 11px;
            outline: none;
            transition: all .3s var(--ease);
        }
        
        .sheet input:focus,
        .sheet textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--ring);
            background: var(--bg-elev);
        }
        
        .sheet input::placeholder,
        .sheet textarea::placeholder {
            color: var(--ink3);
        }
        
        .sheet textarea {
            resize: none;
            min-height: 80px;
            line-height: 1.5;
        }
        
        .sheet input[type=date] {
            appearance: none;
        }
        
        .opts {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .opt {
            font-family: 'Space Grotesk';
            font-size: 13px;
            padding: 9px 15px;
            border-radius: 10px;
            border: 1px solid var(--line2);
            background: var(--bg-elev);
            color: var(--ink2);
            cursor: pointer;
            transition: all .25s var(--spring);
        }
        
        .opt:hover {
            transform: translateY(-2px);
            border-color: var(--accent);
            color: var(--accent);
        }
        
        .opt.sel {
            background: var(--accent);
            color: var(--on-accent);
            border-color: var(--accent);
            font-weight: 600;
            box-shadow: 0 6px 16px -6px var(--accent-glow);
        }
        
        .submit {
            width: 100%;
            padding: 15px;
            font-family: 'Sora';
            font-size: 16px;
            font-weight: 700;
            color: var(--on-accent);
            background: var(--accent);
            border: none;
            border-radius: 13px;
            cursor: pointer;
            margin-top: 6px;
            box-shadow: 0 10px 26px -8px var(--accent-glow);
            transition: transform .3s var(--spring), box-shadow .3s, background .5s;
        }
        
        .submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 36px -8px var(--accent-glow);
        }
        
        .submit:active {
            transform: scale(.97);
        }
        
        .thinking {
            display: none;
            font-family: 'JetBrains Mono';
            font-size: 12px;
            color: var(--accent);
            background: var(--accent-soft);
            border: 1px solid var(--line);
            border-radius: 11px;
            padding: 13px 16px;
            margin-bottom: 12px;
        }
        
        .thinking.on {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid var(--line2);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        
        .estep {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .estep input {
            flex: 1;
        }

        .estep input[type=url] {
            flex: 0 0 38%;
            font-size: 13px;
        }
        
        .estep button {
            padding: 9px 12px;
            border: 1px solid var(--line2);
            border-radius: 10px;
            background: var(--bg-elev);
            color: var(--ink3);
            cursor: pointer;
            transition: all .25s;
        }
        
        .estep button:hover {
            border-color: var(--accent);
            color: var(--accent);
        }
        
        .tag-input-row {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .tag-input-row input {
            flex: 1;
        }
        
        .tag-add-btn {
            padding: 0 16px;
            border: 1px solid var(--line2);
            border-radius: 10px;
            background: var(--bg-elev);
            color: var(--ink);
            cursor: pointer;
            font-family: 'Space Grotesk';
            font-weight: 600;
            font-size: 13px;
            transition: all .25s;
        }
        
        .tag-add-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }
        
        .tag-pills-edit {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        .tagpill-x {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-family: 'Space Grotesk';
            font-weight: 500;
            padding: 5px 8px 5px 12px;
            border-radius: 20px;
            background: var(--accent-soft);
            color: var(--accent);
            border: 1px solid var(--accent-glow);
        }
        
        .tagpill-x button {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 12px;
            padding: 0;
            display: flex;
        }
        /* recurrence row */
        
        .recur-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        /* toast */
        
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translate(-50%, 20px);
            z-index: 300;
            opacity: 0;
            pointer-events: none;
            background: var(--accent);
            border: 1px solid var(--accent);
            color: var(--on-accent);
            padding: 12px 22px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 500;
            font-family: 'Space Grotesk';
            box-shadow: var(--shadow-lg);
            transition: all .35s var(--spring);
            display: flex;
            align-items: center;
            gap: 9px;
            white-space: nowrap;
            max-width: 90vw;
        }
        
        .toast.show {
            opacity: 1;
            transform: translate(-50%, 0);
        }
        
        .toast .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--on-accent);
            flex-shrink: 0;
        }
        
        .toast-action {
            margin-left: 6px;
            background: transparent;
            border: 1px solid var(--on-accent);
            color: var(--on-accent);
            font-family: 'Space Grotesk';
            font-size: 12px;
            font-weight: 600;
            padding: 4px 11px;
            border-radius: 8px;
            cursor: pointer;
            transition: opacity .2s;
            flex-shrink: 0;
        }
        
        .toast-action:hover {
            opacity: .75;
        }
        /* ── Celebration overlay: unmissable confirmation for timer-complete / task-complete ── */
        
        .celebrate-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 10, 10, .5);
            backdrop-filter: blur(4px);
            z-index: 400;
            opacity: 0;
            pointer-events: none;
            transition: opacity .35s var(--ease);
        }
        
        .celebrate-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }
        
        .celebrate-card {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(.9);
            z-index: 401;
            width: min(360px, 86vw);
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 34px 28px 28px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            opacity: 0;
            pointer-events: none;
            transition: opacity .4s var(--spring), transform .4s var(--spring);
        }
        
        .celebrate-card.show {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
            pointer-events: auto;
            animation: celebrateBounce .6s var(--spring);
        }
        
        @keyframes celebrateBounce {
            0% {
                transform: translate(-50%, -50%) scale(.7);
            }
            55% {
                transform: translate(-50%, -50%) scale(1.04);
            }
            100% {
                transform: translate(-50%, -50%) scale(1);
            }
        }
        
        .celebrate-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: var(--accent-soft);
            border: 1px solid var(--accent-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: var(--accent);
            animation: celebratePulse 1.6s ease-in-out infinite;
        }
        
        @keyframes celebratePulse {
            0%,
            100% {
                box-shadow: 0 0 0 0 var(--accent-soft);
            }
            50% {
                box-shadow: 0 0 0 12px transparent;
            }
        }
        
        .celebrate-title {
            font-family: 'Sora';
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -.3px;
            color: var(--ink);
            margin-bottom: 8px;
        }
        
        .celebrate-sub {
            font-size: 13.5px;
            color: var(--ink3);
            line-height: 1.55;
            margin-bottom: 22px;
        }
        
        .celebrate-dismiss {
            width: 100%;
            padding: 13px;
            font-family: 'Sora';
            font-size: 14px;
            font-weight: 700;
            color: var(--on-accent);
            background: var(--accent);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 10px 26px -8px var(--accent-glow);
            transition: transform .25s var(--spring), box-shadow .25s;
        }
        
        .celebrate-dismiss:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -8px var(--accent-glow);
        }
        /* footer */
        
        .foot {
            text-align: center;
            padding: 42px 20px 10px;
            color: var(--ink3);
        }
        
        .foot .n {
            font-family: 'Sora';
            font-size: 16px;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: -.3px;
            transition: color .5s;
        }
        
        .foot .d {
            font-size: 10px;
            font-family: 'JetBrains Mono';
            letter-spacing: 2px;
            margin-top: 6px;
            text-transform: uppercase;
        }
        
        .foot .k {
            font-size: 10px;
            font-family: 'JetBrains Mono';
            margin-top: 16px;
            color: var(--ink3);
        }
        
        .foot kbd {
            background: var(--bg-elev);
            border: 1px solid var(--line2);
            border-radius: 5px;
            padding: 1px 6px;
            font-size: 10px;
            color: var(--ink2);
        }

        .report-header {
            font-family: 'Space Grotesk';
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 14px;
        }
        .report-table {
            display: flex;
            flex-direction: column;
            gap: 1px;
            background: var(--line);
            border-radius: var(--r);
            overflow: hidden;
        }
        .report-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 14px;
            background: var(--bg-elev);
        }
        .report-header-row {
            background: var(--bg-sunk);
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--ink3);
        }
        .report-cell {
            font-family: 'JetBrains Mono';
            font-size: 13px;
            color: var(--ink);
        }
        .matrix-view.active { display: grid !important; }
        .matrix-view {
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .mx-quad {
            background: var(--bg-elev);
            border: 1px solid var(--line);
            border-radius: var(--r-lg);
            padding: 14px;
            min-height: 160px;
        }
        .mx-quad h4 {
            font-family: 'Space Grotesk';
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .mx-quad.q1 { border-top: 3px solid var(--bad); }
        .mx-quad.q2 { border-top: 3px solid var(--good); }
        .mx-quad.q3 { border-top: 3px solid var(--warn); }
        .mx-quad.q4 { border-top: 3px solid var(--ink4); }
        .mx-item {
            font-size: 12px;
            padding: 7px 9px;
            background: var(--bg-sunk);
            border-radius: 8px;
            margin-bottom: 6px;
            cursor: pointer;
            transition: background .2s;
        }
        .mx-item:hover { background: var(--accent-soft); }
        .mx-empty { font-size: 11px; color: var(--ink4); font-style: italic; }
        @media (max-width: 600px) {
            .matrix-view { grid-template-columns: 1fr; }
        }
        
        @media (prefers-reduced-motion:reduce) {
            * {
                animation-duration: .001ms!important;
                transition-duration: .05ms!important;
            }
        }
        
         :focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }
        /* print stylesheet for PDF export */
        
        @media print {
            .header,
            .stats,
            .focus,
            .toolbar,
            .tagrow,
            .tabs,
            .actions,
            .panel,
            .toast,
            .overlay,
            .sheet,
            .foot,
            .pin-btn,
            .drag-handle {
                display: none !important;
            }
            body {
                background: #fff;
                color: #000;
            }
            .list {
                display: block !important;
            }
            .card {
                break-inside: avoid;
                border: 1px solid #ccc;
                box-shadow: none;
                margin-bottom: 10px;
            }
            #print-title {
                display: block !important;
                text-align: center;
                font-family: 'Space Grotesk', sans-serif;
                font-size: 22px;
                font-weight: 800;
                margin: 0 0 20px;
            }
        }
        
        #print-title {
            display: none;
        }
        /* ════════════════════════════════════════════
           RESPONSIVE BREAKPOINTS
           Mobile-first base styles above; this section adapts
           layout for very small phones up through desktop.
        ════════════════════════════════════════════ */
        /* ── Small phones (≤ 380px): tighten header, 2-col stats ── */
        
        @media (max-width: 380px) {
            .app {
                padding: 0 14px 120px;
            }
            .header {
                padding: 18px 0 6px;
            }
            .brand-row {
                flex-wrap: wrap;
            }
            .logo {
                width: 38px;
                height: 38px;
                font-size: 18px;
                border-radius: 10px;
            }
            .brand-txt h1 {
                font-size: 20px;
            }
            .head-right {
                gap: 7px;
            }
            .icon-btn {
                width: 38px;
                border-radius: 10px;
            }
            .tt-icon {
                font-size: 16px;
            }
            .clock-chip {
                min-width: 0;
                padding: 7px 11px;
                flex-grow: 1;
            }
            .clock-time {
                font-size: 16px;
            }
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
            .stat-num {
                font-size: 23px;
            }
            .onboard-slide {
                padding: 26px 18px 12px;
            }
            .onboard-slide .oic {
                width: 54px;
                height: 54px;
                font-size: 24px;
                margin-bottom: 12px;
            }
            .onboard-slide h3 {
                font-size: 16px;
            }
            .onboard-slide p {
                font-size: 12.5px;
                min-height: 70px;
            }
            .onboard-skip {
                top: 12px;
                right: 12px;
                font-size: 11.5px;
            }
            .onboard-footer {
                padding: 4px 18px 18px;
            }
            .onboard-btn {
                padding: 10px 14px;
                font-size: 12.5px;
            }
            .focus {
                padding: 14px;
                gap: 13px;
            }
            .timer-ring {
                width: 76px;
                height: 76px;
            }
            .timer-ring svg {
                width: 76px;
                height: 76px;
            }
            .timer-time {
                font-size: 17px;
            }
            .focus-sub {
                font-size: 13.5px;
            }
            .tbtn {
                padding: 7px 11px;
                font-size: 10px;
            }
            .task-name {
                font-size: 15.5px;
            }
            .act {
                padding: 6px 10px;
                font-size: 11px;
            }
            .sheet {
                padding: 14px 16px 32px;
            }
        }
        /* ── Very small phones (≤ 340px): final safety net ── */
        
        @media (max-width: 340px) {
            .brand-txt .tag {
                display: none;
            }
            .clock-date {
                display: none;
            }
            .timer-btns {
                width: 100%;
            }
            .tbtn {
                flex: 1;
                text-align: center;
            }
        }
        /* ── Landscape phones: reclaim vertical space ── */
        
        @media (max-height: 480px) and (orientation: landscape) {
            .header {
                padding: 14px 0 4px;
                position: relative;
            }
            .onboard-slide {
                padding: 18px 20px 8px;
            }
            .onboard-footer {
                padding: 4px 20px 16px;
            }
            .stats {
                margin: 12px 0 14px;
            }
        }
        /* ── Tablets (≥ 600px): roomier stat cards, side-by-side export grid stays, bigger touch targets are unnecessary ── */
        
        @media (min-width: 600px) {
            .stats {
                gap: 14px;
            }
            .stat {
                padding: 18px 14px 16px;
            }
            .stat-num {
                font-size: 30px;
            }
            .card {
                padding: 20px 22px;
            }
            .task-name {
                font-size: 18px;
            }
        }
        /* ── Tablet/desktop (≥ 720px): wider canvas, two-column-friendly internals ── */
        
        @media (min-width: 720px) {
            .header {
                padding: 32px 0 10px;
            }
            .brand-txt h1 {
                font-size: 25px;
            }
            .toolbar {
                gap: 12px;
            }
            .add-btn {
                width: 54px;
                height: 54px;
                font-size: 28px;
            }
            .export-grid {
                grid-template-columns: 1fr 1fr;
                gap: 14px;
            }
            .palette-row .swatch {
                min-width: 130px;
            }
            .actions {
                gap: 9px;
            }
            .act {
                padding: 8px 15px;
            }
        }
        /* ── Desktop (≥ 980px): comfortable reading width, hover affordances assumed ── */
        
        @media (min-width: 980px) {
            .focus {
                padding: 22px 26px;
            }
            .timer-ring {
                width: 100px;
                height: 100px;
            }
            .timer-ring svg {
                width: 100px;
                height: 100px;
            }
        }
        /* ── Respect notch / home-indicator safe areas on supported devices ── */
        
        @supports (padding: max(0px)) {
            .app {
                padding-left: max(18px, env(safe-area-inset-left));
                padding-right: max(18px, env(safe-area-inset-right));
                padding-bottom: max(120px, calc(env(safe-area-inset-bottom) + 100px));
            }
            .toast {
                bottom: max(30px, calc(env(safe-area-inset-bottom) + 14px));
            }
        }
    </style>
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('taskvel_theme_v1');
                var theme = savedTheme || (window.matchMedia && window.matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
                var accent = localStorage.getItem('taskvel_accent_v1') || 'samal';
                if (!localStorage.getItem('taskvel_samal_migrated')) { accent = 'samal'; localStorage.setItem('taskvel_accent_v1','samal'); localStorage.setItem('taskvel_samal_migrated','1'); }
                document.documentElement.setAttribute('data-accent', accent);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
                document.documentElement.setAttribute('data-accent', 'samal');
            }
        })();
    </script>
</head>

<body>
    <div class="aurora"><span class="a1"></span><span class="a2"></span><span class="a3"></span></div>
    <div class="grid-overlay"></div>
    <div id="print-title">Taskvel — Task Export</div>

    <div class="app">
        <div class="header">
            <div class="brand-row">
                <div class="brand">
                    <div class="logo">T</div>
                    <div class="brand-txt">
                        <h1>Task<span>vel</span><span class="free-pill">Free</span></h1>
                        <div class="tag">by <a href="https://www.samalconsultancy.com" target="_blank" rel="noopener" class="by-samal">Samal Consultancy</a></div>
                    </div>
                </div>
                <div class="head-right">
                    <button class="icon-btn" id="notif-btn" onclick="togglePanel('notif-panel')" aria-label="Notifications" title="Notifications">
                        <span class="tt-icon">◔</span>
                        <span class="badge-dot" id="notif-dot"></span>
                    </button>
                    <button class="icon-btn" id="hist-btn" onclick="togglePanel('hist-panel')" aria-label="Focus history" title="Focus history">
                        <span class="tt-icon">▤</span>
                    </button>
                    <button class="icon-btn" id="export-btn" onclick="togglePanel('export-panel')" aria-label="Export" title="Export">
                        <span class="tt-icon">↓</span>
                    </button>
                    <button class="icon-btn" id="tmpl-btn" onclick="togglePanel('tmpl-panel')" aria-label="Templates" title="Templates">
                        <span class="tt-icon">▧</span>
                    </button>
                    <button class="icon-btn" id="palette-btn" onclick="togglePanel('palette-panel')" aria-label="Choose colour theme" title="Colour theme">
                        <span class="tt-icon">◑</span>
                    </button>
                    <button class="icon-btn" id="theme-toggle" onclick="toggleTheme()" aria-label="Toggle light or dark mode" title="Light / dark">
                        <span class="tt-icon" id="tt-icon">☾</span>
                    </button>
                    <div class="clock-chip">
                        <div class="clock-time" id="clock"><span>--:--</span><span class="sec">:--</span></div>
                        <div class="clock-date" id="clock-date">—</div>
                    </div>
                </div>
            </div>

            <!-- Theme picker panel -->
            <div class="panel" id="palette-panel">
                <div class="panel-head"><span class="panel-title">Colour theme</span><button class="panel-close" onclick="closePanel('palette-panel')">✕</button></div>
                <div class="palette-row">
                    <button class="swatch sw-samal" data-accent="samal" onclick="setAccent('samal')"><span class="sw-dot"></span><span class="sw-name">Samal</span></button>
                    <button class="swatch sw-mono" data-accent="mono" onclick="setAccent('mono')"><span class="sw-dot"></span><span class="sw-name">Mono</span></button>
                    <button class="swatch sw-indigo" data-accent="indigo" onclick="setAccent('indigo')"><span class="sw-dot"></span><span class="sw-name">Indigo</span></button>
                    <button class="swatch sw-emerald" data-accent="emerald" onclick="setAccent('emerald')"><span class="sw-dot"></span><span class="sw-name">Emerald</span></button>
                    <button class="swatch sw-amber" data-accent="amber" onclick="setAccent('amber')"><span class="sw-dot"></span><span class="sw-name">Amber</span></button>
                </div>
            </div>

            <!-- Notifications panel -->
            <div class="panel" id="notif-panel">
                <div class="panel-head"><span class="panel-title">Notifications</span><button class="panel-close" onclick="closePanel('notif-panel')">✕</button></div>
                <div class="notif-list" id="notif-list"></div>
            </div>

            <!-- Focus history panel -->
            <div class="panel" id="hist-panel">
                <div class="panel-head"><span class="panel-title">Focus history</span><button class="panel-close" onclick="closePanel('hist-panel')">✕</button></div>
                <div class="hist-tot">
                    <div class="box">
                        <div class="num" id="hist-today">0</div>
                        <div class="lbl">Today · min</div>
                    </div>
                    <div class="box">
                        <div class="num" id="hist-week">0</div>
                        <div class="lbl">This week · min</div>
                    </div>
                    <div class="box">
                        <div class="num" id="hist-avg">0</div>
                        <div class="lbl">Daily avg · min</div>
                    </div>
                </div>
                <div class="chart" id="hist-chart"></div>
            </div>

            <div class="panel" id="tmpl-panel">
                <div class="panel-head"><span class="panel-title">Task templates</span><button class="panel-close" onclick="closePanel('tmpl-panel')">✕</button></div>
                <div class="notif-list" id="tmpl-list"></div>
            </div>

            <!-- Export panel -->
            <div class="panel" id="export-panel">
                <div class="panel-head"><span class="panel-title">Export tasks</span><button class="panel-close" onclick="closePanel('export-panel')">✕</button></div>
                <!-- <div class="export-filters">
                    <div class="ef-row">
                        <label>Who's waiting on this?</label>
                        <select id="ef-person" onchange="updateExportSummary()">
                            <option value="">All</option>
                        </select>
                    </div>
                    <div class="ef-row">
                        <label>Shared with</label>
                        <select id="ef-collab" onchange="updateExportSummary()">
                            <option value="">All</option>
                        </select>
                    </div>
                    <div class="ef-row">
                        <label>Tag</label>
                        <select id="ef-tag" onchange="updateExportSummary()">
                            <option value="">All</option>
                        </select>
                    </div>
                </div> -->
                <div class="export-filters">
                    <div class="ef-row">
                        <label>Status</label>
                        <select id="ef-status" onchange="updateExportSummary()">
                            <option value="all">Done & undone</option>
                            <option value="done">Done only</option>
                            <option value="pending">Undone only</option>
                        </select>
                    </div>
                    <div class="ef-row">
                        <label>Date range</label>
                        <select id="ef-daterange" onchange="onDateRangeChange()">
                            <option value="all">Any time</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="last7">Last 7 days</option>
                            <option value="last30">Last 30 days</option>
                            <option value="custom">Custom range…</option>
                        </select>
                    </div>
                    <div class="ef-row" id="ef-datefield-row" style="display:none">
                        <label>Date field</label>
                        <select id="ef-datefield" onchange="updateExportSummary()">
                            <option value="addedOn">Added date</option>
                            <option value="deadline">Deadline</option>
                        </select>
                    </div>
                    <div class="ef-row" id="ef-custom-from-row" style="display:none">
                        <label>From</label>
                        <input type="date" id="ef-from" onchange="updateExportSummary()" style="flex:1;max-width:60%;padding:7px 10px;font-size:12px;font-family:'Sora';color:var(--ink);background:var(--bg);border:1px solid var(--line2);border-radius:9px;outline:none" />
                    </div>
                    <div class="ef-row" id="ef-custom-to-row" style="display:none">
                        <label>To</label>
                        <input type="date" id="ef-to" onchange="updateExportSummary()" style="flex:1;max-width:60%;padding:7px 10px;font-size:12px;font-family:'Sora';color:var(--ink);background:var(--bg);border:1px solid var(--line2);border-radius:9px;outline:none" />
                    </div>
                    <div class="ef-row">
                        <label>Who's waiting on this?</label>
                        <select id="ef-person" onchange="updateExportSummary()">
                            <option value="">All</option>
                        </select>
                    </div>
                    <div class="ef-row">
                        <label>Shared with</label>
                        <select id="ef-collab" onchange="updateExportSummary()">
                            <option value="">All</option>
                        </select>
                    </div>
                    <div class="ef-row">
                        <label>Tag</label>
                        <select id="ef-tag" onchange="updateExportSummary()">
                            <option value="">All</option>
                        </select>
                    </div>
                </div>
                <div class="ef-summary" id="ef-summary">Exporting all tasks</div>
                <div class="export-grid" style="margin-bottom:10px">
                    <div class="export-card" onclick="backupData()">
                        <span class="xic">⇩</span>
                        <span class="xname">Full Backup</span>
                        <span class="xdesc">All data as .json</span>
                    </div>
                    <div class="export-card" onclick="document.getElementById('restore-file').click()">
                        <span class="xic">⇧</span>
                        <span class="xname">Restore</span>
                        <span class="xdesc">Load a backup file</span>
                    </div>
                </div>
                <input type="file" id="restore-file" accept=".json" style="display:none" onchange="restoreData(event)" />
                <div class="export-grid">
                    <div class="export-card" onclick="exportCSV()">
                        <span class="xic">▦</span>
                        <span class="xname">CSV</span>
                        <span class="xdesc">Spreadsheet-ready file</span>
                    </div>
                    <div class="export-card" onclick="exportPDF()">
                        <span class="xic">▥</span>
                        <span class="xname">PDF</span>
                        <span class="xdesc">Print-ready document</span>
                    </div>
                </div>
            </div>

            <div class="greeting" id="greeting">Welcome back.</div>
        </div>

        <!-- Onboarding carousel (shown only on first load when there are zero tasks ever created) -->
        <div class="onboard-carousel" id="onboard" style="display:none">
            <button class="onboard-skip" onclick="skipOnboarding()">Skip</button>
            <div class="onboard-track" id="onboard-track">

                <!-- Slide 1 -->
                <div class="onboard-slide" data-slide="0">
                    <div class="oic">◫</div>
                    <h3>Welcome to Taskvel</h3>
                    <p>A fast, focused task organiser that ranks what matters and helps you actually get it done. Let's walk through the basics — takes about 30 seconds.</p>
                </div>

                <!-- Slide 2 -->
                <div class="onboard-slide" data-slide="1">
                    <div class="oic">↯</div>
                    <h3>Tasks rank themselves</h3>
                    <p>When you add a task, just pick its urgency and the impact if it drags on. Taskvel scores and ranks it automatically — no manual sorting, ever.</p>
                </div>

                <!-- Slide 3 -->
                <div class="onboard-slide" data-slide="2">
                    <div class="oic">◉</div>
                    <h3>Built-in focus timer</h3>
                    <p>Pick a task, hit Focus, and a Pomodoro-style timer keeps you on track. Every minute is logged automatically to your daily and weekly focus history.</p>
                </div>

                <!-- Slide 4 -->
                <div class="onboard-slide" data-slide="3">
                    <div class="oic">▤</div>
                    <h3>Tags, deadlines & more</h3>
                    <p>Group tasks with tags, set deadlines that auto-escalate priority, repeat recurring work, and export everything to CSV or PDF whenever you need to.</p>
                    <button class="onboard-cta" onclick="finishOnboarding()">+ Add your first task</button>
                </div>

            </div>

            <div class="onboard-footer">
                <div class="onboard-dots" id="onboard-dots"></div>
                <div class="onboard-nav">
                    <button class="onboard-btn ghost" id="onboard-back" onclick="onboardBack()" style="visibility:hidden">Back</button>
                    <button class="onboard-btn primary" id="onboard-next" onclick="onboardNext()">Next</button>
                </div>
            </div>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-num c" id="s-total">0</div>
                <div class="stat-label">Total</div>
                <div class="stat-hint" id="s-total-hint"></div>
            </div>
            <div class="stat">
                <div class="stat-num a" id="s-urgent">0</div>
                <div class="stat-label">Urgent</div>
                <div class="stat-hint" id="s-urgent-hint"></div>
            </div>
            <div class="stat">
                <div class="stat-num g" id="s-done">0</div>
                <div class="stat-label">Done</div>
                <div class="stat-prog"><i id="s-prog"></i></div>
            </div>
            <div class="stat">
                <div class="stat-num v" id="s-focus">0</div>
                <div class="stat-label">Focus·m</div>
                <div class="stat-hint" id="s-focus-hint">today</div>
            </div>
            <div class="stat">
                <div class="stat-num" id="streak-count" style="color:var(--warn)">0</div>
                <div class="stat-label">Streak🔥</div>
                <div class="stat-hint">days</div>
            </div>
        </div>

        <!-- Focus timer -->
        <div class="focus">
            <div class="timer-ring">
                <svg width="92" height="92" viewBox="0 0 92 92">
                                    <circle class="ring-bg" cx="46" cy="46" r="40" />
                                    <circle class="ring-fg" id="ring" cx="46" cy="46" r="40" stroke-dasharray="251.3" stroke-dashoffset="0" />
                                </svg>
                <div class="timer-display">
                    <div class="timer-time" id="t-time">25:00</div>
                    <div class="timer-mode" id="t-mode">Focus</div>
                </div>
            </div>
            <div class="focus-body">
                <div class="focus-title"><span>Focus session</span><span class="today-mins" id="focus-today-inline">0m today</span></div>
                <div class="focus-sub" id="t-task">No task selected</div>
                <div class="timer-btns">
                    <button class="tbtn primary" id="t-toggle" onclick="toggleTimer()">▶ Start</button>
                    <button class="tbtn" onclick="resetTimer()">↺ Reset</button>
                    <button class="tbtn" onclick="cycleMode()" id="t-cycle">25 / 5</button>
                </div>
            </div>
        </div>

        <!-- toolbar -->
        <div class="toolbar">
            <div class="search">
                <span class="ic">⌕</span>
                <input type="text" id="search" placeholder="Search tasks, people, tags, notes…" oninput="render()" />
            </div>
            <button class="add-btn" onclick="openSheet()" aria-label="Add task">+</button>
        </div>
        <div id="bulk-bar" style="display:none;align-items:center;gap:8px;margin-bottom:12px;padding:10px 14px;background:var(--accent-soft);border:1px solid var(--accent-glow);border-radius:12px">
            <span id="bulk-count" style="font-size:12.5px;font-weight:600;font-family:'Space Grotesk'"></span>
            <button class="act" onclick="bulkDone()">✓ Done</button>
            <button class="act" onclick="bulkDelete()">× Delete</button>
            <button class="act" onclick="exitBulkMode()">Cancel</button>
        </div>

        <div class="tagrow" id="tagrow"></div>

        <div class="tabs" id="tabs"></div>

        <div class="list" id="list"></div>
        <div class="remarks-view" id="remarks-view"></div>

        <div class="time-report-view" id="time-report-view"></div>
        <div class="matrix-view" id="matrix-view" style="display:none"></div>
        <div class="matrix-view" id="review-view" style="display:none;grid-template-columns:1fr"></div>
    </div>

    <!-- ADD SHEET -->
    <div class="overlay" id="ov" onclick="closeSheet()"></div>
    <div class="sheet" id="sheet">
        <div class="handle"></div>
        <button class="sheet-close" onclick="closeSheet()" aria-label="Close">✕</button>
        <h2>New task</h2>
        <div class="sub">Capture it, rank it, get it done.</div>
        <div class="fg"><label>What's the task?</label><input type="text" id="f-name" placeholder="e.g. Ship the Wispr auth endpoint" /></div>
        <div class="fg"><label>Who's waiting on this?</label><input type="text" id="f-person" placeholder="e.g. Client, teammate, future you…" /></div>
        <div class="fg"><label>Shared with (optional)</label><input type="text" id="f-collab" placeholder="e.g. Rohit, Design team…" /></div>
        <div class="fg"><label>Urgency</label>
            <div class="opts">
                <button class="opt" onclick="pick(this,'urg','critical')">■ Critical</button>
                <button class="opt" onclick="pick(this,'urg','high')">◆ High</button>
                <button class="opt" onclick="pick(this,'urg','medium')">● Medium</button>
                <button class="opt" onclick="pick(this,'urg','low')">○ Low</button>
            </div>
        </div>
        <div class="fg"><label>Impact if it drags on</label>
            <div class="opts">
                <button class="opt" onclick="pick(this,'dmg','severe')">Severe</button>
                <button class="opt" onclick="pick(this,'dmg','moderate')">Moderate</button>
                <button class="opt" onclick="pick(this,'dmg','minor')">Minor</button>
            </div>
        </div>
        <div class="fg"><label>Deadline (optional — auto-escalates)</label><input type="date" id="f-deadline" /></div>
        <div class="fg"><label>Repeats</label>
            <div class="opts recur-row">
                <button class="opt sel" onclick="pick(this,'recur','none')">Never</button>
                <button class="opt" onclick="pick(this,'recur','daily')">Daily</button>
                <button class="opt" onclick="pick(this,'recur','weekly')">Weekly</button>
                <button class="opt" onclick="pick(this,'recur','monthly')">Monthly</button>
            </div>
        </div>
        <div class="fg"><label>Tags (optional)</label>
            <div class="tag-input-row"><input type="text" id="f-tag-input" placeholder="e.g. work, personal…" /><button class="tag-add-btn" onclick="addTagToForm()">Add</button></div>
            <div class="tag-pills-edit" id="f-tags"></div>
        </div>
        <div class="fg"><label>Step 1</label><div class="estep"><input type="text" id="f-s1" placeholder="First small move" /><input type="url" id="f-sl1" placeholder="Link (optional)" /></div></div>
<div class="fg"><label>Step 2</label><div class="estep"><input type="text" id="f-s2" placeholder="Optional" /><input type="url" id="f-sl2" placeholder="Link (optional)" /></div></div>
<div class="fg"><label>Step 3</label><div class="estep"><input type="text" id="f-s3" placeholder="Optional" /><input type="url" id="f-sl3" placeholder="Link (optional)" /></div></div>
        <div class="thinking" id="thinking">
            <div class="spinner"></div>Ranking by urgency × impact…</div>
        <button class="submit" onclick="addTask()">Add & rank</button>
    </div>

    <!-- EDIT SHEET -->
    <div class="overlay" id="e-ov" onclick="closeEdit()"></div>
    <div class="sheet" id="e-sheet">
        <div class="handle"></div>
        <button class="sheet-close" onclick="closeEdit()" aria-label="Close">✕</button>
        <h2>Edit task</h2>
        <div class="sub">Tune anything — steps, deadline, priority.</div>
        <div class="fg"><label>Task name</label><input type="text" id="e-name" /></div>
        <div class="fg"><label>Who's waiting on this?</label><input type="text" id="e-person" /></div>
        <div class="fg"><label>Shared with (optional)</label><input type="text" id="e-collab" placeholder="e.g. Rohit, Design team…" /></div>
        <div class="fg"><label>Urgency</label>
            <div class="opts">
                <button class="opt" onclick="pickE(this,'urg','critical')">■ Critical</button>
                <button class="opt" onclick="pickE(this,'urg','high')">◆ High</button>
                <button class="opt" onclick="pickE(this,'urg','medium')">● Medium</button>
                <button class="opt" onclick="pickE(this,'urg','low')">○ Low</button>
            </div>
        </div>
        <div class="fg"><label>Impact</label>
            <div class="opts">
                <button class="opt" onclick="pickE(this,'dmg','severe')">Severe</button>
                <button class="opt" onclick="pickE(this,'dmg','moderate')">Moderate</button>
                <button class="opt" onclick="pickE(this,'dmg','minor')">Minor</button>
            </div>
        </div>
        <div class="fg"><label>Deadline</label><input type="date" id="e-deadline" /></div>
        <div class="fg"><label>Repeats</label>
            <div class="opts recur-row">
                <button class="opt" onclick="pickE(this,'recur','none')">Never</button>
                <button class="opt" onclick="pickE(this,'recur','daily')">Daily</button>
                <button class="opt" onclick="pickE(this,'recur','weekly')">Weekly</button>
                <button class="opt" onclick="pickE(this,'recur','monthly')">Monthly</button>
            </div>
        </div>
        <div class="fg"><label>Tags</label>
            <div class="tag-input-row"><input type="text" id="e-tag-input" placeholder="e.g. work, personal…" /><button class="tag-add-btn" onclick="addTagToEditForm()">Add</button></div>
            <div class="tag-pills-edit" id="e-tags"></div>
        </div>
        <div class="fg"><label>Steps</label>
            <div id="e-steps"></div>
            <button class="opt" style="width:100%;justify-content:center;margin-top:6px" onclick="addEStep()">+ Add step</button></div>
            <button class="opt" style="width:100%;justify-content:center;margin-bottom:10px" onclick="saveAsTemplate()">💾 Save as Template</button>
            <button class="submit" onclick="saveEdit()">Save changes</button>
    </div>

    <!-- REMARK SHEET -->
    <div class="overlay" id="r-ov" onclick="closeRemark()"></div>
    <div class="sheet" id="r-sheet">
        <div class="handle"></div>
        <button class="sheet-close" onclick="closeRemark()" aria-label="Close">✕</button>
        <h2 id="r-title">Add remark</h2>
        <div class="sub">Context, updates, follow-ups — anything relevant.</div>
        <div class="fg"><label>Your remark</label><textarea id="r-text" placeholder="e.g. Client called, needs this by Friday. Following up Monday." style="min-height:120px"></textarea></div>
        <button class="submit" onclick="addRemark()">Save remark</button>
    </div>

    <div class="toast" id="toast"><span class="dot"></span><span id="toast-msg"></span><button class="toast-action" id="toast-action" onclick="toastAction()" style="display:none">Undo</button></div>

    <!-- Celebration overlay: big, hard-to-miss confirmation for Pomodoro completion and task completion -->
    <div class="celebrate-overlay" id="celebrate-overlay" onclick="dismissCelebration()"></div>
    <div class="celebrate-card" id="celebrate-card" role="alert" aria-live="assertive">
        <div class="celebrate-icon" id="celebrate-icon">◉</div>
        <div class="celebrate-title" id="celebrate-title">Focus session complete</div>
        <div class="celebrate-sub" id="celebrate-sub">Nice work — take a 5 minute break.</div>
        <button class="celebrate-dismiss" onclick="dismissCelebration()">Got it</button>
    </div>
    <div class="celebrate-overlay" id="brief-overlay" onclick="dismissBriefing()"></div>
    <div class="celebrate-card" id="brief-card" role="alert" aria-live="assertive" style="text-align:left">
        <div class="celebrate-icon" id="brief-icon" style="margin:0 0 14px">☀</div>
        <div class="celebrate-title" id="brief-title">Good morning</div>
        <div id="brief-body" style="font-size:13.5px;color:var(--ink3);line-height:1.7;margin-bottom:20px"></div>
        <button class="celebrate-dismiss" onclick="dismissBriefing()">Let's go</button>
    </div>

    <div class="foot">
        <div class="n">Taskvel</div>
        <div class="d">Focus · Rank · Ship</div>
        <div class="k">Shortcuts: <kbd>⌘K</kbd> commands · <kbd>N</kbd> new · <kbd>/</kbd> search · <kbd>Space</kbd> timer · <kbd>T</kbd> dark · <kbd>Esc</kbd> close</div>
    </div>
    <div class="overlay" id="cmdk-ov" onclick="closeCmdk()"></div>
    <div class="sheet" id="cmdk-sheet" style="max-width:480px;padding-top:14px">
        <input type="text" id="cmdk-input" placeholder="Type a command… (add task, dark mode, export csv)" style="width:100%;padding:14px 16px;font-size:15px;font-family:'Sora';background:var(--bg);border:1px solid var(--line2);border-radius:12px;outline:none;margin-bottom:10px" oninput="filterCmdk()" />
        <div id="cmdk-results" style="display:flex;flex-direction:column;gap:4px;max-height:340px;overflow-y:auto"></div>
    </div>

    <script>
        // ════════════════════════════════════════════
        // STATE
        // ════════════════════════════════════════════
        let tasks = [],
            remarks = [],
            notifications = [],
            focusLog = {}; // focusLog: {'YYYY-MM-DD': minutes}
        let filter = 'all',
            activeTag = null,
            focusMins = 0;
        let streakData = { count: 0, lastActiveDate: null, best: 0 };
        const sel = {
                recur: 'none'
            },
            editSel = {};
        let editingId = null,
            remarkingId = null;
        let dragSrcId = null;
        let bulkMode = false, bulkSelected = new Set();
        function enterBulkMode(firstId) {
            bulkMode = true; bulkSelected = new Set([firstId]);
            render();
        }
        let kbFocusIdx = -1;
        function kbGetCards() { return Array.from(document.querySelectorAll('#list .card')); }
        function kbMove(dir) {
            const cards = kbGetCards(); if (!cards.length) return;
            kbFocusIdx = Math.max(0, Math.min(cards.length - 1, kbFocusIdx + dir));
            cards.forEach((c, i) => c.style.boxShadow = i === kbFocusIdx ? '0 0 0 2px var(--accent)' : '');
            cards[kbFocusIdx].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
        function kbAction(action) {
            const cards = kbGetCards(); if (kbFocusIdx < 0 || kbFocusIdx >= cards.length) return;
            const id = parseInt(cards[kbFocusIdx].id.replace('card-', ''));
            if (action === 'open') openEdit(id);
            else if (action === 'done') markDone(id);
            else if (action === 'del') delTask(id);
        }
        function exitBulkMode() {
            bulkMode = false; bulkSelected.clear();
            document.getElementById('bulk-bar').style.display = 'none';
            render();
        }
        function toggleBulkSelect(id) {
            if (bulkSelected.has(id)) bulkSelected.delete(id); else bulkSelected.add(id);
            if (bulkSelected.size === 0) { exitBulkMode(); return; }
            updateBulkBar();
            document.querySelectorAll('.card').forEach(c => {
                const cid = parseInt(c.id.replace('card-', ''));
                c.style.outline = bulkSelected.has(cid) ? '2px solid var(--accent)' : 'none';
            });
        }
        function updateBulkBar() {
            document.getElementById('bulk-bar').style.display = bulkSelected.size ? 'flex' : 'none';
            document.getElementById('bulk-count').textContent = `${bulkSelected.size} selected`;
        }
        function bulkDone() {
            tasks.forEach(t => { if (bulkSelected.has(t.id)) { t.done = true; t.steps.forEach(s => s.done = true); } });
            save(); recordActivity(); toast(`${bulkSelected.size} tasks marked done ✓`);
            exitBulkMode();
        }
        function bulkDelete() {
            const n = bulkSelected.size;
            tasks = tasks.filter(t => !bulkSelected.has(t.id));
            remarks = remarks.filter(r => !bulkSelected.has(r.taskId));
            save(); saveR(); toast(`${n} tasks removed`);
            exitBulkMode(); renderTabs();
        }

        const LS_T = 'taskvel_tasks_v1',
            LS_R = 'taskvel_remarks_v1',
            LS_F = 'taskvel_focus_v1';
        const LS_THEME = 'taskvel_theme_v1',
            LS_ACCENT = 'taskvel_accent_v1';
        const LS_NOTIF = 'taskvel_notifications_v1',
            LS_FLOG = 'taskvel_focuslog_v1';
        const LS_ORDER = 'taskvel_manualorder_v1',
            LS_ONBOARDED = 'taskvel_onboarded_v1';
        const LS_STREAK = 'taskvel_streak_v1';

        function todayKey(d) {
            d = d || new Date();
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        function load() {
            try {
                tasks = JSON.parse(localStorage.getItem(LS_T)) || []
            } catch (e) {
                tasks = []
            }
            try {
                remarks = JSON.parse(localStorage.getItem(LS_R)) || []
            } catch (e) {
                remarks = []
            }
            try {
                focusMins = parseInt(localStorage.getItem(LS_F)) || 0
            } catch (e) {
                focusMins = 0
            }
            try {
                notifications = JSON.parse(localStorage.getItem(LS_NOTIF)) || []
            } catch (e) {
                notifications = []
            }
            try {
                focusLog = JSON.parse(localStorage.getItem(LS_FLOG)) || {}
            } catch (e) {
                focusLog = {}
            }
            try {
                streakData = JSON.parse(localStorage.getItem(LS_STREAK)) || { count: 0, lastActiveDate: null, best: 0 };
            } catch (e) {
                streakData = { count: 0, lastActiveDate: null, best: 0 };
            }
            // migrate older tasks missing new fields, never destructive
            tasks.forEach(t => {
                if (!Array.isArray(t.tags)) t.tags = [];
                if (!t.recur) t.recur = 'none';
                if (typeof t.collab !== 'string') t.collab = '';
                if (typeof t.order !== 'number') t.order = t.id || 0;
            });
        }

        function save() {
            localStorage.setItem(LS_T, JSON.stringify(tasks))
        }

        function saveR() {
            localStorage.setItem(LS_R, JSON.stringify(remarks))
        }

        function saveF() {
            localStorage.setItem(LS_F, focusMins)
        }

        function saveNotif() {
            localStorage.setItem(LS_NOTIF, JSON.stringify(notifications))
        }

        function saveFLog() {
            localStorage.setItem(LS_FLOG, JSON.stringify(focusLog))
        }
        function saveStreak() {
            localStorage.setItem(LS_STREAK, JSON.stringify(streakData));
        }

        // Call whenever the user completes a task or a focus minute — bumps the streak
        // if today hasn't already been counted, and resets it if a day was missed.
        function recordActivity() {
            const today = todayKey();
            if (streakData.lastActiveDate === today) return; // already counted today
            const y = new Date();
            y.setDate(y.getDate() - 1);
            const yesterday = todayKey(y);
            if (streakData.lastActiveDate === yesterday) {
                streakData.count += 1;
            } else {
                streakData.count = 1; // streak broken or first ever activity
            }
            streakData.lastActiveDate = today;
            if (streakData.count > streakData.best) streakData.best = streakData.count;
            saveStreak();
            updateStreakUI();
        }

        function updateStreakUI() {
            const el = document.getElementById('streak-count');
            if (el) el.textContent = streakData.count;
        }

        // ════════════════════════════════════════════
        // DAILY BRIEFING — shown once per day on first load
        // ════════════════════════════════════════════
        const LS_BRIEFED = 'taskvel_briefed_date_v1';

        function maybeShowBriefing() {
            const today = todayKey();
            if (localStorage.getItem(LS_BRIEFED) === today) return;
            if (tasks.length === 0) return; // nothing to brief on for brand-new users
            localStorage.setItem(LS_BRIEFED, today);

            const overdue = tasks.filter(t => !t.done && daysUntil(t.deadline) !== null && daysUntil(t.deadline) < 0);
            const dueToday = tasks.filter(t => !t.done && daysUntil(t.deadline) === 0);
            const urgent = tasks.filter(t => !t.done && ['critical', 'high'].includes(effRank(t)));

            const hr = new Date().getHours();
            const greeting = hr < 5 ? 'Working late' : hr < 12 ? 'Good morning' : hr < 17 ? 'Good afternoon' : 'Good evening';

            let lines = [];
            if (overdue.length) lines.push(`<b style="color:var(--bad)">${overdue.length} overdue</b> — top of mind: "${esc(overdue[0].name)}"`);
            if (dueToday.length) lines.push(`<b style="color:var(--warn)">${dueToday.length} due today</b>`);
            if (urgent.length) lines.push(`${urgent.length} urgent task${urgent.length === 1 ? '' : 's'} waiting on you`);
            if (streakData.count > 1) lines.push(`🔥 ${streakData.count}-day streak — keep it alive today`);
            if (!lines.length) lines.push(`Nothing urgent — a good day to get ahead.`);

            document.getElementById('brief-icon').textContent = overdue.length ? '⚠' : '☀';
            document.getElementById('brief-title').textContent = `${greeting}. Here's your day.`;
            document.getElementById('brief-body').innerHTML = lines.map(l => `<div style="margin-bottom:6px">• ${l}</div>`).join('');

            document.getElementById('brief-overlay').classList.add('show');
            document.getElementById('brief-card').classList.add('show');
        }
        function dismissBriefing() {
            document.getElementById('brief-overlay').classList.remove('show');
            document.getElementById('brief-card').classList.remove('show');
        }

        // ════════════════════════════════════════════
        // THEME (lightness)
        // ════════════════════════════════════════════
        function applyThemeIcon() {
            const t = document.documentElement.getAttribute('data-theme');
            const el = document.getElementById('tt-icon');
            if (el) el.textContent = t === 'dark' ? '☀' : '☾';
            const accent = document.documentElement.getAttribute('data-accent') || 'samal';
            const map = {
                samal: {
                    light: '#FAF8F3',
                    dark: '#0A1128'
                },
                mono: {
                    light: '#f6f6f4',
                    dark: '#0b0b0b'
                },
                indigo: {
                    light: '#f5f6fb',
                    dark: '#0c0e1c'
                },
                emerald: {
                    light: '#f3f8f5',
                    dark: '#07140f'
                },
                amber: {
                    light: '#fbf7f1',
                    dark: '#160e05'
                }
            };
            let meta = document.getElementById('dyn-theme-color');
            if (!meta) {
                meta = document.createElement('meta');
                meta.id = 'dyn-theme-color';
                meta.name = 'theme-color';
                document.head.appendChild(meta);
            }
            meta.setAttribute('content', (map[accent] || map.samal)[t === 'dark' ? 'dark' : 'light']);
        }

        function toggleTheme() {
            const cur = document.documentElement.getAttribute('data-theme');
            const next = cur === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try {
                localStorage.setItem(LS_THEME, next)
            } catch (e) {}
            applyThemeIcon();
            toast(next === 'dark' ? 'Dark mode' : 'Light mode');
        }

        // ════════════════════════════════════════════
        // ACCENT (colour theme)
        // ════════════════════════════════════════════
        const ACCENT_NAMES = {
            samal: 'Samal',
            mono: 'Mono',
            indigo: 'Indigo',
            emerald: 'Emerald',
            amber: 'Amber'
        };

        function setAccent(name) {
            document.documentElement.setAttribute('data-accent', name);
            try {
                localStorage.setItem(LS_ACCENT, name)
            } catch (e) {}
            markActiveSwatch();
            applyThemeIcon();
            toast(ACCENT_NAMES[name] + ' theme');
        }

        function markActiveSwatch() {
            const cur = document.documentElement.getAttribute('data-accent') || 'samal';
            document.querySelectorAll('.swatch').forEach(s => s.classList.toggle('active', s.getAttribute('data-accent') === cur));
        }

        // ════════════════════════════════════════════
        // PANELS (palette / notifications / history / export) — only one open at a time
        // ════════════════════════════════════════════
        const ALL_PANELS = ['palette-panel', 'notif-panel', 'hist-panel', 'export-panel', 'tmpl-panel'];

        function togglePanel(id) {
            const isOpen = document.getElementById(id).classList.contains('open');
            ALL_PANELS.forEach(p => document.getElementById(p).classList.remove('open'));
            if (!isOpen) {
                document.getElementById(id).classList.add('open');
                if (id === 'palette-panel') markActiveSwatch();
                if (id === 'notif-panel') {
                    renderNotifPanel();
                    markNotifsSeen();
                }
                if (id === 'hist-panel') renderHistory();
                if (id === 'export-panel') populateExportFilters();
                if (id === 'tmpl-panel') renderTemplates();
            }
        }

        function closePanel(id) {
            document.getElementById(id).classList.remove('open')
        }

        function closeAllPanels() {
            ALL_PANELS.forEach(p => document.getElementById(p).classList.remove('open'))
        }

        // ════════════════════════════════════════════
        // CLOCK
        // ════════════════════════════════════════════
        function tickClock() {
            const d = new Date();
            const h = String(d.getHours()).padStart(2, '0'),
                m = String(d.getMinutes()).padStart(2, '0'),
                s = String(d.getSeconds()).padStart(2, '0');
            document.getElementById('clock').innerHTML = `<span>${h}:${m}</span><span class="sec">:${s}</span>`;
            document.getElementById('clock-date').textContent = d.toLocaleDateString('en-IN', {
                weekday: 'short',
                day: 'numeric',
                month: 'short'
            }).toUpperCase();
        }

        function setGreeting() {
            const hr = new Date().getHours();
            let g = 'Good evening';
            if (hr < 5) g = 'Working late';
            else if (hr < 12) g = 'Good morning';
            else if (hr < 17) g = 'Good afternoon';
            const pending = tasks.filter(t => !t.done).length;
            const line = pending ? `You have <b>${pending}</b> open ${pending === 1 ? 'task' : 'tasks'}.` : (tasks.length ? `All tasks complete. Nice work.` : `Ready when you are.`);
            document.getElementById('greeting').innerHTML = `${g}. ${line}`;
        }

        // ════════════════════════════════════════════
        // SCORING / RANK
        // ════════════════════════════════════════════
        function score(u, d) {
            return ({
                critical: 4,
                high: 3,
                medium: 2,
                low: 1
            }[u] || 1) * ({
                severe: 3,
                moderate: 2,
                minor: 1
            }[d] || 1)
        }

        function rank(s) {
            return s >= 10 ? 'critical' : s >= 6 ? 'high' : s >= 3 ? 'medium' : 'low'
        }
        const rankLabel = {
            critical: '■ Critical',
            high: '◆ High',
            medium: '● Medium',
            low: '○ Low'
        };
        const rankCls = {
            critical: 'b-critical',
            high: 'b-high',
            medium: 'b-medium',
            low: 'b-low'
        };

        function daysUntil(s) {
            if (!s) return null;
            const dl = new Date(s);
            dl.setHours(0, 0, 0, 0);
            const now = new Date();
            now.setHours(0, 0, 0, 0);
            return Math.round((dl.getTime() - now.getTime()) / 864e5);
        }

        function effRank(t) {
            const d = daysUntil(t.deadline);
            if (d === null) return t.rank;
            const L = ['low', 'medium', 'high', 'critical'],
                o = L.indexOf(t.rank);
            if (d < 0 || d <= 1) return 'critical';
            if (d <= 3) return L[Math.max(o + 1, 2)];
            if (d <= 7) return L[Math.min(o + 1, 3)];
            return t.rank;
        }

        function wasEsc(t) {
            return t.deadline && effRank(t) !== t.rank
        }

        function dlPill(t) {
            const d = daysUntil(t.deadline);
            if (d === null) return '';
            if (d < 0) return `<span class="dl dl-overdue">! ${Math.abs(d)}d overdue</span>`;
            if (d === 0) return `<span class="dl dl-urgent">Due today</span>`;
            if (d === 1) return `<span class="dl dl-urgent">Due tomorrow</span>`;
            if (d <= 3) return `<span class="dl dl-soon">${d}d left</span>`;
            if (d <= 7) return `<span class="dl dl-soon">${d}d left</span>`;
            return `<span class="dl dl-safe">${d}d left</span>`;
        }

        function fmt(s) {
            const d = new Date(s);
            return d.toLocaleDateString('en-IN', {
                day: 'numeric',
                month: 'short'
            }) + ' · ' + d.toLocaleTimeString('en-IN', {
                hour: '2-digit',
                minute: '2-digit'
            })
        }

        function esc(s) {
            return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        }

        // ── Colour-coded progress: red = danger (urgent + behind), yellow = caution, green = on track ──
        function progressClass(t) {
            const pct = t.steps.length ? (t.steps.filter(s => s.done).length / t.steps.length * 100) : (t.done ? 100 : 0);
            const er = effRank(t);
            const d = daysUntil(t.deadline);
            if (t.done || pct === 100) return 'prog-good';
            if (pct === 0 && !t.steps.length) return 'prog-neutral';
            // overdue or critical with low progress -> red
            if ((d !== null && d < 0) || (er === 'critical' && pct < 60)) return 'prog-bad';
            // high urgency with partial progress, or soon deadline -> yellow
            if (er === 'high' || (d !== null && d <= 3 && pct < 80) || (pct > 0 && pct < 50)) return 'prog-warn';
            // good progress or low urgency -> green
            if (pct >= 50) return 'prog-good';
            return 'prog-neutral';
        }

        // ════════════════════════════════════════════
        // TAGS
        // ════════════════════════════════════════════
        let formTags = [],
            editFormTags = [];

        function normalizeTag(t) {
            return t.trim().toLowerCase().replace(/\s+/g, ' ').slice(0, 24)
        }

        function addTagToForm() {
            const inp = document.getElementById('f-tag-input');
            const v = normalizeTag(inp.value);
            if (v && !formTags.includes(v)) formTags.push(v);
            inp.value = '';
            renderFormTags();
        }

        function renderFormTags() {
            document.getElementById('f-tags').innerHTML = formTags.map((t, i) => `<span class="tagpill-x">${esc(t)}<button onclick="removeFormTag(${i})" aria-label="Remove tag">✕</button></span>`).join('');
        }

        function removeFormTag(i) {
            formTags.splice(i, 1);
            renderFormTags()
        }

        function addTagToEditForm() {
            const inp = document.getElementById('e-tag-input');
            const v = normalizeTag(inp.value);
            if (v && !editFormTags.includes(v)) editFormTags.push(v);
            inp.value = '';
            renderEditFormTags();
        }

        function renderEditFormTags() {
            document.getElementById('e-tags').innerHTML = editFormTags.map((t, i) => `<span class="tagpill-x">${esc(t)}<button onclick="removeEditFormTag(${i})" aria-label="Remove tag">✕</button></span>`).join('');
        }

        function removeEditFormTag(i) {
            editFormTags.splice(i, 1);
            renderEditFormTags()
        }

        function allTags() {
            const s = new Set();
            tasks.forEach(t => (t.tags || []).forEach(tag => s.add(tag)));
            return Array.from(s).sort();
        }

        function renderTagRow() {
            const tags = allTags();
            const row = document.getElementById('tagrow');
            if (!tags.length) {
                row.innerHTML = '';
                activeTag = null;
                return;
            }
            row.innerHTML = tags.map(t => `<button class="tagchip ${activeTag === t ? 'active' : ''}" onclick="toggleTagFilter('${t.replace(/'/g, "\\'")}')">#${esc(t)}</button>`).join('');
        }

        function toggleTagFilter(t) {
            activeTag = (activeTag === t) ? null : t;
            renderTagRow();
            render()
        }

        // ════════════════════════════════════════════
        // RECURRENCE — when a recurring task is completed, spawn the next occurrence
        // ════════════════════════════════════════════
        function nextDate(dateStr, recur) {
            const base = dateStr ? new Date(dateStr) : new Date();
            const d = new Date(base);
            if (recur === 'daily') d.setDate(d.getDate() + 1);
            else if (recur === 'weekly') d.setDate(d.getDate() + 7);
            else if (recur === 'monthly') d.setMonth(d.getMonth() + 1);
            return d.toISOString().slice(0, 10);
        }

        function spawnRecurrence(t) {
            if (!t.recur || t.recur === 'none') return;
            const newDeadline = t.recur.startsWith('RRULE:') ? parseRecurrence(t.recur, t.deadline || todayKey()) : nextDate(t.deadline || todayKey(), t.recur);
            const clone = {
                id: Date.now() + Math.floor(Math.random() * 1000),
                name: t.name,
                person: t.person,
                collab: t.collab,
                urgency: t.urgency,
                damage: t.damage,
                rank: t.rank,
                score: t.score,
                deadline: newDeadline,
                pinned: false,
                recur: t.recur,
                tags: (t.tags || []).slice(),
                steps: (t.steps || []).map(s => ({
                    text: s.text,
                    done: false,
                    deadline: null,
                    link: s.link
                })),
                done: false,
                addedOn: new Date().toISOString(),
                order: Date.now()
            };
            tasks.push(clone);
            pushNotification('↻', `Recurring task renewed: "${t.name}" — next due ${new Date(newDeadline).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })}`);
        }
        function parseRecurrence(rule, fromDateStr) {
            // Lightweight RRULE-lite parser — no external library needed.
            // Supports: RRULE:FREQ=DAILY|WEEKLY|MONTHLY|YEARLY;INTERVAL=n;BYDAY=MO,TU,WE,TH,FR,SA,SU
            const base = fromDateStr ? new Date(fromDateStr) : new Date();
            const params = {};
            rule.replace(/^RRULE:/, '').split(';').forEach(pair => {
                const [k, v] = pair.split('=');
                if (k && v) params[k.toUpperCase()] = v;
            });

            const freq = params.FREQ || 'DAILY';
            const interval = parseInt(params.INTERVAL) || 1;
            const dayMap = { SU: 0, MO: 1, TU: 2, WE: 3, TH: 4, FR: 5, SA: 6 };

            const d = new Date(base);

            if (freq === 'DAILY') {
                d.setDate(d.getDate() + interval);
            } else if (freq === 'WEEKLY') {
                if (params.BYDAY) {
                    const wantedDays = params.BYDAY.split(',').map(x => dayMap[x.trim().toUpperCase()]).filter(x => x !== undefined).sort((a, b) => a - b);
                    if (wantedDays.length) {
                        let next = new Date(d);
                        for (let i = 1; i <= 7 * interval + 7; i++) {
                            next.setDate(next.getDate() + 1);
                            if (wantedDays.includes(next.getDay())) { d.setTime(next.getTime()); break; }
                        }
                    } else {
                        d.setDate(d.getDate() + 7 * interval);
                    }
                } else {
                    d.setDate(d.getDate() + 7 * interval);
                }
            } else if (freq === 'MONTHLY') {
                d.setMonth(d.getMonth() + interval);
            } else if (freq === 'YEARLY') {
                d.setFullYear(d.getFullYear() + interval);
            } else {
                // unknown FREQ — fall back to daily
                d.setDate(d.getDate() + 1);
            }

            return d.toISOString().slice(0, 10);
        }

        // ════════════════════════════════════════════
        // NOTIFICATIONS (in-app only)
        // ════════════════════════════════════════════
        function pushNotification(icon, msg) {
            notifications.unshift({
                id: Date.now() + Math.random(),
                icon,
                msg,
                createdAt: new Date().toISOString(),
                seen: false
            });
            notifications = notifications.slice(0, 50);
            saveNotif();
            updateNotifDot();
        }

        function updateNotifDot() {
            const has = notifications.some(n => !n.seen);
            document.getElementById('notif-dot').classList.toggle('show', has);
        }

        function markNotifsSeen() {
            notifications.forEach(n => n.seen = true);
            saveNotif();
            updateNotifDot();
        }

        function renderNotifPanel() {
            const list = document.getElementById('notif-list');
            if (!notifications.length) {
                list.innerHTML = `<div class="notif-empty">No notifications yet. You'll see timer completions and deadline alerts here.</div>`;
                return;
            }
            list.innerHTML = notifications.slice(0, 20).map(n => `<div class="notif-item"><span class="ic">${n.icon}</span><div class="body"><div class="msg">${esc(n.msg)}</div><div class="time">${fmt(n.createdAt)}</div></div></div>`).join('');
        }
        // sweep for deadline-near tasks; called on load and periodically
        let lastDeadlineSweep = null;

        function sweepDeadlines() {
            const today = todayKey();
            if (lastDeadlineSweep === today) return;
            lastDeadlineSweep = today;
            tasks.forEach(t => {
                if (t.done) return;
                const d = daysUntil(t.deadline);
                if (d === 0) pushNotification('!', `"${t.name}" is due today.`);
                else if (d === 1) pushNotification('⏳', `"${t.name}" is due tomorrow.`);
                else if (d !== null && d < 0 && d >= -1) pushNotification('!', `"${t.name}" is overdue.`);
            });
            updateNotifDot();
        }

        // ════════════════════════════════════════════
        // FOCUS HISTORY
        // ════════════════════════════════════════════
        function logFocusMinute() {
            const k = todayKey();
            focusLog[k] = (focusLog[k] || 0) + 1;
            saveFLog();
            recordActivity();
        }

        function last7Days() {
            const out = [];
            for (let i = 6; i >= 0; i--) {
                const d = new Date();
                d.setDate(d.getDate() - i);
                out.push({
                    key: todayKey(d),
                    label: d.toLocaleDateString('en-IN', {
                        weekday: 'narrow'
                    }),
                    date: d
                });
            }
            return out;
        }

        function renderHistory() {
            const days = last7Days();
            const todayMin = focusLog[todayKey()] || 0;
            const weekMin = days.reduce((sum, d) => sum + (focusLog[d.key] || 0), 0);
            const avgMin = Math.round(weekMin / 7);
            document.getElementById('hist-today').textContent = todayMin;
            document.getElementById('hist-week').textContent = weekMin;
            document.getElementById('hist-avg').textContent = avgMin;
            const max = Math.max(1, ...days.map(d => focusLog[d.key] || 0));
            document.getElementById('hist-chart').innerHTML = days.map(d => {
                const mins = focusLog[d.key] || 0;
                const h = Math.max(2, Math.round((mins / max) * 84));
                return `<div class="col"><div class="mlabel">${mins || ''}</div><div class="barwrap"><div class="bar" style="height:${h}px"></div></div><div class="dlabel">${d.label}</div></div>`;
            }).join('');
            document.getElementById('focus-today-inline').textContent = todayMin + 'm today';
        }

        // ════════════════════════════════════════════
        // EXPORT — CSV + PDF, with optional filtering by person / shared-with / tag
        // ════════════════════════════════════════════
        function csvEscape(v) {
            v = String(v == null ? '' : v);
            if (/[",\n]/.test(v)) return '"' + v.replace(/"/g, '""') + '"';
            return v;
        }
        // Populate the three export filter dropdowns from whatever values currently exist on tasks.
        function populateExportFilters() {
            const personSel = document.getElementById('ef-person');
            const collabSel = document.getElementById('ef-collab');
            const tagSel = document.getElementById('ef-tag');
            if (!personSel || !collabSel || !tagSel) return;

            const people = Array.from(new Set(tasks.map(t => (t.person || '').trim()).filter(Boolean))).sort();
            const collabs = Array.from(new Set(tasks.map(t => (t.collab || '').trim()).filter(Boolean))).sort();
            const tags = allTags();

            const keepValue = (sel, fn) => {
                const v = sel.value;
                sel.innerHTML = '<option value="">All</option>' + fn();
                if (Array.from(sel.options).some(o => o.value === v)) sel.value = v;
            };

            keepValue(personSel, () => people.map(p => `<option value="${esc(p)}">${esc(p)}</option>`).join(''));
            keepValue(collabSel, () => collabs.map(c => `<option value="${esc(c)}">${esc(c)}</option>`).join(''));
            keepValue(tagSel, () => tags.map(tg => `<option value="${esc(tg)}">#${esc(tg)}</option>`).join(''));

            updateExportSummary();
        }
        // // Returns the task list narrowed by whichever export filters are set (AND across all three).
        // function getExportFilteredTasks() {
        //     const person = document.getElementById('ef-person') ? document.getElementById('ef-person').value : '';
        //     const collab = document.getElementById('ef-collab') ? document.getElementById('ef-collab').value : '';
        //     const tag = document.getElementById('ef-tag') ? document.getElementById('ef-tag').value : '';
        //     return tasks.filter(t =>
        //         (!person || (t.person || '').trim() === person) &&
        //         (!collab || (t.collab || '').trim() === collab) &&
        //         (!tag || (t.tags || []).includes(tag))
        //     );
        // }

        // function updateExportSummary() {
        //     const summary = document.getElementById('ef-summary');
        //     if (!summary) return;
        //     const filtered = getExportFilteredTasks();
        //     const person = document.getElementById('ef-person').value;
        //     const collab = document.getElementById('ef-collab').value;
        //     const tag = document.getElementById('ef-tag').value;
        //     const bits = [];
        //     if (person) bits.push(`waiting on ${person}`);
        //     if (collab) bits.push(`shared with ${collab}`);
        //     if (tag) bits.push(`tagged #${tag}`);
        //     const filterText = bits.length ? ' — ' + bits.join(', ') : '';
        //     summary.textContent = `Exporting ${filtered.length} of ${tasks.length} task${tasks.length === 1 ? '' : 's'}${filterText}`;
        // }

        // Returns {fromKey, toKey} (inclusive) for the selected date range, or null for "all".
        function getExportDateBounds() {
            const range = document.getElementById('ef-daterange') ? document.getElementById('ef-daterange').value : 'all';
            if (range === 'all') return null;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (range === 'today') return {
                from: today,
                to: today
            };
            if (range === 'yesterday') {
                const y = new Date(today);
                y.setDate(y.getDate() - 1);
                return {
                    from: y,
                    to: y
                };
            }
            if (range === 'last7') {
                const f = new Date(today);
                f.setDate(f.getDate() - 6);
                return {
                    from: f,
                    to: today
                };
            }
            if (range === 'last30') {
                const f = new Date(today);
                f.setDate(f.getDate() - 29);
                return {
                    from: f,
                    to: today
                };
            }
            if (range === 'custom') {
                const fv = document.getElementById('ef-from').value;
                const tv = document.getElementById('ef-to').value;
                const f = fv ? new Date(fv) : null,
                    t = tv ? new Date(tv) : null;
                if (f) f.setHours(0, 0, 0, 0);
                if (t) t.setHours(0, 0, 0, 0);
                return {
                    from: f,
                    to: t
                };
            }
            return null;
        }

        function onDateRangeChange() {
            const range = document.getElementById('ef-daterange').value;
            const isCustom = range === 'custom';
            const showDateField = range !== 'all';
            document.getElementById('ef-datefield-row').style.display = showDateField ? 'flex' : 'none';
            document.getElementById('ef-custom-from-row').style.display = isCustom ? 'flex' : 'none';
            document.getElementById('ef-custom-to-row').style.display = isCustom ? 'flex' : 'none';
            updateExportSummary();
        }

        // Returns the task list narrowed by whichever export filters are set (AND across all).
        function getExportFilteredTasks() {
            const person = document.getElementById('ef-person') ? document.getElementById('ef-person').value : '';
            const collab = document.getElementById('ef-collab') ? document.getElementById('ef-collab').value : '';
            const tag = document.getElementById('ef-tag') ? document.getElementById('ef-tag').value : '';
            const status = document.getElementById('ef-status') ? document.getElementById('ef-status').value : 'all';
            const bounds = getExportDateBounds();
            const dateField = document.getElementById('ef-datefield') ? document.getElementById('ef-datefield').value : 'addedOn';

            return tasks.filter(t => {
                if (person && (t.person || '').trim() !== person) return false;
                if (collab && (t.collab || '').trim() !== collab) return false;
                if (tag && !(t.tags || []).includes(tag)) return false;
                if (status === 'done' && !t.done) return false;
                if (status === 'pending' && t.done) return false;
                if (bounds) {
                    const raw = dateField === 'deadline' ? t.deadline : t.addedOn;
                    if (!raw) return false;
                    const d = new Date(raw);
                    d.setHours(0, 0, 0, 0);
                    if (bounds.from && d < bounds.from) return false;
                    if (bounds.to && d > bounds.to) return false;
                }
                return true;
            });
        }

        function updateExportSummary() {
            const summary = document.getElementById('ef-summary');
            if (!summary) return;
            const filtered = getExportFilteredTasks();
            const person = document.getElementById('ef-person').value;
            const collab = document.getElementById('ef-collab').value;
            const tag = document.getElementById('ef-tag').value;
            const status = document.getElementById('ef-status').value;
            const range = document.getElementById('ef-daterange').value;
            const dateField = document.getElementById('ef-datefield').value;
            const bits = [];
            if (status === 'done') bits.push('done only');
            else if (status === 'pending') bits.push('undone only');
            if (range !== 'all') {
                const rangeLabels = {
                    today: 'today',
                    yesterday: 'yesterday',
                    last7: 'last 7 days',
                    last30: 'last 30 days',
                    custom: 'custom range'
                };
                const fieldLabel = dateField === 'deadline' ? 'deadline' : 'added';
                bits.push(`${fieldLabel} ${rangeLabels[range] || ''}`.trim());
            }
            if (person) bits.push(`waiting on ${person}`);
            if (collab) bits.push(`shared with ${collab}`);
            if (tag) bits.push(`tagged #${tag}`);
            const filterText = bits.length ? ' — ' + bits.join(', ') : '';
            summary.textContent = `Exporting ${filtered.length} of ${tasks.length} task${tasks.length === 1 ? '' : 's'}${filterText}`;
        }

        function exportCSV() {
            const filtered = getExportFilteredTasks();
            if (!filtered.length) {
                toast(tasks.length ? 'No tasks match that filter' : 'No tasks to export yet');
                return
            }
            const headers = ['Name', 'Person', 'Shared With', 'Urgency', 'Impact', 'Rank', 'Score', 'Deadline', 'Repeats', 'Tags', 'Status', 'Steps Done', 'Steps Total', 'Added On'];
            const rows = filtered.map(t => [
                t.name, t.person || '', t.collab || '', t.urgency, t.damage, effRank(t), t.score,
                t.deadline || '', t.recur || 'none', (t.tags || []).join('; '),
                t.done ? 'Done' : 'Pending', t.steps.filter(s => s.done).length, t.steps.length,
                t.addedOn ? new Date(t.addedOn).toLocaleDateString('en-IN') : ''
            ]);
            const csv = [headers, ...rows].map(r => r.map(csvEscape).join(',')).join('\r\n');
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `taskvel-export-${todayKey()}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            closePanel('export-panel');
            toast(filtered.length === tasks.length ? 'CSV downloaded ✓' : `CSV downloaded — ${filtered.length} filtered task${filtered.length === 1 ? '' : 's'} ✓`);
        }

        function exportPDF() {
            const filtered = getExportFilteredTasks();
            if (!filtered.length) {
                toast(tasks.length ? 'No tasks match that filter' : 'No tasks to export yet');
                return
            }
            closePanel('export-panel');
            toast('Opening print dialog — choose "Save as PDF"');
            if (filtered.length === tasks.length) {
                // no filter active — print the list exactly as already rendered
                setTimeout(() => window.print(), 300);
                return;
            }
            // a filter is active — temporarily swap #list to the filtered set, print, then restore the normal view
            const listEl = document.getElementById('list');
            const originalHTML = listEl.innerHTML;
            renderTaskCardsInto(listEl, filtered);
            setTimeout(() => {
                window.print();
                setTimeout(() => {
                    listEl.innerHTML = originalHTML;
                }, 200);
            }, 300);
        }
        function backupData() {
        const payload = {
            version: 1, exportedAt: new Date().toISOString(),
            tasks, remarks, notifications, focusLog, streakData,
            theme: localStorage.getItem(LS_THEME), accent: localStorage.getItem(LS_ACCENT)
        };
        const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = `taskvel-backup-${todayKey()}.json`;
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(url);
        toast('Backup downloaded ✓');
    }
    function restoreData(e) {
        const file = e.target.files[0]; if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
            try {
                const data = JSON.parse(reader.result);
                if (!Array.isArray(data.tasks)) throw new Error('Invalid backup file');
                tasks = data.tasks || [];
                remarks = data.remarks || [];
                notifications = data.notifications || [];
                focusLog = data.focusLog || {};
                streakData = data.streakData || { count: 0, lastActiveDate: null, best: 0 };
                save(); saveR(); saveNotif(); saveFLog(); saveStreak();
                if (data.theme) { document.documentElement.setAttribute('data-theme', data.theme); localStorage.setItem(LS_THEME, data.theme); }
                if (data.accent) { document.documentElement.setAttribute('data-accent', data.accent); localStorage.setItem(LS_ACCENT, data.accent); }
                render(); renderTabs(); updateStreakUI(); applyThemeIcon(); updateNotifDot();
                toast('Backup restored ✓');
            } catch (err) {
                toast('Invalid backup file');
            }
            e.target.value = '';
        };
        reader.readAsText(file);
    }

        // ════════════════════════════════════════════
        // TABS
        // ════════════════════════════════════════════
        const TABS = [
            ['all', 'All'],
            ['today', 'Today'],
            ['pending', 'Pending'],
            ['done', 'Done'],
            ['matrix', 'Matrix'],
            ['review', 'Weekly Review'],
            ['remarks', 'Remarks'],
            ['time-report', 'Time Report']
        ];

        function renderTabs() {
            document.getElementById('tabs').innerHTML = TABS.map(([k, lbl]) => {
                let c = '';
                if (k === 'all') c = tasks.length;
                else if (k === 'today') c = tasks.filter(t => !t.done && ['critical', 'high'].includes(effRank(t))).length;
                else if (k === 'pending') c = tasks.filter(t => !t.done).length;
                else if (k === 'done') c = tasks.filter(t => t.done).length;
                else if (k === 'remarks') c = remarks.length;
                return `<button class="tab ${filter === k ? 'active' : ''}" onclick="setFilter('${k}')">${lbl}<span class="cnt">${c}</span></button>`;
            }).join('');
        }

        function setFilter(f) {
            filter = f;
            renderTabs();
            const isR = f === 'remarks';
            const isTR = f === 'time-report';
            const isMX = f === 'matrix';
            const isRV = f === 'review';
            document.getElementById('list').classList.toggle('hidden', isR || isTR || isMX || isRV);
            document.getElementById('remarks-view').classList.toggle('active', isR);
            document.getElementById('time-report-view').classList.toggle('active', isTR);
            document.getElementById('matrix-view').classList.toggle('active', isMX);
            document.getElementById('review-view').classList.toggle('active', isRV);
            if (isR) renderRemarks();
            else if (isTR) renderTimeReport();
            else if (isMX) renderMatrix();
            else if (isRV) renderWeeklyReview();
            else render();
        }

        // ════════════════════════════════════════════
        // RENDER TASKS
        // ════════════════════════════════════════════
        function getFiltered() {
            const q = document.getElementById('search').value.trim().toLowerCase();
            let list = tasks.slice();
            if (filter === 'today') list = list.filter(t => !t.done && ['critical', 'high'].includes(effRank(t)));
            else if (filter === 'pending') list = list.filter(t => !t.done);
            else if (filter === 'done') list = list.filter(t => t.done);
            if (activeTag) list = list.filter(t => (t.tags || []).includes(activeTag));
            if (q) list = list.filter(t => (t.name + ' ' + (t.person || '') + ' ' + (t.collab || '') + ' ' + (t.tags || []).join(' ') + ' ' + t.steps.map(s => s.text).join(' ') + ' ' + remarks.filter(r => r.taskId == t.id).map(r => r.text).join(' ')).toLowerCase().includes(q));
            return list;
        }

        function updateOnboardingAndEmptyStates() {
            const onboardingDismissed = localStorage.getItem(LS_ONBOARDED) === '1';
            const shouldShow = tasks.length === 0 && !onboardingDismissed;
            document.getElementById('onboard').style.display = shouldShow ? 'block' : 'none';
            // hints under stats when zero, to avoid the "looks broken" issue
            document.getElementById('s-total-hint').textContent = tasks.length === 0 ? 'tap + to start' : '';
            document.getElementById('s-urgent-hint').textContent = (tasks.length > 0 && tasks.filter(t => !t.done && ['critical', 'high'].includes(effRank(t))).length === 0) ? 'all clear' : '';
        }

        // ════════════════════════════════════════════
        // ONBOARDING CAROUSEL — multi-step banners with Next/Back/dots/Skip
        // ════════════════════════════════════════════
        const ONBOARD_SLIDE_COUNT = document.querySelectorAll('.onboard-slide').length || 4;
        let onboardIdx = 0;

        function renderOnboardDots() {
            const dots = document.getElementById('onboard-dots');
            if (!dots) return;
            dots.innerHTML = '';
            for (let i = 0; i < ONBOARD_SLIDE_COUNT; i++) {
                const b = document.createElement('button');
                b.className = 'onboard-dot' + (i === onboardIdx ? ' active' : '');
                b.setAttribute('aria-label', 'Go to step ' + (i + 1));
                b.onclick = () => goToOnboardSlide(i);
                dots.appendChild(b);
            }
        }

        function paintOnboardSlide() {
            const track = document.getElementById('onboard-track');
            if (!track) return;
            track.style.transform = `translateX(-${onboardIdx * 100}%)`;
            renderOnboardDots();
            const backBtn = document.getElementById('onboard-back');
            const nextBtn = document.getElementById('onboard-next');
            if (backBtn) backBtn.style.visibility = onboardIdx === 0 ? 'hidden' : 'visible';
            if (nextBtn) {
                const isLast = onboardIdx === ONBOARD_SLIDE_COUNT - 1;
                nextBtn.textContent = isLast ? 'Get started' : 'Next';
                nextBtn.onclick = isLast ? finishOnboarding : onboardNext;
            }
        }

        function goToOnboardSlide(i) {
            onboardIdx = Math.max(0, Math.min(ONBOARD_SLIDE_COUNT - 1, i));
            paintOnboardSlide();
        }

        function onboardNext() {
            if (onboardIdx < ONBOARD_SLIDE_COUNT - 1) {
                onboardIdx++;
                paintOnboardSlide();
            }
        }

        function onboardBack() {
            if (onboardIdx > 0) {
                onboardIdx--;
                paintOnboardSlide();
            }
        }

        function skipOnboarding() {
            try {
                localStorage.setItem(LS_ONBOARDED, '1')
            } catch (e) {}
            document.getElementById('onboard').style.display = 'none';
            toast('You can revisit tips anytime from the help icon');
        }

        function finishOnboarding() {
            try {
                localStorage.setItem(LS_ONBOARDED, '1')
            } catch (e) {}
            document.getElementById('onboard').style.display = 'none';
            openSheet();
        }

        // basic swipe support for touch devices
        function initOnboardSwipe() {
            const track = document.getElementById('onboard-track');
            if (!track) return;
            let startX = 0,
                isDown = false;
            track.addEventListener('touchstart', e => {
                isDown = true;
                startX = e.touches[0].clientX;
            }, {
                passive: true
            });
            track.addEventListener('touchend', e => {
                if (!isDown) return;
                isDown = false;
                const dx = e.changedTouches[0].clientX - startX;
                if (Math.abs(dx) < 40) return;
                if (dx < 0) onboardNext();
                else onboardBack();
            }, {
                passive: true
            });
        }

        // Builds the HTML for a single task card. Shared by the live render() and the
        // PDF-export filtered print path so the markup never has to be duplicated.
        function cardHTML(t, idx) {
            const er = effRank(t);
            const doneS = t.steps.filter(s => s.done).length;
            const pct = t.steps.length ? Math.round(doneS / t.steps.length * 100) : (t.done ? 100 : 0);
            const steps = t.steps.length ? `<div class="steps">${t.steps.map((s, i) =>
                `<div class="step" onclick="toggleStep(${t.id},${i})">
                    <div class="box ${s.done ? 'on' : ''}"></div>
                    <span class="step-t ${s.done ? 'struck' : ''}">
                        ${s.link ? `<a href="${esc(s.link)}" target="_blank" rel="noopener" onclick="event.stopPropagation()">${esc(s.text)}</a>` : esc(s.text)}
                    </span>
                </div>`).join('')}</div>` : '';
            const pcls = progressClass(t);
            const prog = (t.steps.length || t.done) ? `<div class="prog ${pcls}"><i style="width:${pct}%"></i></div>` : '';
            const tr = remarks.filter(r => r.taskId == t.id);
            const rmk = tr.length ? `<div class="card-rmk">${tr.map(r => `<div class="rmk-item"><div><div class="t">${esc(r.text)}</div><div class="d">${fmt(r.createdAt)}</div></div><button class="rmk-x" onclick="delRemark(${r.id})">✕</button></div>`).join('')}</div>` : '';
            const tagsHtml = (t.tags || []).length ? `<div class="tag-row">${t.tags.map(tg => `<span class="tagpill">#${esc(tg)}</span>`).join('')}</div>` : '';
            const recurHtml = (t.recur && t.recur !== 'none') ? `<span class="recur-badge">↻ ${t.recur}</span>` : '';
            const collabHtml = t.collab ? `<span class="collab-badge">⟐ ${esc(t.collab)}</span>` : '';
            const timeHtml = t.timeSpent ? `<span class="dl dl-safe">⏱ ${formatTime(t.timeSpent)}</span>` : '';
            return `<div class="card ${t.done ? 'done' : ''}" id="card-${t.id}" draggable="true"
                ondragstart="dragStart(event,${t.id})" ondragover="dragOver(event,${t.id})" ondragleave="dragLeave(event,${t.id})" ondrop="dragDrop(event,${t.id})" ondragend="dragEnd(event)"
                style="animation-delay:${idx * 40}ms" onmousemove="cardMove(event,this)">
                <div class="card-top">
                    <div class="card-top-left">
                        <span class="drag-handle" title="Drag to reorder" aria-hidden="true">⠿</span>
                        <div class="badges"><span class="badge ${rankCls[er]}">${rankLabel[er]}</span>${dlPill(t)}${recurHtml}${collabHtml}${timeHtml}</div>
                    </div>
                    <button class="pin-btn ${t.pinned ? 'pinned' : ''}" onclick="${bulkMode ? `toggleBulkSelect(${t.id})` : `togglePin(${t.id})`}" oncontextmenu="event.preventDefault(); ${bulkMode ? `toggleBulkSelect(${t.id})` : `enterBulkMode(${t.id})`}" title="${bulkMode ? 'Select' : 'Pin (right-click to multi-select)'}">${bulkMode ? (bulkSelected.has(t.id) ? '☑' : '☐') : (t.pinned ? '★' : '☆')}</button>
                </div>
                ${wasEsc(t) ? `<div class="escalated">↑ Auto-escalated from ${t.rank} · deadline pressure</div>` : ''}
                <div class="task-name ${t.done ? 'struck' : ''}">${esc(t.name)}</div>
                <div class="task-meta">${t.person ? `<span>◴ ${esc(t.person)}</span>` : ''}${t.steps.length ? `<span>✓ ${doneS}/${t.steps.length} steps</span>` : ''}<span>↯ score ${t.score}</span></div>
                ${tagsHtml}
                ${prog}${steps}${rmk}
                <div class="actions">
                    ${t.done ? `<button class="act" onclick="markUndone(${t.id})">↺ Undo</button>` : `<button class="act done-act" onclick="markDone(${t.id})">✓ Done</button>`}
                    <button class="act focus-act" onclick="setFocusTask(${t.id})">◉ Focus</button>
                    ${t.timeTrackingStarted ? `<button class="act" onclick="stopTimeTracking(${t.id})">◼ Stop</button>` : `<button class="act" onclick="startTimeTracking(${t.id})">▶ Track</button>`}
                    <button class="act" onclick="openEdit(${t.id})">✎ Edit</button>
                    <button class="act" onclick="openRemark(${t.id})">❝ Remark</button>
                    <button class="act del" onclick="delTask(${t.id})">× Remove</button>
                </div>
            </div>`;
        }
                        // Renders a given task array (already sorted/filtered by the caller) into any container —
                        // used for the temporary filtered PDF-export view so the normal #list render path stays untouched.
                        function renderTaskCardsInto(container, taskArr) {
                            container.innerHTML = taskArr.map((t, idx) => cardHTML(t, idx)).join('');
                        }

                        function render() {
    const ro = { critical: 4, high: 3, medium: 2, low: 1 };
    document.getElementById('s-total').textContent = tasks.length;
    document.getElementById('s-urgent').textContent = tasks.filter(t => !t.done && ['critical', 'high'].includes(effRank(t))).length;
    const done = tasks.filter(t => t.done).length;
    document.getElementById('s-done').textContent = done;
    document.getElementById('s-prog').style.width = (tasks.length ? Math.round(done / tasks.length * 100) : 0) + '%';
    document.getElementById('s-focus').textContent = focusLog[todayKey()] || 0;
    setGreeting();
    renderTagRow();
    updateOnboardingAndEmptyStates();
    populateExportFilters();

    const list = document.getElementById('list');
    const f = getFiltered();
    if (!f.length) {
        const q = document.getElementById('search').value.trim();
        const noTasksAtAll = tasks.length === 0;
        list.innerHTML = noTasksAtAll ? '' : `<div class="empty"><div class="ic">${q ? '⌕' : filter === 'done' ? '✓' : '◫'}</div>
        <div class="h">${q ? 'Nothing matches that' : filter === 'done' ? 'Nothing completed yet' : 'No tasks in this view'}</div>
        <div class="s">${q ? 'Try a different search term.' : filter === 'done' ? 'Complete a task to see it here.' : 'Try a different tab or clear your filters.'}</div></div>`;
        return;
    }
    const manualOrderActive = filter === 'all' && !activeTag && !document.getElementById('search').value.trim();
    f.sort((a, b) => {
        if (a.pinned !== b.pinned) return a.pinned ? -1 : 1;
        if (a.done !== b.done) return a.done ? 1 : -1;
        const r = (ro[effRank(b)] || 0) - (ro[effRank(a)] || 0);
        if (r) return r;
        if (manualOrderActive) return (a.order || 0) - (b.order || 0);
        return b.score - a.score;
    });
    renderTaskCardsInto(list, f);
}
                        function cardMove(e, el) {

                            const r = el.getBoundingClientRect();
                            el.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
                            el.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100) + '%');
                        }

                        // ════════════════════════════════════════════
                        // DRAG TO REORDER (manual override, only active in the default "All" view with no filters)
                        // ════════════════════════════════════════════
                        function dragStart(e, id) {
                            dragSrcId = id;
                            e.dataTransfer.effectAllowed = 'move';
                            try { e.dataTransfer.setData('text/plain', String(id)); } catch (err) {}
                            const card = document.getElementById('card-' + id);
                            if (card) card.classList.add('dragging');
                        }
                        function dragOver(e, id) {
                            e.preventDefault();
                            if (dragSrcId === id) return;
                            const card = document.getElementById('card-' + id);
                            if (card) card.classList.add('drag-over');
                        }
                        function dragLeave(e, id) {
                            const card = document.getElementById('card-' + id);
                            if (card) card.classList.remove('drag-over');
                        }
                        function dragDrop(e, targetId) {
                            e.preventDefault();
                            const targetCard = document.getElementById('card-' + targetId);
                            if (targetCard) targetCard.classList.remove('drag-over');
                            if (dragSrcId === null || dragSrcId === targetId) return;
                            const srcIdx = tasks.findIndex(t => t.id === dragSrcId);
                            const tgtIdx = tasks.findIndex(t => t.id === targetId);
                            if (srcIdx < 0 || tgtIdx < 0) return;
                            // reassign 'order' field based on new sequence among currently visible (sorted) tasks
                            const visibleIds = Array.from(document.querySelectorAll('.card')).map(c => parseInt(c.id.replace('card-', '')));
                            const srcPos = visibleIds.indexOf(dragSrcId);
                            const tgtPos = visibleIds.indexOf(targetId);
                            if (srcPos < 0 || tgtPos < 0) return;
                            visibleIds.splice(srcPos, 1);
                            visibleIds.splice(tgtPos, 0, dragSrcId);
                            visibleIds.forEach((id, i) => { const t = tasks.find(t => t.id === id); if (t) t.order = i; });
                            save();
                            render();
                            toast('Order updated');
                        }
                        function dragEnd(e) {
                            document.querySelectorAll('.card').forEach(c => { c.classList.remove('dragging'); c.classList.remove('drag-over') });
                            dragSrcId = null;
                        }

                        // ════════════════════════════════════════════
                        // TASK OPS
                        // ════════════════════════════════════════════
                        function toggleStep(id, i) {
                            const t = tasks.find(t => t.id === id);
                            if (t) {
                                t.steps[i].done = !t.steps[i].done;
                                if (t.steps.length && t.steps.every(s => s.done) && !t.done) {
                                    completeTask(t);
                                }
                                if (t.steps[i].deadline) {
                                    const d = new Date(t.steps[i].deadline);
                                    d.setHours(23, 59, 59, 999); // end of day
                                    if (d < new Date()) {
                                        pushNotification('!', `Step "${t.steps[i].text}" in task "${t.name}" is overdue.`);
                                    }
                                }
                                save();
                                render();
                            }
                        }
                        function completeTask(t) {
                            t.done = true;
                            recordActivity();
                            toast('All steps done — task complete ✓');
                            celebrateTaskDone(t.name);
                            flash(t.id);
                            if (t.recur && t.recur !== 'none') spawnRecurrence(t);
                        }
                        function markDone(id) {
                            const t = tasks.find(t => t.id === id);
                            if (t) {
                                t.done = true; t.steps.forEach(s => s.done = true);
                                save(); recordActivity(); flash(id); setTimeout(render, 80);
                                if (t.recur && t.recur !== 'none') { spawnRecurrence(t); save(); }
                                toast('Task completed ✓');
                                celebrateTaskDone(t.name);
                            }
                        }
                        function markUndone(id) { const t = tasks.find(t => t.id === id); if (t) { t.done = false; save(); render() } }
                        function delTask(id) {
                            const idx = tasks.findIndex(t => t.id === id); if (idx < 0) return;
                            const removedTask = tasks[idx];
                            const removedRemarks = remarks.filter(r => r.taskId === id);
                            const c = document.getElementById('card-' + id);
                            if (c) { c.style.transition = 'all .3s'; c.style.opacity = '0'; c.style.transform = 'translateX(40px) scale(.97)' }
                            setTimeout(() => { tasks = tasks.filter(t => t.id !== id); remarks = remarks.filter(r => r.taskId !== id); save(); saveR(); render(); renderTabs(); }, 260);
                            toast('Task removed', 'Undo', () => {
                                tasks.splice(Math.min(idx, tasks.length), 0, removedTask);
                                remarks = removedRemarks.concat(remarks);
                                save(); saveR(); render(); renderTabs(); toast('Task restored');
                            }, 4500);
                        }
                        function togglePin(id) { const t = tasks.find(t => t.id === id); if (t) { t.pinned = !t.pinned; save(); render(); toast(t.pinned ? 'Pinned to top ★' : 'Unpinned') } }
                        function flash(id) { const c = document.getElementById('card-' + id); if (c) { c.classList.add('flash'); setTimeout(() => c.classList.remove('flash'), 650) } }

function startTimeTracking(id) {
    const t = tasks.find(t => t.id === id);
    if (t) {
        t.timeTrackingStarted = Date.now();
        save();
        render();
        toast('Tracking started ▶');
    }
}

function stopTimeTracking(id) {
    const t = tasks.find(t => t.id === id);
    if (t && t.timeTrackingStarted) {
        const duration = Date.now() - t.timeTrackingStarted;
        t.timeSpent = (t.timeSpent || 0) + duration;
        delete t.timeTrackingStarted;
        save();
        render();
        toast('Logged ' + formatTime(duration) + ' ⏱');
    }
}
                        // ════════════════════════════════════════════
                        // ADD
                        // ════════════════════════════════════════════
                        function openSheet() {
                            document.getElementById('ov').classList.add('open'); document.getElementById('sheet').classList.add('open');
                            setTimeout(() => document.getElementById('f-name').focus(), 350);
                        }
                        function closeSheet() {
                            document.getElementById('ov').classList.remove('open'); document.getElementById('sheet').classList.remove('open');
                            if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
                        }
                        function pick(el, g, v) {
                            document.querySelectorAll('#sheet .opt').forEach(b => { const o = b.getAttribute('onclick'); if (o && o.includes(`'${g}'`) && o.includes("pick(") && !o.includes('pickE')) b.classList.remove('sel') });
                            el.classList.add('sel'); sel[g] = v;
                        }
                        function resetAddForm() {
                            ['f-name', 'f-person', 'f-collab', 'f-s1', 'f-s2', 'f-s3', 'f-sl1', 'f-sl2', 'f-sl3', 'f-deadline', 'f-tag-input'].forEach(i => document.getElementById(i).value = '');
                            document.querySelectorAll('#sheet .opt').forEach(b => b.classList.remove('sel'));
                            sel.urg = null; sel.dmg = null; sel.recur = 'none';
                            formTags = []; renderFormTags();
                            const neverBtn = document.querySelector('#sheet .recur-row .opt');
                            if (neverBtn) neverBtn.classList.add('sel');
                        }
                        function addTask() {
                            const name = document.getElementById('f-name').value.trim();
                            if (!name) { toast('Enter the task name first'); return }
                            if (!sel.urg || !sel.dmg) { toast('Pick urgency and impact'); return }
                            document.getElementById('thinking').classList.add('on');
                            setTimeout(() => {
                                document.getElementById('thinking').classList.remove('on');
                                const sc = score(sel.urg, sel.dmg);
                                tasks.push({
                                    id: Date.now(), name,
                                    person: document.getElementById('f-person').value.trim(),
                                    collab: document.getElementById('f-collab').value.trim(),
                                    urgency: sel.urg, damage: sel.dmg, rank: rank(sc), score: sc,
                                    deadline: document.getElementById('f-deadline').value || null,
                                    recur: sel.recur || 'none',
                                    tags: formTags.slice(),
                                    pinned: false,
                                    steps: [1, 2, 3].map(n => {
                                        const text = document.getElementById('f-s' + n).value.trim();
                                        let link = document.getElementById('f-sl' + n).value.trim() || null;
                                        if (link && !/^https?:\/\//i.test(link)) link = 'https://' + link;
                                        return text ? { text, done: false, deadline: null, link } : null;
                                    }).filter(Boolean),
                                    done: false, addedOn: new Date().toISOString(), order: Date.now()
                                });
                                save();
                                try { localStorage.setItem(LS_ONBOARDED, '1') } catch (e) {}
                                resetAddForm();
                                closeSheet(); render(); renderTabs(); toast('Task added & ranked ✓');
                            }, 650);
                        }

                        // ════════════════════════════════════════════
                        // EDIT
                        // ════════════════════════════════════════════
                        function openEdit(id) {
                            const t = tasks.find(t => t.id === id); if (!t) return; editingId = id;
                            document.getElementById('e-name').value = t.name || '';
                            document.getElementById('e-person').value = t.person || '';
                            document.getElementById('e-collab').value = t.collab || '';
                            document.getElementById('e-deadline').value = t.deadline || '';
                            editSel.urg = t.urgency; editSel.dmg = t.damage; editSel.recur = t.recur || 'none';
                            editFormTags = (t.tags || []).slice();
                            renderEditFormTags();
                            document.querySelectorAll('#e-sheet .opt').forEach(b => {
                                b.classList.remove('sel');
                                const o = b.getAttribute('onclick') || '';
                                const m = o.match(/pickE\(this,'(\w+)','(\w+)'\)/);
                                if (m && ((m[1] === 'urg' && m[2] === t.urgency) || (m[1] === 'dmg' && m[2] === t.damage) || (m[1] === 'recur' && m[2] === (t.recur || 'none')))) b.classList.add('sel');
                            });
                            renderESteps(t.steps || []);
                            document.getElementById('e-ov').classList.add('open'); document.getElementById('e-sheet').classList.add('open');
                        }
                        function renderESteps(steps) {
                            window._eDone = steps.map(s => s.done || false);
                            document.getElementById('e-steps').innerHTML = steps.map((s, i) => 
                                `<div class="estep" id="es-${i}">
                                    <input type="text" id="est-${i}" value="${esc(s.text)}" placeholder="Step ${i+1}"/>
                                    <input type="url" id="esl-${i}" value="${esc(s.link || '')}" placeholder="Link"/>
                                    <button onclick="rmEStep(${i})">✕</button>
                                </div>`
                            ).join('');
                        }
                        function addEStep() {
                            const c = document.getElementById('e-steps'); const i = c.querySelectorAll('.estep').length;
                            window._eDone = window._eDone || []; window._eDone.push(false);
                            const d = document.createElement('div'); d.className = 'estep'; d.id = 'es-' + i;
                            d.innerHTML = `<input type="text" id="est-${i}" placeholder="Step ${i + 1}"/><input type="url" id="esl-${i}" placeholder="Link"/><button onclick="rmEStep(${i})">✕</button>`;
                            c.appendChild(d);
                        }
                        function rmEStep(i) {
                            const el = document.getElementById('es-' + i); if (el) el.remove();
                            if (window._eDone) window._eDone.splice(i, 1);
                            document.getElementById('e-steps').querySelectorAll('.estep').forEach((row, n) => {
                                row.id = 'es-' + n;
                                const t = row.querySelector('input[type=text]'); if (t) t.id = 'est-' + n;
                                const l = row.querySelector('input[type=url]'); if (l) l.id = 'esl-' + n;
                                const b = row.querySelector('button'); if (b) b.setAttribute('onclick', `rmEStep(${n})`);
                            });
                        }
                        function pickE(el, g, v) {
                            document.querySelectorAll(`#e-sheet .opt[onclick*="pickE"][onclick*="'${g}'"]`).forEach(b => b.classList.remove('sel'));
                            el.classList.add('sel'); editSel[g] = v;
                        }
                        function saveEdit() {
                            const t = tasks.find(t => t.id === editingId);
                            if (!t) return;
                            const name = document.getElementById('e-name').value.trim();
                            if (!name) { toast('Task name cannot be empty'); return }
                            const rows = document.getElementById('e-steps').querySelectorAll('.estep');
                                t.steps = Array.from(rows).map((row, i) => {
                                    const textInp = row.querySelector('input[type=text]');
                                    const linkInp = row.querySelector('input[type=url]');
                                    const text = textInp ? textInp.value.trim() : '';
                                    let link = linkInp && linkInp.value.trim() ? linkInp.value.trim() : null;
                                    if (link && !/^https?:\/\//i.test(link)) link = 'https://' + link;
                                    return {
                                        text,
                                        done: (window._eDone || [])[i] || false,
                                        link
                                    };
                                }).filter(s => s.text);
                            t.name = name;
                            t.person = document.getElementById('e-person').value.trim();
                            t.collab = document.getElementById('e-collab').value.trim();
                            t.deadline = document.getElementById('e-deadline').value || null;
                            if (editSel.urg) t.urgency = editSel.urg;
                            if (editSel.dmg) t.damage = editSel.dmg;
                            t.recur = editSel.recur || 'none';
                            t.tags = editFormTags.slice();
                            t.score = score(t.urgency, t.damage);
                            t.rank = rank(t.score);
                            save();
                            closeEdit();
                            render();
                            renderTabs();
                            toast('Task updated ✓');
                        }
                        function closeEdit() {
                            document.getElementById('e-ov').classList.remove('open'); document.getElementById('e-sheet').classList.remove('open');
                            editingId = null;
                            if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
                        }

                        // ════════════════════════════════════════════
                        // REMARKS
                        // ════════════════════════════════════════════
                        function openRemark(id) {
                            remarkingId = id; const t = tasks.find(t => t.id === id);
                            document.getElementById('r-title').textContent = 'Remark — ' + (t ? t.name : '');
                            document.getElementById('r-text').value = '';
                            document.getElementById('r-ov').classList.add('open'); document.getElementById('r-sheet').classList.add('open');
                            setTimeout(() => document.getElementById('r-text').focus(), 350);
                        }
                        function closeRemark() {
                            document.getElementById('r-ov').classList.remove('open'); document.getElementById('r-sheet').classList.remove('open');
                            remarkingId = null;
                            if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
                        }
                        function addRemark() {
                            const txt = document.getElementById('r-text').value.trim(); if (!txt) { toast('Write something first'); return }
                            const t = tasks.find(t => t.id === remarkingId);
                            remarks.unshift({ id: Date.now(), text: txt, taskId: remarkingId || null, taskName: t ? t.name : null, createdAt: new Date().toISOString() });
                            saveR(); closeRemark(); render(); renderTabs(); if (filter === 'remarks') renderRemarks(); toast('Remark saved ✓');
                        }
                        function delRemark(id) { remarks = remarks.filter(r => r.id !== id); saveR(); renderTabs(); if (filter === 'remarks') renderRemarks(); else render() }
                        function renderRemarks() {
                            const v = document.getElementById('remarks-view');
                            if (!remarks.length) { v.innerHTML = `<div class="empty"><div class="ic">◫</div><div class="h">No remarks yet</div><div class="s">Add context or follow-ups from any task's Remark button.</div></div>`; return }
                            v.innerHTML = remarks.map((r, i) => {
                                const d = new Date(r.createdAt);
                                const ds = d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) + ' · ' + d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
                                return `<div class="rmk-card" style="animation-delay:${i * 40}ms">${r.taskName ? `<div class="rmk-link">› ${esc(r.taskName)}</div>` : '<span class="rmk-gen">General</span>'}
                                <div class="rmk-body">${esc(r.text)}</div><div class="rmk-time">${ds}</div>
                                <div class="actions" style="margin-top:10px"><button class="act del" onclick="delRemark(${r.id})">× Delete</button></div></div>`;
                                        }).join('');
                        }
                        function renderTimeReport() {
                            const reportData = {};
                            tasks.forEach(t => {
                                if (t.timeSpent) {
                                    t.tags.forEach(tag => {
                                        if (!reportData[tag]) {
                                            reportData[tag] = 0;
                                        }
                                        reportData[tag] += t.timeSpent;
                                    });
                                }
                            });

                            const v = document.getElementById('time-report-view');
                            if (Object.keys(reportData).length === 0) {
                                v.innerHTML = `<div class="empty"><div class="ic">◔</div><div class="h">No time tracked yet</div><div class="s">Start tracking time on tasks to see a report here.</div></div>`;
                                return;
                            }

                            v.innerHTML = `
                                <div class="report-header">Time Report</div>
                                <div class="report-table">
                                    <div class="report-row report-header-row">
                                        <div class="report-cell">Tag</div>
                                        <div class="report-cell">Time Spent</div>
                                    </div>
                                    ${Object.entries(reportData).map(([tag, time]) => `
                                        <div class="report-row">
                                            <div class="report-cell">#${esc(tag)}</div>
                                            <div class="report-cell">${formatTime(time)}</div>
                                        </div>
                                    `).join('')}
                                </div>
                            `;
                        }

                        function renderMatrix() {
                            const v = document.getElementById('matrix-view');
                            const open = tasks.filter(t => !t.done);
                            // Urgent = critical/high effective rank OR due within 3 days; Important = severe/moderate damage
                            const isUrgent = t => ['critical', 'high'].includes(effRank(t));
                            const isImportant = t => t.damage === 'severe' || t.damage === 'moderate';

                            const q1 = open.filter(t => isUrgent(t) && isImportant(t));   // Do first
                            const q2 = open.filter(t => !isUrgent(t) && isImportant(t));  // Schedule
                            const q3 = open.filter(t => isUrgent(t) && !isImportant(t));  // Delegate/quick
                            const q4 = open.filter(t => !isUrgent(t) && !isImportant(t)); // Eliminate/later

                            const quad = (title, icon, cls, list) => `
                                <div class="mx-quad ${cls}">
                                    <h4>${icon} ${title} <span style="color:var(--ink4);font-weight:400">(${list.length})</span></h4>
                                    ${list.length ? list.slice(0, 6).map(t => `<div class="mx-item" onclick="openEdit(${t.id})">${esc(t.name)}</div>`).join('') : '<div class="mx-empty">Nothing here</div>'}
                                    ${list.length > 6 ? `<div class="mx-empty">+${list.length - 6} more</div>` : ''}
                                </div>`;

                            v.innerHTML = quad('Do First', '🔥', 'q1', q1) + quad('Schedule', '📅', 'q2', q2) +
                                        quad('Quick Wins', '⚡', 'q3', q3) + quad('Later', '💤', 'q4', q4);
                        }

                        function renderWeeklyReview() {
                        const v = document.getElementById('review-view');
                        const days = last7Days();
                        const weekMin = days.reduce((sum, d) => sum + (focusLog[d.key] || 0), 0);
                        const doneThisWeek = tasks.filter(t => t.done && t.addedOn && new Date(t.addedOn) >= new Date(Date.now() - 7 * 864e5)).length;
                        const tagCounts = {};
                        tasks.forEach(t => (t.tags || []).forEach(tg => tagCounts[tg] = (tagCounts[tg] || 0) + 1));
                        const topTags = Object.entries(tagCounts).sort((a, b) => b[1] - a[1]).slice(0, 5);

                        v.innerHTML = `
                            <div class="mx-quad" style="grid-column:1/-1">
                                <h4>📊 This Week at a Glance</h4>
                                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:10px">
                                    <div class="hist-tot box"><div class="num">${weekMin}</div><div class="lbl">Focus min</div></div>
                                    <div class="hist-tot box"><div class="num">${doneThisWeek}</div><div class="lbl">Tasks done</div></div>
                                    <div class="hist-tot box"><div class="num">${streakData.count}</div><div class="lbl">Day streak</div></div>
                                </div>
                            </div>
                            <div class="mx-quad" style="grid-column:1/-1;margin-top:12px">
                                <h4>🏷 Top tags this period</h4>
                                ${topTags.length ? topTags.map(([tg, c]) => `<div class="mx-item">#${esc(tg)} — ${c} task${c === 1 ? '' : 's'}</div>`).join('') : '<div class="mx-empty">No tags used yet</div>'}
                            </div>`;
                    }

                        // ════════════════════════════════════════════
                        // TASK TEMPLATES
                        // ════════════════════════════════════════════
                        const LS_TEMPLATES = 'taskvel_templates_v1';
                        let templates = [];
                        function loadTemplates() {
                            try { templates = JSON.parse(localStorage.getItem(LS_TEMPLATES)) || []; } catch (e) { templates = []; }
                        }
                        function saveTemplates() { localStorage.setItem(LS_TEMPLATES, JSON.stringify(templates)); }

                        function saveAsTemplate() {
                            const t = tasks.find(t => t.id === editingId); if (!t) return;
                            const name = prompt('Template name:', t.name);
                            if (!name) return;
                            templates.push({
                                id: Date.now(), name,
                                urgency: t.urgency, damage: t.damage, tags: (t.tags || []).slice(),
                                recur: t.recur, steps: (t.steps || []).map(s => ({ text: s.text, link: s.link || null }))
                            });
                            saveTemplates();
                            toast('Template saved ✓');
                        }
                        function renderTemplates() {
                            const el = document.getElementById('tmpl-list');
                            if (!templates.length) { el.innerHTML = `<div class="notif-empty">No templates yet. Save one from any task's edit screen.</div>`; return; }
                            el.innerHTML = templates.map(tm => `
                                <div class="notif-item">
                                    <div class="body">
                                        <div class="msg"><b>${esc(tm.name)}</b> — ${tm.urgency}/${tm.damage}, ${tm.steps.length} step${tm.steps.length === 1 ? '' : 's'}</div>
                                    </div>
                                    <button class="rmk-x" onclick="useTemplate(${tm.id})" title="Use">▶</button>
                                    <button class="rmk-x" onclick="deleteTemplate(${tm.id})" title="Delete">✕</button>
                                </div>`).join('');
                        }
                        function useTemplate(id) {
                            const tm = templates.find(t => t.id === id); if (!tm) return;
                            closePanel('tmpl-panel');
                            openSheet();
                            setTimeout(() => {
                                document.getElementById('f-name').value = tm.name;
                                formTags = (tm.tags || []).slice(); renderFormTags();
                                sel.recur = tm.recur || 'none';
                                document.querySelectorAll('#sheet .recur-row .opt').forEach(b => b.classList.remove('sel'));
                                const idx = ['none', 'daily', 'weekly', 'monthly'].indexOf(sel.recur);
                                const btns = document.querySelectorAll('#sheet .recur-row .opt');
                                if (btns[idx]) btns[idx].classList.add('sel');
                                ['urg', 'dmg'].forEach(g => {
                                    const val = g === 'urg' ? tm.urgency : tm.damage;
                                    document.querySelectorAll(`#sheet .opt[onclick*="'${g}'"][onclick*="'${val}'"]`).forEach(b => { b.classList.add('sel'); sel[g] = val; });
                                });
                                (tm.steps || []).forEach((s, i) => {
                                    const inp = document.getElementById('f-s' + (i + 1)); if (inp) inp.value = s.text;
                                    const linp = document.getElementById('f-sl' + (i + 1)); if (linp) linp.value = s.link || '';
                                });
                            }, 400);
                        }
                        function deleteTemplate(id) {
                            templates = templates.filter(t => t.id !== id);
                            saveTemplates(); renderTemplates();
                            toast('Template deleted');
                        }

                        

                        function formatTime(ms) {
                            const seconds = Math.floor(ms / 1000);
                            const minutes = Math.floor(seconds / 60);
                            const hours = Math.floor(minutes / 60);
                            const days = Math.floor(hours / 24);

                            if (days > 0) {
                                return `${days}d ${hours % 24}h`;
                            } else if (hours > 0) {
                                return `${hours}h ${minutes % 60}m`;
                            } else {
                                return `${minutes}m`;
                            }
                        }

                        // ════════════════════════════════════════════
                        // FOCUS TIMER (Pomodoro)
                        // ════════════════════════════════════════════
                        const MODES = [{ f: 25, b: 5, label: '25 / 5' }, { f: 50, b: 10, label: '50 / 10' }, { f: 15, b: 3, label: '15 / 3' }];
                        let modeIdx = 0, isBreak = false, running = false, total = MODES[0].f * 60, remaining = MODES[0].f * 60, focusTaskId = null;
                        let tHandle = null, endAt = 0, creditedMins = 0;
                        const RING_LEN = 251.3;

                        function paintTimer() {
                            const r = Math.max(0, Math.ceil(remaining));
                            const m = String(Math.floor(r / 60)).padStart(2, '0'), s = String(r % 60).padStart(2, '0');
                            document.getElementById('t-time').textContent = `${m}:${s}`;
                            document.getElementById('t-mode').textContent = isBreak ? 'Break' : 'Focus';
                            document.getElementById('ring').style.strokeDashoffset = RING_LEN * (1 - r / total);
                            document.getElementById('t-cycle').textContent = MODES[modeIdx].label;
                        }
                        function tick() {
                            remaining = (endAt - Date.now()) / 1000;
                            if (!isBreak) {
                                const elapsed = Math.floor(total - Math.max(0, remaining));
                                const mins = Math.floor(elapsed / 60);
                                if (mins > creditedMins) {
                                    const delta = mins - creditedMins;
                                    focusMins += delta; creditedMins = mins; saveF();
                                    for (let i = 0; i < delta; i++) logFocusMinute();
                                    document.getElementById('s-focus').textContent = focusLog[todayKey()] || 0;
                                    document.getElementById('focus-today-inline').textContent = (focusLog[todayKey()] || 0) + 'm today';
                                }
                            }
                            if (remaining <= 0) {
                                chime();
                                if (!isBreak) {
                                    pushNotification('◉', 'Focus session complete — time for a break.');
                                    showCelebration('◉', 'Focus session complete!', `Great session — take a ${MODES[modeIdx].b} minute break before the next one.`, 5000);
                                    notifyOS('Taskvel — focus session complete', `Time for a ${MODES[modeIdx].b} minute break.`);
                                    isBreak = true; total = MODES[modeIdx].b * 60;
                                } else {
                                    pushNotification('▶', 'Break over — back to focus.');
                                    showCelebration('▶', 'Break\u2019s over!', 'Ready when you are — start your next focus session.', 5000);
                                    notifyOS('Taskvel — break\u2019s over', 'Ready to start your next focus session.');
                                    isBreak = false; total = MODES[modeIdx].f * 60;
                                }
                                remaining = total; endAt = Date.now() + total * 1000; creditedMins = 0;
                                if (isBreak) document.body.classList.remove('focus-mode');
                                else document.body.classList.add('focus-mode');
                            }
                            paintTimer();
                        }
                        function toggleTimer() {
                            const btn = document.getElementById('t-toggle');
                            if (!running) {
                                running = true; endAt = Date.now() + remaining * 1000;
                                btn.innerHTML = '⏸ Pause'; btn.classList.add('pulse');
                                clearInterval(tHandle); tHandle = setInterval(tick, 250);
                                if (!isBreak) document.body.classList.add('focus-mode');
                            } else {
                                running = false; remaining = Math.max(0, (endAt - Date.now()) / 1000);
                                btn.innerHTML = '▶ Resume'; btn.classList.remove('pulse'); clearInterval(tHandle); paintTimer();
                                document.body.classList.remove('focus-mode');
                            }
                        }
                        function resetTimer() {
                            running = false; clearInterval(tHandle); isBreak = false; total = MODES[modeIdx].f * 60; remaining = total; creditedMins = 0;
                            const btn = document.getElementById('t-toggle'); btn.innerHTML = '▶ Start'; btn.classList.remove('pulse'); paintTimer();
                        }
                        function cycleMode() { modeIdx = (modeIdx + 1) % MODES.length; resetTimer(); toast('Timer set to ' + MODES[modeIdx].label) }
                        function setFocusTask(id) {
                            const t = tasks.find(t => t.id === id); if (!t) return; focusTaskId = id;
                            document.getElementById('t-task').textContent = t.name; toast('Focusing on: ' + t.name);
                            if (!running) toggleTimer();
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                        function chime() {
                            try {
                                const AC = window.AudioContext || window.webkitAudioContext; if (!AC) return;
                                const ctx = new AC();
                                [880, 1320].forEach((f, i) => {
                                    const o = ctx.createOscillator(), g = ctx.createGain();
                                    o.type = 'sine'; o.frequency.value = f; o.connect(g); g.connect(ctx.destination);
                                    const t0 = ctx.currentTime + i * 0.18;
                                    g.gain.setValueAtTime(0, t0); g.gain.linearRampToValueAtTime(0.18, t0 + 0.02);
                                    g.gain.exponentialRampToValueAtTime(0.0001, t0 + 0.35);
                                    o.start(t0); o.stop(t0 + 0.36);
                                });
                                setTimeout(() => ctx.close && ctx.close(), 900);
                            } catch (e) {}
                        }
                        document.addEventListener('visibilitychange', () => { if (!document.hidden && running) tick() });

                        // ════════════════════════════════════════════
                        // TOAST
                        // ════════════════════════════════════════════
                        let toastH = null, _toastCb = null;
                        function toast(msg, actionLabel, cb, dur) {
                            const t = document.getElementById('toast');
                            document.getElementById('toast-msg').textContent = msg;
                            const ab = document.getElementById('toast-action');
                            if (actionLabel && cb) { ab.textContent = actionLabel; ab.style.display = ''; _toastCb = cb; }
                            else { ab.style.display = 'none'; _toastCb = null; }
                            t.classList.add('show'); clearTimeout(toastH);
                            toastH = setTimeout(() => { t.classList.remove('show'); _toastCb = null; }, dur || 2400);
                        }
                        function toastAction() { if (_toastCb) { const cb = _toastCb; _toastCb = null; document.getElementById('toast').classList.remove('show'); clearTimeout(toastH); cb(); } }

                        // ════════════════════════════════════════════
                        // CELEBRATION OVERLAY — unmissable on-screen confirmation, used for:
                        //  1) Pomodoro focus/break session completing
                        //  2) Completing a task (with a rotating motivating message)
                        // This is deliberately a centered modal-style card (not just a toast) because
                        // toasts auto-dismiss in ~2s and are easy to miss if the user looked away from
                        // the screen — exactly the moment a timer finishing matters most.
                        // ════════════════════════════════════════════
                        let celebrateTimer = null;
                        function showCelebration(icon, title, sub, autoDismissMs) {
                            const overlay = document.getElementById('celebrate-overlay');
                            const card = document.getElementById('celebrate-card');
                            document.getElementById('celebrate-icon').textContent = icon;
                            document.getElementById('celebrate-title').textContent = title;
                            document.getElementById('celebrate-sub').textContent = sub;
                            overlay.classList.add('show');
                            card.classList.add('show');
                            clearTimeout(celebrateTimer);
                            if (autoDismissMs) celebrateTimer = setTimeout(dismissCelebration, autoDismissMs);
                        }
                        function dismissCelebration() {
                            document.getElementById('celebrate-overlay').classList.remove('show');
                            document.getElementById('celebrate-card').classList.remove('show');
                            clearTimeout(celebrateTimer);
                        }

                        // Feel-good messages shown when a task is completed — picked at random so
                        // finishing tasks doesn't feel repetitive over time.
                        const TASK_DONE_MESSAGES = [
                            'One less thing weighing on you. Nice work.',
                            "That's real progress — keep the momentum going.",
                            'Done is better than perfect. Onto the next one.',
                            "You're clearing the board. Well earned.",
                            'Small win, real win. Stack another one.',
                            "That's how the list gets shorter. Great job.",
                            'Logged and done. Your future self thanks you.'
                        ];
                        function celebrateTaskDone(taskName) {
                            const msg = TASK_DONE_MESSAGES[Math.floor(Math.random() * TASK_DONE_MESSAGES.length)];
                            showCelebration('✓', 'Task complete!', taskName ? `"${taskName}" — ${msg}` : msg, 3200);
                            notifyOS('Task complete ✓', taskName ? `"${taskName}" — ${msg}` : msg);
                        }

                        // Browser-native notification (in addition to the in-app overlay) so the user
                        // gets a system-level alert even if the Taskvel tab isn't focused. Permission
                        // is requested lazily on first use rather than on page load, to avoid an
                        // unsolicited permission prompt before the user has interacted with the app.
                        let notifPermissionAsked = false;
                        function notifyOS(title, body) {
                            try {
                                if (!('Notification' in window)) return;
                                if (Notification.permission === 'granted') {
                                    new Notification(title, { body, icon: undefined, silent: false });
                                } else if (Notification.permission !== 'denied' && !notifPermissionAsked) {
                                    notifPermissionAsked = true;
                                    Notification.requestPermission().then(perm => {
                                        if (perm === 'granted') new Notification(title, { body });
                                    });
                                }
                            } catch (e) { /* notifications unsupported or blocked — overlay + toast still cover it */ }
                        }

                        // ════════════════════════════════════════════
                        // KEYBOARD
                        // ════════════════════════════════════════════
                        document.addEventListener('keydown', e => {
                            if (e.key === 'Escape') {
                                closeSheet(); closeEdit(); closeRemark(); closeAllPanels(); dismissCelebration(); closeCmdk();
                                if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
                                return;
                            }
                            const el = e.target;
                            const tag = (el && el.tagName || '').toLowerCase();
                            const isField = tag === 'input' || tag === 'textarea' || (el && el.isContentEditable);
                            let activelyTyping = false;
                            if (isField) {
                                if (el.id === 'search') activelyTyping = true;
                                else if (el.closest && el.closest('.sheet.open')) activelyTyping = true;
                                else { if (el.blur) el.blur(); }
                            }
                            if (activelyTyping) return;

                            if (e.key === 'n' || e.key === 'N') { e.preventDefault(); openSheet() }
                            else if (e.key === '/') { e.preventDefault(); const s = document.getElementById('search'); s.focus(); s.select && s.select() }
                            else if (e.key === 't' || e.key === 'T') { e.preventDefault(); toggleTheme() }
                            else if (e.code === 'Space' || e.key === ' ') {
                                e.preventDefault();
                                if (tag === 'button' && el.blur) el.blur();
                                toggleTimer();
                            } else if (e.key === 'ArrowDown' && filter !== 'remarks') { e.preventDefault(); kbMove(1); }
                            else if (e.key === 'ArrowUp' && filter !== 'remarks') { e.preventDefault(); kbMove(-1); }
                            else if (e.key === 'Enter' && kbFocusIdx >= 0) { e.preventDefault(); kbAction('open'); }
                            else if (e.key === 'd' || e.key === 'D') { kbAction('done'); }
                            else if (e.key === 'Delete' || e.key === 'Backspace') { if (kbFocusIdx >= 0) { e.preventDefault(); kbAction('del'); } }
                        });

                        // ════════════════════════════════════════════
                        // COMMAND PALETTE (⌘K / Ctrl+K)
                        // ════════════════════════════════════════════
                        const CMDK_ACTIONS = [
                            { label: '+ Add new task', icon: '＋', run: () => openSheet() },
                            { label: 'Toggle dark / light mode', icon: '☾', run: () => toggleTheme() },
                            { label: 'Start focus timer', icon: '◉', run: () => { if (!running) toggleTimer(); } },
                            { label: 'Reset focus timer', icon: '↺', run: () => resetTimer() },
                            { label: 'Export as CSV', icon: '▦', run: () => exportCSV() },
                            { label: 'Export as PDF', icon: '▥', run: () => exportPDF() },
                            { label: 'Open notifications', icon: '◔', run: () => togglePanel('notif-panel') },
                            { label: 'Open focus history', icon: '▤', run: () => togglePanel('hist-panel') },
                            { label: 'Change colour theme', icon: '◑', run: () => togglePanel('palette-panel') },
                            { label: 'Go to All tasks', icon: '▤', run: () => setFilter('all') },
                            { label: 'Go to Today', icon: '☀', run: () => setFilter('today') },
                            { label: 'Go to Pending', icon: '○', run: () => setFilter('pending') },
                            { label: 'Go to Done', icon: '✓', run: () => setFilter('done') },
                            { label: 'Go to Remarks', icon: '❝', run: () => setFilter('remarks') },
                            { label: 'Go to Time Report', icon: '⏱', run: () => setFilter('time-report') },
                            { label: 'Focus search box', icon: '⌕', run: () => { const s = document.getElementById('search'); s.focus(); s.select(); } },
                        ];

                        function openCmdk() {
                            document.getElementById('cmdk-ov').classList.add('open');
                            document.getElementById('cmdk-sheet').classList.add('open');
                            const inp = document.getElementById('cmdk-input');
                            inp.value = '';
                            renderCmdkResults(CMDK_ACTIONS);
                            setTimeout(() => inp.focus(), 200);
                        }
                        function closeCmdk() {
                            document.getElementById('cmdk-ov').classList.remove('open');
                            document.getElementById('cmdk-sheet').classList.remove('open');
                        }
                        function filterCmdk() {
                            const q = document.getElementById('cmdk-input').value.trim().toLowerCase();
                            const matches = q ? CMDK_ACTIONS.filter(a => a.label.toLowerCase().includes(q)) : CMDK_ACTIONS;
                            renderCmdkResults(matches);
                        }
                        function renderCmdkResults(list) {
                            const el = document.getElementById('cmdk-results');
                            if (!list.length) { el.innerHTML = `<div class="notif-empty">No matching commands</div>`; return; }
                            el.innerHTML = list.map((a, i) => `<button class="act" style="justify-content:flex-start;width:100%;padding:11px 14px" onclick="runCmdk(${i})" data-idx="${i}">${a.icon}&nbsp;&nbsp;${esc(a.label)}</button>`).join('');
                            window._cmdkList = list;
                        }
                        function runCmdk(i) {
                            const action = window._cmdkList[i];
                            closeCmdk();
                            if (action) setTimeout(action.run, 150);
                        }
                        document.addEventListener('keydown', e => {
                            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                                e.preventDefault();
                                const isOpen = document.getElementById('cmdk-sheet').classList.contains('open');
                                if (isOpen) closeCmdk(); else openCmdk();
                            }
                        });

                        // ════════════════════════════════════════════
                        // INIT
                        // ════════════════════════════════════════════
                        function init() {
                             load(); loadTemplates(); renderTabs(); render(); updateStreakUI(); paintTimer(); tickClock(); setInterval(tickClock, 1000);
                                setTimeout(maybeShowBriefing, 600);
                            applyThemeIcon(); markActiveSwatch();
                            sweepDeadlines(); setInterval(sweepDeadlines, 60 * 60 * 1000);
                            updateNotifDot();
                            renderHistory();
                            paintOnboardSlide(); initOnboardSwipe();
                            const neverBtn = document.querySelector('#sheet .recur-row .opt');
                            if (neverBtn) neverBtn.classList.add('sel');

                            // Track visitor count
                            const visitorCountKey = 'taskvel_visitor_count';
                            let visitorCount = parseInt(localStorage.getItem(visitorCountKey)) || 0;
                            visitorCount++;
                            localStorage.setItem(visitorCountKey, visitorCount);
                            console.log(`Total visitors: ${visitorCount}`);
                        }
                        init();
                        if ('serviceWorker' in navigator) {
                            window.addEventListener('load', () => {
                                navigator.serviceWorker.register('sw.js').catch(() => {});
                            });
                        }
    </script>
</body>

</html>