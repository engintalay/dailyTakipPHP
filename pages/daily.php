<?php
/**
 * Daily Notes page
 * PHP 5.3 compatible
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';

$currentUser = requireLogin();
$isAdmin = isAdmin($currentUser);

$pageTitle = 'Daily Notlar';
$effectiveUserId = isset($_SESSION['impersonating_user_id']) ? $_SESSION['impersonating_user_id'] : $currentUser['id'];

// Filters
$filterUser = isset($_GET['userId']) ? $_GET['userId'] : '';
$filterTag = isset($_GET['tag']) ? $_GET['tag'] : '';
$filterStart = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$filterEnd = isset($_GET['endDate']) ? $_GET['endDate'] : '';
$reportDate = $filterStart ? $filterStart : ($filterEnd ? $filterEnd : date('Y-m-d'));

// Get users for admin
$users = array();
if ($isAdmin) {
    $users = getAllUsers(true);
}

// Build filters
$filters = array();
if ($filterUser) $filters['user_id'] = $filterUser;
if ($filterTag) $filters['tag'] = $filterTag;
if ($filterStart) $filters['start_date'] = $filterStart;
if ($filterEnd) $filters['end_date'] = $filterEnd;

$notes = getDailyNotes($filters);

// Summary for the selected report day
$dayStatuses = getDailyStatuses(array('date' => $reportDate));
$statusCounts = array(
    STATUS_LEAVE => 0,
    STATUS_SICK => 0,
    STATUS_REMOTE => 0,
    STATUS_OFFICE => 0
);
foreach ($dayStatuses as $dayStatus) {
    if (isset($statusCounts[$dayStatus['type']])) {
        $statusCounts[$dayStatus['type']]++;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Daily Notlar</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?php echo escapeHtml(formatDateShort($reportDate)); ?> özeti</p>
        </div>
        <button id="toggleAddForm" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">+ Not Ekle</button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="rounded-xl border border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/40 p-3">
            <div class="text-xs font-medium text-amber-800 dark:text-amber-200">İzinli</div>
            <div class="text-2xl font-bold text-amber-950 dark:text-amber-100 mt-1"><?php echo $statusCounts[STATUS_LEAVE]; ?></div>
        </div>
        <div class="rounded-xl border border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950/40 p-3">
            <div class="text-xs font-medium text-red-800 dark:text-red-200">Hasta</div>
            <div class="text-2xl font-bold text-red-950 dark:text-red-100 mt-1"><?php echo $statusCounts[STATUS_SICK]; ?></div>
        </div>
        <div class="rounded-xl border border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-950/40 p-3">
            <div class="text-xs font-medium text-blue-800 dark:text-blue-200">Remote</div>
            <div class="text-2xl font-bold text-blue-950 dark:text-blue-100 mt-1"><?php echo $statusCounts[STATUS_REMOTE]; ?></div>
        </div>
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-950/40 p-3">
            <div class="text-xs font-medium text-emerald-800 dark:text-emerald-200">Kurumda</div>
            <div class="text-2xl font-bold text-emerald-950 dark:text-emerald-100 mt-1"><?php echo $statusCounts[STATUS_OFFICE]; ?></div>
        </div>
    </div>

    <!-- Add/Edit Form -->
    <form id="addNoteForm" method="POST" action="<?php echo APP_URL; ?>api/daily-notes.php" class="hidden bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 space-y-4" enctype="multipart/form-data">
        <input type="hidden" name="action" id="formAction" value="create">
        <input type="hidden" name="note_id" id="noteId">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

        <div class="flex items-center justify-between">
            <h2 class="font-semibold" id="formTitle">Not Ekle</h2>
            <button type="button" id="cancelEdit" class="hidden text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">İptal</button>
        </div>

        <button type="submit" id="submitBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 w-full">Kaydet</button>

        <?php if ($isAdmin): ?>
        <div>
            <label class="block text-sm font-medium mb-1">Kullanıcı</label>
            <select name="user_id" id="noteUserId" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                <option value="">Kendim (<?php echo escapeHtml($currentUser['name']); ?>)</option>
                <?php foreach ($users as $u): ?>
                <option value="<?php echo $u['id']; ?>"><?php echo escapeHtml($u['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
        <input type="hidden" name="user_id" value="<?php echo $effectiveUserId; ?>">
        <?php endif; ?>

        <div>
            <label class="block text-sm font-medium mb-1">Tarih</label>
            <input type="date" name="date" id="noteDate" required
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                   value="<?php echo date('Y-m-d'); ?>">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Not <span class="text-red-500">*</span></label>
            <textarea name="content" id="noteContent" required rows="3"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 resize-none"
                      placeholder="Bugün ne yaptın? ..."></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Etiketler (virgülle ayır)</label>
            <input type="text" name="tags" id="noteTags"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                   placeholder="frontend, bugfix, toplantı">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Jira Linki</label>
            <textarea name="jira_link" id="noteJiraLink" rows="2"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 resize-none"
                   placeholder="Her satıra bir Jira linki yazın"></textarea>
        </div>

        <button type="submit" id="submitBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 w-full">Kaydet</button>

        <div>
            <label class="block text-sm font-medium mb-1">Dosya Ekle</label>
            <input type="file" name="files[]" id="noteFiles" multiple
                   class="w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/20 dark:file:text-blue-400">
            <div id="attachedFiles" class="mt-2 space-y-1 hidden"></div>
        </div>
    </form>

    <!-- Last Month Tasks Section -->
    <div id="lastMonthTasks" class="hidden bg-gray-50 dark:bg-gray-900/30 border border-gray-200 dark:border-gray-700 rounded-lg p-4 mt-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Son 1 Aydaki Görevler</h3>
            <button type="button" id="toggleTasks" class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">Göster/Gizle</button>
        </div>
        <div id="tasksContent" class="space-y-2">
            <p class="text-xs text-gray-500 dark:text-gray-400 italic">Yükleniyor...</p>
        </div>
    </div>

    <!-- Filters -->
        <button type="submit" id="submitBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Kaydet</button>
    </form>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 space-y-3">
        <form method="GET" id="filterForm" class="flex flex-wrap gap-3 items-center">
            <input type="date" name="startDate" value="<?php echo escapeHtml($filterStart); ?>" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
            <input type="date" name="endDate" value="<?php echo escapeHtml($filterEnd); ?>" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
            <input type="text" name="tag" value="<?php echo escapeHtml($filterTag); ?>" placeholder="Etiket ara..." class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
            <button type="submit" class="px-3 py-1.5 text-sm rounded-lg border transition-colors <?php echo $filterStart || $filterEnd || $filterTag ? 'bg-amber-500 text-white border-amber-500' : 'border-gray-300 dark:border-gray-600 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'; ?>">
                ⏳ Eksik Notlar
            </button>
            <button type="button" onclick="document.getElementById('filterForm').reset(); window.location.href='<?php echo APP_URL; ?>pages/daily.php';" class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Temizle</button>
        </form>
    </div>

    <!-- Missing Users -->
    <div id="missingUsersSection" class="hidden bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
        <h2 class="font-semibold mb-3">⏳ Not Girmeyen Kullanıcılar</h2>
        <div id="missingUsersContent">Yükleniyor...</div>
    </div>

    <!-- Notes List -->
    <div class="space-y-3" id="notesList">
        <?php if (empty($notes)): ?>
        <p class="text-gray-500 dark:text-gray-400">Henüz not eklenmemiş.</p>
        <?php else: ?>
        <?php $previousNoteDate = ''; ?>
        <?php foreach ($notes as $note):
            $files = json_decode($note['files'], true);
            if (!is_array($files)) $files = array();
            $noteDateKey = substr($note['date'], 0, 10);
            if ($noteDateKey !== $previousNoteDate):
                $previousNoteDate = $noteDateKey;
        ?>
        <div class="flex items-center gap-3 pt-2">
            <div class="h-px flex-1 bg-slate-300 dark:bg-slate-600"></div>
            <span class="px-3 py-1 rounded-full bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200 text-xs font-semibold whitespace-nowrap">
                <?php echo escapeHtml(formatDateShort($noteDateKey)); ?>
            </span>
            <div class="h-px flex-1 bg-slate-300 dark:bg-slate-600"></div>
        </div>
        <?php endif; ?>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4" data-note-id="<?php echo $note['id']; ?>">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <?php echo getUserAvatar($note['name'], 'w-7 h-7'); ?>
                    <span class="text-sm font-medium"><?php echo escapeHtml($note['name']); ?></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">·</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo formatDateShort($note['date']); ?></span>
                </div>
                <?php if ($isAdmin || $note['user_id'] === $effectiveUserId): ?>
                <div class="flex gap-2">
                    <button type="button" onclick="editNote(<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>)" class="text-xs text-gray-500 hover:text-blue-500">Düzenle</button>
                    <button type="button" onclick="deleteNote('<?php echo $note['id']; ?>')" class="text-xs text-gray-500 hover:text-red-500">Sil</button>
                </div>
                <?php endif; ?>
            </div>

            <p class="text-sm whitespace-pre-wrap"><?php echo escapeHtml($note['content']); ?></p>

            <?php echo getJiraLinkHtml($note['jira_link']); ?>

            <?php if (!empty($files)): ?>
            <div class="mt-2 space-y-1">
                <?php foreach ($files as $i => $f): ?>
                <a href="<?php echo escapeHtml($f['url']); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline mr-3">
                    <span>📎</span>
                    <?php echo escapeHtml($f['name']); ?>
                    <span class="text-gray-500 dark:text-gray-400">(<?php echo formatFileSize($f['size']); ?>)</span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($note['tags'])): ?>
            <div class="flex gap-1 mt-2">
                <?php foreach (array_filter(array_map('trim', explode(',', $note['tags']))) as $tag): ?>
                <button type="button" onclick="filterByTag('<?php echo escapeHtml($tag); ?>')" class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400"><?php echo escapeHtml($tag); ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
let attachedFiles = [];

document.getElementById('noteFiles').addEventListener('change', function(e) {
    attachedFiles = Array.from(e.target.files);
    updateAttachedFilesDisplay();
});

function updateAttachedFilesDisplay() {
    const container = document.getElementById('attachedFiles');
    if (attachedFiles.length === 0) {
        container.classList.add('hidden');
        return;
    }
    container.classList.remove('hidden');
    container.innerHTML = attachedFiles.map((f, i) =>
        '<div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">' +
        '<span>📎 ' + escapeHtml(f.name) + '</span>' +
        '<span>(' + formatFileSize(f.size) + ')</span>' +
        '<button type="button" onclick="removeAttachedFile(' + i + ')" class="text-red-500 hover:text-red-700">×</button>' +
        '</div>'
    ).join('');
}

function removeAttachedFile(index) {
    attachedFiles.splice(index, 1);
    updateAttachedFilesDisplay();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function formatDateShort(dateStr) {
    if (!dateStr) return '';
    const months = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;
    const day = date.getDate();
    const month = months[date.getMonth() + 1];
    const year = date.getFullYear();
    return day + ' ' + month + ' ' + year;
}

document.getElementById('toggleAddForm').addEventListener('click', function() {
    const form = document.getElementById('addNoteForm');
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
        this.textContent = 'Formu Kapat';
    } else {
        form.classList.add('hidden');
        this.textContent = '+ Not Ekle';
        resetForm();
    }
});

document.getElementById('cancelEdit').addEventListener('click', resetForm);

document.getElementById('addNoteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var noteId = document.getElementById('noteId').value;
    var url = form.getAttribute('action') + (noteId ? '?id=' + encodeURIComponent(noteId) : '');
    var submitButton = document.getElementById('submitBtn');
    submitButton.disabled = true;
    submitButton.textContent = 'Kaydediliyor...';

    fetch(url, {
        method: 'POST',
        body: new FormData(form)
    }).then(function(response) {
        return response.json();
    }).then(function(data) {
        if (data.error) {
            alert('Hata: ' + data.error);
            submitButton.disabled = false;
            submitButton.textContent = noteId ? 'Güncelle' : 'Kaydet';
            return;
        }
        window.location.reload();
    }).catch(function() {
        alert('Not kaydedilemedi. Lütfen tekrar deneyin.');
        submitButton.disabled = false;
        submitButton.textContent = noteId ? 'Güncelle' : 'Kaydet';
    });
});

function resetForm() {
    document.getElementById('addNoteForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('noteId').value = '';
    document.getElementById('formTitle').textContent = 'Not Ekle';
    document.getElementById('submitBtn').textContent = 'Kaydet';
    document.getElementById('cancelEdit').classList.add('hidden');
    document.getElementById('noteDate').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('noteContent').value = '';
    document.getElementById('noteJiraLink').value = '';
    attachedFiles = [];
    updateAttachedFilesDisplay();
}

function editNote(note) {
    const form = document.getElementById('addNoteForm');
    form.classList.remove('hidden');
    document.getElementById('toggleAddForm').textContent = 'Formu Kapat';

    document.getElementById('formAction').value = 'update';
    document.getElementById('noteId').value = note.id;
    document.getElementById('formTitle').textContent = 'Notu Düzenle';
    document.getElementById('submitBtn').textContent = 'Güncelle';
    document.getElementById('cancelEdit').classList.remove('hidden');

    document.getElementById('noteDate').value = note.date.split('T')[0];
    document.getElementById('noteContent').value = note.content;
    document.getElementById('noteTags').value = note.tags || '';
    document.getElementById('noteJiraLink').value = note.jira_link || '';

    if (note.user_id && <?php echo $isAdmin ? 'true' : 'false'; ?>) {
        document.getElementById('noteUserId').value = note.user_id;
    }

    // Load existing files
    try {
        attachedFiles = note.files ? (typeof note.files === 'string' ? JSON.parse(note.files) : note.files) : [];
        if (!Array.isArray(attachedFiles)) attachedFiles = [];
    } catch (e) {
        attachedFiles = [];
    }
    updateAttachedFilesDisplay();

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function deleteNote(id) {
    if (!confirm('Bu notu silmek istediğinize emin misiniz?')) return;

    fetch('<?php echo APP_URL; ?>api/daily-notes.php?id=' + id, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf_token: '<?php echo $csrfToken; ?>' })
    }).then(r => r.json()).then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
        }
    });
}

function filterByTag(tag) {
    const url = new URL(window.location);
    url.searchParams.set('tag', tag);
    window.location.href = url.toString();
}

// Check for missing users
document.getElementById('filterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const params = new URLSearchParams(formData);
    window.location.href = '<?php echo APP_URL; ?>pages/daily.php?' + params.toString();
});

// Load missing users when filters change
function loadMissingUsers() {
    const params = new URLSearchParams(window.location.search);
    const start = params.get('startDate') || '<?php echo date('Y-m-d'); ?>';
    const end = params.get('endDate') || '<?php echo date('Y-m-d'); ?>';

    fetch('<?php echo APP_URL; ?>api/missing-notes.php?startDate=' + start + '&endDate=' + end)
        .then(r => r.json())
        .then(data => {
            const section = document.getElementById('missingUsersSection');
            const content = document.getElementById('missingUsersContent');

            if (data.length === 0) {
                content.innerHTML = '<div class="flex items-center gap-2 text-sm text-emerald-600"><span>✅</span><span>Seçilen tarih aralığında herkes notunu girmiş.</span></div>';
            } else {
                content.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 mb-2">' + data.length + ' kullanıcı not girmemiş:</p>' +
                    '<div class="space-y-2">' + data.map(u =>
                        '<div class="flex items-center justify-between px-3 py-2 rounded-lg bg-amber-50 dark:bg-amber-950/20">' +
                        '<div class="flex items-center gap-2">' +
                        '<div class="w-7 h-7 rounded-full bg-amber-500 flex items-center justify-center text-white text-xs font-bold">' + u.name.charAt(0).toUpperCase() + '</div>' +
                        '<span class="text-sm font-medium">' + escapeHtml(u.name) + '</span>' +
                        '</div>' +
                        (<?php echo $isAdmin ? 'true' : 'false'; ?> ?
                        '<button onclick="addNoteForUser(\'' + u.id + '\')" class="text-xs px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Not Ekle</button>' : '') +
                        '</div>'
                    ).join('') + '</div>';
            }
            section.classList.remove('hidden');
        });
}

// Load on page load if filters exist
if (window.location.search.includes('startDate') || window.location.search.includes('endDate') || window.location.search.includes('tag')) {
    loadMissingUsers();
}

// Auto-load missing users for admin
<?php if ($isAdmin): ?>
setTimeout(loadMissingUsers, 100);
<?php endif; ?>

function addNoteForUser(userId) {
    const form = document.getElementById('addNoteForm');
    form.classList.remove('hidden');
    document.getElementById('toggleAddForm').textContent = 'Formu Kapat';

    document.getElementById('noteUserId').value = userId;
    document.getElementById('noteDate').value = '<?php echo date('Y-m-d'); ?>';

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Load last month's tasks
function loadLastMonthTasks() {
    const section = document.getElementById('lastMonthTasks');
    const content = document.getElementById('tasksContent');
    
    if (!section || !content) return;
    
    fetch('<?php echo APP_URL; ?>api/daily-notes-history.php')
        .then(r => r.json())
        .then(data => {
            if (!Array.isArray(data) || data.length === 0) {
                content.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400 italic">Son 1 ayda not bulunmuyor.</p>';
                section.classList.add('hidden');
                return;
            }
            
            section.classList.remove('hidden');
            content.innerHTML = data.map(task => {
                const jiraBase = 'https://jira.gelirler.gov.tr/browse/';
                // Extract issue key from URL if needed
                let issueKey = '';
                if (task.jira_link) {
                    const match = task.jira_link.match(/\/([A-Z]+-[0-9]+)$/);
                    if (match) {
                        issueKey = match[1];
                    }
                }
                
                let jiraHtml = '';
                if (task.jira_link) {
                    jiraHtml = '<div class="mt-1">' +
                        '<a href="' + escapeHtml(task.jira_link) + '" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400">' + escapeHtml(issueKey || task.jira_link) + '</a>' +
                        '</div>';
                }
                
                return '<div class="flex items-start gap-2 p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">' +
                    '<div class="flex-1 min-w-0">' +
                    '<div class="flex items-center gap-2 mb-1">' +
                    '<span class="text-[10px] text-gray-500 dark:text-gray-400">' + escapeHtml(formatDateShort(task.date)) + '</span>' +
                    '</div>' +
                    '<p class="text-xs text-gray-600 dark:text-gray-300 whitespace-pre-wrap line-clamp-3 mb-2">' + escapeHtml(task.content || '') + '</p>' +
                    jiraHtml +
                    '</div>' +
                    '<button type="button" class="shrink-0 px-2 py-1 text-xs bg-emerald-600 text-white rounded hover:bg-emerald-700" data-task-content="' + escapeHtml(task.content || '') + '" data-task-jira="' + escapeHtml(task.jira_link || '') + '" onclick="copyTaskToNote(this)">Ekle</button>' +
                    '</div>';
            }).join('');
        })
        .catch(() => {
            content.innerHTML = '<p class="text-xs text-red-500 italic">Notlar yüklenemedi.</p>';
        });
}

// Copy task to note
function copyTaskToNote(btn) {
    const noteContentInput = document.getElementById('noteContent');
    if (!noteContentInput) return;
    
    // Get task data from data attributes
    const jiraLink = btn.getAttribute('data-task-jira');
    const noteContent = btn.getAttribute('data-task-content');
    
    // Add Jira link to jira_link field
    const jiraLinkInput = document.getElementById('noteJiraLink');
    if (jiraLink && jiraLinkInput) {
        const currentJiraLink = jiraLinkInput.value.trim();
        if (!currentJiraLink.toLowerCase().includes(jiraLink.toLowerCase())) {
            if (currentJiraLink) {
                jiraLinkInput.value = currentJiraLink + '\n' + jiraLink;
            } else {
                jiraLinkInput.value = jiraLink;
            }
        }
    }
    
    // Add note content to content field
    if (noteContent && noteContentInput) {
        const currentContent = noteContentInput.value.trim();
        if (!currentContent.toLowerCase().includes(noteContent.toLowerCase())) {
            if (currentContent) {
                noteContentInput.value = currentContent + '\n\n' + noteContent;
            } else {
                noteContentInput.value = noteContent;
            }
        }
    }
    
    // Show success feedback
    const originalText = btn.textContent;
    btn.textContent = 'Eklendi!';
    btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
    btn.classList.add('bg-emerald-400', 'cursor-default');
    setTimeout(function() {
        btn.textContent = originalText;
        btn.classList.remove('bg-emerald-400', 'cursor-default');
        btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
    }, 1500);
}

// Toggle tasks section
document.getElementById('toggleTasks').addEventListener('click', function() {
    const section = document.getElementById('lastMonthTasks');
    if (section.classList.contains('hidden')) {
        section.classList.remove('hidden');
        this.textContent = 'Gizle';
        if (section.querySelector('#tasksContent p')?.textContent.includes('Yükleniyor...')) {
            loadLastMonthTasks();
        }
    } else {
        section.classList.add('hidden');
        this.textContent = 'Göster';
    }
});

// Auto-load tasks when form is opened
document.getElementById('toggleAddForm').addEventListener('click', function() {
    const form = document.getElementById('addNoteForm');
    if (!form.classList.contains('hidden')) {
        // Form is now visible, load tasks if not already loaded
        const tasksContent = document.getElementById('tasksContent');
        if (tasksContent && tasksContent.innerHTML.includes('Yükleniyor...')) {
            loadLastMonthTasks();
        }
        
        // Load today's note content
        loadTodayNote();
    }
});

// Load today's note content for quick copy
function loadTodayNote() {
    const today = document.getElementById('noteDate').value;
    if (!today) return;
    
    fetch('<?php echo APP_URL; ?>api/daily-notes.php?userId=<?php echo $effectiveUserId; ?>&startDate=' + today + '&endDate=' + today + '&limit=1')
        .then(r => r.json())
        .then(data => {
            if (Array.isArray(data) && data.length > 0) {
                const note = data[0];
                const jiraInput = document.getElementById('noteJiraLink');
                const currentJira = jiraInput.value.trim();
                
                // If no jira link yet, add from today's note
                if (!currentJira && note.jira_link) {
                    jiraInput.value = note.jira_link;
                }
            }
        })
        .catch(function() {
            // Silent fail - this is just convenience
        });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
