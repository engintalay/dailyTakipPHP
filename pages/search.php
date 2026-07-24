<?php
/**
 * Search page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models.php';

$currentUser = requireLogin();

$pageTitle = 'Arama';
$currentPath = 'pages/search.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$filterTag = isset($_GET['tag']) ? $_GET['tag'] : '';
$filterUser = isset($_GET['userId']) ? $_GET['userId'] : '';
$filterStart = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$filterEnd = isset($_GET['endDate']) ? $_GET['endDate'] : '';

$filters = array();
if ($query) $filters['search'] = $query;
if ($filterTag) $filters['tag'] = $filterTag;
if ($filterUser) $filters['user_id'] = $filterUser;
if ($filterStart) $filters['start_date'] = $filterStart;
if ($filterEnd) $filters['end_date'] = $filterEnd;

$results = array();
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($query || $filterTag || $filterUser || $filterStart || $filterEnd)) {
    $searched = true;
    $results = getDailyNotes($filters);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold">Arama</h1>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 space-y-4">
        <form method="GET" id="searchForm">
            <div class="flex gap-3">
                <input type="text" name="q" value="<?php echo escapeHtml($query); ?>"
                       class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm"
                       placeholder="Notlarda ara..." autofocus>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Ara</button>
            </div>

            <div class="flex flex-wrap gap-3 text-sm">
                <input type="date" name="startDate" value="<?php echo escapeHtml($filterStart); ?>" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                <input type="date" name="endDate" value="<?php echo escapeHtml($filterEnd); ?>" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                <input type="text" name="tag" value="<?php echo escapeHtml($filterTag); ?>" placeholder="Etiket" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                <button type="button" onclick="document.getElementById('searchForm').reset(); window.location.href='<?php echo APP_URL; ?>pages/search.php';"
                        class="px-3 py-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg">Temizle</button>
            </div>
        </form>
    </div>

    <?php if ($searched): ?>
    <div class="space-y-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <?php echo empty($results) ? 'Sonuç bulunamadı.' : count($results) . ' sonuç bulundu.'; ?>
        </p>

        <?php foreach ($results as $note): ?>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-sm font-medium"><?php echo escapeHtml($note['name']); ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">·</span>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo formatDateShort($note['date']); ?></span>
            </div>
            <p class="text-sm whitespace-pre-wrap"><?php echo escapeHtml($note['content']); ?></p>
            <?php if (!empty($note['tags'])): ?>
            <div class="flex gap-1 mt-2">
                <?php foreach (array_filter(array_map('trim', explode(',', $note['tags']))) as $tag): ?>
                <button type="button" onclick="window.location.href='<?php echo APP_URL; ?>pages/search.php?tag=<?php echo urlencode($tag); ?>'"
                        class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                    <?php echo escapeHtml($tag); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>