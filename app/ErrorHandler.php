<?php

declare(strict_types=1);

namespace app;

use Throwable;

final class ErrorHandler
{
    private static bool $handling = false;

    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(Throwable $e): void
    {
        self::render($e);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR];
        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        self::render(new \ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        ));
    }

    public static function toStatusCode(Throwable $e): int
    {
        $code = (int) $e->getCode();
        return $code >= 400 && $code <= 599 ? $code : 500;
    }

    public static function log(Throwable $e, int $code): void
    {
        $dir = dirname(__DIR__) . '/runtime/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $record = [
            'time' => date(DATE_ATOM),
            'code' => $code,
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        file_put_contents(
            $dir . '/error.log',
            json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    private static function render(Throwable $e): void
    {
        if (self::$handling) {
            http_response_code(500);
            echo 'Internal Server Error';
            return;
        }

        self::$handling = true;
        $code = self::toStatusCode($e);
        self::log($e, $code);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($code);
        echo 'Internal Server Error';
    }
}
