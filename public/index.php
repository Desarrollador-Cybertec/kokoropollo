<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

// Configurar zona horaria y manejo de errores
$appConfig = require ROOT_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

if ($appConfig['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

use App\Core\{Router, Request, Session};

Session::start();

$router  = new Router();
$request = new Request();

require ROOT_PATH . '/config/routes.php';

$router->dispatch($request);
