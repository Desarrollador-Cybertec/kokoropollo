<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = $request->path();

        // Normalize root path
        if ($path === '') {
            $path = '/';
        }

        // Favicon — responder vacío para no registrar error
        if ($path === '/favicon.ico') {
            http_response_code(204);
            exit;
        }

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            Logger::getInstance()->warning("Ruta no encontrada: {$method} {$path}");
            Response::notFound();
        }

        [$controllerClass, $action] = $handler;

        if (!class_exists($controllerClass)) {
            Logger::getInstance()->error("Controlador no encontrado: {$controllerClass}");
            Response::notFound();
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $action)) {
            Logger::getInstance()->error("Acción no encontrada: {$controllerClass}::{$action}");
            Response::notFound();
        }

        $controller->$action($request);
    }
}
