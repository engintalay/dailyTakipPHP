<?php
/**
 * Reports page (Admin only)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';

$currentUser = requireAdminAccess();
$isAdmin = true;

$pageTitle = 'Raporlar';
$currentPath = 'pages/reports.php';

$period = isset($_GET['period']) ? $_GET['period'] : 'weekly';
$range = $period === 'weekly' ? getWeekRange() : getMonthRange();
$startStr = formatDateOnly($range['start']);
$endStr = formatDateOnly($range['end']);
$startLabel = formatDateShort($startStr);
$endLabel = formatDateShort($endStr);

// Fetch all data
$notes = getDailyNotes(array('start_date' => $startStr, 'end_date' => $endStr));
$statuses = getDailyStatuses(array('start_date' => $startStr, 'end_date' => $endStr));
$attendance = getAttendances(array('start_date' => $startStr, 'end_date' => $endStr));
$users = getAllUsers(true);

$userMap = array();
foreach ($users as $u) $userMap[$u['id']] = $u['name'];

$userNoteCount = array();
$userStatusCount = array();
$userAttendance = array();
$userNotes = array();

foreach ($users as $u) {
    $userNoteCount[$u['id']] = 0;
    $userStatusCount[$u['id']] = array(STATUS_OFFICE => 0, STATUS_REMOTE => 0, STATUS_LEAVE => 0, STATUS_SICK => 0);
    $userAttendance[$u['id']] = array('present' => 0, 'total' => 0);
    $userNotes[$u['id']] = array();
}

foreach ($notes as $n) {
    if (isset($userNoteCount[$n['user_id']])) {
        $userNoteCount[$n['user_id']]++;
        $userNotes[$n['user_id']][] = $n['content'];
    }
}

foreach ($statuses as $s) {
    if (isset($userStatusCount[$s['user_id']][$s['type']])) {
        $userStatusCount[$s['user_id']][$s['type']]++;
    }
}

foreach ($attendance as $a) {
    if (isset($userAttendance[$a['user_id']])) {
        $userAttendance[$a['user_id']]['total']++;
        if ($a['present']) $userAttendance[$a['user_id']]['present']++;
    }
}

// Tag statistics
$tagCount = array();
$tagNotes = array();
foreach ($notes as $n) {
    foreach (array_filter(array_map('trim', explode(',', $n['tags']))) as $tag) {
        $tagCount[$tag] = (isset($tagCount[$tag]) ? $tagCount[$tag] : 0) + 1;
        if (!isset($tagNotes[$tag])) $tagNotes[$tag] = array();
        $tagNotes[$tag][] = $n['name'] . ': ' . $n['content'];
    }
}

// Notes by date
$notesByDate = array();
foreach ($notes as $n) {
    $key = formatDateShort($n['date']);
    if (!isset($notesByDate[$key])) $notesByDate[$key] = array();
    $notesByDate[$key][] = $n;
}
$dateLabels = array_keys($notesByDate);
sort($dateLabels);
$dateNoteCount = array();
foreach ($dateLabels as $d) $dateNoteCount[] = count($notesByDate[$d]);

$topTags = array_slice(array_keys($tagCount), 0, 10);
usort($topTags, function($a, $b) use ($tagCount) {
    return $tagCount[$b] - $tagCount[$a];
});

$totalNotes = count($notes);
$totalStatuses = count($statuses);
$totalAttendance = count($attendance);
$periodLabel = $period === 'weekly' ? 'Haftalık' : 'Aylık';

include __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Raporlar</h1>
        <div class="flex gap-2">
            <a href="<?php echo APP_URL; ?>pages/reports.php?period=weekly"
               class="px-4 py-2 rounded-lg text-sm <?php echo $period === 'weekly' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'; ?>">
                Haftalık
            </a>
            <a href="<?php echo APP_URL; ?>pages/reports.php?period=monthly"
               class="px-4 py-2 rounded-lg text-sm <?php echo $period === 'monthly' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'; ?>">
                Aylık
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5" id="report-content">
        <h2 class="text-lg font-semibold mb-1"><?php echo $periodLabel; ?> Özet Rapor</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4"><?php echo escapeHtml($startLabel); ?> — <?php echo escapeHtml($endLabel); ?></p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-950/20 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-blue-600"><?php echo $totalNotes; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Daily Not</div>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-950/20 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-emerald-600"><?php echo $totalStatuses; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Durum Girişi</div>
            </div>
            <div class="bg-amber-50 dark:bg-amber-950/20 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-amber-600"><?php echo $totalAttendance; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Katılım Kaydı</div>
            </div>
        </div>

        <h3 class="font-semibold mb-3">Kullanıcı Bazında Özet</h3>
        <div class="overflow-x-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="text-left p-2 font-medium">İsim</th>
                        <th class="text-center p-2 font-medium">Not</th>
                        <th class="text-center p-2 font-medium">🏢 Ofis</th>
                        <th class="text-center p-2 font-medium">🏠 Remote</th>
                        <th class="text-center p-2 font-medium">🌴 İzin</th>
                        <th class="text-center p-2 font-medium">🤒 Hasta</th>
                        <th class="text-center p-2 font-medium">Katılım</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u):
                        $notes = isset($userNoteCount[$u['id']]) ? $userNoteCount[$u['id']] : 0;
                        $statuses = isset($userStatusCount[$u['id']]) ? $userStatusCount[$u['id']] : array();
                        $att = isset($userAttendance[$u['id']]) ? $userAttendance[$u['id']] : array('present' => 0, 'total' => 0);
                        $attRate = $att['total'] > 0 ? round(($att['present'] / $att['total']) * 100) : 0;
                    ?>
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="p-2 font-medium"><?php echo escapeHtml($u['name']); ?></td>
                        <td class="text-center p-2"><?php echo $notes; ?></td>
                        <td class="text-center p-2"><?php echo isset($statuses[STATUS_OFFICE]) ? $statuses[STATUS_OFFICE] : 0; ?></td>
                        <td class="text-center p-2"><?php echo isset($statuses[STATUS_REMOTE]) ? $statuses[STATUS_REMOTE] : 0; ?></td>
                        <td class="text-center p-2"><?php echo isset($statuses[STATUS_LEAVE]) ? $statuses[STATUS_LEAVE] : 0; ?></td>
                        <td class="text-center p-2"><?php echo isset($statuses[STATUS_SICK]) ? $statuses[STATUS_SICK] : 0; ?></td>
                        <td class="text-center p-2"><?php echo $attRate; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($notes)): ?>
        <h3 class="font-semibold mb-3">Günlük Özet</h3>
        <div class="space-y-4 mb-6">
            <?php foreach (array_reverse($notesByDate, true) as $date => $dayNotes): ?>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                <h4 class="font-medium text-sm mb-2 text-blue-600"><?php echo $date; ?></h4>
                <ul class="space-y-1.5">
                    <?php foreach ($dayNotes as $note): ?>
                    <li class="text-sm">
                        <span class="font-medium text-gray-500 dark:text-gray-400"><?php echo escapeHtml($note['name']); ?>:</span>
                        <?php echo escapeHtml($note['content']); ?>
                        <?php if (!empty($note['tags'])): ?>
                        <span class="ml-1.5 text-xs text-gray-500 dark:text-gray-400">
                            [<?php echo escapeHtml(implode(', ', array_filter(array_map('trim', explode(',', $note['tags']))))); ?>]
                        </span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($notes)): ?>
        <h3 class="font-semibold mb-3">Kullanıcı Bazında Yapılan İşler</h3>
        <div class="space-y-3 mb-6">
            <?php foreach ($users as $u):
                $userNoteList = isset($userNotes[$u['id']]) ? $userNotes[$u['id']] : array();
                if (empty($userNoteList)) continue;
            ?>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                <h4 class="font-medium text-sm mb-1.5"><?php echo escapeHtml($u['name']); ?></h4>
                <ul class="list-disc list-inside space-y-0.5">
                    <?php foreach ($userNoteList as $content): ?>
                    <li class="text-sm text-gray-500 dark:text-gray-400"><?php echo escapeHtml($content); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($topTags)): ?>
        <h3 class="font-semibold mb-3">En Çok Kullanılan Etiketler</h3>
        <div class="flex flex-wrap gap-2 mb-6">
            <?php foreach ($topTags as $tag): ?>
            <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                <?php echo escapeHtml($tag); ?> (<?php echo $tagCount[$tag]; ?>)
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="flex gap-2">
        <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700">🖨️ Yazdır / PDF</button>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
