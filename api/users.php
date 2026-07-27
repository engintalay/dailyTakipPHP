<?php
/**
 * Users API
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireLogin();
$isAdmin = isAdmin($currentUser);
$canViewManagement = canViewManagement($currentUser);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!$canViewManagement) {
        jsonResponse(array('error' => 'Admin required'), 403);
    }

    $users = getAllUsers(false);
    jsonResponse($users);
} elseif ($method === 'POST') {
    if (!$isAdmin) {
        jsonResponse(array('error' => 'Admin required'), 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['action'])) {
        jsonResponse(array('error' => 'Invalid request'), 400);
    }

    $csrf = isset($input['csrf_token']) ? $input['csrf_token'] : '';
    if (!verifyCsrfToken($csrf)) {
        jsonResponse(array('error' => 'Invalid CSRF token'), 403);
    }

    if ($input['action'] === 'create') {
        $name = trim(isset($input['name']) ? $input['name'] : '');
        $email = trim(isset($input['email']) ? $input['email'] : '');
        $password = isset($input['password']) ? $input['password'] : '';
        $role = isset($input['role']) ? $input['role'] : ROLE_MEMBER;

        if (!$name || !$email || !$password) {
            jsonResponse(array('error' => 'İsim, e-posta ve şifre gerekli'), 400);
        }

        $result = createUser($name, $email, $password, $role);
        if (isset($result['error'])) {
            jsonResponse(array('error' => $result['error']), 400);
        }
        jsonResponse($result);
    } elseif ($input['action'] === 'update' && !empty($input['user_id'])) {
        $data = array();
        if (!empty($input['name'])) $data['name'] = trim($input['name']);
        if (!empty($input['email'])) $data['email'] = trim($input['email']);
        if (!empty($input['role'])) $data['role'] = $input['role'];
        if (isset($input['is_active'])) $data['is_active'] = (bool)$input['is_active'];
        if (!empty($input['password'])) $data['password'] = $input['password'];

        $result = updateUser($input['user_id'], $data);
        if (isset($result['error'])) {
            jsonResponse(array('error' => $result['error']), 400);
        }
        jsonResponse($result);
    } elseif ($input['action'] === 'delete' && !empty($input['user_id'])) {
        if ($input['user_id'] === $currentUser['id']) {
            jsonResponse(array('error' => 'Kendi hesabınızı silemezsiniz'), 400);
        }
        deleteUser($input['user_id']);
        jsonResponse(array('success' => true));
    }
} else {
    jsonResponse(array('error' => 'Method not allowed'), 405);
}
