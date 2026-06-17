<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

require_staff_login();

$pdo = db();
ensure_all_schema($pdo);
$event = get_event_settings($pdo);

// Student stats
$total      = (int) $pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn();
$checkedIn  = (int) $pdo->query("SELECT COUNT(*) FROM registrations WHERE attendance_status = 'checked_in'")->fetchColumn();
$pending    = max(0, $total - $checkedIn);

// Parent stats
$parentTotal     = (int) $pdo->query('SELECT COUNT(*) FROM parent_registrations')->fetchColumn();
$parentCheckedIn = (int) $pdo->query("SELECT COUNT(*) FROM parent_registrations WHERE attendance_status = 'checked_in'")->fetchColumn();
$parentPending   = max(0, $parentTotal - $parentCheckedIn);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - <?= e(SCHOOL_NAME) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .section-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #5a6a7e;
            margin: 28px 0 10px;
        }
        .stats-divider { margin-top: 8px; }
    </style>
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <h1 class="brand"><?= e(SCHOOL_NAME) ?></h1>
            <nav class="nav" aria-label="Main navigation">
                <a href="dashboard.php">Dashboard</a>
                <a class="secondary" href="event.php">Event Info</a>
                <a class="secondary" href="import_students.php">Import CSV</a>
                <a class="secondary" href="students.php">Students</a>
                <a class="secondary" href="checkin.php">Check In</a>
                <a class="secondary" href="logout.php">Log Out</a>
            </nav>
        </header>

        <section class="hero-panel">
            <div>
                <p class="eyebrow">Current Event</p>
                <h2><?= e($event['event_name']) ?></h2>
                <p><?= e($event['announcement']) ?></p>
            </div>
            <dl class="event-meta">
                <div><dt>Date</dt><dd><?= e($event['event_date'] ?: 'Not set') ?></dd></div>
                <div><dt>Time</dt><dd><?= e($event['event_time'] ?: 'Not set') ?></dd></div>
                <div><dt>Venue</dt><dd><?= e($event['venue'] ?: 'Not set') ?></dd></div>
            </dl>
        </section>

        <p class="section-label" style="padding: 0 24px;">👨‍👩‍👧 Parent Registrations</p>
        <section class="stats-grid">
            <article class="stat-card"><span>Total Parents</span><strong><?= $parentTotal ?></strong></article>
            <article class="stat-card"><span>Parents Checked In</span><strong><?= $parentCheckedIn ?></strong></article>
            <article class="stat-card"><span>Parents Pending</span><strong><?= $parentPending ?></strong></article>
        </section>

        <p class="section-label stats-divider" style="padding: 0 24px;">🎓 Student Registrations</p>
        <section class="stats-grid">
            <article class="stat-card"><span>Total Students</span><strong><?= $total ?></strong></article>
            <article class="stat-card"><span>Students Checked In</span><strong><?= $checkedIn ?></strong></article>
            <article class="stat-card"><span>Students Pending</span><strong><?= $pending ?></strong></article>
        </section>

        <section class="action-grid">
            <a class="action-card" href="import_students.php"><strong>Import Students</strong><span>Upload the school CSV and generate unique QR tokens.</span></a>
            <a class="action-card" href="print_passes.php"><strong>Print QR Passes</strong><span>Open all student passes with name, class, admission number, and QR code.</span></a>
            <a class="action-card" href="checkin.php"><strong>Entrance Scanner</strong><span>Use a phone camera to verify passes at the event gate.</span></a>
            <a class="action-card" href="event.php"><strong>Event Settings</strong><span>Update event name, date, venue and announcement shown on homepage.</span></a>
        </section>
    </main>
</body>
</html>
