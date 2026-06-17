<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

require_staff_login();

$tables = ['event_settings', 'registrations', 'parent_registrations'];
$counts = [];
$error = null;

try {
    $pdo = db();
    ensure_all_schema($pdo);

    foreach ($tables as $table) {
        $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }
} catch (Throwable $exception) {
    http_response_code(500);
    $error = $exception->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Setup - <?= e(SCHOOL_NAME) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <h1 class="brand">Database Setup</h1>
            <nav class="nav" aria-label="Main navigation">
                <a class="secondary" href="dashboard.php">Dashboard</a>
                <a class="secondary" href="parent_register.php">Parent Registration</a>
                <a class="secondary" href="checkin.php">Check In</a>
            </nav>
        </header>

        <section class="panel">
                        <?php if ($error !== null): ?>
                <h2>Database Connection Failed</h2>
                <p class="error"><?= e($error) ?></p>
            <?php else: ?>
                <h2>Database Ready</h2>
                <p class="success-note">Required tables were created or confirmed successfully.</p>
                <dl class="details">
                    <?php foreach ($counts as $table => $count): ?>
                        <div><dt><?= e($table) ?></dt><dd><?= $count ?> records</dd></div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>