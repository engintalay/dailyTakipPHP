<?php
/**
 * Team Status Calendar page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

$currentUser = requireLogin();
$isAdmin = isAdmin($currentUser);

// Get all active users
$users = getAllUsers(true);

// Current month
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$today = date('Y-m-d');
list($year, $month) = explode('-', $currentMonth);
$monthNames = array('', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık');
$monthLabel = $monthNames[(int)$month] . ' ' . $year;
$daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
$firstDay = (int)date('w', mktime(0, 0, 0, $month, 1, $year));
$firstDay = $firstDay === 0 ? 6 : $firstDay - 1; // Mon=0

// Get statuses for the month
$monthStart = sprintf('%s-%02d-01', $year, $month);
$monthEnd = sprintf('%s-%02d-%02d', $year, $month, $daysInMonth);

$statuses = getDailyStatuses(array(
    'start_date' => $monthStart,
    'end_date' => $monthEnd
));

$statusMap = array();
foreach ($statuses as $s) {
    $statusMap[$s['user_id'] . '-' . $s['date']] = $s['type'];
}

// Handle click to cycle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (verifyCsrfToken($csrf)) {
        if ($_POST['action'] === 'cycle_status') {
            $userId = $_POST['user_id'];
            $dateStr = $_POST['date'];
            $currentType = isset($_POST['current_type']) ? $_POST['current_type'] : '';

            // Verify admin or own status
            if (!$isAdmin && $userId !== getEffectiveUserId()) {
                jsonResponse(array('error' => 'Unauthorized'), 403);
            }

            $order = array(STATUS_OFFICE, STATUS_REMOTE, STATUS_LEAVE, STATUS_SICK);
            $currentIdx = array_search($currentType, $order);

            if ($currentIdx === false) {
                // No status, set to OFFICE
                setDailyStatus($userId, $dateStr, STATUS_OFFICE);
            } elseif ($currentIdx === count($order) - 1) {
                // Last status, delete
                deleteDailyStatus($userId, $dateStr);
            } else {
                // Next status
                setDailyStatus($userId, $dateStr, $order[$currentIdx + 1]);
            }

            jsonResponse(array('success' => true));
            exit;
        }
    }
    jsonResponse(array('error' => 'Invalid request'), 400);
}

$pageTitle = 'Ekip Durum Takvimi';
$currentPath = 'pages/team-status.php';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Ekip Durum Takvimi</h1>
        <div class="flex gap-2">
            <a href="<?php echo APP_URL; ?>pages/team-status.php?month=<?php echo date('Y-m', strtotime($currentMonth . ' -1 month')); ?>"
               class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">←</a>
            <span class="px-3 py-1 text-sm font-medium"><?php echo escapeHtml($monthLabel); ?></span>
            <a href="<?php echo APP_URL; ?>pages/team-status.php?month=<?php echo date('Y-m', strtotime($currentMonth . ' +1 month')); ?>"
               class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">→</a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700/50">
                    <th class="text-left p-2 font-medium sticky left-0 bg-gray-50 dark:bg-gray-700/50 z-10 min-w-[100px]">İsim</th>
                    <?php for ($day = 1; $day <= $daysInMonth; $day++):
                        $dow = (int)date('w', mktime(0, 0, 0, $month, $day, $year));
                        $isWeekend = $dow === 0 || $dow === 6;
                        $dateStr = sprintf('%s-%02d-%02d', $year, $month, $day);
                        $isToday = $dateStr === $today;
                        $dayNames = array('Pzt','Sal','Çar','Per','Cum','Cmt','Paz');
                        $dayName = $dayNames[$dow === 0 ? 6 : $dow - 1];
                    ?>
                    <th title="<?php echo $isToday ? 'Bugün' : ''; ?>" class="text-center p-1 font-medium min-w-[28px] <?php echo $isWeekend ? 'bg-slate-200 text-slate-800 dark:bg-slate-600 dark:text-white' : ''; ?> <?php echo $isToday ? 'bg-blue-200 text-blue-950 ring-2 ring-blue-600 ring-inset dark:bg-blue-900 dark:text-blue-100 dark:ring-blue-400' : ''; ?>">
                        <div><?php echo $day; ?></div>
                        <div class="text-gray-500 dark:text-gray-400"><?php echo $dayName; ?></div>
                        <?php if ($isToday): ?><div class="text-[9px] font-bold text-blue-700 dark:text-blue-200">Bugün</div><?php endif; ?>
                    </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="border-t border-gray-200 dark:border-gray-700">
                    <td class="p-2 font-medium sticky left-0 bg-white dark:bg-gray-800 z-10"><?php echo escapeHtml($u['name']); ?></td>
                    <?php for ($day = 1; $day <= $daysInMonth; $day++):
                        $dateStr = sprintf('%s-%02d-%02d', $year, $month, $day);
                        $status = isset($statusMap[$u['id'] . '-' . $dateStr]) ? $statusMap[$u['id'] . '-' . $dateStr] : null;
                        $dow = (int)date('w', mktime(0, 0, 0, $month, $day, $year));
                        $isWeekend = $dow === 0 || $dow === 6;
                        $isToday = $dateStr === $today;
                    ?>
                    <td onclick="cycleStatus('<?php echo $u['id']; ?>', '<?php echo $dateStr; ?>', '<?php echo $status ?: ''; ?>')"
                        class="text-center p-1 border-l border-gray-200 dark:border-gray-700 cursor-pointer transition-colors hover:ring-2 hover:ring-blue-500 hover:ring-inset <?php
                            echo $isWeekend ? 'bg-slate-100 dark:bg-slate-800' : '';
                            echo $isToday ? ' bg-blue-50 ring-2 ring-blue-500 ring-inset dark:bg-blue-950/40 dark:ring-blue-400' : '';
                            echo $status ? ' ' . getStatusColorClass($status) : ' hover:bg-gray-100 dark:hover:bg-gray-700';
                        ?>">
                        <?php if ($status): ?>
                            <span class="text-sm"><?php echo getStatusEmoji($status); ?></span>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <?php endfor; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="flex gap-4 text-xs text-gray-500">
        <span class="px-2 py-1 rounded bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">Hafta sonu</span>
        <span>🏢 Ofis</span>
        <span>🏠 Remote</span>
        <span>🌴 İzin</span>
        <span>🤒 Hasta</span>
        <span class="text-gray-400">- Boş</span>
    </div>
</div>

<script>
async function cycleStatus(userId, dateStr, currentType) {
    const order = ['OFFICE', 'REMOTE', 'LEAVE', 'SICK'];
    const currentIdx = order.indexOf(currentType);

    let newType;
    if (currentIdx === -1) {
        newType = 'OFFICE';
    } else if (currentIdx === order.length - 1) {
        newType = ''; // Delete
    } else {
        newType = order[currentIdx + 1];
    }

    const formData = new FormData();
    formData.append('action', 'cycle_status');
    formData.append('csrf_token', '<?php echo $csrfToken; ?>');
    formData.append('user_id', userId);
    formData.append('date', dateStr);
    formData.append('current_type', currentType);

    if (newType) {
        formData.append('type', newType);
    }

    try {
        const response = await fetch('<?php echo APP_URL; ?>pages/team-status.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert('Hata: ' + (result.error || 'Bilinmeyen hata'));
        }
    } catch (e) {
        alert('İstek başarısız: ' + e.message);
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
