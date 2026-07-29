<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/models.php';

$currentUser = requireManagementAccess();
$isAdmin = isAdmin($currentUser);
$isViewer = $currentUser['role'] === 'VIEWER';

$pageTitle = 'Jira İşleri';
$currentPath = 'pages/admin/jira-tasks.php';

$users = getAllUsers(true);

include __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="<?php echo APP_URL; ?>index.php" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">← Geri</a>
            <h1 class="text-2xl font-bold">Jira İşleri</h1>
        </div>
        <?php if (!$isViewer): ?>
        <button onclick="openCreateModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">+ Yeni İş</button>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
        <form id="filterForm" method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Arama</label>
                <input type="text" name="search" id="f_search" placeholder="Link veya açıklama..."
                       class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm w-48">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Öncelik</label>
                <select name="priority" id="f_priority" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    <option value="">Tümü</option>
                    <option value="HIGH">Yüksek</option>
                    <option value="NORMAL">Normal</option>
                    <option value="LOW">Düşük</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Atama</label>
                <select name="assigned" id="f_assigned" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    <option value="empty">Atanmamış</option>
                    <option value="">Tümü</option>
                    <option value="not_empty">Atanmış</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Durum</label>
                <select name="status" id="f_status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    <option value="">Tümü</option>
                    <option value="PENDING">Bekliyor</option>
                    <option value="ASSIGNED">Atanmış</option>
                    <option value="DONE">Tamamlandı</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Filtrele</button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left p-3 font-medium">Jira Linki</th>
                    <th class="text-left p-3 font-medium">Açıklama</th>
                    <th class="text-center p-3 font-medium">Öncelik</th>
                    <th class="text-center p-3 font-medium">Durum</th>
                    <th class="text-left p-3 font-medium">Atanan</th>
                    <th class="text-right p-3 font-medium">İşlem</th>
                </tr>
            </thead>
            <tbody id="tasksBody">
                <tr>
                    <td colspan="6" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">Yükleniyor...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="taskModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center" onclick="closeTaskModal()">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-lg w-full mx-4 shadow-xl" onclick="event.stopPropagation()">
        <h3 class="font-semibold text-lg mb-1" id="taskModalTitle">Yeni İş</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Jira iş bilgilerini girin.</p>
        <form id="taskForm" class="space-y-4">
            <input type="hidden" name="task_id" id="f_task_id">
            <div>
                <label class="block text-sm font-medium mb-1">Jira Linki <span class="text-red-500">*</span></label>
                <input type="url" name="jira_link" id="f_jira_link" required placeholder="https://jira...."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Kısa Açıklama <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="f_title" required
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Öncelik</label>
                <select name="priority" id="f_priority_modal" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                    <option value="LOW">Düşük</option>
                    <option value="NORMAL" selected>Normal</option>
                    <option value="HIGH">Yüksek</option>
                </select>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Kaydet</button>
                <button type="button" onclick="closeTaskModal()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600">İptal</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Modal -->
<div id="assignModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center" onclick="closeAssignModal()">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-sm w-full mx-4 shadow-xl" onclick="event.stopPropagation()">
        <h3 class="font-semibold text-lg mb-1">İş Ata</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" id="assignTaskTitle"></p>
        <form id="assignForm" class="space-y-4">
            <input type="hidden" name="task_id" id="a_task_id">
            <div>
                <label class="block text-sm font-medium mb-1">Kime atanacak?</label>
                <select name="assigned_to" id="a_assigned_to" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                    <option value="">Seçin</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo escapeHtml($u['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Ata</button>
                <button type="button" onclick="closeAssignModal()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600">İptal</button>
            </div>
        </form>
    </div>
</div>

<script>
var currentEditId = '';

function loadTasks() {
    var params = new URLSearchParams();
    ['search', 'priority', 'status', 'assigned'].forEach(function(k) {
        var v = document.getElementById('f_' + k).value;
        if (v) params.set(k, v);
    });

    fetch('<?php echo APP_URL; ?>api/jira-tasks.php?' + params.toString())
        .then(function(r) { return r.json(); })
        .then(function(tasks) {
            var tbody = document.getElementById('tasksBody');
            if (!tasks || tasks.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">İş bulunamadı.</td></tr>';
                return;
            }
            var html = '';
            tasks.forEach(function(t) {
                var statusLabels = {'PENDING':'Bekliyor','ASSIGNED':'Atanmış','DONE':'Tamamlandı'};
                var statusClasses = {'PENDING':'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400','ASSIGNED':'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400','DONE':'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'};
                var priorityLabels = {'LOW':'Düşük','NORMAL':'Normal','HIGH':'Yüksek'};
                var priorityClasses = {'LOW':'text-slate-500','NORMAL':'text-blue-600 dark:text-blue-300','HIGH':'text-red-600 dark:text-red-300'};
                var actions = '';
                <?php if (!$isViewer): ?>
                actions = '<div class="flex justify-end gap-1">' +
                    (t.status !== 'DONE' ? '<button onclick="openAssignModal(\'' + t.id + '\',\'' + escapeHtml(t.title) + '\')" class="px-2 py-1 text-xs bg-amber-100 text-amber-700 rounded hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-400">Ata</button>' : '') +
                    '<button onclick="openEditModal(\'' + t.id + '\')" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400">Düzenle</button>' +
                    '<button onclick="deleteTask(\'' + t.id + '\')" class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400">Sil</button>' +
                    '</div>';
                <?php endif; ?>
                html += '<tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">' +
                    '<td class="p-3"><a href="' + escapeHtml(t.jira_link) + '" target="_blank" rel="noopener" class="text-blue-600 hover:underline text-xs break-all">' + escapeHtml(t.jira_link) + '</a></td>' +
                    '<td class="p-3 font-medium">' + escapeHtml(t.title) + '</td>' +
                    '<td class="p-3 text-center"><span class="text-xs font-medium ' + priorityClasses[t.priority] + '">● ' + priorityLabels[t.priority] + '</span></td>' +
                    '<td class="p-3 text-center"><span class="text-xs px-2 py-0.5 rounded-full ' + statusClasses[t.status] + '">' + statusLabels[t.status] + '</span></td>' +
                    '<td class="p-3 text-sm">' + (t.assigned_name ? escapeHtml(t.assigned_name) : '<span class="text-gray-400">-</span>') + '</td>' +
                    '<td class="p-3">' + actions + '</td>' +
                    '</tr>';
            });
            tbody.innerHTML = html;
        })
        .catch(function() {
            document.getElementById('tasksBody').innerHTML = '<tr><td colspan="6" class="p-6 text-center text-sm text-red-500">Yüklenirken hata oluştu.</td></tr>';
        });
}

function escapeHtml(s) {
    if (!s) return '';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function openCreateModal() {
    currentEditId = '';
    document.getElementById('taskModalTitle').textContent = 'Yeni İş';
    document.getElementById('f_task_id').value = '';
    document.getElementById('f_jira_link').value = '';
    document.getElementById('f_title').value = '';
    document.getElementById('f_priority_modal').value = 'NORMAL';
    document.getElementById('taskModal').classList.remove('hidden');
}

function openEditModal(id) {
    currentEditId = id;
    <?php if (!$isViewer): ?>
    fetch('<?php echo APP_URL; ?>api/jira-tasks.php')
        .then(function(r) { return r.json(); })
        .then(function(tasks) {
            for (var i = 0; i < tasks.length; i++) {
                if (tasks[i].id === id) {
                    document.getElementById('taskModalTitle').textContent = 'İşi Düzenle';
                    document.getElementById('f_task_id').value = id;
                    document.getElementById('f_jira_link').value = tasks[i].jira_link;
                    document.getElementById('f_title').value = tasks[i].title;
                    document.getElementById('f_priority_modal').value = tasks[i].priority;
                    document.getElementById('taskModal').classList.remove('hidden');
                    return;
                }
            }
        });
    <?php endif; ?>
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.add('hidden');
}

document.getElementById('taskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var action = document.getElementById('f_task_id').value ? 'update' : 'create';
    var formData = new FormData();
    formData.append('action', action);
    formData.append('csrf_token', '<?php echo $csrfToken; ?>');
    formData.append('task_id', document.getElementById('f_task_id').value);
    formData.append('jira_link', document.getElementById('f_jira_link').value);
    formData.append('title', document.getElementById('f_title').value);
    formData.append('priority', document.getElementById('f_priority_modal').value);

    fetch('<?php echo APP_URL; ?>api/jira-tasks.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) { alert(data.error); return; }
            closeTaskModal();
            loadTasks();
        })
        .catch(function() { alert('İşlem başarısız'); });
});

function openAssignModal(id, title) {
    document.getElementById('a_task_id').value = id;
    document.getElementById('a_assigned_to').value = '';
    document.getElementById('assignTaskTitle').textContent = title;
    document.getElementById('assignModal').classList.remove('hidden');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}

document.getElementById('assignForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData();
    formData.append('action', 'assign');
    formData.append('csrf_token', '<?php echo $csrfToken; ?>');
    formData.append('task_id', document.getElementById('a_task_id').value);
    formData.append('assigned_to', document.getElementById('a_assigned_to').value);

    fetch('<?php echo APP_URL; ?>api/jira-tasks.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) { alert(data.error); return; }
            closeAssignModal();
            loadTasks();
        })
        .catch(function() { alert('Atama başarısız'); });
});

function deleteTask(id) {
    if (!confirm('Bu işi silmek istediğinize emin misiniz?')) return;
    var formData = new FormData();
    formData.append('action', 'delete');
    formData.append('csrf_token', '<?php echo $csrfToken; ?>');
    formData.append('task_id', id);

    fetch('<?php echo APP_URL; ?>api/jira-tasks.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) { alert(data.error); return; }
            loadTasks();
        })
        .catch(function() { alert('Silme başarısız'); });
}

document.getElementById('filterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    loadTasks();
});

// Initial load
loadTasks();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
