<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

require_staff_login();

$pdo = db();
ensure_all_schema($pdo);
$event = get_event_settings($pdo);
$error = null;
$imported = 0;
$skipped = 0;

function normalize_header(string $header): string
{
    return strtolower(preg_replace('/[^a-z0-9]+/i', '', trim($header)));
}

function csv_value(array $row, array $map, array $possibleNames): string
{
    foreach ($possibleNames as $name) {
        if (isset($map[$name]) && isset($row[$map[$name]])) {
            return trim((string) $row[$map[$name]]);
        }
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['students_csv']) || $_FILES['students_csv']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload a valid CSV file.';
    } else {
        $handle = fopen($_FILES['students_csv']['tmp_name'], 'r');
        if ($handle === false) {
            $error = 'The uploaded file could not be opened.';
        } else {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                $error = 'The CSV file is empty.';
            } else {
                $map = [];
                foreach ($headers as $index => $header) {
                    $map[normalize_header((string) $header)] = $index;
                }

                $insert = $pdo->prepare(
                    'INSERT INTO registrations
                        (registration_id, qr_token, student_name, admission_no, class_name, event_name)
                     VALUES
                        (:registration_id, :qr_token, :student_name, :admission_no, :class_name, :event_name)'
                );
                $exists = $pdo->prepare('SELECT id FROM registrations WHERE admission_no = :admission_no LIMIT 1');

                while (($row = fgetcsv($handle)) !== false) {
                    $studentName = csv_value($row, $map, ['name', 'studentname', 'fullname']);
                    $admissionNo = csv_value($row, $map, ['admissionno', 'admissionnumber', 'admission']);
                    $className = csv_value($row, $map, ['class', 'classname']);
                    $eventName = csv_value($row, $map, ['event', 'eventname']) ?: $event['event_name'];

                    if ($studentName === '' || $admissionNo === '' || $className === '') {
                        $skipped++;
                        continue;
                    }

                    $exists->execute(['admission_no' => $admissionNo]);
                    if ($exists->fetch()) {
                        $skipped++;
                        continue;
                    }

                    $insert->execute([
                        'registration_id' => generate_registration_id($pdo),
                        'qr_token' => generate_qr_token(),
                        'student_name' => $studentName,
                        'admission_no' => $admissionNo,
                        'class_name' => $className,
                        'event_name' => $eventName,
                    ]);
                    $imported++;
                }
            }
            fclose($handle);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import Students - <?= e(SCHOOL_NAME) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <h1 class="brand">Import Students</h1>
            <nav class="nav" aria-label="Main navigation">
                <a class="secondary" href="dashboard.php">Dashboard</a>
                <a href="import_students.php">Import CSV</a>
                <a class="secondary" href="students.php">Students</a>
                <a class="secondary" href="logout.php">Log Out</a>
            </nav>
        </header>

        <section class="grid">
            <div class="panel">
                <h2>Upload Excel CSV</h2>
                <?php if ($error !== null): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
                <?php if ($imported > 0 || $skipped > 0): ?><p class="success-note"><?= $imported ?> students imported. <?= $skipped ?> rows skipped.</p><?php endif; ?>
                <form class="form-grid" method="post" action="import_students.php" enctype="multipart/form-data">
                    <label>CSV File <input type="file" name="students_csv" accept=".csv,text/csv" required></label>
                    <button class="button" type="submit">Import and Generate QR Tokens</button>
                </form>
            </div>
            <div class="panel">
                <h2>CSV Columns</h2>
                <p class="message">Export the Excel file as CSV with these columns:</p>
                <pre class="sample-csv">Name,Admission No,Class
John Doe,STU2026-001,SS3A
Mary James,STU2026-002,SS3B</pre>
                <p class="message">Event name is taken from the current event settings unless the CSV has an Event column.</p>
            </div>
        </section>
    </main>
</body>
</html>
