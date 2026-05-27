<?php

declare(strict_types=1);

use App\Core\{Csrf, View};

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios — Kokoro Pollo</title>
    <?= Csrf::meta() ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
</head>
<body class="page-usuarios bg-light">

<div class="container py-4">
    <h2 class="text-center mb-4">Gestión de Usuarios</h2>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a class="volver-btn-claro" href="/dashboard">← REGRESAR</a>
        <button class="btn btn-primary" onclick="abrirModal()">➕ Añadir Usuario</button>
    </div>

    <table class="table table-striped table-hover" id="tablaUsuarios">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="tituloModal">Añadir Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formUsuario">
                    <input type="hidden" id="idUsuario">

                    <div class="mb-3">
                        <label class="form-label">Nombre:</label>
                        <input type="text" class="form-control" id="nombre" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Usuario:</label>
                        <input type="text" class="form-control" id="usuario" required maxlength="60">
                    </div>
                    <div id="campoClave" class="mb-3">
                        <label class="form-label">Contraseña:</label>
                        <input type="password" class="form-control" id="clave">
                    </div>
                    <div id="opcionCambiarClave" class="form-check mb-3" style="display:none;">
                        <input class="form-check-input" type="checkbox" id="chkCambiarClave"
                               onchange="toggleCambioClave()">
                        <label class="form-check-label" for="chkCambiarClave">
                            Cambiar contraseña
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol:</label>
                        <select id="rol" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Administrador">Administrador</option>
                            <option value="Empleado">Empleado</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="btnGuardar">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
let modo  = 'crear';
const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));

function cargarUsuarios() {
    fetch('/usuarios/list')
        .then(r => r.json())
        .then(data => {
            let rows = '';
            data.forEach(u => {
                rows += `<tr>
                    <td>${u.id}</td>
                    <td>${u.nombre}</td>
                    <td>${u.usuario}</td>
                    <td>${u.rol}</td>
                    <td>
                        <button class="btn btn-sm btn-warning me-1"
                            onclick="editarUsuario(${u.id},'${u.nombre}','${u.usuario}','${u.rol}')">✏️</button>
                        <button class="btn btn-sm btn-danger"
                            onclick="eliminarUsuario(${u.id})">🗑️</button>
                    </td>
                </tr>`;
            });
            document.querySelector('#tablaUsuarios tbody').innerHTML = rows;
        });
}

function abrirModal() {
    modo = 'crear';
    document.getElementById('tituloModal').textContent = 'Añadir Usuario';
    document.getElementById('formUsuario').reset();
    document.getElementById('idUsuario').value = '';
    document.getElementById('campoClave').style.display = 'block';
    document.getElementById('opcionCambiarClave').style.display = 'none';
    modal.show();
}

function editarUsuario(id, nombre, usuario, rol) {
    modo = 'editar';
    document.getElementById('tituloModal').textContent = 'Editar Usuario';
    document.getElementById('idUsuario').value = id;
    document.getElementById('nombre').value   = nombre;
    document.getElementById('usuario').value  = usuario;
    document.getElementById('rol').value      = rol;
    document.getElementById('campoClave').style.display = 'none';
    document.getElementById('opcionCambiarClave').style.display = 'block';
    document.getElementById('chkCambiarClave').checked = false;
    document.getElementById('clave').value = '';
    modal.show();
}

function toggleCambioClave() {
    const mostrar = document.getElementById('chkCambiarClave').checked;
    document.getElementById('campoClave').style.display = mostrar ? 'block' : 'none';
}

document.getElementById('btnGuardar').addEventListener('click', () => {
    const id      = document.getElementById('idUsuario').value;
    const nombre  = document.getElementById('nombre').value.trim();
    const usuario = document.getElementById('usuario').value.trim();
    const clave   = document.getElementById('clave').value.trim();
    const rol     = document.getElementById('rol').value.trim();

    if (!nombre || !usuario || (modo === 'crear' && !clave) || !rol) {
        Swal.fire('Error', 'Por favor completa todos los campos obligatorios.', 'warning');
        return;
    }

    const endpoint = modo === 'crear' ? '/usuarios/create' : '/usuarios/update';
    const datos = { id, nombre, usuario, rol };
    if (modo === 'crear' || document.getElementById('chkCambiarClave').checked) {
        datos.clave = clave;
    }

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN,
        },
        body: JSON.stringify(datos),
    })
    .then(r => r.json())
    .then(r => {
        Swal.fire(r.status === 'ok' ? 'Éxito' : 'Error', r.mensaje, r.status === 'ok' ? 'success' : 'error');
        if (r.status === 'ok') { modal.hide(); cargarUsuarios(); }
    });
});

function eliminarUsuario(id) {
    Swal.fire({
        title: '¿Eliminar usuario?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('/usuarios/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN,
            },
            body: JSON.stringify({ id }),
        })
        .then(r => r.json())
        .then(r => {
            Swal.fire(r.status === 'ok' ? 'Eliminado' : 'Error', r.mensaje, r.status === 'ok' ? 'success' : 'error');
            if (r.status === 'ok') cargarUsuarios();
        });
    });
}

cargarUsuarios();
</script>

</body>
</html>
