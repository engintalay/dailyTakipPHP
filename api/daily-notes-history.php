<?php
/**
 * Daily Notes History API - Son 1 ayın notlarını getirir
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireLogin();
$isAdmin = isAdmin($currentUser);
$effectiveUserId = getEffectiveUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(array('error' => 'Method not allowed'), 405);
}

// Son 1 ayın tarih aralığını hesapla
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-1 month'));

$filters = array(
    'user_id' => $effectiveUserId,
    'start_date' => $startDate,
    'end_date' => $endDate
);

$notes = getDailyNotes($filters);

// Notları formatla - mükerrer notları filtrele
$uniqueNotes = array();
$tasks = array();

foreach ($notes as $note) {
    // Benzersiz not kontrolü - aynı tarih ve içerikte tekrar etmesin
    $key = $note['date'] . '_' . md5($note['content']);
    
    if (!isset($uniqueNotes[$key])) {
        $uniqueNotes[$key] = true;
        
        $tasks[] = array(
            'id' => $note['id'],
            'date' => $note['date'],
            'content' => $note['content'],
            'jira_link' => $note['jira_link'],
            'tags' => $note['tags']
        );
    }
}

// Tarihe göre sırala (yeniden eskiye)
usort($tasks, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

jsonResponse($tasks);
