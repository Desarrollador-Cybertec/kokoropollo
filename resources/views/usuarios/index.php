<?php
declare(strict_types=1);
use App\Core\Csrf;
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios — Kokoro Pollo</title>
    <?= Csrf::meta() ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: { extend: { colors: {
            rojo:  { 950:'#1a0505', 900:'#2b1a1a', 800:'#3b0a0a', 700:'#3c1f1f', 600:'#4a0e0e', 500:'#5a1a1a' },
            oro:   { DEFAULT:'#d4af37', claro:'#e6c857', oscuro:'#3b0a0a' }
        }}}
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
</head>
<body class="bg-gray-100 min-h-screen py-8">

<div class="max-w-4xl mx-auto px-4">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
        <a href="/dashboard"
           class="bg-yellow-400 hover:bg-yellow-300 text-black font-black text-lg px-6 py-3 rounded-xl transition-colors">
            ← REGRESAR
        </a>
        <h1 class="text-rojo-800 text-3xl font-black tracking-wide">👥 Gestión de Usuarios</h1>
        <button onclick="abrirModal()"
                class="bg-rojo-800 hover:bg-rojo-600 text-oro font-black text-lg px-6 py-3 rounded-xl transition-colors">
            ➕ Añadir Usuario
        </button>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <table class="w-full text-lg" id="tablaUsuarios">
            <thead>
                <tr class="bg-rojo-800 text-oro font-bold text-left">
                    <th class="px-5 py-4">ID</th>
                    <th class="px-5 py-4">Nombre</th>
                    <th class="px-5 py-4">Usuario</th>
                    <th class="px-5 py-4">Rol</th>
                    <th class="px-5 py-4">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-800"></tbody>
        </table>
    </div>

</div>

<!-- Modal -->
<div id="modal"
     class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">

        <div class="flex justify-between items-center mb-6">
            <h2 id="tituloModal" class="text-rojo-800 text-2xl font-black">Añadir Usuario</h2>
            <button onclick="cerrarModal()"
                    class="text-gray-400 hover:text-red-500 text-4xl font-black leading-none">&times;</button>
        </div>

        <div class="space-y-4">
            <input type="hidden" id="idUsuario">

            <div>
                <label class="text-gray-700 text-lg font-bold block mb-1">Nombre</label>
                <input type="text" id="nombre" maxlength="100"
                       class="w-full border-2 border-gray-300 focus:border-yellow-500 text-rojo-800 text-xl px-4 py-3 rounded-xl outline-none">
            </div>

            <div>
                <label class="text-gray-700 text-lg font-bold block mb-1">Usuario</label>
                <input type="text" id="usuario" maxlength="60"
                       class="w-full border-2 border-gray-300 focus:border-yellow-500 text-rojo-800 text-xl px-4 py-3 rounded-xl outline-none">
            </div>

            <div id="campoClave">
                <label class="text-gray-700 text-lg font-bold block mb-1">Contraseña</label>
                <input type="password" id="clave"
                       class="w-full border-2 border-gray-300 focus:border-yellow-500 text-rojo-800 text-xl px-4 py-3 rounded-xl outline-none">
            </div>

            <div id="opcionCambiarClave" class="hidden flex items-center gap-3">
                <input type="checkbox" id="chkCambiarClave" onchange="toggleCambioClave()"
                       class="w-5 h-5 accent-yellow-500">
                <label for="chkCambiarClave" class="text-gray-700 text-lg font-semibold cursor-pointer">
                    Cambiar contraseña
                </label>
            </div>

            <div>
                <label class="text-gray-700 text-lg font-bold block mb-1">Rol</label>
                <select id="rol"
                        class="w-full border-2 border-gray-300 focus:border-yellow-500 text-rojo-800 text-xl px-4 py-3 rounded-xl outline-none bg-white">
                    <option value="">Seleccione...</option>
                    <option value="Administrador">Administrador</option>
                    <option value="Empleado">Empleado</option>
                </select>
            </div>
        </div>

        <div class="flex gap-4 mt-8">
            <button onclick="cerrarModal()"
                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-lg py-4 rounded-xl transition-colors">
                Cancelar
            </button>
            <button id="btnGuardar"
                    class="flex-1 bg-rojo-800 hover:bg-rojo-600 text-oro font-black text-lg py-4 rounded-xl transition-colors">
                Guardar
            </button>
        </div>

    </div>
</div>

<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
let modo = 'crear';

function abrirModal() {
    modo = 'crear';
    document.getElementById('tituloModal').textContent = 'Añadir Usuario';
    document.getElementById('idUsuario').value = '';
    document.getElementById('nombre').value   = '';
    document.getElementById('usuario').value  = '';
    document.getElementById('clave').value    = '';
    document.getElementById('rol').value      = '';
    document.getElementById('campoClave').style.display      = 'block';
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
    document.getElementById('nombre').value   = nombre;
    document.getElementById('usuario').value  = usuario;
    document.getElementById('rol').value      = rol;
    document.getElementById('clave').value    = '';
    document.getElementById('campoClave').style.display      = 'none';
    document.getElementById('opcionCambiarClave').style.display = 'flex';
    document.getElementById('chkCambiarClave').checked = false;
    document.getElementById('modal').classList.remove('hidden');
}

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
                <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-4 text-gray-400">${u.id}</td>
                    <td class="px-5 py-4 font-semibold">${u.nombre}</td>
                    <td class="px-5 py-4">${u.usuario}</td>
                    <td class="px-5 py-4">
                        <span class="px-3 py-1 rounded-full text-base font-bold ${u.rol === 'Administrador' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'}">
                            ${u.rol}
                        </span>
                    </td>
                    <td class="px-5 py-4 flex gap-2">
                        <button onclick="editarUsuario(${u.id},'${u.nombre}','${u.usuario}','${u.rol}')"
                                class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold px-4 py-2 rounded-lg transition-colors text-base">
                            ✏️ Editar
                        </button>
                        <button onclick="eliminarUsuario(${u.id})"
                                class="bg-red-600 hover:bg-red-500 text-white font-bold px-4 py-2 rounded-lg transition-colors text-base">
                            🗑️ Eliminar
                        </button>
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
    const datos = { id, nombre, usuario, rol };
    if (modo === 'crear' || document.getElementById('chkCambiarClave').checked) {
        datos.clave = clave;
    }

    fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
        body: JSON.stringify(datos),
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
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#b91c1c',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('/usuarios/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify({ id }),
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
