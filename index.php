<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

$pdo = db();
ensure_app_schema($pdo);
$event = get_event_settings($pdo);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redeemers International Group of Schools</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #1a3a6b;
            --primary-dark: #0f2347;
            --accent: #c8a84b;
            --accent-dark: #a8882e;
            --white: #ffffff;
            --light: #f4f6fb;
            --text: #1e2a3a;
            --text-muted: #5a6a7e;
            --border: #dde3ee;
            --success: #1a7a4a;
            --radius: 12px;
            --shadow: 0 4px 24px rgba(26,58,107,0.10);
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--light);
            color: var(--text);
            line-height: 1.6;
        }

        /* ── HEADER ── */
        .site-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 60%, #2a5298 100%);
            color: var(--white);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 16px rgba(0,0,0,0.18);
        }
        .header-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .school-logo-badge {
            width: 62px;
            height: 62px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 900;
            color: var(--primary-dark);
            flex-shrink: 0;
            border: 3px solid rgba(255,255,255,0.3);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .school-title h1 {
            font-size: clamp(1.1rem, 3vw, 1.55rem);
            font-weight: 800;
            letter-spacing: 0.3px;
            line-height: 1.2;
        }
        .school-title p {
            font-size: 0.82rem;
            opacity: 0.78;
            margin-top: 2px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(160deg, var(--primary) 0%, #2a5298 50%, var(--primary-dark) 100%);
            color: var(--white);
            text-align: center;
            padding: 72px 24px 80px;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-badge {
            display: inline-block;
            background: var(--accent);
            color: var(--primary-dark);
            font-size: 0.78rem;
            font-weight: 700;
            padding: 5px 16px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 18px;
        }
        .hero h2 {
            font-size: clamp(1.8rem, 5vw, 3rem);
            font-weight: 900;
            margin-bottom: 14px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .hero p {
            font-size: 1.1rem;
            opacity: 0.88;
            max-width: 580px;
            margin: 0 auto;
        }

        /* ── CONTAINER ── */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ── EVENT DETAILS ── */
        .event-section {
            padding: 60px 0 50px;
        }
        .section-label {
            text-align: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--accent-dark);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }
        .section-title {
            text-align: center;
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 40px;
        }
        .event-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .event-card-header {
            background: linear-gradient(90deg, var(--primary) 0%, #2a5298 100%);
            color: var(--white);
            padding: 22px 32px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .event-card-header .event-icon {
            font-size: 2rem;
        }
        .event-card-header h3 {
            font-size: 1.35rem;
            font-weight: 800;
        }
        .event-card-header p {
            font-size: 0.88rem;
            opacity: 0.82;
            margin-top: 2px;
        }
        .event-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0;
            border-top: 1px solid var(--border);
        }
        .event-meta-item {
            padding: 22px 28px;
            border-right: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .event-meta-item:last-child { border-right: none; }
        .meta-icon {
            font-size: 1.4rem;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .meta-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            font-weight: 600;
        }
        .meta-value {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: 2px;
        }
        .announcement-box {
            background: #fffbf0;
            border-top: 1px solid var(--border);
            padding: 20px 28px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .announcement-box .ann-icon { font-size: 1.2rem; flex-shrink: 0; margin-top: 2px; }
        .announcement-box p { color: var(--text); font-size: 0.95rem; line-height: 1.6; }

        /* ── ACTION SECTION ── */
        .action-section {
            padding: 20px 0 70px;
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 28px;
            margin-top: 0;
        }
        .action-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            padding: 40px 36px;
            text-align: center;
            text-decoration: none;
            color: var(--text);
            transition: transform 0.18s, box-shadow 0.18s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(26,58,107,0.15);
        }
        .action-card .card-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 6px;
        }
        .action-card.parent .card-icon { background: #e8f4fd; }
        .action-card.admin .card-icon { background: #edf7f0; }
        .action-card h3 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
        }
        .action-card p {
            font-size: 0.92rem;
            color: var(--text-muted);
            line-height: 1.5;
        }
        .action-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 13px 32px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.15s, transform 0.1s;
            cursor: pointer;
            border: none;
        }
        .action-card.parent .action-btn {
            background: var(--primary);
            color: var(--white);
        }
        .action-card.parent .action-btn:hover { background: var(--primary-dark); }
        .action-card.admin .action-btn {
            background: var(--white);
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        .action-card.admin .action-btn:hover { background: var(--light); }

        /* ── FOOTER ── */
        .site-footer {
            background: var(--primary-dark);
            color: rgba(255,255,255,0.85);
            padding: 48px 24px 28px;
        }
        .footer-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 36px;
            padding-bottom: 32px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .footer-col h4 {
            color: var(--accent);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 700;
            margin-bottom: 14px;
        }
        .footer-col p, .footer-col a {
            font-size: 0.9rem;
            line-height: 1.8;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            display: block;
        }
        .footer-col a:hover { color: var(--accent); }
        .footer-bottom {
            max-width: 1100px;
            margin: 20px auto 0;
            text-align: center;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.45);
        }

        @media (max-width: 600px) {
            .event-meta-grid { grid-template-columns: 1fr 1fr; }
            .event-meta-item { border-right: none; border-bottom: 1px solid var(--border); }
            .action-grid { grid-template-columns: 1fr; }
            .header-inner { padding: 14px 16px; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
    <div class="header-inner">
        <div class="school-logo-badge">R</div>
        <div class="school-title">
            <h1>Redeemers International Group of Schools</h1>
            <p>Excellence &bull; Integrity &bull; Innovation</p>
        </div>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div class="hero-badge">&#127891; School Event</div>
    <h2><?= e($event['event_name']) ?></h2>
    <p><?= e($event['announcement'] ?: 'Join us for this special occasion. Register below to secure your attendance pass.') ?></p>
</section>

<!-- EVENT DETAILS -->
<section class="event-section">
    <div class="container">
        <p class="section-label">Upcoming Event</p>
        <h2 class="section-title">Event Details</h2>
        <div class="event-card">
            <div class="event-card-header">
                <div class="event-icon">🎓</div>
                <div>
                    <h3><?= e($event['event_name']) ?></h3>
                    <p>Redeemers International Group of Schools</p>
                </div>
            </div>
            <div class="event-meta-grid">
                <div class="event-meta-item">
                    <div class="meta-icon">📅</div>
                    <div>
                        <div class="meta-label">Date</div>
                        <div class="meta-value"><?= $event['event_date'] ? date('F j, Y', strtotime($event['event_date'])) : 'To be announced' ?></div>
                    </div>
                </div>
                <div class="event-meta-item">
                    <div class="meta-icon">🕐</div>
                    <div>
                        <div class="meta-label">Time</div>
                        <div class="meta-value"><?= e($event['event_time'] ?: 'To be announced') ?></div>
                    </div>
                </div>
                <div class="event-meta-item">
                    <div class="meta-icon">📍</div>
                    <div>
                        <div class="meta-label">Venue</div>
                        <div class="meta-value"><?= e($event['venue'] ?: 'To be announced') ?></div>
                    </div>
                </div>
            </div>
            <?php if (!empty($event['announcement'])): ?>
            <div class="announcement-box">
                <div class="ann-icon">📢</div>
                <p><?= e($event['announcement']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ACTION SECTION -->
<section class="action-section">
    <div class="container">
        <p class="section-label">Get Started</p>
        <h2 class="section-title">How would you like to proceed?</h2>
        <div class="action-grid">
            <div class="action-card parent">
                <div class="card-icon">👨‍👩‍👧</div>
                <h3>Parent Registration</h3>
                <p>Register your ward(s) for the event and receive a unique QR code pass to download and present at the entrance.</p>
                <a class="action-btn" href="parent_register.php">Register Now</a>
            </div>
            <div class="action-card admin">
                <div class="card-icon">🔐</div>
                <h3>Administrator Login</h3>
                <p>School staff and administrators can log in to manage event settings, view registrations, and operate the entrance scanner.</p>
                <a class="action-btn" href="login.php">Admin Login</a>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-col">
            <h4>Redeemers International Group of Schools</h4>
            <p>Committed to raising leaders of excellence through quality education and strong moral values.</p>
        </div>
        <div class="footer-col">
            <h4>Contact Us</h4>
            <p>📞 +234 800 000 0000</p>
            <p>📧 info@redeemersschools.edu.ng</p>
        </div>
        <div class="footer-col">
            <h4>Address</h4>
            <p>Redeemers International Group of Schools<br>Lagos, Nigeria</p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> Redeemers International Group of Schools. All rights reserved.
    </div>
</footer>

</body>
</html>
