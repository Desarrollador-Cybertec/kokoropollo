<?php
// Archivo de diagnóstico — eliminar después de confirmar que funciona
echo '<pre>';
echo 'PHP version: ' . PHP_VERSION . "\n";
echo 'Document root: ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'no definido') . "\n";
echo 'Script: ' . ($_SERVER['SCRIPT_FILENAME'] ?? 'no definido') . "\n";
echo 'ROOT_PATH sería: ' . dirname(__DIR__) . "\n";
echo '.env existe: ' . (file_exists(dirname(__DIR__) . '/.env') ? 'SÍ' : 'NO') . "\n";
echo 'vendor/autoload.php existe: ' . (file_exists(dirname(__DIR__) . '/vendor/autoload.php') ? 'SÍ' : 'NO') . "\n";
echo 'views/auth/login.php existe: ' . (file_exists(dirname(__DIR__) . '/resources/views/auth/login.php') ? 'SÍ' : 'NO') . "\n";
echo '</pre>';
