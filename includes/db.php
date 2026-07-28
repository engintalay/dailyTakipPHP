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
define('DB_TODOS', DB_DIR . 'todos.json');
define('DB_ONCALL', DB_DIR . 'on_call.json');
define('DB_HOLIDAYS', DB_DIR . 'holidays.json');

if (!is_dir(DB_DIR)) {
    mkdir(DB_DIR, 0755, true);
}

function _unicodeJsonReplace($m) {
    return html_entity_decode("&#" . hexdec($m[1]) . ";", ENT_COMPAT, "UTF-8");
}

function unicodeJsonEncode($data, $pretty = false) {
    $json = json_encode($data);
    if (!$json) return '[]';
    $json = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', '_unicodeJsonReplace', $json);
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
    $ret = file_put_contents($file, unicodeJsonEncode($data, true));
    if ($ret !== false) {
        @chmod($file, 0666);
    }
    return $ret;
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
    if (!file_exists(DB_TODOS)) saveJson(DB_TODOS, array());
    if (!file_exists(DB_ONCALL)) saveJson(DB_ONCALL, array('team' => array(), 'assignments' => array()));
    if (!file_exists(DB_HOLIDAYS)) saveJson(DB_HOLIDAYS, array());
    // Ensure all data files are writable (fix permission issues with ssh mount)
    $files = glob(DB_DIR . '*.json');
    if (is_array($files)) {
        foreach ($files as $f) {
            @chmod($f, 0666);
        }
    }
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

// Todo functions
function getTodos($filters = array()) {
    $todos = loadJson(DB_TODOS);
    $users = loadJson(DB_USERS);
    $userMap = array();
    foreach ($users as $u) $userMap[$u['id']] = $u['name'];

    $children = array();
    foreach ($todos as $todo) {
        if (!empty($todo['parent_id'])) {
            if (!isset($children[$todo['parent_id']])) $children[$todo['parent_id']] = 0;
            $children[$todo['parent_id']]++;
        }
    }

    $result = array();
    foreach ($todos as $todo) {
        if (!empty($filters['assigned_to']) && $todo['assigned_to'] !== $filters['assigned_to']) continue;
        if (!empty($filters['creator_id']) && $todo['creator_id'] !== $filters['creator_id']) continue;
        if (!empty($filters['parent_id']) && $todo['parent_id'] !== $filters['parent_id']) continue;
        if (!empty($filters['status']) && $todo['status'] !== $filters['status']) continue;
        if (empty($filters['include_done']) && $todo['status'] === 'DONE') continue;

        $todo['creator_name'] = isset($userMap[$todo['creator_id']]) ? $userMap[$todo['creator_id']] : 'Bilinmeyen';
        $todo['assignee_name'] = isset($userMap[$todo['assigned_to']]) ? $userMap[$todo['assigned_to']] : 'Atanmamış';
        $todo['subtask_count'] = isset($children[$todo['id']]) ? $children[$todo['id']] : 0;
        $result[] = $todo;
    }

    usort($result, function($a, $b) {
        if ($a['status'] === 'DONE' && $b['status'] !== 'DONE') return 1;
        if ($a['status'] !== 'DONE' && $b['status'] === 'DONE') return -1;
        return strcmp($b['created_at'], $a['created_at']);
    });
    return $result;
}

function getTodoById($id) {
    $todos = loadJson(DB_TODOS);
    foreach ($todos as $todo) {
        if ($todo['id'] === $id) return $todo;
    }
    return null;
}

function createTodo($creatorId, $title, $description = '', $assignedTo = '', $dueDate = '', $priority = 'NORMAL', $parentId = '') {
    $todos = loadJson(DB_TODOS);
    $todo = array(
        'id' => generateId(),
        'parent_id' => $parentId,
        'title' => $title,
        'description' => $description,
        'creator_id' => $creatorId,
        'assigned_to' => $assignedTo ? $assignedTo : $creatorId,
        'status' => 'TODO',
        'priority' => in_array($priority, array('LOW', 'NORMAL', 'HIGH')) ? $priority : 'NORMAL',
        'due_date' => $dueDate,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'completed_at' => ''
    );
    $todos[] = $todo;
    saveJson(DB_TODOS, $todos);
    return $todo;
}

function updateTodo($id, $data) {
    $todos = loadJson(DB_TODOS);
    $updated = null;
    foreach ($todos as &$todo) {
        if ($todo['id'] !== $id) continue;
        foreach (array('title', 'description', 'assigned_to', 'due_date', 'priority', 'parent_id') as $field) {
            if (isset($data[$field])) $todo[$field] = $data[$field];
        }
        if (isset($data['status']) && in_array($data['status'], array('TODO', 'IN_PROGRESS', 'DONE'))) {
            $todo['status'] = $data['status'];
            $todo['completed_at'] = $data['status'] === 'DONE' ? date('Y-m-d H:i:s') : '';
        }
        $todo['updated_at'] = date('Y-m-d H:i:s');
        $updated = $todo;
        break;
    }
    saveJson(DB_TODOS, $todos);
    return $updated;
}

function deleteTodo($id) {
    $todos = loadJson(DB_TODOS);
    $kept = array();
    foreach ($todos as $todo) {
        if ($todo['id'] !== $id && $todo['parent_id'] !== $id) $kept[] = $todo;
    }
    saveJson(DB_TODOS, $kept);
    return true;
}

// On-Call functions
function getOnCallData() {
    $data = loadJson(DB_ONCALL);
    if (!isset($data['team'])) $data['team'] = array();
    if (!isset($data['assignments'])) $data['assignments'] = array();
    return $data;
}

function saveOnCallData($data) {
    return saveJson(DB_ONCALL, $data);
}

function getOnCallTeam() {
    $data = getOnCallData();
    return $data['team'];
}

function setOnCallTeam($teamUserIds) {
    $data = getOnCallData();
    $data['team'] = $teamUserIds;
    return saveOnCallData($data);
}

function getOnCallAssignmentsForMonth($year, $month) {
    $data = getOnCallData();
    $result = array();
    $prefix = sprintf('%s-%02d-', $year, $month);
    foreach ($data['assignments'] as $a) {
        if (strpos($a['date'], $prefix) === 0) {
            $result[$a['date']] = $a['user_id'];
        }
    }
    return $result;
}

function getOnCallAssignmentsForRange($startDate, $endDate) {
    $data = getOnCallData();
    $result = array();
    foreach ($data['assignments'] as $a) {
        if ($a['date'] >= $startDate && $a['date'] <= $endDate) {
            $result[$a['date']] = $a['user_id'];
        }
    }
    return $result;
}

function getOnCallAssignment($date) {
    $data = getOnCallData();
    foreach ($data['assignments'] as $a) {
        if ($a['date'] === $date) return $a['user_id'];
    }
    return null;
}

function setOnCallAssignment($date, $userId) {
    $data = getOnCallData();
    $assignments = array();
    foreach ($data['assignments'] as $a) {
        if ($a['date'] !== $date) $assignments[] = $a;
    }
    $assignments[] = array('date' => $date, 'user_id' => $userId);
    $data['assignments'] = $assignments;
    return saveOnCallData($data);
}

function removeOnCallAssignment($date) {
    $data = getOnCallData();
    $assignments = array();
    foreach ($data['assignments'] as $a) {
        if ($a['date'] !== $date) $assignments[] = $a;
    }
    $data['assignments'] = $assignments;
    return saveOnCallData($data);
}

function getOnCallSuggestions($year, $month) {
    $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
    $startDate = sprintf('%s-%02d-01', $year, $month);
    $endDate = sprintf('%s-%02d-%02d', $year, $month, $daysInMonth);
    return getOnCallSuggestionsForRange($startDate, $endDate);
}

function getOnCallSuggestionsForRange($startDate, $endDate) {
    $data = getOnCallData();
    $team = $data['team'];
    if (empty($team)) return array();

    $holidays = getHolidays();
    $holidayDates = array();
    foreach ($holidays as $h) {
        $holidayDates[$h['date']] = true;
    }

    $existingAssignments = array();
    foreach ($data['assignments'] as $a) {
        $existingAssignments[$a['date']] = $a['user_id'];
    }

    $suggestions = array();
    $teamIdx = 0;
    $teamCount = count($team);

    list($sy, $sm, $sd) = explode('-', $startDate);
    list($ey, $em, $ed) = explode('-', $endDate);
    $startTs = mktime(0, 0, 0, (int)$sm, (int)$sd, (int)$sy);
    $endTs = mktime(0, 0, 0, (int)$em, (int)$ed, (int)$ey);

    // Find last assigned team member index from assignments before the range
    $lastDateBefore = '';
    foreach ($data['assignments'] as $a) {
        if ($a['date'] < $startDate && $a['date'] > $lastDateBefore) {
            $lastDateBefore = $a['date'];
        }
    }
    if ($lastDateBefore && isset($existingAssignments[$lastDateBefore])) {
        $lastUserId = $existingAssignments[$lastDateBefore];
        foreach ($team as $idx => $uid) {
            if ($uid === $lastUserId) {
                $teamIdx = ($idx + 1) % $teamCount;
                break;
            }
        }
    }

    for ($ts = $startTs; $ts <= $endTs; $ts = $ts + 86400) {
        $dateStr = date('Y-m-d', $ts);
        $dow = (int)date('w', $ts);

        // Skip weekends
        if ($dow === 0 || $dow === 6) continue;
        // Skip holidays
        if (isset($holidayDates[$dateStr])) continue;
        // Skip already assigned
        if (isset($existingAssignments[$dateStr])) continue;

        $suggestions[$dateStr] = $team[$teamIdx];
        $teamIdx = ($teamIdx + 1) % $teamCount;
    }

    return $suggestions;
}

// Holidays functions
function getHolidays() {
    return loadJson(DB_HOLIDAYS);
}

function getHoliday($date) {
    $holidays = loadJson(DB_HOLIDAYS);
    foreach ($holidays as $h) {
        if ($h['date'] === $date) return $h;
    }
    return null;
}

function saveHoliday($date, $name) {
    $holidays = loadJson(DB_HOLIDAYS);
    $found = false;
    foreach ($holidays as &$h) {
        if ($h['date'] === $date) {
            $h['name'] = $name;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $holidays[] = array('date' => $date, 'name' => $name);
    }
    return saveJson(DB_HOLIDAYS, $holidays);
}

function deleteHoliday($date) {
    $holidays = loadJson(DB_HOLIDAYS);
    $kept = array();
    foreach ($holidays as $h) {
        if ($h['date'] !== $date) $kept[] = $h;
    }
    return saveJson(DB_HOLIDAYS, $kept);
}

// Initialize on include
initDatabase();
