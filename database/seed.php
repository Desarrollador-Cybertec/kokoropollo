<?php

/**
 * seed.php — Setup completo de la base de datos
 *
 * Ejecutar una sola vez en instalaciones nuevas o para restaurar el estado base:
 *   php database/seed.php
 *
 * Acciones que realiza:
 *   1. Crea la BD si no existe
 *   2. Aplica schema.sql (tablas base, idempotente)
 *   3. Aplica todas las migraciones en orden (idempotentes)
 *   4. Siembra datos iniciales (usuario Jefe, fila de caja)
 *
 * NO elimina datos existentes. Es seguro correr en BD ya poblada.
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

$autoloadPath = ROOT_PATH . '/vendor/autoload.php';
if (is_file($autoloadPath) && is_readable($autoloadPath)) {
    require $autoloadPath;
}

// ──────────────────────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Divide un archivo SQL en sentencias individuales, ignorando comentarios
 * y respetando literales de cadena.
 *
 * @return list<string>
 */
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';

    $inSingle = $inDouble = $inBacktick = $inLineComment = $inBlockComment = false;
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
            if ($i === 0 || $sql[$i - 1] !== '\\') {
                $inSingle = !$inSingle;
            }
        } elseif ($char === '"' && !$inSingle && !$inBacktick) {
            if ($i === 0 || $sql[$i - 1] !== '\\') {
                $inDouble = !$inDouble;
            }
        } elseif ($char === '`' && !$inSingle && !$inDouble) {
            $inBacktick = !$inBacktick;
        }

        if ($char === ';' && !$inSingle && !$inDouble && !$inBacktick) {
            $stmt = trim($buffer);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $stmt = trim($buffer);
    if ($stmt !== '') {
        $statements[] = $stmt;
    }

    return $statements;
}

/**
 * Ejecuta todas las sentencias de un archivo SQL.
 * Lanza excepción ante cualquier error.
 */
function executeSqlFile(PDO $pdo, string $filePath): void
{
    if (!is_file($filePath)) {
        throw new RuntimeException("Archivo SQL no encontrado: {$filePath}");
    }

    $sql = (string) file_get_contents($filePath);
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;

    foreach (splitSqlStatements($sql) as $stmt) {
        $pdo->exec($stmt);
    }
}

/**
 * Ejecuta todas las sentencias de un archivo SQL ignorando errores esperados
 * de idempotencia (objeto ya existe, clave duplicada, etc.).
 *
 * Códigos ignorados:
 *   1060 — Duplicate column name
 *   1061 — Duplicate key name (índice/unique)
 *   1062 — Duplicate entry (datos)
 *   1068 — Multiple primary key defined
 *   1826 — Duplicate check constraint name (MariaDB ≥ 10.5)
 *   3822 — Duplicate check constraint name (variante)
 *
 * @return int Número de sentencias omitidas
 */
function executeSqlFileSafe(PDO $pdo, string $filePath): int
{
    if (!is_file($filePath)) {
        throw new RuntimeException("Archivo SQL no encontrado: {$filePath}");
    }

    $skipCodes = [1060, 1061, 1062, 1068, 1826, 3822];

    $sql = (string) file_get_contents($filePath);
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;

    $skipped = 0;
    foreach (splitSqlStatements($sql) as $stmt) {
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            $code = (int) ($e->errorInfo[1] ?? 0);
            if (in_array($code, $skipCodes, true)) {
                $skipped++;
            } else {
                throw $e;
            }
        }
    }

    return $skipped;
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

/**
 * Carga el archivo .env en $_ENV / getenv() sin necesidad de la librería dotenv.
 */
function loadEnvFallback(string $rootPath): void
{
    $envPath = $rootPath . '/.env';
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        [$key, $value] = $parts;
        $key   = trim($key);
        $value = trim($value);

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

// ──────────────────────────────────────────────────────────────────────────────
// Setup principal
// ──────────────────────────────────────────────────────────────────────────────

try {
    // ── Cargar variables de entorno ──────────────────────────────────────────
    if (class_exists(Dotenv\Dotenv::class)) {
        $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->load();
    } else {
        loadEnvFallback(ROOT_PATH);
    }

    $host   = (string) ($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost');
    $port   = (int)    ($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306);
    $dbName = trim((string) ($_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: ''));
    $user   = (string) ($_ENV['DB_USER'] ?? getenv('DB_USER') ?: '');
    $pass   = (string) ($_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '');

    if ($dbName === '') {
        throw new RuntimeException('DB_NAME no está configurado en .env');
    }
    if ($user === '') {
        throw new RuntimeException('DB_USER no está configurado en .env');
    }

    $pdoOptions = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // ── 1. Crear la base de datos si no existe ───────────────────────────────
    $adminPdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $user,
        $pass,
        $pdoOptions
    );
    $adminPdo->exec(
        "CREATE DATABASE IF NOT EXISTS `{$dbName}`
         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    echo "BD '{$dbName}': lista." . PHP_EOL;

    // ── 2. Conectar a la base de datos ───────────────────────────────────────
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
        $user,
        $pass,
        $pdoOptions
    );
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    // ── 3. Esquema base ──────────────────────────────────────────────────────
    echo 'Aplicando schema.sql...' . PHP_EOL;
    executeSqlFile($pdo, ROOT_PATH . '/database/schema.sql');

    // ── 4. Migraciones en orden ──────────────────────────────────────────────
    $migrations = discoverMigrationFiles(ROOT_PATH . '/database/migrations');
    if ($migrations === []) {
        throw new RuntimeException('No se encontraron archivos de migración en database/migrations.');
    }

    echo 'Aplicando migraciones...' . PHP_EOL;
    foreach ($migrations as $file) {
        $skipped = executeSqlFileSafe($pdo, ROOT_PATH . '/database/migrations/' . $file);
        $note    = $skipped > 0 ? " ({$skipped} ya aplicada(s))" : '';
        echo "  [OK] {$file}{$note}" . PHP_EOL;
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    // ── 5. Datos semilla ─────────────────────────────────────────────────────
    echo 'Sembrando datos iniciales...' . PHP_EOL;

    // Usuario inicial con rol Jefe (máximo nivel de acceso)
    $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, 'Jefe')"
    );
    $stmt->execute(['Administrador', 'admin', $hash]);
    echo $stmt->rowCount() > 0
        ? '  [OK] Usuario admin creado  (rol: Jefe).' . PHP_EOL
        : '  [--] Usuario admin ya existe, sin cambios.' . PHP_EOL;

    // Garantizar fila única de caja
    $pdo->exec("INSERT IGNORE INTO caja (id, total) VALUES (1, 0.00)");
    echo '  [OK] Fila de caja lista.' . PHP_EOL;

    // ── Resumen ──────────────────────────────────────────────────────────────
    echo PHP_EOL;
    echo '╔══════════════════════════════════════════╗' . PHP_EOL;
    echo '║       Setup completado correctamente     ║' . PHP_EOL;
    echo '╠══════════════════════════════════════════╣' . PHP_EOL;
    echo '║  usuario : admin                         ║' . PHP_EOL;
    echo '║  clave   : admin123                      ║' . PHP_EOL;
    echo '║  rol     : Jefe                          ║' . PHP_EOL;
    echo '╠══════════════════════════════════════════╣' . PHP_EOL;
    echo '║  ⚠  Cambia la clave antes de producción  ║' . PHP_EOL;
    echo '╚══════════════════════════════════════════╝' . PHP_EOL;

    exit(0);
} catch (PDOException $e) {
    fwrite(STDERR, 'Error de base de datos: ' . $e->getMessage() . PHP_EOL);
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
