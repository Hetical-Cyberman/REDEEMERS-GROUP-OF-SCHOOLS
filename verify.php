<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

require_staff_login();

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
        'class_name'      => $r['class_name'] . ' (' . $r['num_wards'] . ' ward' . ((int)$r['num_wards'] > 1 ? 's' : '') . ')',
        'event_name'      => $r['event_name'],
        'checkin_time'    => $r['checkin_time'] !== null
            ? date('g:i A', strtotime((string) $r['checkin_time']))
            : date('g:i A'),
        'type'            => 'parent',
    ];
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (string) ($_POST['qr'] ?? '')
    : (string) ($_GET['token'] ?? '');

$qrValue = normalize_qr_value($input);

if ($qrValue === '') {
    json_response([
        'status'  => 'not_registered',
        'message' => 'NOT REGISTERED',
        'detail'  => 'No QR token supplied.',
    ], 400);
}

$pdo = db();
ensure_all_schema($pdo);
$pdo->beginTransaction();

try {
    // Check student registrations first
    $stmt = $pdo->prepare(
        'SELECT * FROM registrations
         WHERE qr_token = :qr_token OR registration_id = :registration_id
         LIMIT 1 FOR UPDATE'
    );
    $stmt->execute(['qr_token' => $qrValue, 'registration_id' => $qrValue]);
    $registration = $stmt->fetch();
    $isParent = false;

    // If not found in students, check parent registrations
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
        json_response([
            'status'  => 'not_registered',
            'message' => 'NOT REGISTERED',
            'detail'  => 'This QR code is not registered for this event.',
        ], 404);
    }

    $table = $isParent ? 'parent_registrations' : 'registrations';

    if ($registration['attendance_status'] === 'checked_in') {
        $pdo->commit();
        json_response([
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

    json_response([
        'status'  => 'valid',
        'message' => 'SUCCESSFUL, ALLOW ENTRY',
        'detail'  => 'Access granted.',
        'person'  => $isParent ? format_parent($registration) : format_student($registration),
    ]);

} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response([
        'status'  => 'error',
        'message' => 'NOT REGISTERED',
        'detail'  => 'Verification failed. Please try again.',
    ], 500);
}
