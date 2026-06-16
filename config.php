<?php
declare(strict_types=1);

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_NAME', env_value('DB_NAME', 'school_event_access'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));
define('DB_PORT', env_value('DB_PORT', '3306'));

define('SCHOOL_NAME', env_value('SCHOOL_NAME', 'School Event Check-In'));
define('STAFF_USERNAME', env_value('STAFF_USERNAME', 'admin'));
define('STAFF_PASSWORD', env_value('STAFF_PASSWORD', 'change-this-password'));