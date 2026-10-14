<?php

final class LogPushProvider implements PushProviderInterface
{
    public function send(string $deviceToken, string $title, string $message, array $payload = []): bool
    {
        Logger::info('Log push notification requested', [
            'device_token' => $this->maskToken($deviceToken),
            'title' => $title,
            'message_length' => strlen($message),
            'payload_keys' => array_keys($payload),
        ]);

        return true;
    }

    private function maskToken(string $token): string
    {
        if (strlen($token) <= 10) {
            return '***';
        }

        return substr($token, 0, 6) . '***' . substr($token, -4);
    }
}
