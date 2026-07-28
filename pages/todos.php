<?php
/**
 * Todo module
 * PHP 5.3 compatible
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';

$currentUser = requireLogin();
$isAdmin = isAdmin($currentUser);
$pageTitle = 'Todo İşler';
$currentPath = 'pages/todos.php';

$todos = getTodos(array('include_done' => 1));
$users = getAllUsers(true);
$children = array();
$roots = array();
foreach ($todos as $todo) {
    if (!empty($todo['parent_id'])) {
        if (!isset($children[$todo['parent_id']])) $children[$todo['parent_id']] = array();
        $children[$todo['parent_id']][] = $todo;
    } else {
        $roots[] = $todo;
    }
}

$statusLabels = array('TODO' => 'Bekliyor', 'IN_PROGRESS' => 'Devam Ediyor', 'DONE' => 'Tamamlandı');
$priorityLabels = array('LOW' => 'Düşük', 'NORMAL' => 'Normal', 'HIGH' => 'Yüksek');
$statusClasses = array(
    'TODO' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    'IN_PROGRESS' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
    'DONE' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200'
);
$priorityClasses = array(
    'LOW' => 'text-slate-500',
    'NORMAL' => 'text-blue-600 dark:text-blue-300',
    'HIGH' => 'text-red-600 dark:text-red-300'
);

include __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Todo İşler</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Küçük işleri oluşturun, atayın ve takip edin.</p>
        </div>
        <button type="button" id="toggleTodoForm" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">+ Yeni Görev</button>
    </div>

    <form id="todoForm" method="POST" action="<?php echo APP_URL; ?>api/todos.php" class="hidden bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 space-y-4">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Görev başlığı</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" placeholder="Örn. Servis loglarını kontrol et">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Açıklama</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 resize-none" placeholder="İşle ilgili kısa not"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Kime atanacak?</label>
                <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                    <option value="<?php echo $currentUser['id']; ?>">Kendim</option>
                    <?php foreach ($users as $user): if ($user['id'] === $currentUser['id']) continue; ?>
                    <option value="<?php echo $user['id']; ?>"><?php echo escapeHtml($user['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Üst görev</label>
                <select name="parent_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                    <option value="">Ana görev</option>
                    <?php foreach ($roots as $root): if ($root['status'] === 'DONE') continue; ?>
                    <option value="<?php echo $root['id']; ?>">Alt görev: <?php echo escapeHtml($root['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Öncelik</label>
                <select name="priority" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                    <option value="NORMAL">Normal</option>
                    <option value="HIGH">Yüksek</option>
                    <option value="LOW">Düşük</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Son tarih</label>
                <input type="date" name="due_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Görevi Oluştur</button>
    </form>

    <?php if (empty($roots)): ?>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center text-sm text-gray-500 dark:text-gray-400">Henüz görev bulunmuyor.</div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($roots as $todo): ?>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 <?php echo $todo['status'] === 'DONE' ? 'opacity-70' : ''; ?>">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-semibold <?php echo $todo['status'] === 'DONE' ? 'line-through' : ''; ?>"><?php echo escapeHtml($todo['title']); ?></h2>
                        <span class="text-xs px-2 py-0.5 rounded-full <?php echo $statusClasses[$todo['status']]; ?>"><?php echo $statusLabels[$todo['status']]; ?></span>
                        <span class="text-xs font-medium <?php echo $priorityClasses[$todo['priority']]; ?>">● <?php echo $priorityLabels[$todo['priority']]; ?></span>
                    </div>
                    <?php if ($todo['description']): ?><p class="text-sm text-gray-600 dark:text-gray-300 mt-2 whitespace-pre-wrap"><?php echo escapeHtml($todo['description']); ?></p><?php endif; ?>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Atanan: <?php echo escapeHtml($todo['assignee_name']); ?> · Oluşturan: <?php echo escapeHtml($todo['creator_name']); ?><?php if ($todo['due_date']): ?> · Son tarih: <?php echo escapeHtml(formatDateShort($todo['due_date'])); ?><?php endif; ?></p>
                </div>
                <div class="flex flex-wrap justify-end gap-1 shrink-0">
                    <?php if ($todo['status'] !== 'DONE' && $todo['assigned_to'] !== $currentUser['id']): ?>
                    <form class="todo-action-form" method="POST" action="<?php echo APP_URL; ?>api/todos.php"><input type="hidden" name="action" value="claim"><input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><button class="px-2 py-1 text-xs bg-amber-100 text-amber-700 rounded hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-300">Üzerime Al</button></form>
                    <?php endif; ?>
                    <?php if ($todo['status'] !== 'DONE' && ($todo['assigned_to'] === $currentUser['id'] || $isAdmin)): ?>
                    <?php if ($todo['status'] === 'TODO'): ?><form class="todo-action-form" method="POST" action="<?php echo APP_URL; ?>api/todos.php"><input type="hidden" name="action" value="update"><input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>"><input type="hidden" name="status" value="IN_PROGRESS"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><button class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-300">Başlat</button></form><?php endif; ?>
                    <form class="todo-action-form" method="POST" action="<?php echo APP_URL; ?>api/todos.php"><input type="hidden" name="action" value="update"><input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>"><input type="hidden" name="status" value="DONE"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><button class="px-2 py-1 text-xs bg-emerald-100 text-emerald-700 rounded hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300">Tamamla</button></form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($children[$todo['id']])): ?>
            <div class="mt-4 ml-4 pl-4 border-l-2 border-blue-200 dark:border-blue-800 space-y-2">
                <?php foreach ($children[$todo['id']] as $child): ?>
                <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3 <?php echo $child['status'] === 'DONE' ? 'opacity-70' : ''; ?>">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-sm <?php echo $child['status'] === 'DONE' ? 'line-through' : ''; ?>"><?php echo escapeHtml($child['title']); ?></span>
                            <span class="text-xs px-2 py-0.5 rounded-full <?php echo $statusClasses[$child['status']]; ?>"><?php echo $statusLabels[$child['status']]; ?></span>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <?php if ($child['status'] !== 'DONE' && $child['assigned_to'] !== $currentUser['id']): ?><form class="todo-action-form" method="POST" action="<?php echo APP_URL; ?>api/todos.php"><input type="hidden" name="action" value="claim"><input type="hidden" name="todo_id" value="<?php echo $child['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><button class="text-xs text-amber-700 dark:text-amber-300">Üzerime Al</button></form><?php endif; ?>
                            <?php if ($child['status'] !== 'DONE' && ($child['assigned_to'] === $currentUser['id'] || $isAdmin)): ?><?php if ($child['status'] === 'TODO'): ?><form class="todo-action-form" method="POST" action="<?php echo APP_URL; ?>api/todos.php"><input type="hidden" name="action" value="update"><input type="hidden" name="todo_id" value="<?php echo $child['id']; ?>"><input type="hidden" name="status" value="IN_PROGRESS"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><button class="text-xs text-blue-700 dark:text-blue-300">Başlat</button></form><?php endif; ?><form class="todo-action-form" method="POST" action="<?php echo APP_URL; ?>api/todos.php"><input type="hidden" name="action" value="update"><input type="hidden" name="todo_id" value="<?php echo $child['id']; ?>"><input type="hidden" name="status" value="DONE"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><button class="text-xs text-emerald-700 dark:text-emerald-300">Tamamla</button></form><?php endif; ?>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Atanan: <?php echo escapeHtml($child['assignee_name']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('toggleTodoForm').addEventListener('click', function() {
    var form = document.getElementById('todoForm');
    form.classList.toggle('hidden');
    this.textContent = form.classList.contains('hidden') ? '+ Yeni Görev' : 'Formu Kapat';
});

document.querySelectorAll('.todo-action-form, #todoForm').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form) })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.error) { alert(data.error); return; }
                window.location.reload();
            })
            .catch(function() { alert('İşlem gerçekleştirilemedi.'); });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
