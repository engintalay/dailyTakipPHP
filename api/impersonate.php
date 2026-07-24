<?php
/**
 * Impersonation API
 */
require_once __DIR__ . '/../includes/config.php';

if (!isLoggedIn()) {
    jsonResponse(array('error' => 'Unauthorized'), 401);
}

if (!isAdmin(getCurrentUser())) {
    jsonResponse(array('error' => 'Admin required'), 403);
}

if (isset($_GET['stop'])) {
    stopImpersonation();
    header('Location: ' . APP_URL . 'index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($csrf)) {
        jsonResponse(array('error' => 'Invalid CSRF token'), 403);
    }

    $targetId = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    if ($targetId) {
        impersonate($targetId);
        jsonResponse(array('success' => true));
    }
}

jsonResponse(array('error' => 'Invalid request'), 400);