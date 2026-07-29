<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

header('Content-Type: application/json; charset=utf-8');
$currentUser = requireManagementAccess();
$userId = $currentUser['id'];
$isAdmin = isAdmin($currentUser);
$isViewer = $currentUser['role'] === 'VIEWER';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $filters = array();
    if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
    if (!empty($_GET['priority'])) $filters['priority'] = $_GET['priority'];
    if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
    if (isset($_GET['assigned'])) {
        if ($_GET['assigned'] === 'empty') $filters['assigned_empty'] = true;
        elseif ($_GET['assigned'] === 'not_empty') $filters['assigned_not_empty'] = true;
    }
    jsonResponse(getJiraTasks($filters));
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

if ($action === 'create') {
    $jiraLink = trim(isset($input['jira_link']) ? $input['jira_link'] : '');
    $title = trim(isset($input['title']) ? $input['title'] : '');
    if (!$jiraLink || !$title) {
        jsonResponse(array('error' => 'Jira linki ve açıklama gerekli'), 400);
    }
    $priority = isset($input['priority']) ? $input['priority'] : 'NORMAL';
    if (!in_array($priority, array('LOW', 'NORMAL', 'HIGH'))) $priority = 'NORMAL';

    $task = array(
        'id' => generateId(),
        'jira_link' => $jiraLink,
        'title' => $title,
        'priority' => $priority,
        'assigned_to' => '',
        'assigned_at' => '',
        'status' => 'PENDING',
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $userId
    );
    jsonResponse(saveJiraTask($task));
}

$taskId = isset($input['task_id']) ? $input['task_id'] : '';
$task = $taskId ? getJiraTaskById($taskId) : null;
if (!$task) jsonResponse(array('error' => 'İş bulunamadı'), 404);

if ($action === 'update') {
    $data = array('id' => $taskId);
    if (isset($input['jira_link'])) $data['jira_link'] = trim($input['jira_link']);
    if (isset($input['title'])) $data['title'] = trim($input['title']);
    if (isset($input['priority'])) {
        $p = $input['priority'];
        $data['priority'] = in_array($p, array('LOW', 'NORMAL', 'HIGH')) ? $p : $task['priority'];
    }
    jsonResponse(saveJiraTask($data));
}

if ($action === 'assign') {
    $assignedTo = isset($input['assigned_to']) ? $input['assigned_to'] : '';
    if (!$assignedTo || !getUserById($assignedTo)) {
        jsonResponse(array('error' => 'Geçerli bir kullanıcı seçin'), 400);
    }
    $data = array(
        'id' => $taskId,
        'assigned_to' => $assignedTo,
        'assigned_at' => date('Y-m-d H:i:s'),
        'status' => 'ASSIGNED'
    );
    jsonResponse(saveJiraTask($data));
}

if ($action === 'delete') {
    deleteJiraTask($taskId);
    jsonResponse(array('success' => true));
}

jsonResponse(array('error' => 'Geçersiz işlem'), 400);
