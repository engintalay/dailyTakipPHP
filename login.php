<?php
/**
 * Login page
 */
require_once __DIR__ . '/includes/config.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . 'index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Güvenlik hatası, lütfen sayfayı yenileyin';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (!$email || !$password) {
            $error = 'E-posta ve şifre gerekli';
        } else {
            $result = login($email, $password);
            if ($result) {
                header('Location: ' . APP_URL . 'index.php');
                exit;
            } else {
                $error = 'E-posta veya şifre hatalı';
            }
        }
    }
}

$csrfToken = generateCsrfToken();
$pageTitle = 'Giriş Yap';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeHtml($pageTitle); ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
        (function() {
            var saved = localStorage.getItem('dailyTakip-dark');
            if (saved === 'true' || (saved === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>assets/css/app.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📋</text></svg>">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-950 dark:to-slate-900">
    <button type="button" onclick="toggleDarkMode()" class="fixed top-4 right-4 px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" aria-label="Tema değiştir">
        <span id="darkModeIcon">🌙</span>
        <span id="darkModeLabel" class="ml-1">Karanlık Mod</span>
    </button>
    <div class="w-full max-w-md p-8 space-y-6 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-300 dark:border-slate-700">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">dailyTakip</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ekip Daily Takip Sistemi</p>
        </div>

        <?php if ($error): ?>
        <div class="p-3 text-sm text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20 rounded-lg" role="alert">
            <?php echo escapeHtml($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">E-posta</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="ornek@email.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Şifre</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="••••••••">
            </div>

            <button type="submit" class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                Giriş Yap
            </button>
        </form>

    </div>
<script src="<?php echo APP_URL; ?>assets/js/app.js"></script>
</body>
</html>
