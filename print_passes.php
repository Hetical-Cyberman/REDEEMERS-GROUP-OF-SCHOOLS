<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

require_staff_login();

$pdo = db();
ensure_all_schema($pdo);
$event = get_event_settings($pdo);
$students = $pdo->query('SELECT * FROM registrations ORDER BY class_name ASC, student_name ASC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print QR Passes - <?= e(SCHOOL_NAME) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="shell wide print-shell">
        <header class="topbar no-print">
            <h1 class="brand">Print QR Passes</h1>
            <nav class="nav" aria-label="Main navigation">
                <a class="secondary" href="dashboard.php">Dashboard</a>
                <a class="secondary" href="students.php">Students</a>
                <button class="button" type="button" onclick="window.print()">Print</button>
            </nav>
        </header>

        <section class="pass-grid">
            <?php foreach ($students as $student): ?>
                <?php $verifyUrl = current_url_base() . '/verify.php?token=' . rawurlencode($student['qr_token']); ?>
                <article class="print-pass">
                    <h2><?= e($event['event_name']) ?></h2>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= rawurlencode($verifyUrl) ?>" alt="QR code for <?= e($student['student_name']) ?>">
                    <strong><?= e($student['student_name']) ?></strong>
                    <dl>
                        <div><dt>Class</dt><dd><?= e($student['class_name']) ?></dd></div>
                        <div><dt>Admission No</dt><dd><?= e($student['admission_no']) ?></dd></div>
                        <div><dt>Registration ID</dt><dd><?= e($student['registration_id']) ?></dd></div>
                    </dl>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
