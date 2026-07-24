<?php
/**
 * Application configuration
 * PHP 5.3 compatible
 */

define('APP_NAME', 'dailyTakip');
define('APP_DESCRIPTION', 'Ekip Daily Takip Sistemi');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . '/dailyTakip/');
define('SESSION_NAME', 'dailytakip_session');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);

// Role constants
define('ROLE_ADMIN', 'ADMIN');
define('ROLE_MEMBER', 'MEMBER');

// Status types
define('STATUS_OFFICE', 'OFFICE');
define('STATUS_REMOTE', 'REMOTE');
define('STATUS_LEAVE', 'LEAVE');
define('STATUS_SICK', 'SICK');

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// File upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
$GLOBALS['ALLOWED_EXTENSIONS'] = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'zip', 'rar');

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Initialize database
require_once __DIR__ . '/db.php';
initDatabase();

// Include auth
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';