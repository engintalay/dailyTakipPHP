<?php
/**
 * dailyTakip - PHP 5.3 Compatible Daily Tracking System
 * Configuration file
 */

// Error reporting for PHP 5.3
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', '1');
ini_set('date.timezone', 'Europe/Istanbul');

// Application settings
define('APP_NAME', 'dailyTakip');
define('APP_TITLE', 'Ekip Daily Takip Sistemi');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/dailyTakip/');

// Database configuration - Using SQLite for PHP 5.3 compatibility
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/data/dailytakip.sqlite');

// Session configuration
define('SESSION_NAME', 'dailytakip_session');
define('SESSION_LIFETIME', 86400 * 30); // 30 days

// Password hashing cost (PHP 5.3 compatible)
define('PASSWORD_COST', 12);

// Roles
define('ROLE_ADMIN', 'ADMIN');
define('ROLE_MEMBER', 'MEMBER');

// Status types
define('STATUS_OFFICE', 'OFFICE');
define('STATUS_REMOTE', 'REMOTE');
define('STATUS_LEAVE', 'LEAVE');
define('STATUS_SICK', 'SICK');

// Pagination
define('ITEMS_PER_PAGE', 50);

// Upload settings
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'zip', 'rar'));

// Create directories if not exist
if (!is_dir(dirname(DB_PATH))) {
    mkdir(dirname(DB_PATH), 0755, true);
}
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}