<?php
/**
 * Status tracking page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

$currentUser = requireLogin();
$isAdmin = isAdmin($currentUser);
$effectiveUserId = getEffectiveUserId();

// Get users for admin
$users = $isAdmin ? getAllUsers(true) : array();

// Filters
$targetUserId = isset($_GET['userId']) ? $_GET['userId'] : '';
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get current user's status for today
$status = getDailyStatusByUserAndDate($effectiveUserId, $selectedDate);

// Get month statuses for calendar
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
list($year, $month) = explode('-', $currentMonth);
$monthNames = array('', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık');
$monthLabel = $monthNames[(int)$month] . ' ' . $year;
$daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
$firstDay = (int)date('w', mktime(0, 0, 0, $month, 1, $year));
$firstDay = $firstDay === 0 ? 6 : $firstDay - 1; // Mon=0

$monthStart = sprintf('%s-%02d-01', $year, $month);
$monthEnd = sprintf('%s-%02d-%02d', $year, $month, $daysInMonth);

$monthStatuses = getDailyStatuses(array(
    'user_id' => $effectiveUserId,
    'start_date' => $monthStart,
    'end_date' => $monthEnd
));

$statusMap = array();
foreach ($monthStatuses as $s) {
    $statusMap[$s['date']] = $s['type'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (verifyCsrfToken($csrf)) {
        if ($_POST['action'] === 'set_status') {
            $type = $_POST['type'];
            $note = isset($_POST['note']) ? $_POST['note'] : '';
            $date = isset($_POST['date']) ? $_POST['date'] : $selectedDate;
            $userId = $isAdmin && !empty($_POST['user_id']) ? $_POST['user_id'] : $effectiveUserId;

            if (!empty($_POST['range_end'])) {
                $result = setDailyStatusRange($userId, $date, $_POST['range_end'], $type, $note);
            } else {
                $result = setDailyStatus($userId, $date, $type, $note);
            }

            if (isset($result['error'])) {
                flash('error', $result['error']);
            } else {
                flash('success', 'Durum güncellendi');
            }
            header('Location: ' . APP_URL . 'pages/status.php?date=' . $date . ($isAdmin && $userId ? '&userId=' . $userId : ''));
            exit;
        } elseif ($_POST['action'] === 'delete_status') {
            $date = $_POST['date'];
            $userId = $isAdmin && !empty($_POST['user_id']) ? $_POST['user_id'] : $effectiveUserId;
            deleteDailyStatus($userId, $date);
            flash('success', 'Durum silindi');
            header('Location: ' . APP_URL . 'pages/status.php?date=' . $date);
            exit;
        }
    }
}

$pageTitle = 'Durum Takibi';
$currentPath = 'pages/status.php';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold">Durum Takibi</h1>

    <?php if ($isAdmin): ?>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-2">
        <label class="block text-xs font-medium mb-1">Kullanıcı</label>
        <form method="GET" class="flex items-end gap-2">
            <select name="userId" onchange="this.form.submit()" class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                <option value="">Kendim (<?php echo escapeHtml($currentUser['name']); ?>)</option>
                <?php foreach ($users as $u): if ($u['id'] === $currentUser['id']) continue; ?>
                <option value="<?php echo $u['id']; ?>" <?php echo $targetUserId === $u['id'] ? 'selected' : ''; ?>><?php echo escapeHtml($u['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="date" value="<?php echo escapeHtml($selectedDate); ?>">
        </form>
    </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-xs font-semibold">Durum Girişi — <?php
                $filtered = array_filter($users, function($u) use ($targetUserId) { return $u['id'] === $targetUserId; });
                $filtered = array_values($filtered);
                $label = $targetUserId ? (isset($filtered[0]) ? $filtered[0]['name'] : '') : $currentUser['name'];
if (empty($label) && !$targetUserId) $label = $currentUser['name'];
                echo escapeHtml($label);
            ?></h2>
            <label class="flex items-center gap-1 text-xs text-gray-500 cursor-pointer">
                <input type="checkbox" id="rangeMode" class="rounded" <?php echo isset($_GET['range']) ? 'checked' : ''; ?>> Aralık
            </label>
        </div>

        <form method="POST" id="statusForm">
            <input type="hidden" name="action" value="set_status">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <?php if ($isAdmin && $targetUserId): ?><input type="hidden" name="user_id" value="<?php echo escapeHtml($targetUserId); ?>"><?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                <div>
                    <label class="block text-xs text-gray-500 mb-0.5">Tarih</label>
                    <input type="date" name="date" value="<?php echo escapeHtml($selectedDate); ?>" id="selectedDate"
                           class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                           max="<?php echo isset($_GET['range']) ? (isset($_GET['range_end']) ? $_GET['range_end'] : $selectedDate) : ''; ?>">
                </div>
                <div id="rangeEndDiv" class="hidden">
                    <label class="block text-xs text-gray-500 mb-0.5">Bitiş</label>
                    <input type="date" name="range_end" id="rangeEnd" value="<?php echo isset($_GET['range_end']) ? escapeHtml($_GET['range_end']) : $selectedDate; ?>"
                           min="<?php echo $selectedDate; ?>"
                           class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                </div>
            </div>

            <?php
            $statusHere = isset($statusMap[$selectedDate]) ? $statusMap[$selectedDate] : null;
            if ($statusHere && $selectedDate !== date('Y-m-d')): ?>
            <div class="mb-2 text-xs text-gray-500">
                Mevcut: <span class="font-medium px-1.5 py-0.5 rounded-full <?php echo getStatusColorClass($statusHere); ?>">
                    <?php echo getStatusEmoji($statusHere); ?> <?php echo getStatusLabel($statusHere); ?>
                </span>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <?php
                $options = array(
                    STATUS_OFFICE => array('label' => '🏢 Ofiste', 'color' => 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/20'),
                    STATUS_REMOTE => array('label' => '🏠 Remote', 'color' => 'border-blue-500 bg-blue-50 dark:bg-blue-950/20'),
                    STATUS_LEAVE  => array('label' => '🌴 İzinli', 'color' => 'border-amber-500 bg-amber-50 dark:bg-amber-950/20'),
                    STATUS_SICK   => array('label' => '🤒 Hasta', 'color' => 'border-red-500 bg-red-50 dark:bg-red-950/20'),
                    ''            => array('label' => '❌ Boş', 'color' => 'border-gray-300 dark:border-gray-700'),
                );
                foreach ($options as $type => $opt):
                    $active = ($statusHere === $type) ? 'border-2 scale-105 shadow-md' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400';
                ?>
                <button type="button" onclick="setStatus('<?php echo $type; ?>')"
                        class="p-2 rounded-lg border-2 text-center transition-all <?php echo $opt['color'] . ' ' . $active; ?>">
                    <div class="text-xs"><?php echo $opt['label']; ?></div>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="mt-2">
                <label class="block text-xs text-gray-500 mb-0.5">Not</label>
                <input type="text" name="note" id="statusNote"
                       value="<?php echo $status ? escapeHtml($status['note']) : ''; ?>"
                       class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                       placeholder="Not ekle...">
            </div>

            <div id="rangeDisplay" class="hidden mt-1 text-xs text-gray-500"></div>
        </form>
    </div>

    <!-- Calendar -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-xs font-semibold">Takvim — <?php echo escapeHtml($label); ?></h2>
            <div class="flex gap-1">
                <button onclick="window.location.href='<?php echo APP_URL; ?>pages/status.php?month=<?php echo date('Y-m', strtotime($currentMonth . ' -1 month')); ?><?php echo $targetUserId ? '&userId=' . $targetUserId : ''; ?>'" class="px-2 py-0.5 text-[11px] border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-700">←</button>
                <span class="px-2 py-0.5 text-[11px] font-medium"><?php echo escapeHtml($monthLabel); ?></span>
                <button onclick="window.location.href='<?php echo APP_URL; ?>pages/status.php?month=<?php echo date('Y-m', strtotime($currentMonth . ' +1 month')); ?><?php echo $targetUserId ? '&userId=' . $targetUserId : ''; ?>'" class="px-2 py-0.5 text-[11px] border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-700">→</button>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-[1px]">
            <?php foreach (array('Pzt','Sal','Çar','Per','Cum','Cmt','Paz') as $d): ?>
            <div class="text-center text-[10px] font-medium text-gray-500 dark:text-gray-400 py-1"><?php echo $d; ?></div>
            <?php endforeach; ?>

            <?php for ($i = 0; $i < $firstDay; $i++): ?>
            <div></div>
            <?php endfor; ?>

            <?php for ($day = 1; $day <= $daysInMonth; $day++):
                $dateStr = sprintf('%s-%02d-%02d', $year, $month, $day);
                $isToday = $dateStr === date('Y-m-d');
                $isSelected = $dateStr === $selectedDate;
                $status = isset($statusMap[$dateStr]) ? $statusMap[$dateStr] : null;
            ?>
            <button type="button" onclick="setDateAndCycle('<?php echo $dateStr; ?>')"
                    class="text-center text-[10px] border transition-all py-1 rounded-sm <?php
                        echo $isSelected ? 'ring-1 ring-blue-500 border-blue-500' : ($isToday ? 'border-blue-300 bg-blue-50 dark:bg-blue-950/20' : 'border-gray-300 dark:border-gray-600');
                        echo $status ? ' ' . getStatusColorClass($status) : ' text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700';
                    ?>">
                <div class="font-medium leading-tight"><?php echo $day; ?></div>
                <?php if ($status === STATUS_OFFICE): ?><div class="leading-tight">🏢</div>
                <?php elseif ($status === STATUS_REMOTE): ?><div class="leading-tight">🏠</div>
                <?php elseif ($status === STATUS_LEAVE): ?><div class="leading-tight">🌴</div>
                <?php elseif ($status === STATUS_SICK): ?><div class="leading-tight">🤒</div><?php endif; ?>
            </button>
            <?php endfor; ?>
        </div>

        <div class="flex gap-4 mt-1 text-[10px] text-gray-500">
            <span>🏢</span><span>🏠</span><span>🌴</span><span>🤒</span>
        </div>
    </div>
</div>

<script>
document.getElementById('rangeMode').addEventListener('change', function() {
    const endDiv = document.getElementById('rangeEndDiv');
    const display = document.getElementById('rangeDisplay');
    if (this.checked) {
        endDiv.classList.remove('hidden');
    } else {
        endDiv.classList.add('hidden');
        display.classList.add('hidden');
    }
});

function setStatus(type) {
    const form = document.getElementById('statusForm');
    const inputs = form.querySelectorAll('[name="type"]');
    inputs.forEach(i => i.remove());

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'type';
    input.value = type;
    form.appendChild(input);

    form.submit();
}

function setDateAndCycle(dateStr) {
    document.getElementById('selectedDate').value = dateStr;
    // Find current status for this date
    const buttons = document.querySelectorAll('button[onclick^="setDateAndCycle"]');
    let currentStatus = null;
    buttons.forEach(b => {
        if (b.onclick.toString().includes(dateStr)) {
            const statusDiv = b.querySelector('div:last-child');
            if (statusDiv && statusDiv.textContent.trim()) {
                const emoji = statusDiv.textContent.trim();
                if (emoji === '🏢') currentStatus = 'OFFICE';
                else if (emoji === '🏠') currentStatus = 'REMOTE';
                else if (emoji === '🌴') currentStatus = 'LEAVE';
                else if (emoji === '🤒') currentStatus = 'SICK';
            }
        }
    });

    const order = ['OFFICE', 'REMOTE', 'LEAVE', 'SICK'];
    const currentIdx = order.indexOf(currentStatus);

    if (currentIdx === -1) {
        setStatus('OFFICE');
    } else if (currentIdx === order.length - 1) {
        // Delete
        if (confirm('Bu durumu silmek istediğinize emin misiniz?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input name="action" value="delete_status"><input name="csrf_token" value="<?php echo $csrfToken; ?>"><input name="date" value="' + dateStr + '"><?php echo $isAdmin && $targetUserId ? '<input name="user_id" value="' . $targetUserId . '">' : ''; ?>';
            document.body.appendChild(form);
            form.submit();
        }
    } else {
        setStatus(order[currentIdx + 1]);
    }
}

document.getElementById('rangeEnd').addEventListener('change', function() {
    const start = document.getElementById('selectedDate').value;
    const end = this.value;
    if (start && end) {
        document.getElementById('rangeDisplay').innerHTML = start + ' — ' + end;
        document.getElementById('rangeDisplay').classList.remove('hidden');
    }
});

// Initialize
if (document.getElementById('rangeMode').checked) {
    document.getElementById('rangeEndDiv').classList.remove('hidden');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
