<?php

final class ExceptionHandler
{
    public static function register(bool $debug): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');

        set_exception_handler(function (Throwable $exception) use ($debug): void {
            Logger::error('Unhandled exception', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $errors = $debug ? [
                'exception' => get_class($exception),
                'detail' => $exception->getMessage(),
            ] : [];

            Response::error('Server error', $errors, 500);
        });
    }
}
