<?php
/**
 * Seed script for initial data (JSON file-based)
 */
require_once __DIR__ . '/includes/config.php';

echo "Seeding database...\n";

// Check if users already exist
$users = loadJson(DB_USERS);
if (!empty($users)) {
    echo "Database already seeded. Skipping.\n";
    exit(0);
}

// Default passwords
$adminHash = passwordHash('admin123');
$userHash = passwordHash('user123');

$users = array(
    array(
        'id' => generateId(),
        'name' => 'Admin',
        'email' => 'admin@dailytakip.com',
        'password_hash' => $adminHash,
        'role' => ROLE_ADMIN,
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ),
    array(
        'id' => generateId(),
        'name' => 'Ali Yılmaz',
        'email' => 'ali@dailytakip.com',
        'password_hash' => $userHash,
        'role' => ROLE_MEMBER,
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ),
    array(
        'id' => generateId(),
        'name' => 'Ayşe Demir',
        'email' => 'ayse@dailytakip.com',
        'password_hash' => $userHash,
        'role' => ROLE_MEMBER,
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s')
    )
);

saveJson(DB_USERS, $users);

echo "Seed data created:\n";
echo "  Admin: admin@dailytakip.com / admin123\n";
echo "  Ali:   ali@dailytakip.com   / user123\n";
echo "  Ayşe:  ayse@dailytakip.com  / user123\n";