<?php
/**
 * Footer/Layout closing template
 */
require_once __DIR__ . '/config.php';

if (isLoggedIn()): ?>
            </main>
        </div>
    </div>
<?php else: ?>
    </main>
<?php endif; ?>

<!-- Custom JS -->
<script src="<?php echo APP_URL; ?>assets/js/app.js"></script>

<!-- Flash message handler -->
<?php $flash = getFlash(); if ($flash): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showToast('<?php echo escapeHtml($flash['message']); ?>', '<?php echo $flash['type']; ?>');
});
</script>
<?php endif; ?>
</body>
</html>