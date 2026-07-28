<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

header('Content-Type: application/json; charset=utf-8');
$currentUser = requireLogin();
$userId = $currentUser['id'];
$isAdmin = isAdmin($currentUser);
$isViewer = $currentUser['role'] === 'VIEWER';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
    $data = getOnCallData();
    $users = getAllUsers(true);
    $userMap = array();
    foreach ($users as $u) $userMap[$u['id']] = $u;
    $team = array();
    foreach ($data['team'] as $tid) {
        if (isset($userMap[$tid])) $team[] = $userMap[$tid];
    }
    $assignments = getOnCallAssignmentsForMonth($year, $month);
    $assignmentsWithNames = array();
    foreach ($assignments as $date => $uid) {
        $assignmentsWithNames[$date] = array(
            'user_id' => $uid,
            'name' => isset($userMap[$uid]) ? $userMap[$uid]['name'] : 'Bilinmeyen'
        );
    }
    jsonResponse(array(
        'team' => $team,
        'team_ids' => $data['team'],
        'assignments' => $assignmentsWithNames
    ));
}

if ($method !== 'POST') {
    jsonResponse(array('error' => 'Method not allowed'), 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;
if (!$input || empty($input['action'])) {
    jsonResponse(array('error' => 'Invalid request'), 400);
}

$csrf = isset($input['csrf_token']) ? $input['csrf_token'] : '';
if (!verifyCsrfToken($csrf)) {
    jsonResponse(array('error' => 'Invalid CSRF token'), 403);
}

if ($isViewer) {
    jsonResponse(array('error' => 'Salt okunur kullanıcılar değişiklik yapamaz.'), 403);
}

$action = $input['action'];

if ($action === 'set_team') {
    if (!$isAdmin) jsonResponse(array('error' => 'Only admins can set team'), 403);
    $teamIds = isset($input['team_ids']) ? $input['team_ids'] : array();
    if (!is_array($teamIds)) $teamIds = array();
    setOnCallTeam($teamIds);
    jsonResponse(array('success' => true, 'team_ids' => $teamIds));
}

if ($action === 'set_assignment') {
    $date = isset($input['date']) ? $input['date'] : '';
    $targetUserId = isset($input['user_id']) ? $input['user_id'] : '';
    if (!$date || !$targetUserId) {
        jsonResponse(array('error' => 'Tarih ve kullanıcı gerekli'), 400);
    }

    $data = getOnCallData();
    $team = $data['team'];
    $today = date('Y-m-d');

    // Permission check
    if ($isAdmin) {
        // Admin can edit any date
    } else {
        // Non-admin can only edit today and future dates
        if ($date < $today) {
            jsonResponse(array('error' => 'Geçmiş tarihleri yalnızca admin düzenleyebilir'), 403);
        }
        // Non-admin can only assign themselves or must be on team
        if (!in_array($targetUserId, $team)) {
            jsonResponse(array('error' => 'Bu kullanıcı nöbet ekibinde değil'), 403);
        }
        if ($targetUserId !== $userId && !$isAdmin) {
            jsonResponse(array('error' => 'Kendiniz dışında atama yapamazsınız'), 403);
        }
    }

    setOnCallAssignment($date, $targetUserId);
    jsonResponse(array('success' => true));
}

if ($action === 'remove_assignment') {
    $date = isset($input['date']) ? $input['date'] : '';
    if (!$date) jsonResponse(array('error' => 'Tarih gerekli'), 400);

    $today = date('Y-m-d');
    if (!$isAdmin && $date < $today) {
        jsonResponse(array('error' => 'Geçmiş tarihleri yalnızca admin düzenleyebilir'), 403);
    }

    removeOnCallAssignment($date);
    jsonResponse(array('success' => true));
}

if ($action === 'suggest') {
    $year = isset($input['year']) ? (int)$input['year'] : (int)date('Y');
    $month = isset($input['month']) ? (int)$input['month'] : (int)date('m');

    if (!$isAdmin) {
        $today = date('Y-m-d');
        $firstOfMonth = sprintf('%s-%02d-01', $year, $month);
        if ($firstOfMonth < $today) {
            jsonResponse(array('error' => 'Geçmiş aylar için öneri yalnızca admin tarafından yapılabilir'), 403);
        }
    }

    $suggestions = getOnCallSuggestions($year, $month);
    $users = getAllUsers(true);
    $userMap = array();
    foreach ($users as $u) $userMap[$u['id']] = $u;
    $result = array();
    foreach ($suggestions as $date => $uid) {
        $result[$date] = array(
            'user_id' => $uid,
            'name' => isset($userMap[$uid]) ? $userMap[$uid]['name'] : 'Bilinmeyen'
        );
    }
    jsonResponse(array('suggestions' => $result));
}

if ($action === 'apply_suggestions') {
    $year = isset($input['year']) ? (int)$input['year'] : (int)date('Y');
    $month = isset($input['month']) ? (int)$input['month'] : (int)date('m');

    if (!$isAdmin) {
        $today = date('Y-m-d');
        $firstOfMonth = sprintf('%s-%02d-01', $year, $month);
        if ($firstOfMonth < $today) {
            jsonResponse(array('error' => 'Geçmiş aylar için bu işlem yalnızca admin tarafından yapılabilir'), 403);
        }
    }

    $suggestions = getOnCallSuggestions($year, $month);
    foreach ($suggestions as $date => $uid) {
        setOnCallAssignment($date, $uid);
    }
    jsonResponse(array('success' => true, 'applied' => count($suggestions)));
}

jsonResponse(array('error' => 'Geçersiz işlem'), 400);
