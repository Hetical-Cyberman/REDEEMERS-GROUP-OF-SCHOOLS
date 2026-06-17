<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$isPostRequest = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPostRequest) {
    require_staff_login();
}

function normalize_qr_value(string $value): string
{
    $value = trim($value);

    if (filter_var($value, FILTER_VALIDATE_URL)) {
        $query = parse_url($value, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $params);
            if (isset($params['token']) && is_string($params['token'])) {
                return trim($params['token']);
            }
        }
    }

    return $value;
}

function format_student(array $r): array
{
    return [
        'registration_id' => $r['registration_id'],
        'student_name'    => $r['student_name'],
        'admission_no'    => $r['admission_no'],
        'class_name'      => $r['class_name'],
        'event_name'      => $r['event_name'],
        'checkin_time'    => $r['checkin_time'] !== null
            ? date('g:i A', strtotime((string) $r['checkin_time']))
            : date('g:i A'),
        'type'            => 'student',
    ];
}

function format_parent(array $r): array
{
    return [
        'registration_id' => $r['registration_id'],
        'student_name'    => $r['parent_name'],
        'admission_no'    => 'N/A',
        'class_name'      => $r['class_name'] . ' (' . $r['num_wards'] . ' ward' . ((int) $r['num_wards'] > 1 ? 's' : '') . ')',
        'event_name'      => $r['event_name'],
        'checkin_time'    => $r['checkin_time'] !== null
            ? date('g:i A', strtotime((string) $r['checkin_time']))
            : date('g:i A'),
        'type'            => 'parent',
    ];
}

function render_verification_page(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    $status = (string) ($payload['status'] ?? 'not_registered');
    $person = $payload['person'] ?? null;
    $className = $status === 'valid' ? 'success' : ($status === 'already_checked_in' ? 'warning' : 'danger');
    $title = (string) ($payload['message'] ?? 'NOT REGISTERED');
    $detail = (string) ($payload['detail'] ?? '');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> - <?= e(SCHOOL_NAME) ?></title>
        <link rel="stylesheet" href="assets/styles.css">
        <style>
            body { display: grid; place-items: center; padding: 24px; }
            .verify-card { width: min(520px, 100%); text-align: center; }
            .verify-card h1 { margin: 0 0 10px; font-size: 28px; }
            .verify-card.success h1 { color: var(--success); }
            .verify-card.warning h1 { color: var(--warning); }
            .verify-card.danger h1 { color: var(--danger); }
            .verify-card .details { margin-top: 20px; text-align: left; }
            .verify-actions { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-top: 22px; }
        </style>
    </head>
    <body>
        <main class="panel verify-card <?= e($className) ?>">
            <h1><?= e($title) ?></h1>
            <p class="message"><?= e($detail) ?></p>

            <?php if (is_array($person)): ?>
                <dl class="details">
                    <div><dt>Name</dt><dd><?= e($person['student_name'] ?? '') ?></dd></div>
                    <div><dt>Registration ID</dt><dd><?= e($person['registration_id'] ?? '') ?></dd></div>
                    <div><dt>Class</dt><dd><?= e($person['class_name'] ?? '') ?></dd></div>
                    <div><dt>Event</dt><dd><?= e($person['event_name'] ?? '') ?></dd></div>
                    <div><dt>Check-in Time</dt><dd><?= e($person['checkin_time'] ?? '') ?></dd></div>
                </dl>
            <?php endif; ?>

            <div class="verify-actions">
                <a class="button" href="checkin.php">Back to Scanner</a>
                <a class="button secondary" href="dashboard.php">Dashboard</a>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

function respond_verification(array $payload, int $statusCode = 200): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        json_response($payload, $statusCode);
    }

    render_verification_page($payload, $statusCode);
}

$input = $isPostRequest
    ? (string) ($_POST['qr'] ?? '')
    : (string) ($_GET['token'] ?? '');

$qrValue = normalize_qr_value($input);

if ($qrValue === '') {
    respond_verification([
        'status'  => 'not_registered',
        'message' => 'NOT REGISTERED',
        'detail'  => 'No QR token supplied.',
    ], 400);
}

$pdo = db();
ensure_all_schema($pdo);
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare(
        'SELECT * FROM registrations
         WHERE qr_token = :qr_token OR registration_id = :registration_id
         LIMIT 1 FOR UPDATE'
    );
    $stmt->execute(['qr_token' => $qrValue, 'registration_id' => $qrValue]);
    $registration = $stmt->fetch();
    $isParent = false;

    if (!$registration) {
        $stmt2 = $pdo->prepare(
            'SELECT * FROM parent_registrations
             WHERE qr_token = :qr_token OR registration_id = :registration_id
             LIMIT 1 FOR UPDATE'
        );
        $stmt2->execute(['qr_token' => $qrValue, 'registration_id' => $qrValue]);
        $registration = $stmt2->fetch();
        $isParent = true;
    }

    if (!$registration) {
        $pdo->commit();
        respond_verification([
            'status'  => 'not_registered',
            'message' => 'NOT REGISTERED',
            'detail'  => 'This QR code is not registered for this event.',
        ], 404);
    }

    $table = $isParent ? 'parent_registrations' : 'registrations';

    if ($registration['attendance_status'] === 'checked_in') {
        $pdo->commit();
        respond_verification([
            'status'  => 'already_checked_in',
            'message' => 'USER ALREADY EXISTS',
            'detail'  => 'This pass has already been used for entry.',
            'person'  => $isParent ? format_parent($registration) : format_student($registration),
        ], 409);
    }

    $update = $pdo->prepare(
        "UPDATE {$table}
         SET attendance_status = 'checked_in', checkin_time = NOW()
         WHERE id = :id"
    );
    $update->execute(['id' => $registration['id']]);

    $refresh = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
    $refresh->execute(['id' => $registration['id']]);
    $registration = $refresh->fetch();

    $pdo->commit();

    respond_verification([
        'status'  => 'valid',
        'message' => 'SUCCESSFUL, ALLOW ENTRY',
        'detail'  => 'Access granted.',
        'person'  => $isParent ? format_parent($registration) : format_student($registration),
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respond_verification([
        'status'  => 'error',
        'message' => 'NOT REGISTERED',
        'detail'  => 'Verification failed. Please try again.',
    ], 500);
}