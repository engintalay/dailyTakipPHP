<?php
/**
 * Login API
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(array('error' => 'Invalid JSON'), 400);
    }

    $email = trim(isset($input['email']) ? $input['email'] : '');
    $password = isset($input['password']) ? $input['password'] : '';

    if (!$email || !$password) {
        jsonResponse(array('error' => 'E-posta ve şifre gerekli'), 400);
    }

    $result = login($email, $password);

    if ($result) {
        jsonResponse(array(
            'success' => true,
            'user' => array(
                'id' => $result['id'],
                'name' => $result['name'],
                'email' => $result['email'],
                'role' => $result['role']
            )
        ));
    } else {
        jsonResponse(array('error' => 'E-posta veya şifre hatalı'), 401);
    }
} else {
    jsonResponse(array('error' => 'Method not allowed'), 405);
}