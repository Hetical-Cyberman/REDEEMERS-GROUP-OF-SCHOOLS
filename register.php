<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

require_staff_login();

$createdRegistration = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentName = trim((string) ($_POST['student_name'] ?? ''));
    $admissionNo = trim((string) ($_POST['admission_no'] ?? ''));
    $className = trim((string) ($_POST['class_name'] ?? ''));
    $eventName = trim((string) ($_POST['event_name'] ?? ''));

    if ($studentName === '' || $admissionNo === '' || $className === '' || $eventName === '') {
        $error = 'Please complete all registration fields.';
    } else {
        $pdo = db();
ensure_all_schema($pdo);
        $registrationId = generate_registration_id($pdo);
        $qrToken = generate_qr_token();

        $statement = $pdo->prepare(
            'INSERT INTO registrations
                (registration_id, qr_token, student_name, admission_no, class_name, event_name)
             VALUES
                (:registration_id, :qr_token, :student_name, :admission_no, :class_name, :event_name)'
        );
        $statement->execute([
            'registration_id' => $registrationId,
            'qr_token' => $qrToken,
            'student_name' => $studentName,
            'admission_no' => $admissionNo,
            'class_name' => $className,
            'event_name' => $eventName,
        ]);

        $createdRegistration = [
            'registration_id' => $registrationId,
            'qr_token' => $qrToken,
            'student_name' => $studentName,
            'admission_no' => $admissionNo,
            'class_name' => $className,
            'event_name' => $eventName,
            'verify_url' => current_url_base() . '/verify.php?token=' . rawurlencode($qrToken),
        ];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register Student - <?= e(SCHOOL_NAME) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <h1 class="brand"><?= e(SCHOOL_NAME) ?></h1>
            <nav class="nav" aria-label="Main navigation">
                <a class="secondary" href="dashboard.php">Dashboard</a>
                <a href="register.php">Register</a>
                <a class="secondary" href="import_students.php">Import CSV</a>
                <a class="secondary" href="students.php">Students</a>
                <a class="secondary" href="checkin.php">Check In</a>
                <a class="secondary" href="logout.php">Log Out</a>
            </nav>
        </header>

        <section class="grid">
            <div class="panel">
                <h2>Student Registration</h2>

                <?php if ($error !== null): ?>
                    <p class="error"><?= e($error) ?></p>
                <?php endif; ?>

                <form class="form-grid" method="post" action="register.php">
                    <label>
                        Name
                        <input name="student_name" autocomplete="name" required>
                    </label>

                    <label>
                        Admission No
                        <input name="admission_no" required>
                    </label>

                    <label>
                        Class
                        <input name="class_name" placeholder="SS3A" required>
                    </label>

                    <label>
                        Event
                        <input name="event_name" placeholder="Graduation Day" required>
                    </label>

                    <button class="button" type="submit">Generate QR Pass</button>
                </form>
            </div>

            <div class="panel">
                <h2>QR Pass</h2>

                <?php if ($createdRegistration === null): ?>
                    <p class="message">A student pass will appear here after registration.</p>
                <?php else: ?>
                    <div class="pass">
                        <img
                            class="qr-image"
                            src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=<?= rawurlencode($createdRegistration['verify_url']) ?>"
                            alt="QR code for <?= e($createdRegistration['student_name']) ?>"
                        >

                        <dl class="details">
                            <div>
                                <dt>Registration ID</dt>
                                <dd><?= e($createdRegistration['registration_id']) ?></dd>
                            </div>
                            <div>
                                <dt>Name</dt>
                                <dd><?= e($createdRegistration['student_name']) ?></dd>
                            </div>
                            <div>
                                <dt>Class</dt>
                                <dd><?= e($createdRegistration['class_name']) ?></dd>
                            </div>
                            <div>
                                <dt>Admission No</dt>
                                <dd><?= e($createdRegistration['admission_no']) ?></dd>
                            </div>
                            <div>
                                <dt>Event</dt>
                                <dd><?= e($createdRegistration['event_name']) ?></dd>
                            </div>
                        </dl>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>

