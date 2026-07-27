<?php
/**
 * Read-only daily notes summary
 * PHP 5.3 compatible
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';

$currentUser = requireManagementAccess();
$isAdmin = isAdmin($currentUser);
$pageTitle = 'Günlük Özet';
$currentPath = 'pages/daily-summary.php';

$startDate = isset($_GET['startDate']) && $_GET['startDate'] ? $_GET['startDate'] : date('Y-m-01');
$endDate = isset($_GET['endDate']) && $_GET['endDate'] ? $_GET['endDate'] : date('Y-m-d');
$notesByDate = array();
$reportError = '';

if ($startDate > $endDate) {
    $reportError = 'Başlangıç tarihi bitiş tarihinden sonra olamaz.';
} else {
    $notes = getDailyNotes(array('start_date' => $startDate, 'end_date' => $endDate));
    foreach ($notes as $note) {
        $dateKey = substr($note['date'], 0, 10);
        if (!isset($notesByDate[$dateKey])) $notesByDate[$dateKey] = array();
        $notesByDate[$dateKey][] = $note;
    }
}

$copyText = '';
foreach ($notesByDate as $dateKey => $dayNotes) {
    $copyText .= formatDateShort($dateKey) . "\n";
    foreach ($dayNotes as $note) {
        $copyText .= '- ' . $note['name'] . ': ' . $note['content'] . "\n";
    }
    $copyText .= "\n";
}

include __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Günlük Özet</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Günlük işleri tarih bazında görüntüleyin.</p>
    </div>

    <form method="GET" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="startDate" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Başlangıç</label>
            <input type="date" id="startDate" name="startDate" value="<?php echo escapeHtml($startDate); ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
        </div>
        <div>
            <label for="endDate" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş</label>
            <input type="date" id="endDate" name="endDate" value="<?php echo escapeHtml($endDate); ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Özeti Görüntüle</button>
    </form>

    <?php if ($reportError): ?>
    <div class="p-3 rounded-lg border border-red-300 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-950/30 dark:text-red-300">
        <?php echo escapeHtml($reportError); ?>
    </div>
    <?php elseif (empty($notesByDate)): ?>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center text-sm text-gray-500 dark:text-gray-400">
        Seçilen tarih aralığında günlük not bulunamadı.
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden" id="daily-summary-content">
        <div class="flex items-center justify-between gap-3 px-5 py-3 bg-slate-100 dark:bg-slate-700 border-b border-slate-200 dark:border-slate-600">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-white">Günlük İş Özeti</h2>
                <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5"><?php echo escapeHtml(formatDateShort($startDate)); ?> — <?php echo escapeHtml(formatDateShort($endDate)); ?></p>
            </div>
            <button type="button" id="copyDailySummary" class="px-3 py-2 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700">Tümünü Kopyala</button>
        </div>
        <div class="p-5 space-y-5">
            <?php foreach ($notesByDate as $dateKey => $dayNotes): ?>
            <section>
                <div class="flex items-center gap-3 mb-2">
                    <h3 class="font-semibold text-sm text-blue-700 dark:text-blue-300 whitespace-nowrap"><?php echo escapeHtml(formatDateShort($dateKey)); ?></h3>
                    <div class="h-px flex-1 bg-slate-200 dark:bg-slate-600"></div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap"><?php echo count($dayNotes); ?> iş</span>
                </div>
                <div class="space-y-2">
                    <?php foreach ($dayNotes as $note): ?>
                    <article class="pl-3 border-l-2 border-slate-200 dark:border-slate-600">
                        <div class="flex items-center gap-2 mb-1">
                            <?php echo getUserAvatar($note['name'], 'w-6 h-6'); ?>
                            <span class="font-medium text-sm"><?php echo escapeHtml($note['name']); ?></span>
                        </div>
                        <p class="text-sm whitespace-pre-wrap text-slate-700 dark:text-slate-200"><?php echo escapeHtml($note['content']); ?></p>
                        <?php echo getTagsHtml($note['tags']); ?>
                        <?php echo getJiraLinkHtml($note['jira_link']); ?>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$reportError && !empty($notesByDate)): ?>
<script>
document.getElementById('copyDailySummary').addEventListener('click', function() {
    var button = this;
    var text = <?php echo json_encode($copyText); ?>;
    var copied = function() {
        button.textContent = 'Kopyalandı';
        setTimeout(function() { button.textContent = 'Tümünü Kopyala'; }, 1800);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(copied);
    } else {
        var area = document.createElement('textarea');
        area.value = text;
        document.body.appendChild(area);
        area.select();
        document.execCommand('copy');
        area.remove();
        copied();
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
