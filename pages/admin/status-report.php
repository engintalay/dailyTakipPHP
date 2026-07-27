<?php
/**
 * Status report for administrators
 * PHP 5.3 compatible
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/models.php';

$currentUser = requireAdminAccess();
$isAdmin = true;
$pageTitle = 'Durum Raporu';
$currentPath = 'pages/admin/status-report.php';

$defaultStart = date('Y-m-01');
$defaultEnd = date('Y-m-d');
$startDate = isset($_GET['startDate']) && $_GET['startDate'] ? $_GET['startDate'] : $defaultStart;
$endDate = isset($_GET['endDate']) && $_GET['endDate'] ? $_GET['endDate'] : $defaultEnd;

$statuses = array();
$reportError = '';
if ($startDate > $endDate) {
    $reportError = 'Başlangıç tarihi bitiş tarihinden sonra olamaz.';
} else {
    $statuses = getDailyStatuses(array('start_date' => $startDate, 'end_date' => $endDate));
}

$users = getAllUsers(true);
$userMap = array();
$summary = array(STATUS_LEAVE => 0, STATUS_REMOTE => 0, STATUS_SICK => 0);
$userRows = array();
foreach ($users as $user) {
    $userRows[$user['id']] = array(
        'name' => $user['name'],
        'leave' => 0,
        'remote' => 0,
        'sick' => 0,
        'dates' => array(STATUS_LEAVE => array(), STATUS_REMOTE => array(), STATUS_SICK => array())
    );
    $userMap[$user['id']] = $user['name'];
}

foreach ($statuses as $status) {
    if (!isset($summary[$status['type']])) continue;
    $summary[$status['type']]++;
    if (!isset($userRows[$status['user_id']])) continue;

    $key = $status['type'] === STATUS_LEAVE ? 'leave' : ($status['type'] === STATUS_REMOTE ? 'remote' : 'sick');
    $userRows[$status['user_id']][$key]++;
    $userRows[$status['user_id']]['dates'][$status['type']][] = $status['date'];
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Durum Raporu</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">İzin, remote ve hastalık kayıtlarını tarih aralığına göre görüntüleyin.</p>
        </div>
    </div>

    <form method="GET" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="startDate" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Başlangıç</label>
            <input type="date" id="startDate" name="startDate" value="<?php echo escapeHtml($startDate); ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
        </div>
        <div>
            <label for="endDate" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş</label>
            <input type="date" id="endDate" name="endDate" value="<?php echo escapeHtml($endDate); ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Raporu Görüntüle</button>
    </form>

    <?php if ($reportError): ?>
    <div class="p-3 rounded-lg border border-red-300 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-950/30 dark:text-red-300">
        <?php echo escapeHtml($reportError); ?>
    </div>
    <?php else: ?>
    <div class="text-sm text-gray-500 dark:text-gray-400">
        <?php echo escapeHtml(formatDateShort($startDate)); ?> — <?php echo escapeHtml(formatDateShort($endDate)); ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="rounded-xl border border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/40 p-4">
            <div class="text-sm font-medium text-amber-800 dark:text-amber-200">İzinli günleri</div>
            <div class="text-3xl font-bold text-amber-950 dark:text-amber-100 mt-1"><?php echo $summary[STATUS_LEAVE]; ?></div>
        </div>
        <div class="rounded-xl border border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-950/40 p-4">
            <div class="text-sm font-medium text-blue-800 dark:text-blue-200">Remote günleri</div>
            <div class="text-3xl font-bold text-blue-950 dark:text-blue-100 mt-1"><?php echo $summary[STATUS_REMOTE]; ?></div>
        </div>
        <div class="rounded-xl border border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950/40 p-4">
            <div class="text-sm font-medium text-red-800 dark:text-red-200">Hasta günleri</div>
            <div class="text-3xl font-bold text-red-950 dark:text-red-100 mt-1"><?php echo $summary[STATUS_SICK]; ?></div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700/50">
                    <th class="text-left p-3 font-medium">Kullanıcı</th>
                    <th class="text-center p-3 font-medium">🌴 İzin</th>
                    <th class="text-center p-3 font-medium">🏠 Remote</th>
                    <th class="text-center p-3 font-medium">🤒 Hasta</th>
                    <th class="text-left p-3 font-medium">Tarihler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($userRows as $row): ?>
                <tr class="border-t border-gray-200 dark:border-gray-700 align-top">
                    <td class="p-3 font-medium whitespace-nowrap"><?php echo escapeHtml($row['name']); ?></td>
                    <td class="text-center p-3 text-amber-700 dark:text-amber-300"><?php echo $row['leave']; ?></td>
                    <td class="text-center p-3 text-blue-700 dark:text-blue-300"><?php echo $row['remote']; ?></td>
                    <td class="text-center p-3 text-red-700 dark:text-red-300"><?php echo $row['sick']; ?></td>
                    <td class="p-3 text-xs text-gray-600 dark:text-gray-300">
                        <?php if ($row['leave']): ?><div><strong class="text-amber-700 dark:text-amber-300">İzin:</strong> <?php echo escapeHtml(implode(', ', array_map('formatDateShort', $row['dates'][STATUS_LEAVE]))); ?></div><?php endif; ?>
                        <?php if ($row['remote']): ?><div><strong class="text-blue-700 dark:text-blue-300">Remote:</strong> <?php echo escapeHtml(implode(', ', array_map('formatDateShort', $row['dates'][STATUS_REMOTE]))); ?></div><?php endif; ?>
                        <?php if ($row['sick']): ?><div><strong class="text-red-700 dark:text-red-300">Hasta:</strong> <?php echo escapeHtml(implode(', ', array_map('formatDateShort', $row['dates'][STATUS_SICK]))); ?></div><?php endif; ?>
                        <?php if (!$row['leave'] && !$row['remote'] && !$row['sick']): ?><span class="text-gray-400">Kayıt yok</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
