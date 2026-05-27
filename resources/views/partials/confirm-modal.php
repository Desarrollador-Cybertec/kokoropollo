<div id="confirmModal"
     class="hidden fixed inset-0 flex items-center justify-center z-50 p-4"
     style="background-color:rgba(0,0,0,0.75);">
    <div class="w-full max-w-sm rounded-2xl shadow-2xl p-8 text-center"
         style="background-color:var(--rojo-card); border:1px solid var(--rojo-mid);">
        <div class="text-5xl mb-4">⚠️</div>
        <p id="confirmMsg" class="text-xl font-bold mb-8 text-white"></p>
        <div class="flex gap-4">
            <button id="confirmCancel"
                    class="flex-1 font-bold text-lg py-3 rounded-xl btn-secondary">
                Cancelar
            </button>
            <button id="confirmOk"
                    class="flex-1 font-black text-lg py-3 rounded-xl btn-danger">
                Eliminar
            </button>
        </div>
    </div>
</div>
<script>
(function () {
    const modal     = document.getElementById('confirmModal');
    const msgEl     = document.getElementById('confirmMsg');
    const btnOk     = document.getElementById('confirmOk');
    const btnCancel = document.getElementById('confirmCancel');
    let _cb = null;

    window.confirmar = function (texto, callback, textoBtn) {
        msgEl.textContent    = texto;
        btnOk.textContent    = textoBtn ?? 'Eliminar';
        _cb = callback;
        modal.classList.remove('hidden');
    };

    btnOk.addEventListener('click', function () {
        modal.classList.add('hidden');
        if (_cb) _cb();
        _cb = null;
    });

    function cerrar() { modal.classList.add('hidden'); _cb = null; }
    btnCancel.addEventListener('click', cerrar);
    modal.addEventListener('click', function (e) { if (e.target === modal) cerrar(); });
})();
</script>
