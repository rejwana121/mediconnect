<?php require_once 'includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediConnect - Your Health, Our Priority</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --primary: #00897B;
            --primary-dark: #00695C;
            --secondary: #1E88E5;
            --accent: #FF7043;
            --dark: #0D1B2A;
            --text: #2D3748;
            --muted: #718096;
            --bg: #F7FAFC;
        }
        body { font-family:'Inter',sans-serif; color:var(--text); background:#fff; overflow-x:hidden; }

        /* ─── NAVBAR ─── */
        .navbar-custom {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,137,123,0.1);
            padding: 14px 0;
            transition: all 0.3s;
        }
        .navbar-custom.scrolled { box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .navbar-brand { font-weight:800; font-size:22px; color:var(--primary)!important; letter-spacing:-0.5px; }
        .navbar-brand span { color:var(--secondary); }
        .navbar-nav .nav-link { font-weight:500; color:#4A5568; padding:8px 14px!important; border-radius:8px; transition:all 0.2s; }
        .navbar-nav .nav-link:hover { color:var(--primary); background:rgba(0,137,123,0.06); }
        .btn-login { border:2px solid var(--primary); color:var(--primary); border-radius:50px; padding:8px 22px; font-weight:600; transition:all 0.2s; }
        .btn-login:hover { background:var(--primary); color:white; }
        .btn-getstarted { background:linear-gradient(135deg,var(--primary),var(--secondary)); color:white; border:none; border-radius:50px; padding:8px 22px; font-weight:600; box-shadow:0 4px 14px rgba(0,137,123,0.3); transition:all 0.2s; }
        .btn-getstarted:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(0,137,123,0.4); color:white; }

        /* ─── HERO ─── */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #0D1B2A 0%, #1a3a4a 40%, #00695C 100%);
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
        }
        .hero::before {
            content:'';
            position:absolute; inset:0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2300897B' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-blob {
            position:absolute; right:-100px; top:50%; transform:translateY(-50%);
            width:600px; height:600px; border-radius:50%;
            background:radial-gradient(circle, rgba(0,137,123,0.25) 0%, transparent 70%);
            animation:pulse 4s ease-in-out infinite;
        }
        .hero-blob-2 {
            position:absolute; left:-80px; bottom:-80px;
            width:400px; height:400px; border-radius:50%;
            background:radial-gradient(circle, rgba(30,136,229,0.2) 0%, transparent 70%);
            animation:pulse 6s ease-in-out infinite reverse;
        }
        @keyframes pulse { 0%,100%{transform:translateY(-50%) scale(1)} 50%{transform:translateY(-50%) scale(1.08)} }

        .hero-badge {
            display:inline-flex; align-items:center; gap:8px;
            background:rgba(255,255,255,0.1); backdrop-filter:blur(10px);
            border:1px solid rgba(255,255,255,0.2); border-radius:50px;
            padding:8px 18px; color:white; font-size:13px; font-weight:600; margin-bottom:24px;
        }
        .hero-badge .dot { width:8px;height:8px;border-radius:50%;background:#00BFA5;animation:blink 1.5s infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

        .hero h1 { font-size:clamp(2.5rem,5vw,4rem); font-weight:900; color:white; line-height:1.1; letter-spacing:-1px; }
        .hero h1 .highlight { background:linear-gradient(135deg,#00BFA5,#64FFDA); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .hero p { color:rgba(255,255,255,0.75); font-size:1.15rem; line-height:1.7; max-width:520px; }

        .hero-cta-primary { background:white; color:var(--primary); border:none; border-radius:50px; padding:15px 36px; font-weight:700; font-size:16px; transition:all 0.3s; box-shadow:0 8px 24px rgba(0,0,0,0.2); }
        .hero-cta-primary:hover { transform:translateY(-3px); box-shadow:0 14px 32px rgba(0,0,0,0.25); color:var(--primary-dark); }
        .hero-cta-outline { background:transparent; color:white; border:2px solid rgba(255,255,255,0.4); border-radius:50px; padding:15px 36px; font-weight:600; font-size:16px; transition:all 0.3s; backdrop-filter:blur(10px); }
        .hero-cta-outline:hover { border-color:white; background:rgba(255,255,255,0.1); color:white; }

        .hero-stat { text-align:center; padding:0 20px; border-right:1px solid rgba(255,255,255,0.15); }
        .hero-stat:last-child { border:none; }
        .hero-stat .num { font-size:2rem; font-weight:800; color:white; }
        .hero-stat small { color:rgba(255,255,255,0.6); font-size:12px; text-transform:uppercase; letter-spacing:0.5px; }

        /* ─── FLOATING CARD (Hero Right) ─── */
        .hero-visual { position:relative; height:500px; display:flex; align-items:center; justify-content:center; }
        .hero-circle {
            width:320px; height:320px; border-radius:50%;
            background:rgba(255,255,255,0.05); border:2px solid rgba(255,255,255,0.1);
            display:flex; align-items:center; justify-content:center;
            position:relative;
        }
        .hero-circle-inner {
            width:220px; height:220px; border-radius:50%;
            background:rgba(0,191,165,0.15); border:2px solid rgba(0,191,165,0.3);
            display:flex; align-items:center; justify-content:center; font-size:100px;
        }
        .float-card {
            position:absolute; background:white; border-radius:16px;
            padding:14px 20px; box-shadow:0 12px 40px rgba(0,0,0,0.2);
            display:flex; align-items:center; gap:12px; font-weight:600; font-size:14px; color:#2D3748;
            animation:float 3s ease-in-out infinite;
        }
        .float-card:nth-child(2) { top:30px; right:-30px; animation-delay:0s; }
        .float-card:nth-child(3) { bottom:60px; left:-40px; animation-delay:1s; }
        .float-card:nth-child(4) { top:60%; right:-50px; animation-delay:2s; }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
        .float-icon { width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }

        /* ─── SECTION LABEL ─── */
        .section-label { display:inline-block; background:rgba(0,137,123,0.1); color:var(--primary); border-radius:50px; padding:6px 18px; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:14px; }
        .section-title { font-size:clamp(1.8rem,3vw,2.5rem); font-weight:800; color:#0D1B2A; line-height:1.2; letter-spacing:-0.5px; }
        .section-sub { color:var(--muted); font-size:1.05rem; line-height:1.7; }

        /* ─── FEATURES ─── */
        #features { background:#F7FAFC; padding:100px 0; }
        .feature-card-new {
            background:white; border-radius:24px; padding:36px 30px;
            border:1px solid #E2E8F0; transition:all 0.35s ease;
            position:relative; overflow:hidden; height:100%;
        }
        .feature-card-new::before {
            content:''; position:absolute; top:0; left:0; right:0; height:3px;
            background:linear-gradient(90deg,var(--primary),var(--secondary));
            transform:scaleX(0); transform-origin:left; transition:transform 0.35s;
        }
        .feature-card-new:hover { transform:translateY(-10px); box-shadow:0 20px 50px rgba(0,137,123,0.12); border-color:transparent; }
        .feature-card-new:hover::before { transform:scaleX(1); }
        .feat-icon-wrap {
            width:64px; height:64px; border-radius:18px; display:flex; align-items:center; justify-content:center;
            font-size:26px; margin-bottom:22px; transition:all 0.3s;
        }
        .feature-card-new:hover .feat-icon-wrap { transform:scale(1.1) rotate(-5deg); }
        .feature-card-new h5 { font-weight:700; font-size:18px; color:#0D1B2A; margin-bottom:10px; }
        .feature-card-new p { color:var(--muted); line-height:1.7; font-size:15px; }

        /* Icon color combos */
        .ic-1 { background:#E0F2F1; color:#00897B; }
        .ic-2 { background:#E3F2FD; color:#1E88E5; }
        .ic-3 { background:#FFF3E0; color:#FF7043; }
        .ic-4 { background:#F3E5F5; color:#8E24AA; }
        .ic-5 { background:#E8F5E9; color:#2E7D32; }
        .ic-6 { background:#FFF8E1; color:#F9A825; }

        /* ─── STATS BANNER ─── */
        .stats-section { background:linear-gradient(135deg,var(--dark),#1a3a4a); padding:80px 0; }
        .stat-item { text-align:center; padding:0 30px; border-right:1px solid rgba(255,255,255,0.1); }
        .stat-item:last-child { border:none; }
        .stat-num { font-size:3rem; font-weight:900; background:linear-gradient(135deg,#00BFA5,#64FFDA); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .stat-label { color:rgba(255,255,255,0.6); font-size:14px; text-transform:uppercase; letter-spacing:1px; margin-top:4px; }

        /* ─── HOW IT WORKS ─── */
        #how-it-works { padding:100px 0; }
        .step-card {
            background:white; border-radius:24px; padding:40px 30px; text-align:center;
            border:1px solid #E2E8F0; transition:all 0.3s; position:relative;
        }
        .step-card:hover { transform:translateY(-8px); box-shadow:0 20px 50px rgba(0,0,0,0.08); }
        .step-num {
            width:70px; height:70px; border-radius:50%; margin:0 auto 20px;
            display:flex; align-items:center; justify-content:center;
            font-size:26px; font-weight:900; color:white;
            position:relative; z-index:1;
        }
        .step-num::after {
            content:''; position:absolute; inset:-4px; border-radius:50%;
            border:2px dashed rgba(255,255,255,0.3); animation:spin 8s linear infinite;
        }
        @keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
        .step-connector { position:absolute; top:55px; right:-20px; width:40px; height:2px; background:linear-gradient(90deg,var(--primary),var(--secondary)); z-index:2; }
        .step-connector::after { content:'▶'; position:absolute; right:-8px; top:-8px; color:var(--secondary); font-size:10px; }

        /* ─── DOCTORS SECTION ─── */
        #doctors { background:#F7FAFC; padding:100px 0; }
        .doc-card {
            background:white; border-radius:24px; overflow:hidden;
            border:1px solid #E2E8F0; transition:all 0.35s;
        }
        .doc-card:hover { transform:translateY(-10px); box-shadow:0 24px 60px rgba(0,137,123,0.15); border-color:transparent; }
        .doc-card-top { background:linear-gradient(135deg,#00897B,#1E88E5); padding:30px; text-align:center; }
        .doc-avatar-new { width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,0.2);border:3px solid rgba(255,255,255,0.5);display:flex;align-items:center;justify-content:center;font-size:36px;color:white;font-weight:800;margin:0 auto; }
        .doc-card-body { padding:22px; }
        .doc-card-body h6 { font-weight:700; color:#0D1B2A; margin-bottom:4px; }
        .rating-stars { color:#FFA726; font-size:13px; }

        /* ─── CONTACT ─── */
        #contact { padding:100px 0; }
        .contact-wrap { background:linear-gradient(135deg,#0D1B2A,#1a3a4a); border-radius:32px; padding:60px; color:white; }
        .contact-form-input { background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); border-radius:12px; color:white; padding:14px 18px; width:100%; transition:all 0.3s; }
        .contact-form-input:focus { outline:none; border-color:var(--primary); background:rgba(0,137,123,0.1); }
        .contact-form-input::placeholder { color:rgba(255,255,255,0.4); }

        /* ─── FOOTER ─── */
        footer { background:#0D1B2A; color:rgba(255,255,255,0.65); padding:60px 0 24px; }
        .footer-brand { font-size:22px; font-weight:800; color:white; }
        footer h6 { color:white; font-weight:700; margin-bottom:16px; }
        footer a { color:rgba(255,255,255,0.6); text-decoration:none; display:block; margin-bottom:8px; transition:color 0.2s; font-size:15px; }
        footer a:hover { color:#00BFA5; }
        .footer-divider { border-color:rgba(255,255,255,0.08); margin:40px 0 20px; }
        .social-icon { width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.08);display:inline-flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.7);transition:all 0.2s;margin-right:8px; }
        .social-icon:hover { background:var(--primary);color:white; }

        /* ─── RESPONSIVE ─── */
        @media(max-width:768px) {
            .hero { padding:100px 0 60px; }
            .hero h1 { font-size:2.4rem; }
            .hero-visual { display:none; }
            .contact-wrap { padding:30px 20px; }
            .step-connector { display:none; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-heartbeat me-2"></i>Medi<span>Connect</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav mx-auto gap-1">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#doctors">Doctors</a></li>
                <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>
            <div class="d-flex gap-2 mt-3 mt-lg-0">
                <?php if(isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/auth/logout.php" class="btn btn-login">Logout</a>
                    <a href="<?php redirectToDashboard(); ?>" class="btn btn-getstarted">Dashboard</a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-login">Login</a>
                    <a href="auth/register.php" class="btn btn-getstarted">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero" id="home">
    <div class="hero-blob"></div>
    <div class="hero-blob-2"></div>
    <div class="container position-relative" style="z-index:2; padding-top:80px;">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right" data-aos-duration="900">
                <div class="hero-badge">
                    <span class="dot"></span>
                    #1 Telemedicine Platform in Bangladesh
                </div>
                <h1 class="mb-4">
                    Your Health,<br>
                    <span class="highlight">Our Priority</span>
                </h1>
                <p class="mb-5">
                    Connect with verified doctors online. Book appointments instantly,
                    get digital prescriptions, and manage your entire health journey — all in one place.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-5">
                    <a href="auth/register.php" class="btn hero-cta-primary">
                        <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                    </a>
                    <a href="#how-it-works" class="btn hero-cta-outline">
                        <i class="fas fa-play-circle me-2"></i>See How It Works
                    </a>
                </div>
                <div class="d-flex">
                    <div class="hero-stat">
                        <div class="num">500+</div>
                        <small>Verified Doctors</small>
                    </div>
                    <div class="hero-stat">
                        <div class="num">10K+</div>
                        <small>Happy Patients</small>
                    </div>
                    <div class="hero-stat">
                        <div class="num">24/7</div>
                        <small>Support</small>
                    </div>
                    <div class="hero-stat">
                        <div class="num">4.9★</div>
                        <small>Avg Rating</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block" data-aos="fade-left" data-aos-duration="900">
                <div class="hero-visual">
                    <div class="hero-circle">
                        <div class="hero-circle-inner">
                            <i class="fas fa-heartbeat" style="color:#00BFA5;opacity:0.8"></i>
                        </div>
                    </div>
                    <!-- Floating Cards -->
                    <div class="float-card">
                        <div class="float-icon" style="background:#E0F2F1">
                            <i class="fas fa-check-circle" style="color:#00897B"></i>
                        </div>
                        <div><div style="font-size:12px;color:#718096">Appointment</div><div>Confirmed! ✅</div></div>
                    </div>
                    <div class="float-card">
                        <div class="float-icon" style="background:#E3F2FD">
                            <i class="fas fa-user-md" style="color:#1E88E5"></i>
                        </div>
                        <div><div style="font-size:12px;color:#718096">Available Now</div><div>Dr. Rahman</div></div>
                    </div>
                    <div class="float-card">
                        <div class="float-icon" style="background:#FFF3E0">
                            <i class="fas fa-star" style="color:#FF7043"></i>
                        </div>
                        <div><div style="font-size:12px;color:#718096">Patient Review</div><div>★★★★★ 4.9</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section id="features" class="py-5">
    <div class="container py-5">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-label">✦ Why MediConnect</span>
            <h2 class="section-title mt-2">Everything You Need for<br>Better Healthcare</h2>
            <p class="section-sub mt-3 mx-auto" style="max-width:520px">A complete digital healthcare platform designed to make your medical journey smooth, fast, and safe.</p>
        </div>
        <div class="row g-4">
            <?php
            $features = [
                ['fas fa-video','ic-1','Online Consultations','Connect with specialist doctors from the comfort of your home via secure video calls.'],
                ['fas fa-calendar-check','ic-2','Easy Appointment Booking','Book appointments in seconds — choose your doctor, date, and preferred time slot.'],
                ['fas fa-file-prescription','ic-3','Digital Prescriptions','Receive instant digital prescriptions after your consultation, printable anytime.'],
                ['fas fa-history','ic-4','Medical History','Access your complete medical records, past diagnoses, and treatment history anytime.'],
                ['fas fa-shield-alt','ic-5','Secure & Private','Your health data is 256-bit encrypted and completely private. HIPAA-compliant.'],
                ['fas fa-bell','ic-6','Smart Notifications','Get timely reminders for appointments, follow-ups, and medicine schedules.'],
            ];
            foreach($features as $i => [$icon,$cls,$title,$desc]):
            ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $i*80 ?>">
                <div class="feature-card-new">
                    <div class="feat-icon-wrap <?= $cls ?>">
                        <i class="<?= $icon ?>"></i>
                    </div>
                    <h5><?= $title ?></h5>
                    <p><?= $desc ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- STATS BANNER -->
<section class="stats-section">
    <div class="container">
        <div class="row text-center g-4">
            <?php $stats = [['500+','Verified Doctors'],['10,000+','Happy Patients'],['50+','Specializations'],['4.9/5','Average Rating']];
            foreach($stats as $i => [$num,$label]): ?>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="<?= $i*100 ?>">
                <div class="stat-item" style="border:none">
                    <div class="stat-num"><?= $num ?></div>
                    <div class="stat-label"><?= $label ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section id="how-it-works" class="py-5">
    <div class="container py-5">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-label">✦ Simple Process</span>
            <h2 class="section-title mt-2">How It Works</h2>
            <p class="section-sub mt-3">Get started with MediConnect in just 4 easy steps</p>
        </div>
        <div class="row g-4 position-relative">
            <?php
            $steps = [
                ['#00897B','fas fa-user-plus','Create Account','Register as a patient in under 2 minutes. Free & easy.'],
                ['#1E88E5','fas fa-search','Find a Doctor','Browse verified doctors by specialization, ratings & availability.'],
                ['#FF7043','fas fa-calendar-check','Book Appointment','Choose a convenient time slot and confirm your booking instantly.'],
                ['#8E24AA','fas fa-stethoscope','Get Treatment','Consult online, receive digital prescription & book follow-up.'],
            ];
            foreach($steps as $i => [$color,$icon,$title,$desc]): ?>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="<?= $i*100 ?>">
                <div class="step-card">
                    <?php if($i<3): ?><div class="step-connector"></div><?php endif; ?>
                    <div class="step-num" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>99)">
                        <?= $i+1 ?>
                    </div>
                    <div style="width:52px;height:52px;border-radius:16px;background:<?= $color ?>18;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:<?= $color ?>">
                        <i class="<?= $icon ?>"></i>
                    </div>
                    <h5 class="fw-700" style="font-weight:700;margin-bottom:8px;"><?= $title ?></h5>
                    <p style="color:var(--muted);font-size:14px;margin:0"><?= $desc ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="auth/register.php" class="btn btn-getstarted" style="padding:16px 42px;font-size:16px">
                <i class="fas fa-rocket me-2"></i>Get Started — It's Free!
            </a>
        </div>
    </div>
</section>

<!-- DOCTORS -->
<section id="doctors" style="background:#F7FAFC;padding:100px 0;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-label">✦ Our Team</span>
            <h2 class="section-title mt-2">Meet Our Top Doctors</h2>
            <p class="section-sub mt-3">Experienced, verified, and dedicated to your wellbeing</p>
        </div>
        <?php
        $doctors = $conn->query("
            SELECT u.full_name, d.specialization, d.experience_years, d.consultation_fee,
                   COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.doctor_id=d.id),4.5) as avg_rating
            FROM doctors d JOIN users u ON d.user_id=u.id WHERE d.is_approved=1 LIMIT 4
        ")->fetch_all(MYSQLI_ASSOC);
        $colors = ['#00897B','#1E88E5','#FF7043','#8E24AA'];
        ?>
        <div class="row g-4">
            <?php foreach($doctors as $i=>$doc): ?>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= $i*100 ?>">
                <div class="doc-card">
                    <div class="doc-card-top" style="background:linear-gradient(135deg,<?= $colors[$i%4] ?>,<?= $colors[$i%4] ?>99)">
                        <div class="doc-avatar-new"><?= strtoupper(substr($doc['full_name'],0,1)) ?></div>
                        <h6 class="text-white fw-bold mt-3 mb-0"><?= htmlspecialchars(formatDoctorName($doc['full_name'])) ?></h6>
                        <small style="color:rgba(255,255,255,0.8)"><?= $doc['specialization'] ?></small>
                    </div>
                    <div class="doc-card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="rating-stars">
                                <?php $r=round($doc['avg_rating']); for($s=1;$s<=5;$s++) echo $s<=$r?'★':'☆'; ?>
                                <span class="text-muted ms-1" style="font-size:12px"><?= number_format($doc['avg_rating'],1) ?></span>
                            </div>
                            <span class="badge" style="background:rgba(0,137,123,0.1);color:#00897B;border-radius:50px"><?= $doc['experience_years'] ?> yrs</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-weight:700;color:#0D1B2A">৳<?= number_format($doc['consultation_fee'],0) ?></span>
                            <a href="auth/register.php" class="btn btn-sm" style="background:linear-gradient(135deg,#00897B,#1E88E5);color:white;border-radius:50px;padding:5px 16px;font-size:13px;font-weight:600">Book</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach;
            if(empty($doctors)): ?>
            <div class="col-12 text-center text-muted py-5">Doctors will appear here once approved by admin.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CONTACT -->
<section id="contact" style="padding:100px 0;background:#fff;">
    <div class="container" data-aos="fade-up">
        <div class="contact-wrap">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <span class="section-label" style="background:rgba(0,191,165,0.15);color:#00BFA5">✦ Get In Touch</span>
                    <h2 class="section-title text-white mt-3 mb-3">Have Questions?<br>We're Here to Help</h2>
                    <p style="color:rgba(255,255,255,0.65);line-height:1.7">Our support team is available 24/7. Send us a message and we'll get back to you within hours.</p>
                    <div class="mt-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:42px;height:42px;border-radius:12px;background:rgba(0,191,165,0.15);display:flex;align-items:center;justify-content:center;color:#00BFA5"><i class="fas fa-envelope"></i></div>
                            <div style="color:rgba(255,255,255,0.8)">support@mediconnect.com</div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:42px;height:42px;border-radius:12px;background:rgba(0,191,165,0.15);display:flex;align-items:center;justify-content:center;color:#00BFA5"><i class="fas fa-phone"></i></div>
                            <div style="color:rgba(255,255,255,0.8)">+880 1700-000000</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <?= showAlert() ?>
                    <form action="contact_process.php" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="name" class="contact-form-input" placeholder="Your Name" required>
                            </div>
                            <div class="col-md-6">
                                <input type="email" name="email" class="contact-form-input" placeholder="Email Address" required>
                            </div>
                            <div class="col-12">
                                <input type="text" name="subject" class="contact-form-input" placeholder="Subject">
                            </div>
                            <div class="col-12">
                                <textarea name="message" class="contact-form-input" rows="4" placeholder="Your message..." required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn w-100 py-3" style="background:linear-gradient(135deg,#00897B,#1E88E5);color:white;border-radius:12px;font-weight:700;font-size:16px;border:none;box-shadow:0 6px 20px rgba(0,137,123,0.3)">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-brand mb-3"><i class="fas fa-heartbeat me-2" style="color:#00BFA5"></i>MediConnect</div>
                <p style="color:rgba(255,255,255,0.55);line-height:1.7;max-width:280px">Your trusted telemedicine platform connecting patients with qualified healthcare professionals across Bangladesh.</p>
                <div class="mt-4">
                    <a class="social-icon" href="#"><i class="fab fa-facebook-f"></i></a>
                    <a class="social-icon" href="#"><i class="fab fa-twitter"></i></a>
                    <a class="social-icon" href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a class="social-icon" href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Platform</h6>
                <a href="#features">Features</a>
                <a href="#doctors">Doctors</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Account</h6>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php">Register</a>
                <a href="auth/register.php">Join as Doctor</a>
            </div>
            <div class="col-lg-4">
                <h6>Stay Updated</h6>
                <p style="font-size:14px;color:rgba(255,255,255,0.55)">Subscribe for health tips and platform updates</p>
                <div class="d-flex gap-2 mt-2">
                    <input type="email" placeholder="Enter your email" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:10px;padding:10px 14px;color:white;flex:1;font-size:14px">
                    <button style="background:linear-gradient(135deg,#00897B,#1E88E5);border:none;border-radius:10px;padding:10px 18px;color:white;font-weight:600;white-space:nowrap">Subscribe</button>
                </div>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <p class="mb-0" style="font-size:14px">© 2026 MediConnect. Built with ❤️ for better healthcare in Bangladesh.</p>
            <div style="font-size:13px;color:rgba(255,255,255,0.4)">Privacy Policy · Terms of Service</div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
    // AOS Animations
    AOS.init({ once: true, offset: 80 });

    // Navbar scroll effect
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('mainNav');
        nav.classList.toggle('scrolled', window.scrollY > 50);
    });

    // Smooth scroll for nav links
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Counter animation on scroll
    const counters = document.querySelectorAll('.stat-num');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.5 });
    counters.forEach(c => observer.observe(c));
</script>
</body>
</html>
