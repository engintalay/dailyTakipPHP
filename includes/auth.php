<?php
/**
 * Authentication system for dailyTakip
 * PHP 5.3 compatible - JSON file-based storage
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

session_start();

function login($email, $password) {
    $user = getUserByEmail($email);

    if (!$user || !$user['is_active'] || !verifyPassword($password, $user['password_hash'])) {
        return false;
    }

    // Create attendance record for today
    createAttendanceForToday($user['id']);

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();

    session_regenerate_id(true);

    return array(
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    );
}

function logout() {
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

function createAttendanceForToday($userId) {
    $today = date('Y-m-d');
    $attendance = getAttendanceByUserAndDate($userId, $today);

    if (!$attendance) {
        setAttendance($userId, $today, true);
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;

    return getUserById($_SESSION['user_id']);
}

function getEffectiveUser() {
    if (!isLoggedIn()) return null;

    $userId = isset($_SESSION['impersonating_user_id']) ? $_SESSION['impersonating_user_id'] : $_SESSION['user_id'];

    return getUserById($userId);
}

function isAdmin($user = null) {
    if (!$user) $user = getCurrentUser();
    return $user && $user['role'] === ROLE_ADMIN;
}

function canViewManagement($user = null) {
    if (!$user) $user = getCurrentUser();
    return $user && ($user['role'] === ROLE_ADMIN || $user['role'] === ROLE_VIEWER);
}

function requireManagementAccess() {
    $user = requireLogin();
    if (!canViewManagement($user)) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse(array('error' => 'Management access required'), 403);
        }
        header('Location: ' . APP_URL . 'index.php');
        exit;
    }
    return $user;
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse(array('error' => 'Unauthorized'), 401);
        }
        header('Location: ' . APP_URL . 'login.php');
        exit;
    }
    return getEffectiveUser();
}

function requireAdminAccess() {
    $user = requireLogin();
    if (!isAdmin($user)) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse(array('error' => 'Admin required'), 403);
        }
        header('Location: ' . APP_URL . 'index.php');
        exit;
    }
    return $user;
}

function impersonate($targetUserId) {
    $currentUser = getCurrentUser();
    if (!$currentUser || !isAdmin($currentUser)) {
        return false;
    }

    // Verify target user exists and is active
    $targetUser = getUserById($targetUserId);
    if (!$targetUser || !$targetUser['is_active']) {
        return false;
    }

    $_SESSION['impersonating_user_id'] = $targetUserId;
    return true;
}

function stopImpersonation() {
    if (isset($_SESSION['impersonating_user_id'])) {
        unset($_SESSION['impersonating_user_id']);
        return true;
    }
    return false;
}

function isImpersonating() {
    return isset($_SESSION['impersonating_user_id']);
}

function getImpersonatedUser() {
    if (!isImpersonating()) return null;

    return getUserById($_SESSION['impersonating_user_id']);
}

function getEffectiveUserId() {
    if (!isLoggedIn()) return null;

    if (isset($_SESSION['impersonating_user_id'])) {
        return $_SESSION['impersonating_user_id'];
    }
    return $_SESSION['user_id'];
}
