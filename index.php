<?php
session_start();
$loggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChronoX — Intelligent Attendance & Time Tracking</title>
    <meta name="description" content="Next-generation biometric attendance management. Track, analyze, and optimize your workforce in real time.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="landing.css">
</head>
<body>

<!-- ===== ANIMATED BACKGROUND ===== -->
<div class="bg-canvas-wrap">
    <canvas id="heroCanvas"></canvas>
</div>
<div class="grain"></div>

<!-- ===== NAVBAR ===== -->
<nav class="nav" id="nav">
    <div class="nav-inner">
        <a href="index.php" class="logo">
            <span class="logo-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6a6 6 0 1 0 6 6"/><circle cx="12" cy="12" r="2"/>
                </svg>
            </span>
            <span class="logo-text">Chrono<span>X</span></span>
        </a>
        <div class="nav-links" id="navLinks">
            <a href="#features">Features</a>
            <a href="#showcase">Platform</a>
            <a href="#how">How it works</a>
            <a href="#stats">Results</a>
        </div>
        <div class="nav-cta">
            <?php if ($loggedIn): ?>
                <a href="dashboard/dashboard.php" class="btn btn-ghost">Dashboard</a>
                <a href="logout.php" class="btn btn-primary"><span>Logout</span></a>
            <?php else: ?>
                <a href="login.php" class="btn btn-ghost">Sign in</a>
                <a href="login.php" class="btn btn-primary"><span>Get Started</span></a>
            <?php endif; ?>
        </div>
        <button class="nav-burger" id="navBurger" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
</nav>


<!-- ===== HERO ===== -->
<header class="hero">
    <div class="hero-inner">
        <div class="pill reveal" data-reveal>
            <span class="pill-dot"></span>
            Powered by ZKTeco Biometric Engine
        </div>
        <h1 class="hero-title reveal" data-reveal>
            The future of<br>
            <span class="grad-text">workforce time</span><br>
            starts here.
        </h1>
        <p class="hero-sub reveal" data-reveal>
            ChronoX turns raw biometric punches into crystal-clear insight.
            Track attendance, spot patterns, and run your team with precision —
            all from one breathtaking dashboard.
        </p>
        <div class="hero-actions reveal" data-reveal>
            <a href="<?= $loggedIn ? 'dashboard/dashboard.php' : 'login.php' ?>" class="btn btn-primary btn-lg">
                <span>Launch Dashboard</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <a href="#showcase" class="btn btn-glass btn-lg">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                <span>See it in action</span>
            </a>
        </div>
        <div class="hero-badges reveal" data-reveal>
            <div class="hbadge"><strong>99.9%</strong><span>Uptime</span></div>
            <div class="hbadge-div"></div>
            <div class="hbadge"><strong>&lt;1s</strong><span>Sync latency</span></div>
            <div class="hbadge-div"></div>
            <div class="hbadge"><strong>256-bit</strong><span>Encryption</span></div>
        </div>
    </div>
    <div class="hero-scroll">
        <span class="mouse"><span class="wheel"></span></span>
        <span>Scroll to explore</span>
    </div>
</header>


<!-- ===== LOGO / TRUST STRIP ===== -->
<section class="marquee-wrap reveal" data-reveal>
    <div class="marquee">
        <div class="marquee-track">
            <?php
            $words = ['BIOMETRIC','REAL-TIME','SECURE','SCALABLE','CLOUD-READY','ANALYTICS','AUTOMATED','PRECISE'];
            for ($k = 0; $k < 2; $k++):
                foreach ($words as $w):
            ?>
                <span><?= $w ?></span><span class="star">✦</span>
            <?php
                endforeach;
            endfor;
            ?>
        </div>
    </div>
</section>

<!-- ===== FEATURES ===== -->
<section class="section" id="features">
    <div class="container">
        <div class="section-head reveal" data-reveal>
            <span class="eyebrow">Capabilities</span>
            <h2>Everything you need to master <span class="grad-text">time</span>.</h2>
            <p>A complete toolkit engineered for HR teams, operations leaders, and modern businesses.</p>
        </div>
        <div class="feature-grid">
            <article class="feature-card tilt reveal" data-reveal>
                <div class="fc-glow"></div>
                <div class="fc-icon i-blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v1a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/><path d="M19 10v1a7 7 0 0 1-14 0v-1"/><path d="M12 18v4"/></svg>
                </div>
                <h3>Biometric Sync</h3>
                <p>Pull punches from ZKTeco devices in real time with automatic de-duplication and zero manual entry.</p>
            </article>
            <article class="feature-card tilt reveal" data-reveal>
                <div class="fc-glow"></div>
                <div class="fc-icon i-violet">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>
                </div>
                <h3>Live Analytics</h3>
                <p>Beautiful, interactive charts reveal attendance trends, late arrivals, and department performance instantly.</p>
            </article>
            <article class="feature-card tilt reveal" data-reveal>
                <div class="fc-glow"></div>
                <div class="fc-icon i-cyan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5l-8-3z"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
                <h3>Secure by Design</h3>
                <p>Session-based auth, hashed credentials, and role controls keep your workforce data locked down.</p>
            </article>
            <article class="feature-card tilt reveal" data-reveal>
                <div class="fc-glow"></div>
                <div class="fc-icon i-green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                </div>
                <h3>One-click Exports</h3>
                <p>Generate CSV reports for payroll and audits in seconds — attendance or full employee rosters.</p>
            </article>
            <article class="feature-card tilt reveal" data-reveal>
                <div class="fc-glow"></div>
                <div class="fc-icon i-amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <h3>Smart Scheduling</h3>
                <p>Automatic late detection, working-hour calculation, and status flags — no spreadsheets required.</p>
            </article>
            <article class="feature-card tilt reveal" data-reveal>
                <div class="fc-glow"></div>
                <div class="fc-icon i-rose">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>Team Management</h3>
                <p>Add, edit, and organize employees by department and role with a delightfully fast interface.</p>
            </article>
        </div>
    </div>
</section>


<!-- ===== SHOWCASE ===== -->
<section class="section showcase" id="showcase">
    <div class="container">
        <div class="section-head reveal" data-reveal>
            <span class="eyebrow">The Platform</span>
            <h2>A dashboard that feels like <span class="grad-text">the future</span>.</h2>
            <p>Every pixel engineered for clarity. Real-time data, gorgeous visuals, zero clutter.</p>
        </div>
        <div class="showcase-stage reveal" data-reveal>
            <div class="showcase-glow"></div>
            <div class="browser" id="browser3d">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <div class="browser-url">app.chronox.io/dashboard</div>
                </div>
                <div class="browser-body">
                    <div class="mock-kpis">
                        <div class="mock-kpi"><span class="mk-ic i-blue"></span><div><b>248</b><i>Employees</i></div></div>
                        <div class="mock-kpi"><span class="mk-ic i-green"></span><div><b>231</b><i>Present</i></div></div>
                        <div class="mock-kpi"><span class="mk-ic i-rose"></span><div><b>17</b><i>Absent</i></div></div>
                        <div class="mock-kpi"><span class="mk-ic i-amber"></span><div><b>93%</b><i>Rate</i></div></div>
                    </div>
                    <div class="mock-charts">
                        <div class="mock-chart">
                            <div class="mc-head"><b>Weekly Attendance</b></div>
                            <svg class="spark" viewBox="0 0 320 110" preserveAspectRatio="none">
                                <defs><linearGradient id="g1" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="rgba(99,102,241,.5)"/>
                                    <stop offset="100%" stop-color="rgba(99,102,241,0)"/>
                                </linearGradient></defs>
                                <path class="spark-fill" d="M0 80 L45 60 L90 68 L135 35 L180 45 L225 20 L270 30 L320 15 L320 110 L0 110 Z" fill="url(#g1)"/>
                                <path class="spark-line" d="M0 80 L45 60 L90 68 L135 35 L180 45 L225 20 L270 30 L320 15" fill="none" stroke="#818CF8" stroke-width="2.5"/>
                            </svg>
                        </div>
                        <div class="mock-donut">
                            <div class="mc-head"><b>Today</b></div>
                            <div class="donut"><span>93%</span></div>
                        </div>
                    </div>
                    <div class="mock-rows">
                        <div class="mock-row"><span class="av a1">A</span><span class="ln"></span><span class="tag t-green">Present</span></div>
                        <div class="mock-row"><span class="av a2">M</span><span class="ln"></span><span class="tag t-amber">Late</span></div>
                        <div class="mock-row"><span class="av a3">S</span><span class="ln"></span><span class="tag t-green">Present</span></div>
                    </div>
                </div>
            </div>
            <div class="float-card fc-1"><b>+12%</b><span>this week</span></div>
            <div class="float-card fc-2"><span class="live"></span>Live sync</div>
        </div>
    </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="section" id="how">
    <div class="container">
        <div class="section-head reveal" data-reveal>
            <span class="eyebrow">Workflow</span>
            <h2>Live in <span class="grad-text">three steps</span>.</h2>
            <p>From device to dashboard in minutes — no complex setup.</p>
        </div>
        <div class="steps">
            <div class="step reveal" data-reveal>
                <div class="step-num">01</div>
                <h3>Connect your device</h3>
                <p>Point ChronoX at your ZKTeco device's IP. We handle the handshake and pull every punch automatically.</p>
            </div>
            <div class="step-line"></div>
            <div class="step reveal" data-reveal>
                <div class="step-num">02</div>
                <h3>Organize your team</h3>
                <p>Register employees, assign departments and positions, and the system maps punches to people.</p>
            </div>
            <div class="step-line"></div>
            <div class="step reveal" data-reveal>
                <div class="step-num">03</div>
                <h3>Analyze & act</h3>
                <p>Watch attendance flow in real time, catch late arrivals, and export reports whenever you need them.</p>
            </div>
        </div>
    </div>
</section>


<!-- ===== STATS ===== -->
<section class="section stats" id="stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat reveal" data-reveal>
                <div class="stat-num" data-count="99.9" data-suffix="%">0</div>
                <div class="stat-label">System uptime</div>
            </div>
            <div class="stat reveal" data-reveal>
                <div class="stat-num" data-count="500" data-suffix="K+">0</div>
                <div class="stat-label">Punches processed</div>
            </div>
            <div class="stat reveal" data-reveal>
                <div class="stat-num" data-count="40" data-suffix="%">0</div>
                <div class="stat-label">Less admin time</div>
            </div>
            <div class="stat reveal" data-reveal>
                <div class="stat-num" data-count="24" data-suffix="/7">0</div>
                <div class="stat-label">Real-time tracking</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA ===== -->
<section class="section">
    <div class="container">
        <div class="cta reveal" data-reveal>
            <div class="cta-orb"></div>
            <div class="cta-content">
                <h2>Ready to transform<br>how you track time?</h2>
                <p>Join the teams running smarter with ChronoX. Set up in minutes.</p>
                <a href="<?= $loggedIn ? 'dashboard/dashboard.php' : 'login.php' ?>" class="btn btn-primary btn-lg">
                    <span>Get Started Free</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <a href="index.php" class="logo">
                <span class="logo-mark">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6a6 6 0 1 0 6 6"/><circle cx="12" cy="12" r="2"/></svg>
                </span>
                <span class="logo-text">Chrono<span>X</span></span>
            </a>
            <p>Intelligent attendance & time tracking, powered by biometric precision.</p>
        </div>
        <div class="footer-cols">
            <div class="footer-col">
                <h4>Product</h4>
                <a href="#features">Features</a>
                <a href="#showcase">Platform</a>
                <a href="#how">How it works</a>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <a href="#">About</a>
                <a href="#">Careers</a>
                <a href="#">Contact</a>
            </div>
            <div class="footer-col">
                <h4>Access</h4>
                <a href="login.php">Sign in</a>
                <a href="login.php">Get Started</a>
                <a href="install.php">Setup</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom container">
        <span>© <?= date('Y') ?> ChronoX. All rights reserved.</span>
        <span>Powered by ZKTeco &middot; Built with precision.</span>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="landing.js"></script>
</body>
</html>
