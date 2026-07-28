<?php
/**
 * Header/Layout template
 * PHP 5.3 compatible
 */
require_once __DIR__ . '/config.php';

$user = getCurrentUser();
$impersonated = isImpersonating();
$impersonatedUser = $impersonated ? getImpersonatedUser() : null;
$effectiveUser = getEffectiveUser();
$isAdmin = isAdmin($user);
$canViewManagement = canViewManagement($user);
$csrfToken = generateCsrfToken();

$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME;
$currentPath = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeHtml($pageTitle); ?></title>
    <meta name="description" content="<?php echo escapeHtml(APP_DESCRIPTION); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo APP_URL; ?>assets/favicon.svg?v=<?php echo APP_VERSION; ?>">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo APP_URL; ?>assets/favicon.svg?v=<?php echo APP_VERSION; ?>">

    <!-- Tailwind CSS via CDN (class-based dark mode) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>

    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>assets/css/app.css" rel="stylesheet">

    <!-- Alpine.js for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <script>
        window.APP_URL = '<?php echo APP_URL; ?>';
        window.CSRF_TOKEN = '<?php echo $csrfToken; ?>';
        window.IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        window.USER_ID = '<?php echo $effectiveUser ? $effectiveUser['id'] : ''; ?>';
        window.USER_NAME = '<?php echo $effectiveUser ? escapeHtml($effectiveUser['name']) : ''; ?>';
        window.USER_ROLE = '<?php echo $effectiveUser ? $effectiveUser['role'] : ''; ?>';

        // Dark mode initialization
        (function() {
            var saved = localStorage.getItem('dailyTakip-dark');
            if (saved === 'true' || (saved === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 flex flex-col">
    <?php if (isLoggedIn()): ?>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col h-screen fixed left-0 top-0 z-30 hidden lg:flex">
            <!-- Logo -->
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <a href="<?php echo APP_URL; ?>index.php" class="text-xl font-bold text-gray-900 dark:text-white">
                    dailyTakip
                </a>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ekip Takip Sistemi</p>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
                <a href="<?php echo APP_URL; ?>index.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo strpos($currentPath, 'index.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <span class="text-base">📊</span> Dashboard
                </a>

                <a href="<?php echo APP_URL; ?>pages/daily.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo basename(parse_url($currentPath, PHP_URL_PATH)) === 'daily.php' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                     <span class="text-base">📝</span> Daily Notlar
                </a>
                <a href="<?php echo APP_URL; ?>pages/todos.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo strpos($currentPath, 'todos.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <span class="text-base">✅</span> Todo İşler
                </a>

                <a href="<?php echo APP_URL; ?>pages/status.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo basename(parse_url($currentPath, PHP_URL_PATH)) === 'status.php' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <span class="text-base">📍</span> Durum Takibi
                </a>

                <a href="<?php echo APP_URL; ?>pages/team-status.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo strpos($currentPath, 'team-status.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <span class="text-base">📅</span> Ekip Takvimi
                </a>

                <a href="<?php echo APP_URL; ?>pages/oncall.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo strpos($currentPath, 'oncall.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <span class="text-base">🔔</span> Nöbet Takvimi
                </a>

                <a href="<?php echo APP_URL; ?>pages/attendance.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo strpos($currentPath, 'attendance.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <span class="text-base">✅</span> Katılım
                </a>

                <a href="<?php echo APP_URL; ?>pages/search.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo strpos($currentPath, 'search.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <span class="text-base">🔍</span> Arama
                </a>

                <?php if ($canViewManagement): ?>
                <div class="pt-3 pb-1">
                    <p class="px-3 text-xs font-semibold uppercase text-gray-400 dark:text-gray-500">Yönetim</p>
                </div>
                <a href="<?php echo APP_URL; ?>pages/admin/users.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo strpos($currentPath, 'admin/users.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <span class="text-base">👥</span> Kullanıcılar
                </a>
                <a href="<?php echo APP_URL; ?>pages/admin/holidays.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo strpos($currentPath, 'admin/holidays.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <span class="text-base">🎉</span> Tatiller
                </a>

                    <a href="<?php echo APP_URL; ?>pages/reports.php"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo strpos($currentPath, 'reports.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                     <span class="text-base">📈</span> Raporlar
                </a>
                <a href="<?php echo APP_URL; ?>pages/daily-summary.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo strpos($currentPath, 'daily-summary.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <span class="text-base">📚</span> Günlük Özet
                </a>
                <a href="<?php echo APP_URL; ?>pages/admin/status-report.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?php echo strpos($currentPath, 'status-report.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    <span class="text-base">📌</span> Durum Raporu
                </a>
                <?php endif; ?>
            </nav>

            <!-- User section -->
            <div class="p-3 border-t border-gray-200 dark:border-gray-700 space-y-1">
                <button onclick="toggleDarkMode()" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors mb-1">
                    <span id="darkModeIcon" class="text-base">🌙</span>
                    <span id="darkModeLabel">Karanlık Mod</span>
                </button>

                <a href="<?php echo APP_URL; ?>pages/profile.php"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <?php echo getUserAvatar($effectiveUser['name'], 'w-7 h-7'); ?>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate"><?php echo escapeHtml($effectiveUser['name']); ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo escapeHtml(getRoleLabel($effectiveUser['role'])); ?></p>
                    </div>
                </a>

                <?php if ($impersonated): ?>
                <div class="px-3 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <p class="text-xs text-amber-800 dark:text-amber-300 flex items-center gap-1">
                        <span>👤</span> <?php echo escapeHtml($user['name']); ?> olarak giriş yapıyorsunuz
                    </p>
                    <a href="<?php echo APP_URL; ?>api/impersonate.php?stop=1" class="block mt-2 text-xs text-amber-700 dark:text-amber-400 hover:underline">
                        ← Kendi hesabına dön
                    </a>
                </div>
                <?php endif; ?>

                <form action="<?php echo APP_URL; ?>api/logout.php" method="POST" class="w-full">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <span class="text-base">🚪</span> Çıkış Yap
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
            <!-- Top header (mobile) -->
            <header class="lg:hidden h-14 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 bg-white dark:bg-gray-800 z-20">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">dailyTakip</span>
                </div>
                <div class="flex items-center gap-2">
                    <?php echo getUserAvatar($effectiveUser['name'], 'w-8 h-8'); ?>
                </div>
            </header>

            <!-- Mobile sidebar overlay -->
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-20" @click="sidebarOpen = false"></div>
            <aside x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-150" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="lg:hidden fixed left-0 top-0 z-30 w-64 h-screen bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
                <!-- Same sidebar content for mobile -->
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <a href="<?php echo APP_URL; ?>index.php" class="text-xl font-bold text-gray-900 dark:text-white">dailyTakip</a>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ekip Takip Sistemi</p>
                </div>
                <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
                    <a href="<?php echo APP_URL; ?>index.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo strpos($currentPath, 'index.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>📊</span> Dashboard</a>
                    <a href="<?php echo APP_URL; ?>pages/daily.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo basename(parse_url($currentPath, PHP_URL_PATH)) === 'daily.php' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>📝</span> Daily Notlar</a>
                    <a href="<?php echo APP_URL; ?>pages/todos.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo strpos($currentPath, 'todos.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>✅</span> Todo İşler</a>
                    <a href="<?php echo APP_URL; ?>pages/status.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo basename(parse_url($currentPath, PHP_URL_PATH)) === 'status.php' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>📍</span> Durum Takibi</a>
                    <a href="<?php echo APP_URL; ?>pages/team-status.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo strpos($currentPath, 'team-status.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>📅</span> Ekip Takvimi</a>
                    <a href="<?php echo APP_URL; ?>pages/oncall.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo strpos($currentPath, 'oncall.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>🔔</span> Nöbet Takvimi</a>
                    <a href="<?php echo APP_URL; ?>pages/attendance.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo strpos($currentPath, 'attendance.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>✅</span> Katılım</a>
                    <a href="<?php echo APP_URL; ?>pages/search.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo strpos($currentPath, 'search.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>🔍</span> Arama</a>
                    <?php if ($canViewManagement): ?>
                    <div class="pt-3 pb-1"><p class="px-3 text-xs font-semibold uppercase text-gray-400">Yönetim</p></div>
                    <a href="<?php echo APP_URL; ?>pages/admin/users.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo strpos($currentPath, 'admin/users.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>👥</span> Kullanıcılar</a>
                    <a href="<?php echo APP_URL; ?>pages/admin/holidays.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo strpos($currentPath, 'admin/holidays.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>🎉</span> Tatiller</a>
                    <a href="<?php echo APP_URL; ?>pages/reports.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo strpos($currentPath, 'reports.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>📈</span> Raporlar</a>
                    <a href="<?php echo APP_URL; ?>pages/daily-summary.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo strpos($currentPath, 'daily-summary.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>📚</span> Günlük Özet</a>
                    <a href="<?php echo APP_URL; ?>pages/admin/status-report.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo strpos($currentPath, 'status-report.php') !== false ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><span>📌</span> Durum Raporu</a>
                    <?php endif; ?>
                </nav>
                <div class="p-3 border-t border-gray-200 dark:border-gray-700 space-y-1">
                    <button onclick="toggleDarkMode()" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <span id="mobileDarkModeIcon" class="text-base">🌙</span>
                        <span id="mobileDarkModeLabel">Karanlık Mod</span>
                    </button>
                    <a href="<?php echo APP_URL; ?>pages/profile.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <?php echo getUserAvatar($effectiveUser['name'], 'w-7 h-7'); ?>
                        <div class="flex-1 min-w-0"><p class="text-sm font-medium truncate"><?php echo escapeHtml($effectiveUser['name']); ?></p><p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo escapeHtml(getRoleLabel($effectiveUser['role'])); ?></p></div>
                    </a>
                    <?php if ($impersonated): ?>
                    <div class="px-3 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                        <p class="text-xs text-amber-800 dark:text-amber-300">👤 <?php echo escapeHtml($user['name']); ?> olarak giriş yapıyorsunuz</p>
                        <a href="<?php echo APP_URL; ?>api/impersonate.php?stop=1" class="block mt-2 text-xs text-amber-700 dark:text-amber-400 hover:underline">← Kendi hesabına dön</a>
                    </div>
                    <?php endif; ?>
                    <form action="<?php echo APP_URL; ?>api/logout.php" method="POST"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><button type="submit" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"><span>🚪</span> Çıkış Yap</button></form>
                </div>
            </aside>

            <!-- Impersonation banner -->
            <?php if ($impersonated): ?>
            <div class="bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800 px-6 py-2 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-amber-800 dark:text-amber-300">👤</span>
                    <span class="text-sm font-medium text-amber-800 dark:text-amber-300">
                        <?php echo escapeHtml($user['name']); ?> olarak oturum açtınız (<?php echo escapeHtml($impersonatedUser['name']); ?>)
                    </span>
                </div>
                <a href="<?php echo APP_URL; ?>api/impersonate.php?stop=1" class="text-sm text-amber-700 dark:text-amber-400 hover:underline">Kendi hesabına dön</a>
            </div>
            <?php endif; ?>

            <!-- Main content area -->
            <main class="flex-1 p-6" x-data="{ sidebarOpen: false }">
    <?php else: ?>
    <!-- Not logged in - just main content -->
    <main class="flex-1 min-h-screen">
    <?php endif; ?>
