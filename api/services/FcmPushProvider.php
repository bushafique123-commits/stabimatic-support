<?php

final class FcmPushProvider implements PushProviderInterface
{
    public function send(string $deviceToken, string $title, string $message, array $payload = []): bool
    {
        $serverKey = (string) config('push.fcm.server_key', '');
        if ($serverKey === '') {
            Logger::error('FCM push configuration is incomplete', ['has_server_key' => false]);
            return false;
        }

        $body = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $message,
            ],
            'data' => $payload,
        ];

        $response = $this->post((string) config('push.fcm.endpoint'), $serverKey, $body);
        $decoded = json_decode($response, true);
        $success = is_array($decoded) && (int) ($decoded['success'] ?? 0) > 0;

        Logger::info('FCM push attempted', [
            'success' => $success,
            'response' => $this->safeResponse($response),
        ]);

        return $success;
    }

    private function post(string $url, string $serverKey, array $body): string
    {
        $timeout = max(3, (int) config('push.fcm.timeout_seconds', 15));
        $json = json_encode($body, JSON_UNESCAPED_SLASHES);

        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: key=' . $serverKey,
                ],
            ]);
            $response = curl_exec($curl);
            if ($response === false) {
                $error = curl_error($curl);
                curl_close($curl);
                throw new RuntimeException('FCM request failed: ' . $error);
            }
            curl_close($curl);
            return (string) $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: key={$serverKey}\r\n",
                'content' => $json,
                'timeout' => $timeout,
            ],
        ]);

        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            throw new RuntimeException('FCM request failed');
        }

        return (string) $response;
    }

    private function safeResponse(string $response): string
    {
        $response = trim($response);
        return strlen($response) > 160 ? substr($response, 0, 160) : $response;
    }
}
