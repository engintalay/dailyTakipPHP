<?php
/**
 * Attendance API
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireLogin();
$isAdmin = isAdmin($currentUser);
$effectiveUserId = getEffectiveUserId();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $filters = array();
    if (!empty($_GET['userId'])) $filters['user_id'] = $_GET['userId'];
    if (!empty($_GET['startDate'])) $filters['start_date'] = $_GET['startDate'];
    if (!empty($_GET['endDate'])) $filters['end_date'] = $_GET['endDate'];

    $records = getAttendances($filters);
    jsonResponse($records);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['action'])) {
        jsonResponse(array('error' => 'Invalid request'), 400);
    }

    $csrf = isset($input['csrf_token']) ? $input['csrf_token'] : '';
    if (!verifyCsrfToken($csrf)) {
        jsonResponse(array('error' => 'Invalid CSRF token'), 403);
    }

    if ($input['action'] === 'set') {
        $date = isset($input['date']) ? $input['date'] : '';
        $present = isset($input['present']) ? (bool)$input['present'] : true;
        $targetUserId = $isAdmin && !empty($input['user_id']) ? $input['user_id'] : $effectiveUserId;

        if (!$date) {
            jsonResponse(array('error' => 'Tarih gerekli'), 400);
        }

        $record = setAttendance($targetUserId, $date, $present);
        jsonResponse($record);
    } elseif ($input['action'] === 'toggle') {
        $date = isset($input['date']) ? $input['date'] : '';
        $targetUserId = $isAdmin && !empty($input['user_id']) ? $input['user_id'] : $effectiveUserId;

        if (!$date) {
            jsonResponse(array('error' => 'Tarih gerekli'), 400);
        }

        $current = getAttendanceByUserAndDate($targetUserId, $date);
        $present = $current ? !$current['present'] : true;
        $record = setAttendance($targetUserId, $date, $present);
        jsonResponse($record);
    }
} else {
    jsonResponse(array('error' => 'Method not allowed'), 405);
}