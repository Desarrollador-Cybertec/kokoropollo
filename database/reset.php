<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

$autoloadPath = ROOT_PATH . '/vendor/autoload.php';
if (is_file($autoloadPath) && is_readable($autoloadPath)) {
    require $autoloadPath;
}

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

function loadEnvFallback(string $rootPath): void
{
    $envPath = $rootPath . '/.env';
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if ($key === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv("{$key}={$value}");
        }
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

function executeSqlFile(PDO $pdo, string $filePath): void
{
    if (!is_file($filePath)) {
        throw new RuntimeException("No se encontro el archivo SQL: {$filePath}");
    }

    $sql = (string) file_get_contents($filePath);
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;

    foreach (splitSqlStatements($sql) as $statement) {
        $pdo->exec($statement);
    }
}

/**
 * @return list<string>
 */
function discoverMigrationFiles(string $migrationsDir): array
{
    $paths = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql');
    if ($paths === false) {
        return [];
    }

    $files = array_map(static fn(string $path): string => basename($path), $paths);
    natsort($files);

    return array_values($files);
}

try {
    if (class_exists(Dotenv\Dotenv::class)) {
        $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->load();
    } else {
        loadEnvFallback(ROOT_PATH);
    }

    $dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
    $dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '';
    $dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '';
    $dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

    $host = (string) $dbHost;
    $port = (int) $dbPort;
    $dbName = trim((string) $dbName);
    $user = (string) $dbUser;
    $pass = (string) $dbPass;

    if ($dbName === '') {
        throw new RuntimeException('DB_NAME no esta configurado.');
    }
    if ($user === '') {
        throw new RuntimeException('DB_USER no esta configurado.');
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

    echo 'Ejecutando esquema principal...' . PHP_EOL;
    executeSqlFile($pdo, ROOT_PATH . '/database/schema.sql');

    $migrations = discoverMigrationFiles(ROOT_PATH . '/database/migrations');
    if ($migrations === []) {
        throw new RuntimeException('No se encontraron archivos de migración en database/migrations.');
    }

    echo 'Ejecutando migraciones...' . PHP_EOL;
    foreach ($migrations as $file) {
        echo "- {$file}..." . PHP_EOL;
        executeSqlFile($pdo, ROOT_PATH . '/database/migrations/' . $file);
    }

    echo 'Sembrando datos iniciales...' . PHP_EOL;
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, 'Jefe')"
    );
    $stmt->execute([
        'Administrador',
        'admin',
        password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]),
    ]);

    echo 'Proceso completado: DB recreada con esquema completo y datos semilla.' . PHP_EOL;
    echo 'Credenciales iniciales: usuario=admin / clave=admin123' . PHP_EOL;
    exit(0);
} catch (PDOException $e) {
    fwrite(STDERR, 'Error de base de datos: ' . $e->getMessage() . PHP_EOL);
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
