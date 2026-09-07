<?php
/**
 * Daily Tasks History API - Son 1 ayın görevlerini getirir
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireLogin();
$isAdmin = isAdmin($currentUser);
$effectiveUserId = getEffectiveUserId();

// Sadece kendi veya admin olarak atanmış görevleri al
$userId = $effectiveUserId;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(array('error' => 'Method not allowed'), 405);
}

// Son 1 ayın tarih aralığını hesapla
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-1 month'));

$filters = array(
    'user_id' => $userId,
    'start_date' => $startDate,
    'end_date' => $endDate
);

$notes = getDailyNotes($filters);

// Jira linklerini topla (mükerrerleri filtrele)
$uniqueLinks = array();
$tasks = array();

foreach ($notes as $note) {
    if (empty($note['jira_link'])) continue;
    
    $links = preg_split('/[\r\n,]+/', $note['jira_link'], -1, PREG_SPLIT_NO_EMPTY);
    foreach ($links as $link) {
        $link = trim($link);
        if (empty($link)) continue;
        
        // Jira issue key formatı (örn. EBLGANALIZ-3) girilmişse tam URL'ye tamamla
        $url = $link;
        if (preg_match('/^[A-Za-z0-9]+-[0-9]+$/', $link)) {
            $url = 'https://jira.gelirler.gov.tr/browse/' . $link;
        }
        
        // Mükerrer kontrolü
        if (!isset($uniqueLinks[$url])) {
            $uniqueLinks[$url] = true;
            $tasks[] = array(
                'jira_link' => $url,
                'title' => '',
                'date' => $note['date'],
                'note_content' => substr($note['content'], 0, 200),
                'note_id' => $note['id']
            );
        }
    }
}

// Tarihe göre sırala (yeniden eskiye)
usort($tasks, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

jsonResponse($tasks);
