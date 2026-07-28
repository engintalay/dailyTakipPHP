<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/models.php';

$currentUser = requireManagementAccess();
$isAdmin = isAdmin($currentUser);

$pageTitle = 'Tatil Yönetimi';
$currentPath = 'pages/admin/holidays.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        http_response_code(403);
        exit('Salt okunur kullanıcılar değişiklik yapamaz.');
    }
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (verifyCsrfToken($csrf)) {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        if ($action === 'save') {
            $date = isset($_POST['date']) ? $_POST['date'] : '';
            $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
            if ($date && $name) {
                saveHoliday($date, $name);
                flash('success', 'Tatil kaydedildi');
            } else {
                flash('error', 'Tarih ve açıklama gerekli');
            }
        } elseif ($action === 'delete') {
            $date = isset($_POST['date']) ? $_POST['date'] : '';
            if ($date) {
                deleteHoliday($date);
                flash('success', 'Tatil silindi');
            }
        }
    }
    header('Location: ' . APP_URL . 'pages/admin/holidays.php');
    exit;
}

$holidays = getHolidays();
usort($holidays, function($a, $b) { return strcmp($a['date'], $b['date']); });

include __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="<?php echo APP_URL; ?>index.php" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">← Geri</a>
            <h1 class="text-2xl font-bold">Tatil Yönetimi</h1>
        </div>
        <?php if ($isAdmin): ?>
        <button onclick="document.getElementById('addForm').classList.toggle('hidden'); this.textContent = document.getElementById('addForm').classList.contains('hidden') ? '+ Yeni Tatil' : 'İptal';"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">+ Yeni Tatil</button>
        <?php endif; ?>
    </div>

    <?php if ($isAdmin): ?>
    <form id="addForm" method="POST" class="hidden bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 space-y-4">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Tarih</label>
                <input type="date" name="date" required
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Tatil Adı</label>
                <input type="text" name="name" required placeholder="Örn: Ramazan Bayramı"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Kaydet</button>
    </form>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left p-3 font-medium">Tarih</th>
                    <th class="text-left p-3 font-medium">Tatil Adı</th>
                    <th class="text-right p-3 font-medium"><?php echo $isAdmin ? 'İşlem' : ''; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($holidays)): ?>
                <tr class="border-t border-gray-200 dark:border-gray-700">
                    <td colspan="3" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">Henüz tatil eklenmemiş.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($holidays as $h): ?>
                <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-3"><?php echo escapeHtml(formatDateShort($h['date'])); ?></td>
                    <td class="p-3 font-medium"><?php echo escapeHtml($h['name']); ?></td>
                    <td class="p-3 text-right">
                        <?php if ($isAdmin): ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Bu tatili silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="date" value="<?php echo $h['date']; ?>">
                            <button type="submit" class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400">Sil</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Tatiller nöbet takviminde otomatik olarak hariç tutulur. Her yıl güncellenmesi gerekebilir.
    </p>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
