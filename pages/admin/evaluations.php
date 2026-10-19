<?php
/**
 * Evaluations page (Admin only)
 * PHP 5.3 compatible
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/models.php';

$currentUser = requireAdminAccess();

$pageTitle = 'Personel Değerlendirme';
$currentPath = 'pages/admin/evaluations.php';

$periods = getEvaluationPeriods();
$periodId = isset($_GET['period']) ? $_GET['period'] : '';
if (empty($periodId) && !empty($periods)) {
    $periodId = $periods[0]['id'];
}

$evaluations = array();
$criteria = array();
$employeeList = array();
$periodEvaluations = array();

if (!empty($periodId)) {
    $evaluations = getEvaluations(array('period_id' => $periodId));
    $criteria = getEvaluationCriteria();
    // Seçilen dönem için çalışan listesi oluştur
    $periodEvaluations = array();
    foreach ($evaluations as $e) {
        $periodEvaluations[$e['user_id']] = $e;
    }
    // Aktif çalışanları al
    $users = getAllUsers(true);
    foreach ($users as $u) {
        $eval = isset($periodEvaluations[$u['id']]) ? $periodEvaluations[$u['id']] : null;
        // Ağırlıklı puanı hesapla (0-10 ölçeği)
        $weightedAvg = null;
        $rating = '-';
        if ($eval && !empty($eval['scores']) && !empty($criteria)) {
            $scores = $eval['scores'];
            $result = calculateWeightedEvaluation($scores, $criteria);
            $weightedAvg = $result['weighted_avg'];
            $rating = $result['rating'];
        }
        $employeeList[] = array(
            'user' => $u,
            'evaluation' => $eval,
            'weighted_avg' => $weightedAvg,
            'rating' => $rating
        );
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<script>
function deletePeriod(id) {
    if (!confirm('Bu dönem silinecek. Emin misiniz?')) return;
    fetch(APP_URL + 'api/evaluations.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'delete-period', id: id, csrf_token: CSRF_TOKEN})
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert('Hata: ' + data.error);
    });
}

function deleteEvaluation(id) {
    if (!confirm('Bu değerlendirmeyi silmek istediğinize emin misiniz?')) return;
    fetch(APP_URL + 'api/evaluations.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'delete-evaluation', id: id, csrf_token: CSRF_TOKEN})
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert('Hata: ' + data.error);
    });
}
</script>

<script>
function openPeriodModal() {
    document.getElementById('periodModal').style.display = 'flex';
    document.getElementById('periodForm').reset();
}

function closePeriodModal() {
    document.getElementById('periodModal').style.display = 'none';
}
</script>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Personel Değerlendirme</h1>
        <div class="flex gap-2">
            <?php if (!empty($periodId) && !empty($employeeList)): ?>
            <button onclick="window.print()" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">📄 PDF Rapor</button>
            <?php endif; ?>
            <a href="<?php echo APP_URL; ?>pages/admin/evaluation-criteria.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm hover:bg-gray-700">Kriter Yönetimi</a>
            <button onclick="openPeriodModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">+ Dönem Ekle</button>
        </div>
    </div>
            <!-- Dönem Seçici -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Dönem Seçin</label>
                <select onchange="window.location.href=this.value" 
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                    <option value="">-- Bir dönem seçin --</option>
                    <?php foreach ($periods as $p): ?>
                    <option value="evaluations.php?period=<?php echo $p['id']; ?>" 
                            <?php echo $periodId == $p['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['start_date']; ?> - <?php echo $p['end_date']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!empty($periodId)): ?>
            <!-- Çalışan Listesi -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Personel Listesi</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Toplam <?php echo count($employeeList); ?> çalışan
                    </span>
                </div>
                <?php if (empty($employeeList)): ?>
                    <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                        Çalışan bulunamadı.
                    </div>
                <?php else: ?>
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left text-sm text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3 font-medium">Personel</th>
                            <th class="px-6 py-3 font-medium text-center">Puan</th>
                            <th class="px-6 py-3 font-medium text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($employeeList as $e): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <?php echo getUserAvatar($e['user']['name'], 'w-8 h-8'); ?>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            <?php echo escapeHtml($e['user']['name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <?php echo escapeHtml($e['user']['email']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($e['evaluation']): ?>
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-medium 
                                            <?php 
                                            $avg = $e['weighted_avg'];
                                            if ($avg === null) {
                                                echo 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                            } elseif ($avg >= 9) echo 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                            elseif ($avg >= 7) echo 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
                                            elseif ($avg >= 5) echo 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                                            elseif ($avg >= 3) echo 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
                                            else echo 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                            ?>">
                                            <?php echo $avg !== null ? $avg . '/10' : '-'; ?> - <?php echo escapeHtml($e['rating']); ?>
                                        </span>
                                        <?php if ($e['evaluation'] && !empty($e['evaluation']['scores'])): ?>
                                        <span class="text-xs text-gray-400">
                                            Ağırlıklı ortalama
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">Henüz puanlanmamış</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php if ($e['evaluation']): ?>
                                    <a href="evaluation-detail.php?period=<?php echo $periodId; ?>&user=<?php echo $e['user']['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800 text-sm">Düzenle</a>
                                <?php else: ?>
                                    <a href="evaluation-detail.php?period=<?php echo $periodId; ?>&user=<?php echo $e['user']['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800 text-sm">Değerlendir</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Period Modal -->
    <div id="periodModal" style="display:none;" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full">
        <h3 class="text-lg font-semibold mb-4">Dönem Ekle</h3>
        <form id="periodForm" class="space-y-4" action="<?php echo APP_URL; ?>api/evaluations.php" method="POST">
            <input type="hidden" name="action" value="create-period">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div>
                <label class="block text-sm font-medium mb-1">Dönem Adı</label>
                <input type="text" name="name" required 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Başlangıç Tarihi</label>
                    <input type="date" name="start_date" required 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Bitiş Tarihi</label>
                    <input type="date" name="end_date" required 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                </div>
            </div>
            
            <div class="flex gap-2 mt-6">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Kaydet</button>
                <button type="button" onclick="closePeriodModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">İptal</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('periodForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    
    fetch(APP_URL + 'api/evaluations.php', {
        method: 'POST',
        body: formData
    }).then(function(r) {
        return r.json();
    }).then(function(data) {
        if (data.success) {
            closePeriodModal();
            location.reload();
        } else {
            alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
        }
    }).catch(function(err) {
        alert('Bağlantı hatası: ' + err);
    });
});
</script>

<?php
// Yazdırma için seçili dönem bilgisi
$selectedPeriod = null;
foreach ($periods as $p) {
    if ($p['id'] === $periodId) { $selectedPeriod = $p; break; }
}
?>

<!-- Print-only Performans Değerlendirme Raporu -->
<style>
    @media print {
        body * { visibility: hidden; }
        .eval-print-report, .eval-print-report * { visibility: visible; }
        .eval-print-report {
            position: absolute; left: 0; top: 0; width: 100%;
            margin: 0; padding: 20px; background: white;
            display: block !important; font-size: 10pt; color: #000;
        }
        .eval-print-report h1 { font-size: 16pt; margin-bottom: 4px; color: #000; }
        .eval-print-report h2 { font-size: 12pt; margin: 12px 0 6px 0; border-bottom: 1px solid #000; padding-bottom: 3px; color: #000; }
        .eval-print-report h3 { font-size: 11pt; margin: 8px 0 4px 0; color: #000; }
        .eval-print-report table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .eval-print-report th, .eval-print-report td { border: 1px solid #999; padding: 5px 8px; text-align: left; color: #000; }
        .eval-print-report th { background: #f0f0f0 !important; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .eval-print-report .emp-card { border: 1px solid #999; padding: 10px; margin-bottom: 10px; page-break-inside: avoid; }
        .eval-print-report .text-center { text-align: center; }
        .eval-print-report .text-right { text-align: right; }
        .eval-print-report .meta { font-size: 8pt; color: #555; }
        @page { margin: 1cm; }
    }
    .eval-print-report { display: none; }
</style>

<div class="eval-print-report">
    <h1>Personel Değerlendirme Raporu</h1>
    <?php if ($selectedPeriod): ?>
    <p class="meta">
        Dönem: <strong><?php echo escapeHtml($selectedPeriod['name']); ?></strong>
        (<?php echo escapeHtml($selectedPeriod['start_date']); ?> - <?php echo escapeHtml($selectedPeriod['end_date']); ?>)
        | Rapor Tarihi: <?php echo date('d.m.Y H:i'); ?>
    </p>
    <?php endif; ?>

    <!-- Özet Tablo -->
    <h2>Genel Özet</h2>
    <table>
        <thead>
            <tr>
                <th>Personel</th>
                <th>E-posta</th>
                <th class="text-center">Ağırlıklı Puan</th>
                <th class="text-center">Değerlendirme</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employeeList as $e): ?>
            <tr>
                <td><?php echo escapeHtml($e['user']['name']); ?></td>
                <td><?php echo escapeHtml($e['user']['email']); ?></td>
                <td class="text-center"><?php echo $e['weighted_avg'] !== null ? $e['weighted_avg'] . '/10' : '-'; ?></td>
                <td class="text-center"><?php echo escapeHtml($e['rating']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Kriter Ağırlıkları -->
    <?php if (!empty($criteria)): ?>
    <h2>Değerlendirme Kriterleri ve Ağırlıkları</h2>
    <table>
        <thead>
            <tr>
                <th>Kriter</th>
                <th class="text-center">Ağırlık</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($criteria as $c): ?>
            <tr>
                <td><?php echo escapeHtml($c['name']); ?></td>
                <td class="text-center"><?php echo isset($c['weight']) ? '%' . $c['weight'] : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Kişi Bazlı Detay -->
    <h2>Kişi Bazlı Detaylı Değerlendirme</h2>
    <?php foreach ($employeeList as $e): ?>
        <?php if (empty($e['evaluation'])) continue; ?>
        <div class="emp-card">
            <h3><?php echo escapeHtml($e['user']['name']); ?> 
                — <?php echo $e['weighted_avg'] !== null ? $e['weighted_avg'] . '/10' : '-'; ?> 
                (<?php echo escapeHtml($e['rating']); ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Kriter</th>
                        <th class="text-center">Ağırlık</th>
                        <th class="text-center">Puan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($criteria as $c): ?>
                    <?php $score = isset($e['evaluation']['scores'][$c['id']]) ? $e['evaluation']['scores'][$c['id']] : null; ?>
                    <tr>
                        <td><?php echo escapeHtml($c['name']); ?></td>
                        <td class="text-center"><?php echo isset($c['weight']) ? '%' . $c['weight'] : '-'; ?></td>
                        <td class="text-center"><?php echo $score !== null ? $score . '/10' : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!empty($e['evaluation']['notes'])): ?>
            <p><strong>Notlar:</strong> <?php echo escapeHtml($e['evaluation']['notes']); ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
