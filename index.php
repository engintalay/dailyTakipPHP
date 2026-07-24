<?php
/**
 * Dashboard page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/models.php';

$user = requireLogin();
$isAdmin = isAdmin($user);
$effectiveUserId = getEffectiveUserId();
$effectiveUser = getEffectiveUser();

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Get all active users
$users = getAllUsers(true);

// Today's statuses
$todayStatuses = getDailyStatuses(array('start_date' => $today, 'end_date' => $tomorrow));
$statusMap = array();
foreach ($todayStatuses as $s) {
    $statusMap[$s['user_id']] = $s;
}

// Recent notes
$recentNotes = getDailyNotes(array('limit' => 10));

// Users missing notes today
$todayNotes = getDailyNotes(array('start_date' => $today, 'end_date' => $tomorrow));
$userIdsWithNote = array();
foreach ($todayNotes as $n) $userIdsWithNote[] = $n['user_id'];
$missingUsers = array_filter($users, function($u) use ($userIdsWithNote) {
    return !in_array($u['id'], $userIdsWithNote);
});

$pageTitle = 'Dashboard';
$currentPath = 'index.php';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <div class="flex gap-2">
            <a href="<?php echo APP_URL; ?>pages/daily.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">
                + Not Ekle
            </a>
            <a href="<?php echo APP_URL; ?>pages/status.php" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                Durum Belirt
            </a>
        </div>
    </div>

    <?php if (!empty($missingUsers)): ?>
    <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
            <span>⏳</span>
            <h2 class="font-semibold text-amber-800 dark:text-amber-300">Bugün Not Girmeyenler</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($missingUsers as $u): ?>
            <a href="<?php echo APP_URL; ?>pages/daily.php?userId=<?php echo $u['id']; ?>&openForm=true"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 text-sm hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-colors">
                <?php echo getUserAvatar($u['name'], 'w-5 h-5'); ?>
                <?php echo escapeHtml($u['name']); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Bugün Kim Nerede? -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
            <h2 class="font-semibold text-lg mb-4">Bugün Kim Nerede?</h2>
            <div class="space-y-2">
                <?php if (empty($users)): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">Henüz kullanıcı yok.</p>
                <?php else: ?>
                <?php foreach ($users as $user): ?>
                <?php $status = isset($statusMap[$user['id']]) ? $statusMap[$user['id']] : null; ?>
                <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                    <div class="flex items-center gap-3">
                        <?php echo getUserAvatar($user['name'], 'w-8 h-8'); ?>
                        <span class="text-sm font-medium"><?php echo escapeHtml($user['name']); ?></span>
                    </div>
                    <?php if ($status): ?>
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium <?php echo getStatusColorClass($status['type']); ?>">
                        <?php echo getStatusEmoji($status['type']); ?>
                        <?php echo getStatusLabel($status['type']); ?>
                    </span>
                    <?php else: ?>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Belirtilmemiş</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Son Daily Notlar -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-lg">Son Daily Notlar</h2>
                <a href="<?php echo APP_URL; ?>pages/daily.php" class="text-xs text-blue-600 hover:underline">Tümünü Gör</a>
            </div>
            <div class="space-y-3">
                <?php if (empty($recentNotes)): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">Henüz daily notu eklenmemiş.</p>
                <?php else: ?>
                <?php foreach ($recentNotes as $note): ?>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/30 border border-gray-200/50">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400"><?php echo escapeHtml($note['name']); ?></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">·</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo formatDateShort($note['date']); ?></span>
                    </div>
                    <p class="text-sm"><?php echo escapeHtml($note['content']); ?></p>
                    <?php if (!empty($note['tags'])): ?>
                    <div class="flex gap-1 mt-1.5">
                        <?php foreach (array_filter(array_map('trim', explode(',', $note['tags']))) as $tag): ?>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"><?php echo escapeHtml($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>