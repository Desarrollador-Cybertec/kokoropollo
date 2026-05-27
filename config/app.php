<?php

declare(strict_types=1);

return [
    'name'     => $_ENV['APP_NAME']     ?? 'Kokoro Pollo',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Bogota',
];
