<?php
/**
 * Migration script: SQLite -> JSON files
 * Run with: php migrate.php
 */
require_once __DIR__ . '/includes/config.php';

echo "Starting migration from SQLite...\n\n";

$sqlitePath = '/home/engin/projects/dailyTakip/prisma/dev.db';

if (!file_exists($sqlitePath)) {
    die("SQLite database not found at: $sqlitePath\n");
}

try {
    $pdo = new PDO('sqlite:' . $sqlitePath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Helper to convert timestamp
function convertTimestamp($ts) {
    if (empty($ts)) return date('Y-m-d H:i:s');
    // SQLite stores as milliseconds, PHP expects seconds
    $seconds = floor($ts / 1000);
    return date('Y-m-d H:i:s', $seconds);
}

// Load existing JSON data (if any)
$users = loadJson(DB_USERS);
$notes = loadJson(DB_NOTES);
$statuses = loadJson(DB_STATUSES);
$attendances = loadJson(DB_ATTENDANCES);

// Track existing IDs to avoid duplicates
$existingUserIds = array();
foreach ($users as $u) $existingUserIds[] = $u['id'];
$existingNoteIds = array();
foreach ($notes as $n) $existingNoteIds[] = $n['id'];
$existingStatusIds = array();
foreach ($statuses as $s) $existingStatusIds[] = $s['id'];
$existingAttendanceIds = array();
foreach ($attendances as $a) $existingAttendanceIds[] = $a['id'];

// 1. Migrate Users
echo "Migrating users...\n";
$stmt = $pdo->query("SELECT * FROM users");
$count = 0;
while ($row = $stmt->fetch()) {
    if (in_array($row['id'], $existingUserIds)) {
        echo "  Skipping existing user: {$row['email']}\n";
        continue;
    }
    $user = array(
        'id' => $row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'password_hash' => $row['password_hash'],
        'role' => $row['role'],
        'is_active' => (int)$row['is_active'],
        'created_at' => convertTimestamp($row['created_at'])
    );
    $users[] = $user;
    $existingUserIds[] = $row['id'];
    $count++;
}
saveJson(DB_USERS, $users);
echo "  Migrated $count users\n\n";

// 2. Migrate Daily Notes
echo "Migrating daily notes...\n";
$stmt = $pdo->query("SELECT * FROM daily_notes");
$count = 0;
while ($row = $stmt->fetch()) {
    if (in_array($row['id'], $existingNoteIds)) {
        continue;
    }
    $note = array(
        'id' => $row['id'],
        'user_id' => $row['user_id'],
        'date' => convertTimestamp($row['date']),
        'content' => $row['content'],
        'tags' => isset($row['tags']) ? $row['tags'] : '',
        'jira_link' => isset($row['jira_link']) ? $row['jira_link'] : '',
        'files' => isset($row['files']) ? $row['files'] : '[]',
        'created_at' => convertTimestamp($row['created_at'])
    );
    $notes[] = $note;
    $existingNoteIds[] = $row['id'];
    $count++;
}
saveJson(DB_NOTES, $notes);
echo "  Migrated $count notes\n\n";

// 3. Migrate Daily Statuses
echo "Migrating daily statuses...\n";
$stmt = $pdo->query("SELECT * FROM daily_statuses");
$count = 0;
while ($row = $stmt->fetch()) {
    if (in_array($row['id'], $existingStatusIds)) {
        continue;
    }
    $status = array(
        'id' => $row['id'],
        'user_id' => $row['user_id'],
        'date' => convertTimestamp($row['date']),
        'type' => $row['type'],
        'note' => isset($row['note']) ? $row['note'] : '',
        'created_at' => convertTimestamp($row['created_at'])
    );
    $statuses[] = $status;
    $existingStatusIds[] = $row['id'];
    $count++;
}
saveJson(DB_STATUSES, $statuses);
echo "  Migrated $count statuses\n\n";

// 4. Migrate Attendances
echo "Migrating attendances...\n";
$stmt = $pdo->query("SELECT * FROM attendances");
$count = 0;
while ($row = $stmt->fetch()) {
    if (in_array($row['id'], $existingAttendanceIds)) {
        continue;
    }
    $attendance = array(
        'id' => $row['id'],
        'user_id' => $row['user_id'],
        'date' => convertTimestamp($row['date']),
        'present' => (int)$row['present'],
        'note' => isset($row['note']) ? $row['note'] : '',
        'created_at' => convertTimestamp($row['created_at'])
    );
    $attendances[] = $attendance;
    $existingAttendanceIds[] = $row['id'];
    $count++;
}
saveJson(DB_ATTENDANCES, $attendances);
echo "  Migrated $count attendances\n\n";

echo "Migration completed successfully!\n";
echo "Summary:\n";
echo "  Users: " . count($users) . "\n";
echo "  Notes: " . count($notes) . "\n";
echo "  Statuses: " . count($statuses) . "\n";
echo "  Attendances: " . count($attendances) . "\n";