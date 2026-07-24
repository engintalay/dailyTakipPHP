<?php
/**
 * Daily Status API
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
    if (!empty($_GET['date'])) {
        $filters['start_date'] = $_GET['date'];
        $filters['end_date'] = date('Y-m-d', strtotime($_GET['date'] . ' +1 day'));
    }
    if (!empty($_GET['startDate'])) $filters['start_date'] = $_GET['startDate'];
    if (!empty($_GET['endDate'])) $filters['end_date'] = $_GET['endDate'];

    $statuses = getDailyStatuses($filters);
    jsonResponse($statuses);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['action'])) {
        jsonResponse(array('error' => 'Invalid request'), 400);
    }

    $csrf = isset($input['csrf_token']) ? $input['csrf_token'] : '';
    if (!verifyCsrfToken($csrf)) {
        jsonResponse(array('error' => 'Invalid CSRF token'), 403);
    }

    if ($input['action'] === 'delete') {
        $date = isset($input['date']) ? $input['date'] : '';
        $targetUserId = $isAdmin && !empty($input['user_id']) ? $input['user_id'] : $effectiveUserId;

        if (!$date) {
            jsonResponse(array('error' => 'Tarih gerekli'), 400);
        }

        deleteDailyStatus($targetUserId, $date);
        jsonResponse(array('success' => true));
    } else {
        $date = isset($input['date']) ? $input['date'] : '';
        $type = isset($input['type']) ? $input['type'] : '';
        $note = isset($input['note']) ? $input['note'] : '';
        $targetUserId = $isAdmin && !empty($input['user_id']) ? $input['user_id'] : $effectiveUserId;
        $rangeEnd = isset($input['range_end']) ? $input['range_end'] : '';

        if (!$date || !$type) {
            jsonResponse(array('error' => 'Tarih ve durum gerekli'), 400);
        }

        $validTypes = array(STATUS_OFFICE, STATUS_REMOTE, STATUS_LEAVE, STATUS_SICK);
        if (!in_array($type, $validTypes)) {
            jsonResponse(array('error' => 'Geçersiz durum'), 400);
        }

        if ($rangeEnd) {
            $result = setDailyStatusRange($targetUserId, $date, $rangeEnd, $type, $note);
        } else {
            $result = setDailyStatus($targetUserId, $date, $type, $note);
        }

        if (isset($result['error'])) {
            jsonResponse(array('error' => $result['error']), 400);
        }

        jsonResponse($result);
    }
} else {
    jsonResponse(array('error' => 'Method not allowed'), 405);
}