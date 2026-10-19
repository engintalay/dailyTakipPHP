<?php
/**
 * Evaluations API
 * PHP 5.3 compatible
 *
 * Tüm veri işlemleri includes/db.php fonksiyonlarıyla yapılır.
 * Yetki: sadece ADMIN. CSRF korumalı (yazma işlemleri).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

header('Content-Type: application/json; charset=utf-8');

// Sadece admin erişebilir (requireLogin + isAdmin)
$currentUser = requireLogin();
if (!isAdmin($currentUser)) {
    jsonResponse(array('success' => false, 'error' => 'Admin yetkisi gerekli'), 403);
}

$method = $_SERVER['REQUEST_METHOD'];

// Girişi hem form-data hem JSON gövdesinden topla
$input = $_POST;
if ($method === 'POST' && empty($input)) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = $json;
        } else {
            parse_str($raw, $parsed);
            if (is_array($parsed)) $input = $parsed;
        }
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : (isset($input['action']) ? $input['action'] : '');

// Yazma işlemleri için CSRF doğrula
$writeActions = array(
    'create-period', 'delete-period',
    'create-criterion', 'delete-criterion',
    'create-evaluation', 'update-evaluation', 'delete-evaluation'
);
if (in_array($action, $writeActions)) {
    $token = isset($input['csrf_token']) ? $input['csrf_token'] : '';
    if (!verifyCsrfToken($token)) {
        jsonResponse(array('success' => false, 'error' => 'Geçersiz CSRF token'), 403);
    }
}

switch ($action) {
    // ---------- Dönemler ----------
    case 'get-periods':
        jsonResponse(array('success' => true, 'periods' => getEvaluationPeriods()));
        break;

    case 'create-period':
        $name = sanitizeInput(isset($input['name']) ? $input['name'] : '');
        $startDate = sanitizeInput(isset($input['start_date']) ? $input['start_date'] : '');
        $endDate = sanitizeInput(isset($input['end_date']) ? $input['end_date'] : '');
        if ($name === '' || $startDate === '' || $endDate === '') {
            jsonResponse(array('success' => false, 'error' => 'Dönem adı, başlangıç ve bitiş tarihi zorunludur'));
        }
        $period = createEvaluationPeriod($name, $startDate, $endDate);
        jsonResponse(array('success' => true, 'period' => $period));
        break;

    case 'delete-period':
        $id = sanitizeInput(isset($input['id']) ? $input['id'] : '');
        if ($id === '') jsonResponse(array('success' => false, 'error' => 'ID gerekli'));
        deleteEvaluationPeriod($id);
        jsonResponse(array('success' => true));
        break;

    // ---------- Kriterler ----------
    case 'get-criteria':
        jsonResponse(array('success' => true, 'criteria' => getEvaluationCriteria()));
        break;

    case 'create-criterion':
        $name = sanitizeInput(isset($input['name']) ? $input['name'] : '');
        $description = sanitizeInput(isset($input['description']) ? $input['description'] : '');
        if ($name === '') {
            jsonResponse(array('success' => false, 'error' => 'Kriter adı gerekli'));
        }
        $criterion = createEvaluationCriteria($name, $description);
        jsonResponse(array('success' => true, 'criterion' => $criterion));
        break;

    case 'delete-criterion':
        $id = sanitizeInput(isset($input['id']) ? $input['id'] : '');
        if ($id === '') jsonResponse(array('success' => false, 'error' => 'ID gerekli'));
        deleteEvaluationCriteria($id);
        jsonResponse(array('success' => true));
        break;

    // ---------- Değerlendirmeler ----------
    case 'get-evaluations':
        $filters = array();
        if (!empty($_GET['period_id'])) $filters['period_id'] = $_GET['period_id'];
        if (!empty($_GET['user_id'])) $filters['user_id'] = $_GET['user_id'];
        jsonResponse(array('success' => true, 'evaluations' => getEvaluations($filters)));
        break;

    case 'get-user-evaluations':
        $userId = sanitizeInput(isset($_GET['user_id']) ? $_GET['user_id'] : '');
        if ($userId === '') jsonResponse(array('success' => false, 'error' => 'Kullanıcı ID gerekli'));
        jsonResponse(array('success' => true, 'evaluations' => getEvaluations(array('user_id' => $userId))));
        break;

    case 'get-evaluation':
        $id = sanitizeInput(isset($_GET['id']) ? $_GET['id'] : '');
        $evaluation = getEvaluationById($id);
        if (!$evaluation) jsonResponse(array('success' => false, 'error' => 'Değerlendirme bulunamadı'));
        jsonResponse(array('success' => true, 'evaluation' => $evaluation));
        break;

    case 'create-evaluation':
        $userId = sanitizeInput(isset($input['user_id']) ? $input['user_id'] : '');
        $periodId = sanitizeInput(isset($input['period_id']) ? $input['period_id'] : '');
        $notes = sanitizeInput(isset($input['notes']) ? $input['notes'] : '');
        $scores = _parseScores(isset($input['scores']) ? $input['scores'] : array());

        if ($userId === '' || $periodId === '') {
            jsonResponse(array('success' => false, 'error' => 'Dönem ve çalışan gerekli'));
        }
        // Aynı dönem+kullanıcı için zaten varsa güncelle
        $existing = getEvaluations(array('period_id' => $periodId, 'user_id' => $userId));
        if (!empty($existing)) {
            $updated = updateEvaluation($existing[0]['id'], array('scores' => $scores, 'notes' => $notes));
            jsonResponse(array('success' => true, 'evaluation' => $updated, 'updated' => true));
        }
        $evaluation = createEvaluation($userId, $periodId, $scores, $notes);
        jsonResponse(array('success' => true, 'evaluation' => $evaluation));
        break;

    case 'update-evaluation':
        $id = sanitizeInput(isset($input['id']) ? $input['id'] : '');
        if ($id === '') jsonResponse(array('success' => false, 'error' => 'ID gerekli'));
        $notes = sanitizeInput(isset($input['notes']) ? $input['notes'] : '');
        $scores = _parseScores(isset($input['scores']) ? $input['scores'] : array());
        $updated = updateEvaluation($id, array('scores' => $scores, 'notes' => $notes));
        jsonResponse(array('success' => true, 'evaluation' => $updated));
        break;

    case 'delete-evaluation':
        $id = sanitizeInput(isset($input['id']) ? $input['id'] : '');
        if ($id === '') jsonResponse(array('success' => false, 'error' => 'ID gerekli'));
        deleteEvaluation($id);
        jsonResponse(array('success' => true));
        break;

    case 'calculate-weighted':
        $scores = _parseScores(isset($input['scores']) ? $input['scores'] : array());
        $criteria = getEvaluationCriteria();
        $result = calculateWeightedEvaluation($scores, $criteria);
        jsonResponse(array('success' => true, 'result' => $result));
        break;

    default:
        jsonResponse(array('success' => false, 'error' => 'Bilinmeyen işlem: ' . $action));
}

/**
 * Puanları normalize et. Hem dizi (form: scores[criterionId]=4) hem
 * JSON string ({"id":4}) kabul eder. Değerleri 0-10 aralığına kısar.
 */
function _parseScores($raw) {
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : array();
    }
    if (!is_array($raw)) return array();
    $scores = array();
    foreach ($raw as $criterionId => $value) {
        $v = (int)$value;
        if ($v < 0) $v = 0;
        if ($v > 10) $v = 10;
        $scores[$criterionId] = $v;
    }
    return $scores;
}
