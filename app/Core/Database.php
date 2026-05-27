<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require ROOT_PATH . '/config/database.php';

            try {
                self::$instance = new PDO(
                    dsn: "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
                    username: $config['username'],
                    password: $config['password'],
                    options: [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                Logger::getInstance()->critical('Error de conexión a la base de datos', [
                    'error' => $e->getMessage(),
                ]);
                http_response_code(500);
                exit('Error interno del servidor. Intente más tarde.');
            }
        }

        return self::$instance;
    }
}
