<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

require_staff_login();

$pdo = db();
ensure_all_schema($pdo);
$students = $pdo->query('SELECT * FROM registrations ORDER BY class_name ASC, student_name ASC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Students - <?= e(SCHOOL_NAME) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="shell wide">
        <header class="topbar">
            <h1 class="brand">Students</h1>
            <nav class="nav" aria-label="Main navigation">
                <a class="secondary" href="dashboard.php">Dashboard</a>
                <a class="secondary" href="import_students.php">Import CSV</a>
                <a href="students.php">Students</a>
                <a class="secondary" href="print_passes.php">Print Passes</a>
                <a class="secondary" href="logout.php">Log Out</a>
            </nav>
        </header>

        <section class="panel">
            <h2>Registered Students</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Admission No</th><th>Class</th><th>Registration ID</th><th>Status</th><th>Check-in Time</th></tr></thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= e($student['student_name']) ?></td>
                                <td><?= e($student['admission_no']) ?></td>
                                <td><?= e($student['class_name']) ?></td>
                                <td><?= e($student['registration_id']) ?></td>
                                <td><?= e($student['attendance_status']) ?></td>
                                <td><?= e($student['checkin_time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
