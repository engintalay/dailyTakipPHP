<?php
/**
 * Evaluation Detail page (Admin only)
 * PHP 5.3 compatible
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/models.php';

$currentUser = requireAdminAccess();

$pageTitle = 'Değerlendirme Detay';
$currentPath = 'pages/admin/evaluation-detail.php';

// Get period and user
$periodId = isset($_GET['period']) ? $_GET['period'] : '';
$userId = isset($_GET['user']) ? $_GET['user'] : '';

$period = $periodId ? getEvaluationPeriodById($periodId) : null;
$user = $userId ? getUserById($userId) : null;

// Get existing evaluation if exists
$evaluation = null;
if ($periodId && $userId) {
    $evaluations = getEvaluations(array('period_id' => $periodId, 'user_id' => $userId));
    if (!empty($evaluations)) {
        $evaluation = $evaluations[0];
    }
}

$criteria = getEvaluationCriteria();

// Default scores from criteria (0-10 arası)
$scores = array();
if (!empty($criteria) && empty($evaluation)) {
    foreach ($criteria as $c) {
        $scores[$c['id']] = 5; // Default value (5/10 - Beklentileri karşılıyor)
    }
} elseif (!empty($evaluation) && is_array($evaluation['scores'])) {
    $scores = $evaluation['scores'];
}

// Ağırlıklı puanı hesapla
$weightedResult = calculateWeightedEvaluation($scores, $criteria);
$weightedAvg = $weightedResult['weighted_avg'];
$rating = $weightedResult['rating'];

include __DIR__ . '/../../includes/header.php';
?>
<script>
function updatePreview() {
    var scores = {};
    document.querySelectorAll('input[type="range"]').forEach(function(range) {
        scores[range.name.replace('scores[', '').replace(']', '')] = parseInt(range.value);
    });
    
    fetch(APP_URL + 'api/evaluations.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'calculate-weighted',
            scores: scores,
            csrf_token: CSRF_TOKEN
        })
    }).then(r => r.json()).then(data => {
        if (data.success && data.result) {
            var resultDiv = document.getElementById('weightedPreview');
            resultDiv.innerHTML = 
                '<span class="text-sm font-semibold">' + (data.result.weighted_avg || 0) + '/10</span> - ' +
                '<span class="text-sm ' + getRatingClass(data.result.rating) + '">' + data.result.rating + '</span>';
        }
    });
}

function getRatingClass(rating) {
    if (rating === 'Üstün performans') return 'text-green-600';
    if (rating === 'Beklentilerin üzerinde') return 'text-blue-600';
    if (rating === 'Beklentileri karşılıyor') return 'text-yellow-600';
    if (rating === 'Gelişime açık') return 'text-orange-600';
    return 'text-red-600';
}

function handleFormSubmit(e) {
    // Form submit'i engelle, fetch ile gönder
    e.preventDefault();
    
    var formData = new FormData(e.target);
    var scores = {};
    
    // Puanları topla
    e.target.querySelectorAll('input[name^="scores["]').forEach(function(input) {
        var name = input.name.replace('scores[', '').replace(']', '');
        scores[name] = parseInt(input.value);
    });
    
    formData.append('scores', JSON.stringify(scores));
    
    fetch(APP_URL + 'api/evaluations.php', {
        method: 'POST',
        body: formData
    }).then(function(r) {
        return r.json();
    }).then(function(data) {
        if (data.success) {
            location.href = 'evaluations.php?period=<?php echo $periodId; ?>';
        } else {
            alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
        }
    }).catch(function(err) {
        alert('Bağlantı hatası: ' + err);
    });
    
    return false;
}
</script>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">
            <?php if ($evaluation): ?>
                Değerlendirmeyi Düzenle
            <?php else: ?>
                Değerlendirme Yap
            <?php endif; ?>
        </h1>
        <a href="evaluations.php?period=<?php echo $periodId; ?>" 
           class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm hover:bg-gray-700">Geri Dön</a>
    </div>

    <!-- Selected User and Period Info -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <?php echo getUserAvatar($user ? $user['name'] : '-', 'w-12 h-12'); ?>
                <div>
                    <div class="text-lg font-medium text-gray-900 dark:text-white">
                        <?php echo $user ? escapeHtml($user['name']) : 'Kullanıcı Seçin'; ?>
                    </div>
                    <?php if ($user): ?>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <?php echo escapeHtml($user['email']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($period): ?>
            <div class="text-right">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                    Dönem: <?php echo escapeHtml($period['name']); ?>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    <?php echo escapeHtml($period['start_date']); ?> - <?php echo escapeHtml($period['end_date']); ?>
                </div>
                <?php if ($weightedAvg !== null): ?>
                <div class="mt-2 inline-block px-2 py-1 rounded bg-gray-100 dark:bg-gray-700">
                    <span class="text-xs">Ağırlıklı Puan:</span>
                    <span class="font-bold <?php 
                        if ($weightedAvg >= 9) echo 'text-green-600';
                        elseif ($weightedAvg >= 7) echo 'text-blue-600';
                        elseif ($weightedAvg >= 5) echo 'text-yellow-600';
                        elseif ($weightedAvg >= 3) echo 'text-orange-600';
                        else echo 'text-red-600';
                    ?>"><?php echo $weightedAvg; ?>/10</span>
                    <span class="text-xs ml-1">- <?php echo escapeHtml($rating); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Evaluation Form -->
    <?php if ($periodId && $userId && !empty($criteria)): ?>
    <form method="POST" action="<?php echo APP_URL; ?>api/evaluations.php" class="space-y-4" onsubmit="return handleFormSubmit(event);">
        <input type="hidden" name="action" value="<?php echo $evaluation ? 'update-evaluation' : 'create-evaluation'; ?>">
        <input type="hidden" name="period_id" value="<?php echo $periodId; ?>">
        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
        <?php if ($evaluation): ?>
        <input type="hidden" name="id" value="<?php echo $evaluation['id']; ?>">
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        
        <!-- Scores -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 space-y-4">
            <h2 class="text-lg font-semibold">Kriter Puanları (0-10 arası)</h2>
            <p class="text-sm text-gray-500">Puan aralığı: 0-2 (Çok yetersiz), 3-4 (Gelişime açık), 5-6 (Beklentileri karşılıyor), 7-8 (Beklentilerin üzerinde), 9-10 (Üstün performans)</p>
            
            <?php foreach ($criteria as $c): ?>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-start gap-4">
                    <div class="flex-1">
                        <label class="font-medium text-gray-900 dark:text-white">
                            <?php echo escapeHtml($c['name']); ?>
                            <?php if (!empty($c['description'])): ?>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?php echo escapeHtml($c['description']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($c['weight'])): ?>
                            <span class="inline-block ml-2 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-500">
                                Ağırlık: %<?php echo $c['weight']; ?>
                            </span>
                            <?php endif; ?>
                        </label>
                    </div>
                    <div class="flex items-center gap-2 min-w-[120px]">
                        <input type="range" name="scores[<?php echo $c['id']; ?>]" 
                               min="0" max="10" step="1"
                               value="<?php echo isset($scores[$c['id']]) ? $scores[$c['id']] : 5; ?>"
                               class="w-32 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                               oninput="this.nextElementSibling.value = this.value; updatePreview();"
                               onchange="updatePreview();">
                        <output class="w-10 text-center font-bold text-blue-600">
                            <?php echo isset($scores[$c['id']]) ? $scores[$c['id']] : 5; ?>
                        </output>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Ağırlıklı Puan Önizlemesi -->
            <?php if (!empty($criteria)): ?>
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Ağırlıklı Puan:</span>
                    <div id="weightedPreview" class="text-right">
                        <span class="font-bold text-gray-900 dark:text-white">
                            <?php echo $weightedAvg !== null ? $weightedAvg . '/10' : '-'; ?>
                        </span>
                        <span class="ml-2 text-sm <?php 
                            if ($rating === 'Üstün performans') echo 'text-green-600';
                            elseif ($rating === 'Beklentilerin üzerinde') echo 'text-blue-600';
                            elseif ($rating === 'Beklentileri karşılıyor') echo 'text-yellow-600';
                            elseif ($rating === 'Gelişime açık') echo 'text-orange-600';
                            else echo 'text-red-600';
                        ?>"><?php echo escapeHtml($rating); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Notes -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
            <label class="block text-sm font-medium mb-2">Genel Notlar</label>
            <textarea name="notes" rows="4"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                      placeholder="Genel değerlendirmeler, öneriler, gelişim alanları..."><?php echo $evaluation ? escapeHtml($evaluation['notes']) : ''; ?></textarea>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                <?php echo $evaluation ? 'Güncelle' : 'Kaydet'; ?>
            </button>
            <a href="evaluations.php?period=<?php echo $periodId; ?>" 
               class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm hover:bg-gray-700">
                İptal
            </a>
        </div>
    </form>
    <?php else: ?>
    <!-- User/Period not selected -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 text-center">
        <p class="text-gray-500 dark:text-gray-400">
            Lütfen bir dönem ve kullanıcı seçin.
        </p>
        <a href="evaluations.php" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Dönem Seç
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
// Update output when range changes
document.querySelectorAll('input[type="range"]').forEach(function(range) {
    range.addEventListener('input', function() {
        this.nextElementSibling.value = this.value;
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
