<?php
/**
 * Reports page (Admin only)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';

$currentUser = requireManagementAccess();
$isAdmin = isAdmin($currentUser);

$pageTitle = 'Raporlar';
$currentPath = 'pages/reports.php';

$period = isset($_GET['period']) ? $_GET['period'] : 'weekly';
$monthStr = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $monthStr)) $monthStr = date('Y-m');

// Önceki / sonraki ay hesaplama
$monthDt = new DateTime($monthStr . '-01');
$prevMonthDt = clone $monthDt;
$prevMonthDt->modify('-1 month');
$nextMonthDt = clone $monthDt;
$nextMonthDt->modify('+1 month');
$prevMonth = $prevMonthDt->format('Y-m');
$nextMonth = $nextMonthDt->format('Y-m');

$range = $period === 'weekly' ? getWeekRange($monthStr . '-01') : getMonthRange($monthStr . '-01');
$startStr = formatDateOnly($range['start']);
$endStr = formatDateOnly($range['end']);
$startLabel = formatDateShort($startStr);
$endLabel = formatDateShort($endStr);
$monthLabel = formatDateShort($monthStr . '-01');
$monthLabel = preg_replace('/^01 /', '', $monthLabel);

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
        $userNotes[$n['user_id']][] = array(
            'date' => $n['date'],
            'content' => $n['content'],
            'tags' => !empty($n['tags']) ? $n['tags'] : ''
        );
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

// Performans raporu verisi (dönem bazlı)
$performanceReport = null;
if (isset($_GET['type']) && $_GET['type'] === 'performance') {
    $periodId = isset($_GET['period_id']) ? $_GET['period_id'] : '';
    if (!empty($periodId)) {
        $performanceReport = getPerformanceReport($periodId);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Raporlar</h1>
        <div class="flex gap-2">
            <a href="<?php echo APP_URL; ?>pages/reports.php?period=weekly"
               class="px-4 py-2 rounded-lg text-sm <?php echo ($period === 'weekly' || $period === 'monthly') && (!isset($_GET['type']) || $_GET['type'] === 'activity') ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'; ?>">
                Aktivite
            </a>
            <a href="<?php echo APP_URL; ?>pages/reports.php?type=performance"
               class="px-4 py-2 rounded-lg text-sm <?php echo isset($_GET['type']) && $_GET['type'] === 'performance' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'; ?>">
                Performans
            </a>
        </div>
    </div>
    
    <?php if ($performanceReport !== null): ?>
    <!-- Performans Raporu -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
        <h2 class="text-lg font-semibold mb-4">Performans Değerlendirme Raporu</h2>
        
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Dönem Seçin</label>
            <select onchange="window.location.href=this.value" 
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                <option value="">-- Bir dönem seçin --</option>
                <?php 
                $periods = getEvaluationPeriods();
                foreach ($periods as $p): 
                    $selected = isset($_GET['period_id']) && $_GET['period_id'] === $p['id'] ? 'selected' : '';
                ?>
                <option value="reports.php?type=performance&period_id=<?php echo $p['id']; ?>" <?php echo $selected; ?>>
                    <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['start_date']; ?> - <?php echo $p['end_date']; ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if (!empty($performanceReport)): ?>
        <!-- Genel Özet -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-950/20 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-blue-600">
                    <?php echo $performanceReport['summary']['total_evaluated']; ?>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Değerlendirilen</div>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-950/20 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-emerald-600">
                    <?php echo $performanceReport['summary']['average_weighted']; ?>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Ortalama Puan</div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-950/20 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-purple-600">
                    <?php echo count($performanceReport['users']); ?>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Toplam Personel</div>
            </div>
        </div>
        
        <!-- Rating Dağılımı -->
        <?php if (!empty($performanceReport['summary']['rating_distribution'])): ?>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 mb-6">
            <h3 class="font-medium mb-3">Rating Dağılımı</h3>
            <div class="grid grid-cols-5 gap-3">
                <?php foreach ($performanceReport['summary']['rating_distribution'] as $rating => $count): ?>
                <div class="text-center p-2 rounded-lg <?php 
                    if ($rating === 'Üstün performans') echo 'bg-green-50 dark:bg-green-900/20';
                    elseif ($rating === 'Beklentilerin üzerinde') echo 'bg-blue-50 dark:bg-blue-900/20';
                    elseif ($rating === 'Beklentileri karşılıyor') echo 'bg-yellow-50 dark:bg-yellow-900/20';
                    elseif ($rating === 'Gelişime açık') echo 'bg-orange-50 dark:bg-orange-900/20';
                    else echo 'bg-red-50 dark:bg-red-900/20';
                ?>">
                    <div class="text-xl font-bold"><?php echo $count; ?></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        <?php echo $rating; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Kriter Bazlı Ortalamalar -->
        <?php if (!empty($performanceReport['criteria_avg'])): ?>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 mb-6">
            <h3 class="font-medium mb-3">Kriter Bazlı Ortalamalar</h3>
            <div class="space-y-3">
                <?php foreach ($performanceReport['criteria_avg'] as $ca): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                    <div>
                        <div class="font-medium text-gray-700 dark:text-gray-300">
                            <?php echo escapeHtml($ca['criterion']['name']); ?>
                        </div>
                        <?php if (!empty($ca['criterion']['weight'])): ?>
                        <div class="text-xs text-gray-400">Ağırlık: %<?php echo $ca['criterion']['weight']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-blue-600">
                            <?php echo $ca['avg']; ?>/10
                        </div>
                        <div class="text-xs text-gray-400">Ortalama</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Kişisel Raporlar -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
            <h3 class="font-medium mb-3">Kişisel Performans Raporları</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="text-left p-2 font-medium">Personel</th>
                            <th class="text-center p-2 font-medium">Puan</th>
                            <th class="text-center p-2 font-medium">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($performanceReport['users'] as $u): ?>
                        <?php if (empty($u['user']['id'])) continue; ?>
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="p-2">
                                <div class="flex items-center gap-2">
                                    <?php echo getUserAvatar($u['user']['name'], 'w-8 h-8'); ?>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        <?php echo escapeHtml($u['user']['name']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="text-center p-2">
                                <span class="inline-block px-2 py-1 rounded text-sm font-bold
                                    <?php 
                                    if ($u['weighted_avg'] === null) echo 'bg-gray-100 dark:bg-gray-700 dark:text-gray-300';
                                    elseif ($u['weighted_avg'] >= 9) echo 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                    elseif ($u['weighted_avg'] >= 7) echo 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
                                    elseif ($u['weighted_avg'] >= 5) echo 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                                    elseif ($u['weighted_avg'] >= 3) echo 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
                                    else echo 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                    ?>">
                                    <?php echo $u['weighted_avg'] !== null ? $u['weighted_avg'] : '-'; ?>/10
                                </span>
                            </td>
                            <td class="text-center p-2">
                                <span class="text-xs px-2 py-1 rounded
                                    <?php 
                                    if ($u['rating'] === 'Üstün performans') echo 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                    elseif ($u['rating'] === 'Beklentilerin üzerinde') echo 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
                                    elseif ($u['rating'] === 'Beklentileri karşılıyor') echo 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                                    elseif ($u['rating'] === 'Gelişime açık') echo 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
                                    else echo 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                    ?>">
                                    <?php echo escapeHtml($u['rating']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <p>Performans değerlendirmesi yapılmamış bir dönem seçin.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php else: ?>
    <!-- Aktivite Raporu -->
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
                        <td class="p-2 font-medium">
                            <a href="#user-detail-<?php echo $u['id']; ?>"
                               onclick="event.preventDefault(); document.getElementById('user-detail-<?php echo $u['id']; ?>').scrollIntoView({behavior: 'smooth', block: 'start'});"
                               class="text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">
                                <?php echo escapeHtml($u['name']); ?>
                            </a>
                        </td>
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
            ?>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3" id="user-detail-<?php echo $u['id']; ?>">
                <h4 class="font-medium text-sm mb-1.5"><?php echo escapeHtml($u['name']); ?></h4>
                <?php if (empty($userNoteList)): ?>
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">Bu dönemde henüz daily notu girilmemiş</p>
                <?php else: ?>
                <ul class="space-y-1">
                    <?php foreach ($userNoteList as $note): ?>
                    <li class="text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-medium text-gray-700 dark:text-gray-300">📅 <?php echo escapeHtml(formatDateShort($note['date'])); ?>:</span>
                        <?php echo escapeHtml($note['content']); ?>
                        <?php if (!empty($note['tags'])): ?>
                        <span class="ml-1.5 text-xs text-gray-400 dark:text-gray-500">
                            [<?php echo escapeHtml(implode(', ', array_filter(array_map('trim', explode(',', $note['tags']))))); ?>]
                        </span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
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
    <?php endif; ?>

    <div class="flex gap-2">
        <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700">🖨️ Yazdır / PDF</button>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
