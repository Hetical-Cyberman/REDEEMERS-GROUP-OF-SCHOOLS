CREATE TABLE registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_id VARCHAR(30) NOT NULL UNIQUE,
    qr_token CHAR(64) NOT NULL UNIQUE,
    student_name VARCHAR(120) NOT NULL,
    admission_no VARCHAR(50) NOT NULL,
    class_name VARCHAR(50) NOT NULL,
    event_name VARCHAR(120) NOT NULL,
    attendance_status ENUM('registered', 'checked_in') NOT NULL DEFAULT 'registered',
    checkin_time DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_registration_id (registration_id),
    INDEX idx_qr_token (qr_token),
    INDEX idx_attendance_status (attendance_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE event_settings (
    id TINYINT UNSIGNED PRIMARY KEY,
    event_name VARCHAR(120) NOT NULL DEFAULT 'Graduation Day',
    event_date DATE NULL,
    event_time VARCHAR(40) NULL,
    venue VARCHAR(160) NULL,
    announcement TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO event_settings
    (id, event_name, event_date, event_time, venue, announcement)
VALUES
    (1, 'Graduation Day', NULL, '', '', 'Students must present their printed QR pass at the entrance.');

CREATE TABLE parent_registrations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;