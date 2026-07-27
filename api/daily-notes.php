<?php
/**
 * Daily Notes API
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireLogin();
$isAdmin = isAdmin($currentUser);
$effectiveUserId = getEffectiveUserId();

$method = $_SERVER['REQUEST_METHOD'];
$noteId = isset($_GET['id']) ? $_GET['id'] : null;

if ($method === 'GET') {
    $filters = array();
    if (!empty($_GET['userId'])) $filters['user_id'] = $_GET['userId'];
    if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
    if (!empty($_GET['tag'])) $filters['tag'] = $_GET['tag'];
    if (!empty($_GET['startDate'])) $filters['start_date'] = $_GET['startDate'];
    if (!empty($_GET['endDate'])) $filters['end_date'] = $_GET['endDate'];
    if (!empty($_GET['limit'])) $filters['limit'] = (int)$_GET['limit'];
    if (!empty($_GET['offset'])) $filters['offset'] = (int)$_GET['offset'];

    if ($noteId) {
        $note = getDailyNoteById($noteId);
        if ($note) {
            jsonResponse($note);
        } else {
            jsonResponse(array('error' => 'Not found'), 404);
        }
    } else {
        $notes = getDailyNotes($filters);
        jsonResponse($notes);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = $_POST;

    if (!$input || empty($input['action'])) {
        jsonResponse(array('error' => 'Invalid request'), 400);
    }

    $csrf = isset($input['csrf_token']) ? $input['csrf_token'] : '';
    if (!verifyCsrfToken($csrf)) {
        jsonResponse(array('error' => 'Invalid CSRF token'), 403);
    }

    if ($input['action'] === 'create') {
        if (empty($input['date']) || empty($input['content'])) {
            jsonResponse(array('error' => 'Tarih ve içerik gerekli'), 400);
        }

        $userId = ($isAdmin && !empty($input['user_id'])) ? $input['user_id'] : $effectiveUserId;

        $note = createDailyNote(
            $userId,
            $input['date'],
            $input['content'],
            isset($input['tags']) ? $input['tags'] : '',
            isset($input['jira_link']) ? $input['jira_link'] : '',
            isset($input['files']) ? $input['files'] : '[]'
        );

        jsonResponse($note);
    } elseif ($input['action'] === 'update' && $noteId) {
        $data = array();
        if (!empty($input['date'])) $data['date'] = $input['date'];
        if (!empty($input['content'])) $data['content'] = $input['content'];
        if (isset($input['tags'])) $data['tags'] = $input['tags'];
        if (isset($input['jira_link'])) $data['jira_link'] = $input['jira_link'];
        if (isset($input['files'])) $data['files'] = $input['files'];

        $note = updateDailyNote($noteId, $data);
        jsonResponse($note);
    }
} elseif ($method === 'DELETE' && $noteId) {
    $deleteInput = json_decode(file_get_contents('php://input'), true);
    $csrf = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
    if (!$csrf && is_array($deleteInput) && isset($deleteInput['csrf_token'])) {
        $csrf = $deleteInput['csrf_token'];
    }
    if (!verifyCsrfToken($csrf)) {
        jsonResponse(array('error' => 'Invalid CSRF token'), 403);
    }

    $note = getDailyNoteById($noteId);
    if (!$note) {
        jsonResponse(array('error' => 'Not found'), 404);
    }

    if (!$isAdmin && $note['user_id'] !== $effectiveUserId) {
        jsonResponse(array('error' => 'Unauthorized'), 403);
    }

    deleteDailyNote($noteId);
    jsonResponse(array('success' => true));
} else {
    jsonResponse(array('error' => 'Method not allowed'), 405);
}
