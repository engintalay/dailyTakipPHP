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
            <input type="url" name="jira_link" id="noteJiraLink"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                   placeholder="https://jira.company.com/browse/PROJ-123">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Dosya Ekle</label>
            <input type="file" name="files[]" id="noteFiles" multiple
                   class="w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/20 dark:file:text-blue-400">
            <div id="attachedFiles" class="mt-2 space-y-1 hidden"></div>
        </div>

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
        <?php foreach ($notes as $note):
            $files = json_decode($note['files'], true);
            if (!is_array($files)) $files = array();
        ?>
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
                    <button type="button" onclick="editNote(<?php echo json_encode($note); ?>)" class="text-xs text-gray-500 hover:text-blue-500">Düzenle</button>
                    <button type="button" onclick="deleteNote('<?php echo $note['id']; ?>')" class="text-xs text-gray-500 hover:text-red-500">Sil</button>
                </div>
                <?php endif; ?>
            </div>

            <p class="text-sm whitespace-pre-wrap"><?php echo escapeHtml($note['content']); ?></p>

            <?php if (!empty($note['jira_link'])): ?>
            <div class="mt-2">
                <a href="<?php echo escapeHtml($note['jira_link']); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                    <span>🔗</span>
                    <?php echo escapeHtml(preg_replace('/^https?:\/\//', '', rtrim($note['jira_link'], '/'))); ?>
                </a>
            </div>
            <?php endif; ?>

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

function resetForm() {
    document.getElementById('addNoteForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('noteId').value = '';
    document.getElementById('formTitle').textContent = 'Not Ekle';
    document.getElementById('submitBtn').textContent = 'Kaydet';
    document.getElementById('cancelEdit').classList.add('hidden');
    document.getElementById('noteDate').value = '<?php echo date('Y-m-d'); ?>';
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
    attachedFiles = <?php echo isset($note) && isset($note['files']) ? json_encode($note['files']) : '[]'; ?>;
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
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
