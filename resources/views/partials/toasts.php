<?php
declare(strict_types=1);
use App\Core\Session;

$_toastExito = Session::getFlash('exito');
$_toastError = Session::getFlash('error');
?>
<div id="toast-container"
     class="fixed top-5 right-5 z-50 space-y-3 pointer-events-none"
     style="width:min(90vw,360px);">

    <?php if ($_toastExito): ?>
        <div class="toast-item flex items-start gap-3 px-5 py-4 rounded-2xl shadow-2xl font-semibold text-base pointer-events-auto"
             style="background:#134e2a; color:#4ade80; border:1px solid #166534;">
            <span class="text-xl flex-shrink-0">✅</span>
            <span><?= htmlspecialchars($_toastExito, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <?php if ($_toastError): ?>
        <div class="toast-item flex items-start gap-3 px-5 py-4 rounded-2xl shadow-2xl font-semibold text-base pointer-events-auto"
             style="background:#4a0e0e; color:#fca5a5; border:1px solid #b91c1c;">
            <span class="text-xl flex-shrink-0">⚠️</span>
            <span><?= htmlspecialchars($_toastError, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

</div>
<script>
document.querySelectorAll('.toast-item').forEach(function(el) {
    setTimeout(function() {
        el.style.transition = 'opacity .5s, transform .5s';
        el.style.opacity    = '0';
        el.style.transform  = 'translateX(40px)';
        setTimeout(function() { el.remove(); }, 500);
    }, 4000);
});
</script>
