<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function generate_registration_id(PDO $pdo): string
{
    $year = date('Y');
    $prefix = 'REG-' . $year . '-';

    $statement = $pdo->prepare(
        'SELECT registration_id
         FROM registrations
         WHERE registration_id LIKE :prefix
         ORDER BY id DESC
         LIMIT 1'
    );
    $statement->execute(['prefix' => $prefix . '%']);
    $latest = $statement->fetchColumn();

    $nextNumber = 1;
    if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches)) {
        $nextNumber = ((int) $matches[1]) + 1;
    }

    return $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
}

function generate_parent_registration_id(PDO $pdo): string
{
    $year = date('Y');
    $prefix = 'PRG-' . $year . '-';

    $statement = $pdo->prepare(
        'SELECT registration_id
         FROM parent_registrations
         WHERE registration_id LIKE :prefix
         ORDER BY id DESC
         LIMIT 1'
    );
    $statement->execute(['prefix' => $prefix . '%']);
    $latest = $statement->fetchColumn();

    $nextNumber = 1;
    if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches)) {
        $nextNumber = ((int) $matches[1]) + 1;
    }

    return $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
}

function generate_qr_token(): string
{
    return bin2hex(random_bytes(32));
}

function ensure_app_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS event_settings (
            id TINYINT UNSIGNED PRIMARY KEY,
            event_name VARCHAR(120) NOT NULL DEFAULT 'Graduation Day',
            event_date DATE NULL,
            event_time VARCHAR(40) NULL,
            venue VARCHAR(160) NULL,
            announcement TEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $statement = $pdo->prepare(
        "INSERT IGNORE INTO event_settings
            (id, event_name, event_date, event_time, venue, announcement)
         VALUES
            (1, 'Graduation Day', NULL, '', '', 'Students must present their printed QR pass at the entrance.')"
    );
    $statement->execute();
}

function ensure_parent_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS parent_registrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            registration_id VARCHAR(30) NOT NULL UNIQUE,
            qr_token CHAR(64) NOT NULL UNIQUE,
            parent_name VARCHAR(180) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            alt_phone VARCHAR(20) NULL,
            email VARCHAR(120) NULL,
            num_wards TINYINT UNSIGNED NOT NULL DEFAULT 1,
            class_name VARCHAR(50) NOT NULL,
            event_name VARCHAR(120) NOT NULL,
            attendance_status ENUM('registered','checked_in') NOT NULL DEFAULT 'registered',
            checkin_time DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_qr_token (qr_token),
            INDEX idx_registration_id (registration_id),
            INDEX idx_attendance_status (attendance_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function get_event_settings(PDO $pdo): array
{
    ensure_app_schema($pdo);

    $statement = $pdo->query('SELECT * FROM event_settings WHERE id = 1 LIMIT 1');
    $settings = $statement->fetch();

    return $settings ?: [
        'event_name'   => 'Graduation Day',
        'event_date'   => null,
        'event_time'   => '',
        'venue'        => '',
        'announcement' => '',
    ];
}

function current_url_base(): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $isHttps ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

    return $scheme . '://' . $host . ($path === '' ? '' : $path);
}
