<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Database, Logger, Request, Response, Session, View};
use App\Enums\Rol;

final class AuthController
{
    public function showLogin(Request $request): void
    {
        if (Session::has('usuario')) {
            $rol = Rol::tryFrom(Session::get('rol') ?? '');
            Response::redirect($rol?->dashboard() ?? '/');
        }

        View::render('auth/login');
    }

    public function login(Request $request): void
    {
        if (!Csrf::validateToken($request->csrfToken())) {
            Session::flash('error', 'Solicitud no válida. Intente de nuevo.');
            Response::redirect('/');
        }

        $usuario    = trim($request->post('usuario', ''));
        $contrasena = $request->post('contrasena', '');

        if ($usuario === '' || $contrasena === '') {
            Session::flash('error', 'Debe ingresar usuario y contraseña.');
            Response::redirect('/');
        }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id, usuario, clave, rol FROM usuarios WHERE usuario = ?');
        $stmt->execute([$usuario]);
        $row  = $stmt->fetch();

        if ($row === false || !password_verify($contrasena, $row['clave'])) {
            Logger::getInstance()->warning('Intento de login fallido', ['usuario' => $usuario]);
            Session::flash('error', 'Usuario o contraseña incorrectos.');
            Response::redirect('/');
        }

        Session::regenerate();
        Session::set('usuario', $row['usuario']);
        Session::set('rol', $row['rol']);

        $rol = Rol::tryFrom($row['rol']);
        Logger::getInstance()->info('Login exitoso', ['usuario' => $row['usuario']]);

        Response::redirect($rol?->dashboard() ?? '/dashboard');
    }

    public function logout(Request $request): void
    {
        Logger::getInstance()->info('Logout', ['usuario' => Session::get('usuario', 'desconocido')]);
        Session::destroy();
        Response::redirect('/');
    }
}
