<?php
/**
 * Attendance tracking page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

$currentUser = requireLogin();
$isAdmin = isAdmin($currentUser);
$effectiveUserId = getEffectiveUserId();

// Get all users
$users = getAllUsers(true);

// Current week
$weekStart = isset($_GET['week']) ? $_GET['week'] : date('Y-m-d', strtotime('monday this week'));
$weekStartObj = new DateTime($weekStart);
$weekStartObj->modify('monday this week');
$weekStart = $weekStartObj->format('Y-m-d');

$weekEndObj = clone $weekStartObj;
$weekEndObj->modify('+6 days');
$weekEnd = $weekEndObj->format('Y-m-d');

// Get attendance records for the week
$records = getAttendances(array(
    'start_date' => $weekStart,
    'end_date' => $weekEnd
));

$recordMap = array();
foreach ($records as $r) {
    $recordMap[$r['user_id'] . '-' . $r['date']] = $r['present'];
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (verifyCsrfToken($csrf)) {
        if ($_POST['action'] === 'set_attendance') {
            $date = $_POST['date'];
            $present = isset($_POST['present']) ? (int)$_POST['present'] : 1;
            $userId = $isAdmin && !empty($_POST['user_id']) ? $_POST['user_id'] : $effectiveUserId;

            setAttendance($userId, $date, (bool)$present);
            flash('success', 'Katılım güncellendi');

            header('Location: ' . APP_URL . 'pages/attendance.php?week=' . $weekStart);
            exit;
        }
    }
}

$days = array();
for ($i = 0; $i < 7; $i++) {
    $d = clone $weekStartObj;
    $d->modify("+$i days");
    $days[] = $d;
}

$pageTitle = 'Katılım Takibi';
$currentPath = 'pages/attendance.php';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Katılım Takibi</h1>
        <div class="flex gap-2">
            <a href="<?php echo APP_URL; ?>pages/attendance.php?week=<?php echo date('Y-m-d', strtotime($weekStart . ' -7 days')); ?>"
               class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">← Geçen Hafta</a>
            <a href="<?php echo APP_URL; ?>pages/attendance.php?week=<?php echo date('Y-m-d', strtotime($weekStart . ' +7 days')); ?>"
               class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">Sonraki Hafta →</a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700/50">
                    <th class="text-left p-3 font-medium">İsim</th>
                    <?php foreach ($days as $i => $d):
                        $isToday = $d->format('Y-m-d') === date('Y-m-d');
                    ?>
                    <th class="text-center p-3 font-medium <?php echo $isToday ? 'text-blue-600' : ''; ?>">
                        <div><?php echo $d->format('D'); ?></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo $d->format('j'); ?></div>
                    </th>
                    <?php endforeach; ?>
                    <th class="text-center p-3 font-medium">Oran</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u):
                    $present = 0;
                    $total = 0;
                    foreach ($days as $d) {
                        $key = $u['id'] . '-' . $d->format('Y-m-d');
                        if (isset($recordMap[$key])) {
                            $total++;
                            if ($recordMap[$key]) $present++;
                        }
                    }
                    $rate = $total > 0 ? round(($present / $total) * 100) : 0;
                ?>
                <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-3 font-medium">
                        <div class="flex items-center gap-2">
                            <?php echo getUserAvatar($u['name'], 'w-7 h-7'); ?>
                            <?php echo escapeHtml($u['name']); ?>
                        </div>
                    </td>
                    <?php foreach ($days as $d):
                        $dateStr = $d->format('Y-m-d');
                        $key = $u['id'] . '-' . $dateStr;
                        $isPresent = isset($recordMap[$key]) ? $recordMap[$key] : null;
                        $isFuture = $d > new DateTime();
                        $canToggle = ($currentUser['id'] === $u['id'] || $isAdmin) && !$isFuture;
                    ?>
                    <td class="text-center p-3 <?php echo $canToggle ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/30' : ''; ?>"
                        onclick="<?php echo $canToggle ? "toggleAttendance('$dateStr', " . (isset($isPresent) ? $isPresent : 'true') . ", '$u[id]')" : ''; ?>">
                        <?php if ($isFuture): ?>
                            <span class="text-gray-400 text-xs">—</span>
                        <?php elseif ($isPresent === null): ?>
                            <span class="text-gray-400 text-xs">?</span>
                        <?php elseif ($isPresent): ?>
                            <span class="text-lg">✅</span>
                        <?php else: ?>
                            <span class="text-lg">❌</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="text-center p-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium <?php
                            echo $rate >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                ($rate >= 50 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400');
                        ?>">
                            <?php echo $rate; ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
        <h2 class="font-semibold mb-2">Hızlı İşlem</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Bugünkü katılım durumunu işaretle:</p>
        <div class="flex gap-2">
            <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="set_attendance">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>">
                <input type="hidden" name="present" value="1">
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">✅ Katıldı</button>
            </form>
            <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="set_attendance">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>">
                <input type="hidden" name="present" value="0">
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">❌ Katılmadı</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAttendance(date, currentPresent, userId) {
    const present = currentPresent === 'true' ? '0' : '1';
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input name="action" value="set_attendance"><input name="csrf_token" value="<?php echo $csrfToken; ?>"><input name="date" value="' + date + '"><input name="present" value="' + present + '"><?php if ($isAdmin): ?>' + '<input name="user_id" value="' + userId + '">'<?php endif; ?>;
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>