<?php
declare(strict_types=1);
use App\Core\Csrf;

$pageTitle = 'Usuarios — Kokoro Pollo';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>';
require dirname(__DIR__) . '/partials/head.php';
?>
<body style="background-color:var(--rojo-deep);" class="min-h-screen py-8 pb-28">

<?php require dirname(__DIR__) . '/partials/toasts.php' ?>

<div class="max-w-4xl mx-auto px-4">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
        <a href="/dashboard"
           class="font-black text-lg px-6 py-3 rounded-xl btn-primary">
            ← REGRESAR
        </a>
        <h1 class="text-3xl font-black tracking-wide" style="color:var(--oro);">👥 Gestión de Usuarios</h1>
        <button onclick="abrirModal()"
                class="font-black text-lg px-6 py-3 rounded-xl btn-primary">
            ➕ Añadir Usuario
        </button>
    </div>

    <!-- Tabla -->
    <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color:var(--rojo-card);">
        <table class="w-full text-lg" id="tablaUsuarios">
            <thead>
                <tr style="background-color:var(--rojo-mid); color:var(--oro);">
                    <th class="px-5 py-4 text-left font-bold">ID</th>
                    <th class="px-5 py-4 text-left font-bold">Nombre</th>
                    <th class="px-5 py-4 text-left font-bold">Usuario</th>
                    <th class="px-5 py-4 text-left font-bold">Rol</th>
                    <th class="px-5 py-4 text-left font-bold">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-white"></tbody>
        </table>
    </div>

</div>

<!-- Modal -->
<div id="modal"
     class="hidden fixed inset-0 flex items-center justify-center z-50 p-4"
     style="background-color:rgba(0,0,0,.6);">
    <div class="rounded-2xl shadow-2xl w-full max-w-md p-8"
         style="background-color:var(--rojo-card);">

        <div class="flex justify-between items-center mb-6">
            <h2 id="tituloModal" class="text-2xl font-black" style="color:var(--oro);">Añadir Usuario</h2>
            <button onclick="cerrarModal()"
                    class="text-gray-400 hover:text-red-400 text-4xl font-black leading-none transition-colors">&times;</button>
        </div>

        <div class="space-y-4">
            <input type="hidden" id="idUsuario">

            <div>
                <label class="text-sm font-bold block mb-1" style="color:var(--oro);">Nombre</label>
                <input type="text" id="nombre" maxlength="100"
                       class="w-full text-xl px-4 py-3 rounded-xl input-dark">
            </div>

            <div>
                <label class="text-sm font-bold block mb-1" style="color:var(--oro);">Usuario</label>
                <input type="text" id="usuario" maxlength="60"
                       class="w-full text-xl px-4 py-3 rounded-xl input-dark">
            </div>

            <div id="campoClave">
                <label class="text-sm font-bold block mb-1" style="color:var(--oro);">Contraseña</label>
                <input type="password" id="clave"
                       class="w-full text-xl px-4 py-3 rounded-xl input-dark">
            </div>

            <div id="opcionCambiarClave" class="hidden flex items-center gap-3">
                <input type="checkbox" id="chkCambiarClave" onchange="toggleCambioClave()"
                       class="w-5 h-5 accent-yellow-500">
                <label for="chkCambiarClave" class="text-lg font-semibold cursor-pointer text-white">
                    Cambiar contraseña
                </label>
            </div>

            <div>
                <label class="text-sm font-bold block mb-1" style="color:var(--oro);">Rol</label>
                <select id="rol"
                        class="w-full text-xl px-4 py-3 rounded-xl input-dark"
                        style="color:var(--oro); appearance:none;">
                    <option value="" style="background-color:var(--rojo-deep);">Seleccione...</option>
                    <option value="Administrador" style="background-color:var(--rojo-deep);">Administrador</option>
                    <option value="Empleado"      style="background-color:var(--rojo-deep);">Empleado</option>
                </select>
            </div>
        </div>

        <div class="flex gap-4 mt-8">
            <button onclick="cerrarModal()"
                    class="flex-1 font-bold text-lg py-4 rounded-xl btn-secondary">
                Cancelar
            </button>
            <button id="btnGuardar"
                    class="flex-1 font-black text-lg py-4 rounded-xl btn-primary">
                Guardar
            </button>
        </div>

    </div>
</div>

<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
let modo = 'crear';

/* Escapa caracteres HTML para uso seguro dentro de atributos data-* */
function escAttr(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function abrirModal() {
    modo = 'crear';
    document.getElementById('tituloModal').textContent = 'Añadir Usuario';
    document.getElementById('idUsuario').value = '';
    document.getElementById('nombre').value    = '';
    document.getElementById('usuario').value   = '';
    document.getElementById('clave').value     = '';
    document.getElementById('rol').value       = '';
    document.getElementById('campoClave').style.display         = 'block';
    document.getElementById('opcionCambiarClave').style.display = 'none';
    document.getElementById('modal').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modal').classList.add('hidden');
}

document.getElementById('modal').addEventListener('click', function (e) {
    if (e.target === this) cerrarModal();
});

function editarUsuario(id, nombre, usuario, rol) {
    modo = 'editar';
    document.getElementById('tituloModal').textContent = 'Editar Usuario';
    document.getElementById('idUsuario').value = id;
    document.getElementById('nombre').value    = nombre;
    document.getElementById('usuario').value   = usuario;
    document.getElementById('rol').value       = rol;
    document.getElementById('clave').value     = '';
    document.getElementById('campoClave').style.display         = 'none';
    document.getElementById('opcionCambiarClave').style.display = 'flex';
    document.getElementById('chkCambiarClave').checked          = false;
    document.getElementById('modal').classList.remove('hidden');
}

/* Listener delegado — elimina el riesgo de XSS en onclick inline */
document.querySelector('#tablaUsuarios tbody').addEventListener('click', e => {
    const btn = e.target.closest('.btn-edit-user');
    if (btn) editarUsuario(btn.dataset.id, btn.dataset.nombre, btn.dataset.usuario, btn.dataset.rol);
});

function toggleCambioClave() {
    const mostrar = document.getElementById('chkCambiarClave').checked;
    document.getElementById('campoClave').style.display = mostrar ? 'block' : 'none';
}

function cargarUsuarios() {
    fetch('/usuarios/list')
        .then(r => r.json())
        .then(data => {
            const tbody = document.querySelector('#tablaUsuarios tbody');
            tbody.innerHTML = data.map(u => `
                <tr class="border-b tr-dark" style="border-color:var(--rojo-mid);">
                    <td class="px-5 py-4" style="color:#9ca3af;">${u.id}</td>
                    <td class="px-5 py-4 font-semibold">${escAttr(u.nombre)}</td>
                    <td class="px-5 py-4">${escAttr(u.usuario)}</td>
                    <td class="px-5 py-4">
                        <span class="px-3 py-1 rounded-full text-sm font-bold"
                              style="${u.rol === 'Administrador'
                                  ? 'background-color:#78350f; color:#fde68a;'
                                  : 'background-color:#1e3a5f; color:#bfdbfe;'}">
                            ${escAttr(u.rol)}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex gap-2 flex-wrap">
                            <button class="btn-edit-user font-bold px-4 py-2 rounded-lg text-sm btn-primary"
                                    data-id="${u.id}"
                                    data-nombre="${escAttr(u.nombre)}"
                                    data-usuario="${escAttr(u.usuario)}"
                                    data-rol="${escAttr(u.rol)}">
                                ✏️ Editar
                            </button>
                            <button onclick="eliminarUsuario(${parseInt(u.id)})"
                                    class="font-bold px-4 py-2 rounded-lg text-sm btn-danger">
                                🗑️ Eliminar
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        });
}

document.getElementById('btnGuardar').addEventListener('click', () => {
    const id      = document.getElementById('idUsuario').value;
    const nombre  = document.getElementById('nombre').value.trim();
    const usuario = document.getElementById('usuario').value.trim();
    const clave   = document.getElementById('clave').value.trim();
    const rol     = document.getElementById('rol').value;

    if (!nombre || !usuario || (modo === 'crear' && !clave) || !rol) {
        Swal.fire('Campos incompletos', 'Por favor completa todos los campos obligatorios.', 'warning');
        return;
    }

    const endpoint = modo === 'crear' ? '/usuarios/create' : '/usuarios/update';
    const datos    = { id, nombre, usuario, rol };
    if (modo === 'crear' || document.getElementById('chkCambiarClave').checked) {
        datos.clave = clave;
    }

    fetch(endpoint, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
        body:    JSON.stringify(datos),
    })
    .then(r => r.json())
    .then(r => {
        Swal.fire(r.status === 'ok' ? '✅ Éxito' : '❌ Error', r.mensaje,
                  r.status === 'ok' ? 'success' : 'error');
        if (r.status === 'ok') { cerrarModal(); cargarUsuarios(); }
    });
});

function eliminarUsuario(id) {
    Swal.fire({
        title: '¿Eliminar usuario?',
        text:  'Esta acción no se puede deshacer.',
        icon:  'warning',
        showCancelButton:    true,
        confirmButtonColor:  '#b91c1c',
        confirmButtonText:   'Sí, eliminar',
        cancelButtonText:    'Cancelar',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('/usuarios/delete', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body:    JSON.stringify({ id }),
        })
        .then(r => r.json())
        .then(r => {
            Swal.fire(r.status === 'ok' ? '✅ Eliminado' : '❌ Error', r.mensaje,
                      r.status === 'ok' ? 'success' : 'error');
            if (r.status === 'ok') cargarUsuarios();
        });
    });
}

cargarUsuarios();
</script>

</body>
</html>
