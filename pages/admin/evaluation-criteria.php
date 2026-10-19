<?php
/**
 * Evaluation Criteria page (Admin only)
 * PHP 5.3 compatible
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/models.php';

$currentUser = requireAdminAccess();

$pageTitle = 'Değerlendirme Kriterleri';
$currentPath = 'pages/admin/evaluation-criteria.php';

$criteria = getEvaluationCriteria();

include __DIR__ . '/../../includes/header.php';
?>
<script>
function deleteCriterion(id) {
    if (!confirm('Bu kriter silinecek. Emin misiniz?')) return;
    fetch(APP_URL + 'api/evaluations.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'delete-criterion', id: id, csrf_token: CSRF_TOKEN})
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert('Hata: ' + data.error);
    });
}
</script>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Değerlendirme Kriterleri</h1>
        <button onclick="openCriterionModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">+ Kriter Ekle</button>
    </div>

    <!-- Criteria List -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
        <?php if (empty($criteria)): ?>
        <p class="text-gray-500 dark:text-gray-400">Henüz kriter eklenmemiş.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($criteria as $c): ?>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex items-center justify-between">
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white"><?php echo escapeHtml($c['name']); ?></h3>
                    <?php if (!empty($c['description'])): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?php echo escapeHtml($c['description']); ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-400 mt-1">Eklendi: <?php echo formatDateShort($c['created_at']); ?></p>
                </div>
                <button onclick="deleteCriterion('<?php echo $c['id']; ?>')" 
                        class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Sil</button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Criterion Modal -->
<div id="criterionModal" style="display:none;" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full">
        <h3 class="text-lg font-semibold mb-4">Kriter Ekle</h3>
        <form id="criterionForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div>
                <label class="block text-sm font-medium mb-1">Kriter Adı <span class="text-red-500">*</span></label>
                <input type="text" name="name" required 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Açıklama</label>
                <textarea name="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"></textarea>
            </div>
            
            <div class="flex gap-2 mt-6">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Kaydet</button>
                <button type="button" onclick="closeCriterionModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">İptal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCriterionModal() {
    document.getElementById('criterionModal').style.display = 'flex';
    document.getElementById('criterionForm').reset();
}

function closeCriterionModal() {
    document.getElementById('criterionModal').style.display = 'none';
}

document.getElementById('criterionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    formData.append('action', 'create-criterion');
    
    fetch(APP_URL + 'api/evaluations.php', {
        method: 'POST',
        body: formData
    }).then(function(r) {
        return r.json();
    }).then(function(data) {
        if (data.success) {
            closeCriterionModal();
            location.reload();
        } else {
            alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
        }
    }).catch(function(err) {
        alert('Bağlantı hatası: ' + err);
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
