<?php
/**
 * My Evaluations page (Admin or Member)
 * Kullanıcının kendi değerlendirme raporlarını görür
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/models.php';

requireManagementAccess();

$currentUser = getEffectiveUser();
$pageTitle = 'Değerlendirme Raporlarım';
$currentPath = 'pages/my-evaluations.php';

$periods = getEvaluationPeriods();
$periodId = isset($_GET['period']) ? $_GET['period'] : '';
if (empty($periodId) && !empty($periods)) {
    $periodId = $periods[0]['id'];
}

$criteria = array();
$myEvaluations = array();
$myPeriodEvaluations = array();

if (!empty($periodId)) {
    $criteria = getEvaluationCriteria();
    // Sadece kendi değerlendirmelerini al
    $myEvaluations = getEvaluations(array('period_id' => $periodId, 'user_id' => $currentUser['id']));
    if (!empty($myEvaluations)) {
        $myPeriodEvaluations[$currentUser['id']] = $myEvaluations[0];
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<script>
function getRatingClass(rating) {
    if (rating === 'Üstün performans') return 'text-green-600 font-bold';
    if (rating === 'Beklentilerin üzerinde') return 'text-blue-600 font-bold';
    if (rating === 'Beklentileri karşılıyor') return 'text-yellow-600 font-bold';
    if (rating === 'Gelişime açık') return 'text-orange-600 font-bold';
    return 'text-red-600 font-bold';
}
</script>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Değerlendirme Raporlarım</h1>
        <a href="evaluations.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm hover:bg-gray-700">Admin Paneli</a>
    </div>

    <!-- Dönem Seçici -->
    <?php if (!empty($periods)): ?>
    <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Dönem Seçin</label>
        <select onchange="window.location.href=this.value" 
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            <?php foreach ($periods as $p): ?>
            <option value="my-evaluations.php?period=<?php echo $p['id']; ?>" 
                    <?php echo $periodId == $p['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['start_date']; ?> - <?php echo $p['end_date']; ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if (empty($periodId)): ?>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center">
            <p class="text-gray-500 dark:text-gray-400">Lütfen bir dönem seçin.</p>
        </div>
    <?php else: ?>
        <?php $myEval = isset($myPeriodEvaluations[$currentUser['id']]) ? $myPeriodEvaluations[$currentUser['id']] : null; ?>
        
        <?php if ($myEval): ?>
        <!-- Mevcut Değerlendirme -->
        <?php
        // Ağırlıklı puanı hesapla
        $weightedResult = calculateWeightedEvaluation($myEval['scores'], $criteria);
        $weightedAvg = $weightedResult['weighted_avg'];
        $rating = $weightedResult['rating'];
        ?>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-blue-50 dark:bg-blue-900/20">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <?php echo escapeHtml($periods[0]['name']); ?> Dönemi Değerlendirmesi
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-medium 
                            <?php 
                            if ($weightedAvg === null) {
                                echo 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                            } elseif ($weightedAvg >= 9) echo 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                            elseif ($weightedAvg >= 7) echo 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
                            elseif ($weightedAvg >= 5) echo 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                            elseif ($weightedAvg >= 3) echo 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
                            else echo 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                            ?>">
                            <?php echo $weightedAvg !== null ? $weightedAvg . '/10' : 'Henüz'; ?> - <?php echo escapeHtml($rating); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="p-6 space-y-6">
                <?php if (!empty($criteria)): ?>
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white mb-3">Kriter Puanları (0-10 arası)</h3>
                    <div class="grid gap-3">
                        <?php foreach ($criteria as $c): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                            <div>
                                <span class="text-gray-700 dark:text-gray-300 font-medium">
                                    <?php echo escapeHtml($c['name']); ?>
                                </span>
                                <?php if (!empty($c['weight'])): ?>
                                <div class="text-xs text-gray-400">Ağırlık: %<?php echo $c['weight']; ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="text-lg font-bold <?php 
                                $s = isset($myEval['scores'][$c['id']]) ? $myEval['scores'][$c['id']] : null;
                                if ($s === 10) echo 'text-green-600';
                                elseif ($s >= 7) echo 'text-blue-600';
                                elseif ($s >= 5) echo 'text-yellow-600';
                                elseif ($s >= 3) echo 'text-orange-600';
                                elseif ($s >= 0) echo 'text-red-600';
                                else echo 'text-gray-400';
                                ?>">
                                <?php echo $s !== null ? $s . '/10' : '-'; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($myEval['notes'])): ?>
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">Genel Notlar</h3>
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                        <?php echo escapeHtml($myEval['notes']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="text-xs text-gray-500 dark:text-gray-400 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p>Ağırlıklı puan: <span class="<?php echo getRatingClass($rating); ?>"><?php echo $weightedAvg !== null ? $weightedAvg . '/10' : '-'; ?></span> (<?php echo escapeHtml($rating); ?>)</p>
                    <p>Değerlendirme tarihi: <?php echo formatDateShort($myEval['evaluated_at'] ?? $myEval['created_at']); ?></p>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!--Henüz Değerlendirme Yok -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center">
            <p class="text-gray-500 dark:text-gray-400 mb-4">
                Bu dönem için henüz bir değerlendirme yapılmamış.
                <?php if (canViewManagement()): ?>
                <br><a href="evaluations.php" class="text-blue-600 hover:underline">Admin panelinden değerlendirilmeyi bekliyor olabilirsiniz.</a>
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
