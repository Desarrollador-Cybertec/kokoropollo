<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';

use Dotenv\Dotenv;

/**
 * @return list<string>
 */
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';

    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;

    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($inLineComment) {
            if ($char === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        if ($inBlockComment) {
            if ($char === '*' && $next === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }

        if (!$inSingle && !$inDouble && !$inBacktick) {
            if ($char === '-' && $next === '-') {
                $inLineComment = true;
                $i++;
                continue;
            }

            if ($char === '#') {
                $inLineComment = true;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }
        }

        if ($char === "'" && !$inDouble && !$inBacktick) {
            $escaped = $i > 0 && $sql[$i - 1] === '\\';
            if (!$escaped) {
                $inSingle = !$inSingle;
            }
            $buffer .= $char;
            continue;
        }

        if ($char === '"' && !$inSingle && !$inBacktick) {
            $escaped = $i > 0 && $sql[$i - 1] === '\\';
            if (!$escaped) {
                $inDouble = !$inDouble;
            }
            $buffer .= $char;
            continue;
        }

        if ($char === '`' && !$inSingle && !$inDouble) {
            $inBacktick = !$inBacktick;
            $buffer .= $char;
            continue;
        }

        if ($char === ';' && !$inSingle && !$inDouble && !$inBacktick) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

try {
    $dotenv = Dotenv::createImmutable(ROOT_PATH);
    $dotenv->load();
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

    $config = require ROOT_PATH . '/config/database.php';

    $host = (string) ($config['host'] ?? 'localhost');
    $port = (int) ($config['port'] ?? 3306);
    $dbName = trim((string) ($config['database'] ?? ''));
    $user = (string) ($config['username'] ?? '');
    $pass = (string) ($config['password'] ?? '');

    if ($dbName === '') {
        throw new RuntimeException('DB_NAME no esta configurado.');
    }

    $adminPdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    echo "Reseteando base de datos '{$dbName}'..." . PHP_EOL;
    $adminPdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
    $adminPdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $schemaPath = ROOT_PATH . '/database/schema.sql';
    if (!is_file($schemaPath)) {
        throw new RuntimeException('No se encontro database/schema.sql');
    }

    echo 'Ejecutando esquema principal...' . PHP_EOL;
    $sql = (string) file_get_contents($schemaPath);
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;

    foreach (splitSqlStatements($sql) as $statement) {
        $pdo->exec($statement);
    }

    echo 'Ejecutando seeder de usuario admin...' . PHP_EOL;
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        'Administrador',
        'admin',
        password_hash('admin123', PASSWORD_DEFAULT),
        'Administrador',
    ]);

    echo 'Proceso completado: DB recreada, esquema aplicado y usuario seed ejecutado.' . PHP_EOL;
    echo 'Credenciales iniciales: usuario=admin / clave=admin123' . PHP_EOL;
    exit(0);
} catch (PDOException $e) {
    fwrite(STDERR, 'Error de base de datos: ' . $e->getMessage() . PHP_EOL);
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
