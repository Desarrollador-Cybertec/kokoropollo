<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Logger, Request, Response, Session, View};
use App\Enums\Rol;
use App\Middleware\RoleMiddleware;
use App\Models\Usuario;

final class UsuariosController
{
    public function index(Request $request): void
    {
        RoleMiddleware::require(Rol::Administrador);
        View::render('usuarios/index');
    }

    public function list(Request $request): void
    {
        RoleMiddleware::require(Rol::Administrador);
        Response::json((new Usuario())->all());
    }

    public function create(Request $request): void
    {
        RoleMiddleware::require(Rol::Administrador);

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Response::json(['status' => 'error', 'mensaje' => 'Token de seguridad inválido.'], code: 403);
        }

        $data    = $request->json() ?? [];
        $nombre  = trim($data['nombre']  ?? '');
        $usuario = trim($data['usuario'] ?? '');
        $clave   = trim($data['clave']   ?? '');
        $rolStr  = trim($data['rol']     ?? '');
        $rol     = Rol::tryFrom($rolStr);

        if ($nombre === '' || $usuario === '' || $clave === '' || $rol === null) {
            Response::json(['status' => 'error', 'mensaje' => 'Todos los campos son obligatorios.']);
        }

        $ok = (new Usuario())->create($nombre, $usuario, $clave, $rol);
        Response::json(
            $ok
                ? ['status' => 'ok',    'mensaje' => 'Usuario creado correctamente.']
                : ['status' => 'error', 'mensaje' => 'Error al crear el usuario.']
        );
    }

    public function update(Request $request): void
    {
        RoleMiddleware::require(Rol::Administrador);

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Response::json(['status' => 'error', 'mensaje' => 'Token de seguridad inválido.'], code: 403);
        }

        $data    = $request->json() ?? [];
        $id      = (int) ($data['id']      ?? 0);
        $nombre  = trim($data['nombre']    ?? '');
        $usuario = trim($data['usuario']   ?? '');
        $rolStr  = trim($data['rol']       ?? '');
        $clave   = isset($data['clave']) ? trim($data['clave']) : null;
        $rol     = Rol::tryFrom($rolStr);

        if ($id <= 0 || $nombre === '' || $usuario === '' || $rol === null) {
            Response::json(['status' => 'error', 'mensaje' => 'Datos incompletos.']);
        }

        $ok = (new Usuario())->update($id, $nombre, $usuario, $rol, $clave ?: null);
        Response::json(
            $ok
                ? ['status' => 'ok',    'mensaje' => 'Usuario actualizado correctamente.']
                : ['status' => 'error', 'mensaje' => 'Error al actualizar el usuario.']
        );
    }

    public function destroy(Request $request): void
    {
        RoleMiddleware::require(Rol::Administrador);

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Response::json(['status' => 'error', 'mensaje' => 'Token de seguridad inválido.'], code: 403);
        }

        $data = $request->json() ?? [];
        $id   = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            Response::json(['status' => 'error', 'mensaje' => 'ID inválido.']);
        }

        $ok = (new Usuario())->delete($id);
        Logger::getInstance()->info('Usuario eliminado', ['id' => $id, 'por' => Session::get('usuario')]);

        Response::json(
            $ok
                ? ['status' => 'ok',    'mensaje' => 'Usuario eliminado correctamente.']
                : ['status' => 'error', 'mensaje' => 'Error al eliminar el usuario.']
        );
    }
}
