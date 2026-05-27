<?php

declare(strict_types=1);

namespace App\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;

final class Logger
{
    private static ?MonologLogger $instance = null;

    private function __construct() {}

    public static function getInstance(): MonologLogger
    {
        if (self::$instance === null) {
            $logPath  = ROOT_PATH . '/' . ($_ENV['LOG_PATH'] ?? 'storage/logs/app.log');
            $logLevel = Level::fromName($_ENV['LOG_LEVEL'] ?? 'warning');

            self::$instance = new MonologLogger(name: 'kokoro');
            self::$instance->pushHandler(
                new StreamHandler(stream: $logPath, level: $logLevel)
            );
        }

        return self::$instance;
    }
}
