<?php
/**
 * Missing Notes API - returns users who haven't added notes in date range
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

header('Content-Type: application/json; charset=utf-8');

requireLogin();

$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-d');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');

$users = getAllUsers(true);
$notes = getDailyNotes(array('start_date' => $startDate, 'end_date' => $endDate));

$userIdsWithNotes = array();
foreach ($notes as $n) {
    $userIdsWithNotes[] = $n['user_id'];
}

$missingUsers = array_filter($users, function($u) use ($userIdsWithNotes) {
    return !in_array($u['id'], $userIdsWithNotes);
});

jsonResponse(array_values($missingUsers));