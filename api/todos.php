<?php
/**
 * Todo API
 * PHP 5.3 compatible
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

header('Content-Type: application/json; charset=utf-8');
$currentUser = requireLogin();
$userId = $currentUser['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $filters = array('include_done' => isset($_GET['include_done']) ? $_GET['include_done'] : '');
    if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
    if (!empty($_GET['assigned_to'])) $filters['assigned_to'] = $_GET['assigned_to'];
    jsonResponse(getTodos($filters));
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

$action = $input['action'];
$todoId = isset($input['todo_id']) ? $input['todo_id'] : '';
$todo = $todoId ? getTodoById($todoId) : null;

if ($action === 'create') {
    $title = trim(isset($input['title']) ? $input['title'] : '');
    if (!$title) jsonResponse(array('error' => 'Görev başlığı gerekli'), 400);

    $assignedTo = isset($input['assigned_to']) ? $input['assigned_to'] : '';
    if ($assignedTo && !getUserById($assignedTo)) $assignedTo = $userId;
    $parentId = isset($input['parent_id']) ? $input['parent_id'] : '';
    if ($parentId && !getTodoById($parentId)) $parentId = '';

    $created = createTodo(
        $userId,
        $title,
        isset($input['description']) ? trim($input['description']) : '',
        $assignedTo,
        isset($input['due_date']) ? $input['due_date'] : '',
        isset($input['priority']) ? $input['priority'] : 'NORMAL',
        $parentId
    );
    jsonResponse($created);
}

if (!$todo) jsonResponse(array('error' => 'Görev bulunamadı'), 404);

if ($action === 'claim') {
    if ($todo['status'] === 'DONE') jsonResponse(array('error' => 'Tamamlanmış görev devralınamaz'), 400);
    jsonResponse(updateTodo($todoId, array('assigned_to' => $userId)));
}

$isAdmin = isAdmin($currentUser);
$canEdit = $isAdmin || $todo['creator_id'] === $userId || $todo['assigned_to'] === $userId;
if (!$canEdit) jsonResponse(array('error' => 'Bu görev için yetkiniz yok'), 403);

if ($action === 'update') {
    $data = array();
    foreach (array('title', 'description', 'assigned_to', 'due_date', 'priority', 'parent_id', 'status') as $field) {
        if (isset($input[$field])) $data[$field] = $input[$field];
    }
    if (isset($data['assigned_to']) && $data['assigned_to'] && !getUserById($data['assigned_to'])) {
        unset($data['assigned_to']);
    }
    jsonResponse(updateTodo($todoId, $data));
}

if ($action === 'delete') {
    if (!$isAdmin && $todo['creator_id'] !== $userId) {
        jsonResponse(array('error' => 'Bu görevi silme yetkiniz yok'), 403);
    }
    deleteTodo($todoId);
    jsonResponse(array('success' => true));
}

jsonResponse(array('error' => 'Geçersiz işlem'), 400);
