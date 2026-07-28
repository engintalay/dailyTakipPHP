<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

$currentUser = requireLogin();
$isAdmin = isAdmin($currentUser);
$isViewer = $currentUser['role'] === 'VIEWER';
$effectiveUserId = getEffectiveUserId();
$data = getOnCallData();
$team = $data['team'];
$isInTeam = in_array($effectiveUserId, $team);

$today = date('Y-m-d');

// Default: start from this Monday
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('monday this week'));
$startTs = strtotime($startDate);
// Always ensure start is a Monday
$dow = (int)date('w', $startTs);
$mondayOffset = $dow === 0 ? -6 : 1 - $dow;
$startTs = mktime(0, 0, 0, (int)date('m', $startTs), (int)date('d', $startTs) + $mondayOffset, (int)date('Y', $startTs));
$startDate = date('Y-m-d', $startTs);

// End date: 13 days later (14 days total: this Mon-Sun + next Mon-Sun)
$endTs = $startTs + (13 * 86400);
$endDate = date('Y-m-d', $endTs);

$prevStart = date('Y-m-d', $startTs - (14 * 86400));
$nextStart = date('Y-m-d', $endTs + 86400);

$monthNames = array('', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık');
$rangeLabel = date('d', $startTs) . ' ' . $monthNames[(int)date('n', $startTs)] . ' ' . date('Y', $startTs)
    . ' — ' . date('d', $endTs) . ' ' . $monthNames[(int)date('n', $endTs)] . ' ' . date('Y', $endTs);

$holidays = getHolidays();
$holidayMap = array();
foreach ($holidays as $h) $holidayMap[$h['date']] = $h['name'];

$assignments = getOnCallAssignmentsForRange($startDate, $endDate);
$allUsers = getAllUsers(true);
$userMap = array();
foreach ($allUsers as $u) $userMap[$u['id']] = $u;
$teamUsers = array();
foreach ($team as $tid) {
    if (isset($userMap[$tid])) $teamUsers[] = $userMap[$tid];
}

$pageTitle = 'Nöbet Takvimi';
$currentPath = 'pages/oncall.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($csrf)) {
        flash('error', 'CSRF token geçersiz, lütfen sayfayı yenileyin.');
        header('Location: ' . APP_URL . 'pages/oncall.php?start_date=' . $startDate);
        exit;
    }
    if ($_POST['action'] === 'set_team' && $isAdmin && !$isViewer) {
        $teamIds = isset($_POST['team_ids']) ? $_POST['team_ids'] : array();
        if (!is_array($teamIds)) $teamIds = array();
        $teamIds = array_values($teamIds);
        setOnCallTeam($teamIds);
        flash('success', 'Nöbet ekibi güncellendi (' . count($teamIds) . ' üye)');
        header('Location: ' . APP_URL . 'pages/oncall.php?start_date=' . $startDate);
        exit;
    }
    flash('error', 'Geçersiz işlem veya yetkiniz yok.');
    header('Location: ' . APP_URL . 'pages/oncall.php?start_date=' . $startDate);
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header with navigation -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Nöbet Takvimi</h1>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?php echo APP_URL; ?>pages/oncall.php?start_date=<?php echo $prevStart; ?>"
               class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">← Önceki 2 Hafta</a>
            <span class="px-3 py-1 text-sm font-medium text-center whitespace-nowrap"><?php echo escapeHtml($rangeLabel); ?></span>
            <a href="<?php echo APP_URL; ?>pages/oncall.php?start_date=<?php echo $nextStart; ?>"
               class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">Sonraki 2 Hafta →</a>
            <form method="GET" class="flex items-center gap-1 ml-2" onsubmit="var v=this.querySelector('input').value; if(v) window.location='<?php echo APP_URL; ?>pages/oncall.php?start_date='+v; return false;">
                <input type="date" class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700" value="<?php echo $startDate; ?>">
                <button type="submit" class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">Git</button>
            </form>
        </div>
    </div>

    <?php if (!$isViewer && ($isAdmin || $isInTeam)): ?>
    <?php if ($isAdmin): ?>
    <!-- Team Management -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold">Nöbet Ekibi</h2>
            <button onclick="toggleTeamEdit()" class="text-sm px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Düzenle</button>
        </div>
        <div id="teamDisplay" class="flex flex-wrap gap-2">
            <?php if (empty($teamUsers)): ?>
            <span class="text-sm text-gray-500 dark:text-gray-400">Henüz nöbet ekibi belirlenmemiş.</span>
            <?php else: ?>
            <?php foreach ($teamUsers as $tu): ?>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                <?php echo getUserAvatar($tu['name'], 'w-5 h-5'); ?>
                <?php echo escapeHtml($tu['name']); ?>
            </span>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <form id="teamEditForm" method="POST" class="hidden mt-3 space-y-3">
            <input type="hidden" name="action" value="set_team">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="flex flex-wrap gap-2">
                <?php foreach ($allUsers as $u): ?>
                <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo in_array($u['id'], $team) ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : ''; ?>">
                    <input type="checkbox" name="team_ids[]" value="<?php echo $u['id']; ?>"
                           <?php echo in_array($u['id'], $team) ? 'checked' : ''; ?>
                           class="rounded border-gray-300 dark:border-gray-600">
                    <?php echo getUserAvatar($u['name'], 'w-5 h-5'); ?>
                    <?php echo escapeHtml($u['name']); ?>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Kaydet</button>
                <button type="button" onclick="toggleTeamEdit()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600">İptal</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Action buttons -->
    <div class="flex flex-wrap items-center gap-2">
        <button onclick="previewSuggestions()" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm hover:bg-amber-600">Öneriyi Önizle</button>
        <button onclick="applySuggestions()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">Öneri ile Doldur</button>
        <?php if ($isAdmin): ?>
        <a href="<?php echo APP_URL; ?>pages/admin/holidays.php" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Tatilleri Yönet</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- 14-Day Calendar -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700/50">
                    <th class="text-left p-3 font-medium sticky left-0 bg-gray-50 dark:bg-gray-700/50 z-10 min-w-[100px]">Gün</th>
                    <?php for ($i = 0; $i < 14; $i++):
                        $ts = $startTs + ($i * 86400);
                        $day = (int)date('d', $ts);
                        $dow = (int)date('w', $ts);
                        $isWeekend = $dow === 0 || $dow === 6;
                        $dateStr = date('Y-m-d', $ts);
                        $isToday = $dateStr === $today;
                        $dayNames = array('Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz');
                        $dayName = $dayNames[$dow === 0 ? 6 : $dow - 1];
                        $weekNum = $i < 7 ? 1 : 2;
                    ?>
                    <th class="text-center p-2 font-medium min-w-[80px] <?php
                        echo $isWeekend ? 'bg-slate-200 text-slate-600 dark:bg-slate-600 dark:text-slate-300' : '';
                        echo $isToday ? 'bg-blue-200 text-blue-950 ring-2 ring-blue-600 ring-inset dark:bg-blue-900 dark:text-blue-100 dark:ring-blue-400' : '';
                    ?>">
                        <?php if ($i === 0 || $i === 7): ?>
                        <div class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold mb-1">
                            <?php echo $i === 0 ? 'Bu Hafta' : 'Gelecek Hafta'; ?>
                        </div>
                        <?php endif; ?>
                        <div><?php echo $day; ?></div>
                        <div class="text-xs <?php echo $isWeekend ? 'text-slate-500 dark:text-slate-400' : 'text-gray-500 dark:text-gray-400'; ?>"><?php echo $dayName; ?></div>
                        <?php if (isset($holidayMap[$dateStr])): ?>
                        <div class="text-[10px] text-red-600 dark:text-red-400 font-medium"><?php echo escapeHtml($holidayMap[$dateStr]); ?></div>
                        <?php endif; ?>
                        <?php if ($isToday): ?><div class="text-[9px] font-bold text-blue-700 dark:text-blue-200">Bugün</div><?php endif; ?>
                    </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <tr class="border-t border-gray-200 dark:border-gray-700">
                    <td class="p-3 font-medium sticky left-0 bg-white dark:bg-gray-800 z-10 whitespace-nowrap">
                        Nöbetçi
                        <?php if (empty($team)): ?>
                        <div class="text-xs font-normal text-gray-500 dark:text-gray-400 mt-1">(Ekip yok)</div>
                        <?php endif; ?>
                    </td>
                    <?php for ($i = 0; $i < 14; $i++):
                        $ts = $startTs + ($i * 86400);
                        $dow = (int)date('w', $ts);
                        $isWeekend = $dow === 0 || $dow === 6;
                        $dateStr = date('Y-m-d', $ts);
                        $isToday = $dateStr === $today;
                        $isHoliday = isset($holidayMap[$dateStr]);
                        $isExcluded = $isWeekend || $isHoliday;
                        $assignedUserId = isset($assignments[$dateStr]) ? $assignments[$dateStr] : null;
                        $assignedUser = $assignedUserId && isset($userMap[$assignedUserId]) ? $userMap[$assignedUserId] : null;
                        $canEdit = !$isViewer && $isInTeam && !$isExcluded && ($isAdmin || $dateStr >= $today);
                    ?>
                    <td class="text-center p-2 border-l border-gray-200 dark:border-gray-700 <?php
                        echo $isWeekend ? 'bg-slate-100 dark:bg-slate-800' : '';
                        echo $isToday ? 'bg-blue-50 ring-2 ring-blue-500 ring-inset dark:bg-blue-950/40 dark:ring-blue-400' : '';
                        echo $canEdit ? 'cursor-pointer transition-colors hover:ring-2 hover:ring-blue-500 hover:ring-inset' : '';
                    ?>"
                        <?php if ($canEdit): ?>
                        onclick="openAssignModal('<?php echo $dateStr; ?>', '<?php echo $assignedUserId ?: ''; ?>')"
                        <?php endif; ?>
                    >
                        <?php if ($isExcluded): ?>
                            <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                            <?php if ($isHoliday): ?>
                            <div class="text-[10px] text-red-500 dark:text-red-400 mt-0.5">Tatil</div>
                            <?php elseif ($isWeekend): ?>
                            <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">HfT</div>
                            <?php endif; ?>
                        <?php elseif ($assignedUser): ?>
                            <div class="flex flex-col items-center gap-1">
                                <?php echo getUserAvatar($assignedUser['name'], 'w-7 h-7'); ?>
                                <span class="text-xs font-medium"><?php echo escapeHtml($assignedUser['name']); ?></span>
                            </div>
                        <?php else: ?>
                            <span class="text-gray-400 dark:text-gray-500 text-xs">Boş</span>
                            <?php if ($canEdit): ?>
                            <div class="text-[10px] text-blue-500 mt-0.5">Tıkla ata</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <?php endfor; ?>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
        <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">Hafta sonu</span>
        <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400 border border-red-200 dark:border-red-800">Resmi tatil</span>
        <span class="inline-flex items-center gap-1">Nöbet atanabilir</span>
        <span class="inline-flex items-center gap-1 text-gray-400">Boş (atanabilir)</span>
    </div>
</div>

<!-- Assignment Modal -->
<div id="assignModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center" onclick="closeAssignModal()">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-sm w-full mx-4 shadow-xl" onclick="event.stopPropagation()">
        <h3 class="font-semibold text-lg mb-1" id="modalTitle">Nöbet Ata</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" id="modalDate"></p>
        <div class="space-y-2 max-h-60 overflow-y-auto">
            <?php if (empty($team)): ?>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nöbet ekibi henüz belirlenmemiş.</p>
            <?php else: ?>
            <?php foreach ($team as $tid):
                $tu = isset($userMap[$tid]) ? $userMap[$tid] : null;
                if (!$tu) continue;
            ?>
            <button onclick="setAssignment('<?php echo $tu['id']; ?>')"
                    class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left"
                    id="teamOpt_<?php echo $tu['id']; ?>">
                <?php echo getUserAvatar($tu['name'], 'w-7 h-7'); ?>
                <span class="font-medium"><?php echo escapeHtml($tu['name']); ?></span>
            </button>
            <?php endforeach; ?>
            <?php endif; ?>
            <hr class="border-gray-200 dark:border-gray-600 my-2">
            <button onclick="removeAssignment()"
                    class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left">
                <span>✕</span>
                <span>Nöbeti Kaldır</span>
            </button>
        </div>
        <button onclick="closeAssignModal()" class="mt-4 w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600">İptal</button>
    </div>
</div>

<!-- Suggestion Preview Modal -->
<div id="suggestionModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center" onclick="closeSuggestionModal()">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-2xl w-full mx-4 shadow-xl max-h-[80vh] overflow-y-auto" onclick="event.stopPropagation()">
        <h3 class="font-semibold text-lg mb-1">Öneri Önizleme</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" id="suggestionInfo"></p>
        <div id="suggestionList" class="space-y-2"></div>
        <div class="flex gap-2 mt-4">
            <button onclick="applyPreviewSuggestions()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">Önerileri Uygula</button>
            <button onclick="closeSuggestionModal()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Kapat</button>
        </div>
    </div>
</div>

<script>
var selectedDate = '';
var selectedUserId = '';

function openAssignModal(dateStr, userId) {
    selectedDate = dateStr;
    selectedUserId = userId;
    document.getElementById('modalDate').textContent = dateStr + ' (' + getDayName(dateStr) + ')';
    document.getElementById('modalTitle').textContent = userId ? 'Nöbet Değiştir' : 'Nöbet Ata';
    document.getElementById('assignModal').classList.remove('hidden');

    document.querySelectorAll('[id^="teamOpt_"]').forEach(function(el) {
        var uid = el.id.replace('teamOpt_', '');
        if (uid === userId) {
            el.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-300', 'dark:border-blue-700');
        } else {
            el.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-300', 'dark:border-blue-700');
        }
    });
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}

function getDayName(dateStr) {
    var days = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    var d = new Date(dateStr);
    return days[d.getDay()];
}

function setAssignment(targetUserId) {
    var formData = new FormData();
    formData.append('action', 'set_assignment');
    formData.append('csrf_token', '<?php echo $csrfToken; ?>');
    formData.append('date', selectedDate);
    formData.append('user_id', targetUserId);

    fetch('<?php echo APP_URL; ?>api/oncall.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) { alert(data.error); return; }
        window.location.reload();
    })
    .catch(function() { alert('İşlem başarısız'); });
}

function removeAssignment() {
    if (!selectedUserId) { closeAssignModal(); return; }
    var formData = new FormData();
    formData.append('action', 'remove_assignment');
    formData.append('csrf_token', '<?php echo $csrfToken; ?>');
    formData.append('date', selectedDate);

    fetch('<?php echo APP_URL; ?>api/oncall.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) { alert(data.error); return; }
        window.location.reload();
    })
    .catch(function() { alert('İşlem başarısız'); });
}

function previewSuggestions() {
    var formData = new FormData();
    formData.append('action', 'suggest');
    formData.append('csrf_token', '<?php echo $csrfToken; ?>');
    formData.append('start_date', '<?php echo $startDate; ?>');
    formData.append('end_date', '<?php echo $endDate; ?>');

    fetch('<?php echo APP_URL; ?>api/oncall.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) { alert(data.error); return; }
        var suggestions = data.suggestions || {};
        var keys = Object.keys(suggestions);
        if (keys.length === 0) {
            alert('Doldurulacak boş gün bulunamadı.');
            return;
        }
        document.getElementById('suggestionInfo').textContent = keys.length + ' gün için öneri hazırlandı:';
        var list = document.getElementById('suggestionList');
        list.innerHTML = '';
        keys.sort().forEach(function(date) {
            var item = suggestions[date];
            var days = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
            var d = new Date(date);
            var dayName = days[d.getDay()];
            var el = document.createElement('div');
            el.className = 'flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/50';
            el.innerHTML = '<span class="text-sm font-medium">' + date + ' (' + dayName + ')</span><span class="text-sm text-blue-600 dark:text-blue-400">' + item.name + '</span>';
            list.appendChild(el);
        });
        document.getElementById('suggestionModal').classList.remove('hidden');
    })
    .catch(function() { alert('Öneri alınamadı'); });
}

function closeSuggestionModal() {
    document.getElementById('suggestionModal').classList.add('hidden');
}

function applyPreviewSuggestions() {
    var formData = new FormData();
    formData.append('action', 'apply_suggestions');
    formData.append('csrf_token', '<?php echo $csrfToken; ?>');
    formData.append('start_date', '<?php echo $startDate; ?>');
    formData.append('end_date', '<?php echo $endDate; ?>');

    fetch('<?php echo APP_URL; ?>api/oncall.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) { alert(data.error); return; }
        closeSuggestionModal();
        window.location.reload();
    })
    .catch(function() { alert('Uygulama başarısız'); });
}

function applySuggestions() {
    if (!confirm('Boş nöbet günlerini öneriye göre doldurmak istediğinize emin misiniz?')) return;
    var formData = new FormData();
    formData.append('action', 'apply_suggestions');
    formData.append('csrf_token', '<?php echo $csrfToken; ?>');
    formData.append('start_date', '<?php echo $startDate; ?>');
    formData.append('end_date', '<?php echo $endDate; ?>');

    fetch('<?php echo APP_URL; ?>api/oncall.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) { alert(data.error); return; }
        window.location.reload();
    })
    .catch(function() { alert('Uygulama başarısız'); });
}

function toggleTeamEdit() {
    var display = document.getElementById('teamDisplay');
    var form = document.getElementById('teamEditForm');
    if (form) {
        form.classList.toggle('hidden');
        if (display) display.classList.toggle('hidden');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
