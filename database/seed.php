<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

use App\Core\Database;

$db = Database::getInstance();

$nombre  = 'Administrador';
$usuario = 'admin';
$clave   = password_hash('admin123', PASSWORD_DEFAULT);
$rol     = 'Administrador';

$stmt = $db->prepare(
    'INSERT IGNORE INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, ?)'
);

$stmt->execute([$nombre, $usuario, $clave, $rol]);

$afectadas = $stmt->rowCount();

if ($afectadas > 0) {
    echo "Usuario administrador creado: usuario=admin / clave=admin123" . PHP_EOL;
} else {
    echo "El usuario 'admin' ya existe, no se realizaron cambios." . PHP_EOL;
}
