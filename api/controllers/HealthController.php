<?php

final class HealthController
{
    public function __invoke(Request $request): void
    {
        $database = 'unavailable';

        try {
            db()->query('SELECT 1');
            $database = 'ok';
        } catch (Throwable $exception) {
            Logger::error('Database health check failed', ['detail' => $exception->getMessage()]);
        }

        Response::success('STABIMATIC API is running', [
            'service' => 'stabimatic-api',
            'version' => 'v1',
            'environment' => config('app.env'),
            'database' => $database,
        ]);
    }
}
