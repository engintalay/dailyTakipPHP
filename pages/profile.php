<?php
/**
 * Profile page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';

$currentUser = requireLogin();
$effectiveUser = getEffectiveUser();
$isImpersonating = isImpersonating();
$impersonatedUser = $isImpersonating ? getImpersonatedUser() : null;
$isAdmin = isAdmin($currentUser);

$pageTitle = 'Profil';
$currentPath = 'pages/profile.php';

// Handle password change
$passwordError = '';
$passwordSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (verifyCsrfToken($csrf)) {
        $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        if (!$currentPassword || !$newPassword) {
            $passwordError = 'Mevcut şifre ve yeni şifre gerekli';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordError = 'Yeni şifreler eşleşmiyor';
        } elseif (strlen($newPassword) < 6) {
            $passwordError = 'Yeni şifre en az 6 karakter olmalı';
        } else {
            $result = changePassword($currentUser['id'], $currentPassword, $newPassword);
            if (isset($result['error'])) {
                $passwordError = $result['error'];
            } else {
                $passwordSuccess = 'Şifre başarıyla değiştirildi';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6 max-w-2xl">
    <h1 class="text-2xl font-bold">Profil</h1>

    <?php if ($isImpersonating): ?>
    <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
            <span>👤</span>
            <h2 class="font-semibold text-amber-800 dark:text-amber-300">Kullanıcı Taklidi Modu</h2>
        </div>
        <p class="text-sm text-amber-700 dark:text-amber-300 mb-3">
            <?php echo escapeHtml($currentUser['name']); ?> olarak oturum açmış durumdasınız (<?php echo escapeHtml($impersonatedUser['name']); ?>)
        </p>
        <a href="<?php echo APP_URL; ?>api/impersonate.php?stop=1" class="text-sm text-amber-700 dark:text-amber-400 hover:underline">← Kendi hesabına dön</a>
    </div>
    <?php endif; ?>

    <!-- Profile Info -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
        <div class="flex items-center gap-4">
            <?php echo getUserAvatar($effectiveUser['name'], 'w-20 h-20 text-2xl'); ?>
            <div>
                <h2 class="text-xl font-bold"><?php echo escapeHtml($effectiveUser['name']); ?></h2>
                <p class="text-gray-500 dark:text-gray-400"><?php echo escapeHtml($effectiveUser['email']); ?></p>
                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full font-medium <?php
                    echo $effectiveUser['role'] === ROLE_ADMIN
                        ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                        : ($effectiveUser['role'] === ROLE_VIEWER
                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                            : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400');
                ?>">
                    <?php echo escapeHtml(getRoleLabel($effectiveUser['role'])); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
        <h2 class="font-semibold text-lg mb-4">Şifre Değiştir</h2>

        <?php if ($passwordError): ?>
        <div class="mb-4 p-3 text-sm text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20 rounded-lg"><?php echo escapeHtml($passwordError); ?></div>
        <?php endif; ?>

        <?php if ($passwordSuccess): ?>
        <div class="mb-4 p-3 text-sm text-emerald-600 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-900/20 rounded-lg"><?php echo escapeHtml($passwordSuccess); ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div>
                <label class="block text-sm font-medium mb-1">Mevcut Şifre</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Yeni Şifre</label>
                <input type="password" name="new_password" required autocomplete="new-password" minlength="6"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Yeni Şifre (Tekrar)</label>
                <input type="password" name="confirm_password" required autocomplete="new-password"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>

            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Şifreyi Değiştir</button>
        </form>
    </div>

    <!-- Session Info -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
        <h2 class="font-semibold text-lg mb-4">Oturum Bilgileri</h2>
        <dl class="grid grid-cols-2 gap-4 text-sm">
            <dt class="text-gray-500 dark:text-gray-400">Kullanıcı ID</dt>
            <dd class="font-mono text-gray-900 dark:text-white"><?php echo escapeHtml($effectiveUser['id']); ?></dd>

            <dt class="text-gray-500 dark:text-gray-400">Rol</dt>
            <dd><?php echo escapeHtml($effectiveUser['role']); ?></dd>

            <dt class="text-gray-500 dark:text-gray-400">Kayıt Tarihi</dt>
            <dd><?php echo formatDateShort($effectiveUser['created_at']); ?></dd>

            <dt class="text-gray-500 dark:text-gray-400">Son Giriş</dt>
            <dd><?php echo isset($_SESSION['login_time']) ? formatDateTime($_SESSION['login_time']) : 'Bilinmiyor'; ?></dd>

            <?php if ($isImpersonating): ?>
            <dt class="text-gray-500 dark:text-gray-400">Asıl Kullanıcı</dt>
            <dd><?php echo escapeHtml($currentUser['name']); ?> (<?php echo escapeHtml($currentUser['email']); ?>)</dd>
            <?php endif; ?>
        </dl>
    </div>

    <!-- Logout -->
    <form action="<?php echo APP_URL; ?>api/logout.php" method="POST" class="text-center">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Çıkış Yap</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
