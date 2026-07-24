<?php
/**
 * Admin Users Management page
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/models.php';

$currentUser = requireAdminAccess();
$isAdmin = true;

$pageTitle = 'Kullanıcı Yönetimi';
$currentPath = 'pages/admin/users.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (verifyCsrfToken($csrf)) {
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        if ($action === 'create') {
            $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $role = isset($_POST['role']) ? $_POST['role'] : ROLE_MEMBER;

            if ($name && $email && $password) {
                $result = createUser($name, $email, $password, $role);
                if (isset($result['error'])) {
                    flash('error', $result['error']);
                } else {
                    flash('success', 'Kullanıcı oluşturuldu');
                }
            } else {
                flash('error', 'İsim, e-posta ve şifre gerekli');
            }
        } elseif ($action === 'update') {
            $id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
            $data = array();
            if (!empty($_POST['name'])) $data['name'] = trim($_POST['name']);
            if (!empty($_POST['email'])) $data['email'] = trim($_POST['email']);
            if (!empty($_POST['role'])) $data['role'] = $_POST['role'];
            if (isset($_POST['is_active'])) $data['is_active'] = (bool)$_POST['is_active'];
            if (!empty($_POST['password'])) $data['password'] = $_POST['password'];

            if ($id) {
                $result = updateUser($id, $data);
                if (isset($result['error'])) {
                    flash('error', $result['error']);
                } else {
                    flash('success', 'Kullanıcı güncellendi');
                }
            }
        } elseif ($action === 'delete') {
            $id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
            if ($id && $id !== $currentUser['id']) {
                deleteUser($id);
                flash('success', 'Kullanıcı silindi');
            }
        } elseif ($action === 'toggle_active') {
            $id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
            $isActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;
            if ($id && $id !== $currentUser['id']) {
                updateUser($id, array('is_active' => $isActive));
                flash('success', $isActive ? 'Kullanıcı aktif edildi' : 'Kullanıcı pasif edildi');
            }
        } elseif ($action === 'impersonate') {
            $targetId = isset($_POST['user_id']) ? $_POST['user_id'] : '';
            if ($targetId && $targetId !== $currentUser['id']) {
                impersonate($targetId);
                header('Location: ' . APP_URL . 'index.php');
                exit;
            }
        }
    }
    header('Location: ' . APP_URL . 'pages/admin/users.php');
    exit;
}

$users = getAllUsers(false);

include __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="<?php echo APP_URL; ?>index.php" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">← Geri</a>
            <h1 class="text-2xl font-bold">Kullanıcı Yönetimi</h1>
        </div>
        <button onclick="document.getElementById('addUserForm').classList.toggle('hidden'); this.textContent = document.getElementById('addUserForm').classList.contains('hidden') ? '+ Yeni Kullanıcı' : 'İptal';"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">+ Yeni Kullanıcı</button>
    </div>

    <!-- Add/Edit Form -->
    <form id="addUserForm" method="POST" class="hidden bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 space-y-4">
        <input type="hidden" name="action" id="formAction" value="create">
        <input type="hidden" name="user_id" id="editUserId">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">İsim</label>
                <input type="text" name="name" id="formName" required
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">E-posta</label>
                <input type="email" name="email" id="formEmail" required
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" id="passwordLabel">Şifre</label>
                <input type="password" name="password" id="formPassword"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Rol</label>
                <select name="role" id="formRole" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                    <option value="<?php echo ROLE_MEMBER; ?>">Üye</option>
                    <option value="<?php echo ROLE_ADMIN; ?>">Admin</option>
                </select>
            </div>
        </div>

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700" id="submitBtn">Oluştur</button>
    </form>

    <!-- Users Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-center p-3 font-medium w-12">#</th>
                    <th class="text-left p-3 font-medium">İsim</th>
                    <th class="text-left p-3 font-medium">E-posta</th>
                    <th class="text-left p-3 font-medium">Rol</th>
                    <th class="text-left p-3 font-medium">Durum</th>
                    <th class="text-right p-3 font-medium">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $i => $u): ?>
                <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-3 text-center text-gray-500 dark:text-gray-400 text-xs"><?php echo $i + 1; ?></td>
                    <td class="p-3 font-medium"><?php echo escapeHtml($u['name']); ?></td>
                    <td class="p-3 text-gray-500 dark:text-gray-400"><?php echo escapeHtml($u['email']); ?></td>
                    <td class="p-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium <?php
                            echo $u['role'] === ROLE_ADMIN
                                ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                                : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
                        ?>">
                            <?php echo $u['role'] === ROLE_ADMIN ? 'Admin' : 'Üye'; ?>
                        </span>
                    </td>
                    <td class="p-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium <?php
                            echo $u['is_active']
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                        ?>">
                            <?php echo $u['is_active'] ? 'Aktif' : 'Pasif'; ?>
                        </span>
                    </td>
                    <td class="p-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <!-- Impersonate -->
                            <?php if ($u['id'] !== $currentUser['id'] && $u['is_active']): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="impersonate">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="px-2 py-1 text-xs bg-amber-100 text-amber-700 rounded hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-400" title="Bu kullanıcı olarak giriş yap">👤 Giriş Yap</button>
                            </form>
                            <?php endif; ?>

                            <!-- Edit -->
                            <button onclick="editUser(<?php echo json_encode($u); ?>)" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400">Düzenle</button>

                            <!-- Toggle Active -->
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $u['is_active'] ? '0' : '1'; ?>">
                                <button type="submit" class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400">
                                    <?php echo $u['is_active'] ? 'Pasif Yap' : 'Aktif Yap'; ?>
                                </button>
                            </form>

                            <!-- Delete -->
                            <?php if ($u['id'] !== $currentUser['id']): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400">Sil</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('addUserForm').classList.remove('hidden');
    document.getElementById('formAction').value = 'update';
    document.getElementById('editUserId').value = user.id;
    document.getElementById('formName').value = user.name;
    document.getElementById('formEmail').value = user.email;
    document.getElementById('formRole').value = user.role;
    document.getElementById('passwordLabel').textContent = 'Yeni şifre (boş bırakılırsa değişmez)';
    document.getElementById('formPassword').value = '';
    document.getElementById('submitBtn').textContent = 'Güncelle';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Reset form when cancelled
document.querySelector('#addUserForm button[type="button"]').addEventListener('click', function() {
    document.getElementById('addUserForm').classList.add('hidden');
    document.getElementById('formAction').value = 'create';
    document.getElementById('editUserId').value = '';
    document.getElementById('addUserForm').reset();
    document.getElementById('passwordLabel').textContent = 'Şifre';
    document.getElementById('submitBtn').textContent = 'Oluştur';
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>