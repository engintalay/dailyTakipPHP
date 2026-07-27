<?php
/**
 * JSON File-based Database for PHP 5.3+ (no SQLite required)
 * Simple flat-file storage compatible with the existing API
 */

define('DB_DIR', __DIR__ . '/../data/');
define('DB_USERS', DB_DIR . 'users.json');
define('DB_NOTES', DB_DIR . 'daily_notes.json');
define('DB_STATUSES', DB_DIR . 'daily_statuses.json');
define('DB_ATTENDANCES', DB_DIR . 'attendances.json');

if (!is_dir(DB_DIR)) {
    mkdir(DB_DIR, 0755, true);
}

function unicodeJsonEncode($data, $pretty = false) {
    $json = json_encode($data);
    if (!$json) return '[]';
    $json = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', create_function('$m', 'return html_entity_decode("&#" . hexdec($m[1]) . ";", ENT_COMPAT, "UTF-8");'), $json);
    if ($pretty) {
        $result = '';
        $indent = 0;
        $len = strlen($json);
        $inStr = false;
        $esc = false;
        for ($i = 0; $i < $len; $i++) {
            $c = $json[$i];
            if ($esc) { $result .= $c; $esc = false; continue; }
            if ($c == '"' && !$inStr) $inStr = true;
            elseif ($c == '"' && $inStr) $inStr = false;
            elseif ($c == '\\' && $inStr) $esc = true;
            if (!$inStr) {
                if ($c == '{' || $c == '[') {
                    $result .= $c . "\n" . str_repeat('  ', ++$indent);
                    continue;
                }
                if ($c == '}' || $c == ']') {
                    $result .= "\n" . str_repeat('  ', --$indent) . $c;
                    continue;
                }
                if ($c == ',') {
                    $result .= ",\n" . str_repeat('  ', $indent);
                    continue;
                }
                if ($c == ':') {
                    $result .= ': ';
                    continue;
                }
            }
            $result .= $c;
        }
        return $result;
    }
    return $json;
}

function loadJson($file) {
    if (!file_exists($file)) return array();
    $content = file_get_contents($file);
    if (empty($content)) return array();
    $data = json_decode($content, true);
    return is_array($data) ? $data : array();
}

function saveJson($file, $data) {
    return file_put_contents($file, unicodeJsonEncode($data, true));
}

function db() {
    return null; // Not using PDO anymore
}

function initDatabase() {
    // Ensure files exist
    if (!file_exists(DB_USERS)) saveJson(DB_USERS, array());
    if (!file_exists(DB_NOTES)) saveJson(DB_NOTES, array());
    if (!file_exists(DB_STATUSES)) saveJson(DB_STATUSES, array());
    if (!file_exists(DB_ATTENDANCES)) saveJson(DB_ATTENDANCES, array());
}

function seedDefaultUsers() {
    $users = array(
        array(
            'id' => generateId(),
            'name' => 'Admin',
            'email' => 'admin@dailytakip.com',
            'password_hash' => passwordHash('admin123'),
            'role' => 'ADMIN',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ),
        array(
            'id' => generateId(),
            'name' => 'Ali Yılmaz',
            'email' => 'ali@dailytakip.com',
            'password_hash' => passwordHash('user123'),
            'role' => 'MEMBER',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ),
        array(
            'id' => generateId(),
            'name' => 'Ayşe Demir',
            'email' => 'ayse@dailytakip.com',
            'password_hash' => passwordHash('user123'),
            'role' => 'MEMBER',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        )
    );
    saveJson(DB_USERS, $users);
}

function generateId() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function passwordHash($password) {
    if (function_exists('password_hash')) {
        return password_hash($password, PASSWORD_BCRYPT, array('cost' => 12));
    }
    $salt = '$2y$12$' . substr(str_replace('+', '.', base64_encode(openssl_random_pseudo_bytes(16))), 0, 22);
    return crypt($password, $salt);
}

function verifyPassword($password, $hash) {
    if (function_exists('password_verify')) {
        return password_verify($password, $hash);
    }
    return crypt($password, $hash) === $hash;
}

// User functions
function getUserById($id) {
    $users = loadJson(DB_USERS);
    foreach ($users as $u) {
        if ($u['id'] === $id) return $u;
    }
    return null;
}

function getUserByEmail($email) {
    $users = loadJson(DB_USERS);
    foreach ($users as $u) {
        if ($u['email'] === $email) return $u;
    }
    return null;
}

function getAllUsers($activeOnly = true) {
    $users = loadJson(DB_USERS);
    if ($activeOnly) {
        $users = array_filter($users, function($u) { return $u['is_active']; });
    }
    usort($users, function($a, $b) { return strcmp($a['name'], $b['name']); });
    return array_values($users);
}

function createUser($name, $email, $password, $role = 'MEMBER') {
    if (getUserByEmail($email)) {
        return array('error' => 'Bu e-posta zaten kayıtlı');
    }
    $users = loadJson(DB_USERS);
    $user = array(
        'id' => generateId(),
        'name' => $name,
        'email' => $email,
        'password_hash' => passwordHash($password),
        'role' => $role,
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s')
    );
    $users[] = $user;
    saveJson(DB_USERS, $users);
    return $user;
}

function updateUser($id, $data) {
    $users = loadJson(DB_USERS);
    foreach ($users as &$u) {
        if ($u['id'] === $id) {
            if (isset($data['name'])) $u['name'] = $data['name'];
            if (isset($data['email'])) $u['email'] = $data['email'];
            if (isset($data['role'])) $u['role'] = $data['role'];
            if (isset($data['is_active'])) $u['is_active'] = $data['is_active'] ? 1 : 0;
            if (isset($data['password']) && !empty($data['password'])) {
                $u['password_hash'] = passwordHash($data['password']);
            }
            break;
        }
    }
    saveJson(DB_USERS, $users);
    return getUserById($id);
}

function deleteUser($id) {
    $users = loadJson(DB_USERS);
    $users = array_filter($users, function($u) use ($id) { return $u['id'] !== $id; });
    saveJson(DB_USERS, array_values($users));
    return true;
}

function changePassword($userId, $currentPassword, $newPassword) {
    $user = getUserById($userId);
    if (!$user || !verifyPassword($currentPassword, $user['password_hash'])) {
        return array('error' => 'Mevcut şifre hatalı');
    }
    if (strlen($newPassword) < 6) {
        return array('error' => 'Yeni şifre en az 6 karakter olmalı');
    }
    return updateUser($userId, array('password' => $newPassword));
}

// Daily Notes functions
function getDailyNotes($filters = array()) {
    $notes = loadJson(DB_NOTES);
    $users = loadJson(DB_USERS);
    $userMap = array();
    foreach ($users as $u) $userMap[$u['id']] = $u;

    $result = array();
    foreach ($notes as $n) {
        if (!empty($filters['user_id']) && $n['user_id'] !== $filters['user_id']) continue;
        if (!empty($filters['search']) && stripos($n['content'], $filters['search']) === false) continue;
        if (!empty($filters['tag']) && stripos($n['tags'], $filters['tag']) === false) continue;
        if (!empty($filters['start_date']) && $n['date'] < $filters['start_date']) continue;
        if (!empty($filters['end_date']) && $n['date'] > $filters['end_date'] . ' 23:59:59') continue;

        $n['name'] = isset($userMap[$n['user_id']]) ? $userMap[$n['user_id']]['name'] : 'Bilinmeyen';
        $n['email'] = isset($userMap[$n['user_id']]) ? $userMap[$n['user_id']]['email'] : '';
        $result[] = $n;
    }

    usort($result, function($a, $b) {
        $dateCompare = strcmp($b['date'], $a['date']);
        if ($dateCompare !== 0) return $dateCompare;
        $aCreated = isset($a['created_at']) ? $a['created_at'] : '';
        $bCreated = isset($b['created_at']) ? $b['created_at'] : '';
        return strcmp($bCreated, $aCreated);
    });

    if (!empty($filters['limit'])) $result = array_slice($result, 0, (int)$filters['limit']);
    if (!empty($filters['offset'])) $result = array_slice($result, (int)$filters['offset']);

    return $result;
}

function getDailyNoteById($id) {
    $notes = loadJson(DB_NOTES);
    foreach ($notes as $n) {
        if ($n['id'] === $id) return $n;
    }
    return null;
}

function createDailyNote($userId, $date, $content, $tags = '', $jiraLink = '', $files = '[]') {
    $notes = loadJson(DB_NOTES);
    $note = array(
        'id' => generateId(),
        'user_id' => $userId,
        'date' => $date,
        'content' => $content,
        'tags' => $tags,
        'jira_link' => $jiraLink,
        'files' => $files,
        'created_at' => date('Y-m-d H:i:s')
    );
    $notes[] = $note;
    saveJson(DB_NOTES, $notes);
    return $note;
}

function updateDailyNote($id, $data) {
    $notes = loadJson(DB_NOTES);
    foreach ($notes as &$n) {
        if ($n['id'] === $id) {
            if (isset($data['date'])) $n['date'] = $data['date'];
            if (isset($data['content'])) $n['content'] = $data['content'];
            if (isset($data['tags'])) $n['tags'] = $data['tags'];
            if (isset($data['jira_link'])) $n['jira_link'] = $data['jira_link'];
            if (isset($data['files'])) $n['files'] = $data['files'];
            break;
        }
    }
    saveJson(DB_NOTES, $notes);
    return getDailyNoteById($id);
}

function deleteDailyNote($id) {
    $notes = loadJson(DB_NOTES);
    $notes = array_filter($notes, function($n) use ($id) { return $n['id'] !== $id; });
    saveJson(DB_NOTES, array_values($notes));
    return true;
}

// Daily Status functions
function getDailyStatuses($filters = array()) {
    $statuses = loadJson(DB_STATUSES);
    $users = loadJson(DB_USERS);
    $userMap = array();
    foreach ($users as $u) $userMap[$u['id']] = $u;

    $result = array();
    foreach ($statuses as $s) {
        if (!empty($filters['user_id']) && $s['user_id'] !== $filters['user_id']) continue;
        if (!empty($filters['date'])) {
            $d = $filters['date'];
            $next = date('Y-m-d', strtotime($d . ' +1 day'));
            if ($s['date'] < $d || $s['date'] >= $next) continue;
        }
        if (!empty($filters['start_date']) && $s['date'] < $filters['start_date']) continue;
        if (!empty($filters['end_date']) && $s['date'] > $filters['end_date'] . ' 23:59:59') continue;

        $s['name'] = isset($userMap[$s['user_id']]) ? $userMap[$s['user_id']]['name'] : 'Bilinmeyen';
        $s['email'] = isset($userMap[$s['user_id']]) ? $userMap[$s['user_id']]['email'] : '';
        $result[] = $s;
    }

    usort($result, function($a, $b) { return strcmp($b['date'], $a['date']); });

    if (!empty($filters['limit'])) $result = array_slice($result, 0, (int)$filters['limit']);

    return $result;
}

function getDailyStatusByUserAndDate($userId, $date) {
    $statuses = loadJson(DB_STATUSES);
    foreach ($statuses as $s) {
        if ($s['user_id'] === $userId && strpos($s['date'], $date) === 0) {
            return $s;
        }
    }
    return null;
}

function setDailyStatus($userId, $date, $type, $note = '') {
    $validTypes = array('OFFICE', 'REMOTE', 'LEAVE', 'SICK');
    if (!in_array($type, $validTypes)) {
        return array('error' => 'Geçersiz durum');
    }

    $statuses = loadJson(DB_STATUSES);
    $found = false;
    foreach ($statuses as &$s) {
        if ($s['user_id'] === $userId && strpos($s['date'], $date) === 0) {
            $s['type'] = $type;
            $s['note'] = $note;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $statuses[] = array(
            'id' => generateId(),
            'user_id' => $userId,
            'date' => $date,
            'type' => $type,
            'note' => $note,
            'created_at' => date('Y-m-d H:i:s')
        );
    }
    saveJson(DB_STATUSES, $statuses);
    return getDailyStatusByUserAndDate($userId, $date);
}

function setDailyStatusRange($userId, $startDate, $endDate, $type, $note = '') {
    $validTypes = array('OFFICE', 'REMOTE', 'LEAVE', 'SICK');
    if (!in_array($type, $validTypes)) {
        return array('error' => 'Geçersiz durum');
    }

    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $results = array();

    for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
        $dateStr = $d->format('Y-m-d');
        $results[] = setDailyStatus($userId, $dateStr, $type, $note);
    }
    return $results;
}

function deleteDailyStatus($userId, $date) {
    $statuses = loadJson(DB_STATUSES);
    $statuses = array_filter($statuses, function($s) use ($userId, $date) {
        return !($s['user_id'] === $userId && strpos($s['date'], $date) === 0);
    });
    saveJson(DB_STATUSES, array_values($statuses));
    return true;
}

// Attendance functions
function getAttendances($filters = array()) {
    $attendances = loadJson(DB_ATTENDANCES);
    $users = loadJson(DB_USERS);
    $userMap = array();
    foreach ($users as $u) $userMap[$u['id']] = $u;

    $result = array();
    foreach ($attendances as $a) {
        if (!empty($filters['user_id']) && $a['user_id'] !== $filters['user_id']) continue;
        if (!empty($filters['start_date']) && $a['date'] < $filters['start_date']) continue;
        if (!empty($filters['end_date']) && $a['date'] > $filters['end_date'] . ' 23:59:59') continue;

        $a['name'] = isset($userMap[$a['user_id']]) ? $userMap[$a['user_id']]['name'] : 'Bilinmeyen';
        $a['email'] = isset($userMap[$a['user_id']]) ? $userMap[$a['user_id']]['email'] : '';
        $result[] = $a;
    }

    usort($result, function($a, $b) {
        $cmp = strcmp($b['date'], $a['date']);
        if ($cmp === 0) return strcmp($a['user_id'], $b['user_id']);
        return $cmp;
    });

    return $result;
}

function getAttendanceByUserAndDate($userId, $date) {
    $attendances = loadJson(DB_ATTENDANCES);
    foreach ($attendances as $a) {
        if ($a['user_id'] === $userId && strpos($a['date'], $date) === 0) {
            return $a;
        }
    }
    return null;
}

function setAttendance($userId, $date, $present = true, $note = '') {
    $attendances = loadJson(DB_ATTENDANCES);
    $found = false;
    foreach ($attendances as &$a) {
        if ($a['user_id'] === $userId && strpos($a['date'], $date) === 0) {
            $a['present'] = $present ? 1 : 0;
            $a['note'] = $note;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $attendances[] = array(
            'id' => generateId(),
            'user_id' => $userId,
            'date' => $date,
            'present' => $present ? 1 : 0,
            'note' => $note,
            'created_at' => date('Y-m-d H:i:s')
        );
    }
    saveJson(DB_ATTENDANCES, $attendances);
    return getAttendanceByUserAndDate($userId, $date);
}

// Initialize on include
initDatabase();
