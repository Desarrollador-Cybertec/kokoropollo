<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Csrf, Database, Logger, Request, Response, Session, View};
use App\Enums\Rol;
use App\Models\Auditoria;

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

        // C-07: Rate limiting — bloquear tras 5 intentos fallidos durante 5 minutos
        $intentos      = (int) Session::get('login_intentos', 0);
        $ultimoIntento = (int) Session::get('login_ultimo_intento', 0);
        if ($intentos >= 5 && (time() - $ultimoIntento) < 300) {
            Session::flash('error', 'Demasiados intentos fallidos. Espere 5 minutos.');
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
            Session::set('login_intentos', $intentos + 1);
            Session::set('login_ultimo_intento', time());
            Logger::getInstance()->warning('Intento de login fallido', ['usuario' => $usuario]);
            Session::flash('error', 'Usuario o contraseña incorrectos.');
            Response::redirect('/');
        }

        // Login exitoso: limpiar contadores y regenerar sesión + CSRF
        Session::set('login_intentos', 0);
        Session::regenerate();
        // M-02: Rotar CSRF al inicio de sesión para invalidar token previo
        Csrf::generateToken();
        Session::set('usuario_id', (int) $row['id']);
        Session::set('usuario', $row['usuario']);
        Session::set('rol', $row['rol']);

        $rol = Rol::tryFrom($row['rol']);
        Logger::getInstance()->info('Login exitoso', ['usuario' => $row['usuario']]);
        // A-02: Registrar login en tabla de auditoría
        (new Auditoria())->registrar($row['usuario'], 'auth', 'login', 'Inicio de sesión');

        Response::redirect($rol?->dashboard() ?? '/dashboard');
    }

    public function logout(Request $request): void
    {
        $usuarioActual = Session::get('usuario', 'desconocido');
        Logger::getInstance()->info('Logout', ['usuario' => $usuarioActual]);
        // A-02: Registrar logout en tabla de auditoría
        (new Auditoria())->registrar($usuarioActual, 'auth', 'logout', 'Cierre de sesión');
        Session::destroy();
        Response::redirect('/');
    }
}
