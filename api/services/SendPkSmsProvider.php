<?php

final class SendPkSmsProvider implements SmsProviderInterface
{
    public function sendOtp(string $mobile, string $otp): bool
    {
        $apiKey = config('sms.sendpk.api_key', '');
        $templateId = config('sms.sendpk.otp_template_id', '');

        if ($apiKey === '') {
            Logger::error('SendPK SMS configuration is incomplete', [
                'has_api_key' => false,
                'has_template_id' => $templateId !== '',
            ]);
            throw new RuntimeException('SendPK API key is missing. Set SENDPK_API_KEY in the API environment.');
        }

        if ($templateId === '') {
            Logger::error('SendPK SMS configuration is incomplete', [
                'has_api_key' => true,
                'has_template_id' => false,
            ]);
            throw new RuntimeException('SendPK OTP template ID is missing. Create and approve an OTP template in SendPK, then set SENDPK_OTP_TEMPLATE_ID.');
        }

        $params = [
            'api_key' => $apiKey,
            'sender' => config('sms.sendpk.sender', '8584'),
            'mobile' => $this->toPakistanInternational($mobile),
            'template_id' => $templateId,
            'message' => $this->messageJson($otp, $mobile),
            'format' => 'plain',
        ];

        $response = $this->post(config('sms.sendpk.api_url', 'https://sendpk.com/api/sms.php'), $params);
        $success = strpos(trim($response), 'OK') === 0;

        Logger::info('SendPK OTP send attempted', [
            'mobile' => $this->maskMobile($mobile),
            'success' => $success,
            'response' => $this->safeResponse($response),
        ]);

        if (!$success) {
            throw new RuntimeException('SendPK rejected OTP request: ' . $this->safeResponse($response));
        }

        return true;
    }

    public function sendNotification(string $mobile, string $title, string $message): bool
    {
        $apiKey = config('sms.sendpk.api_key', '');
        $templateId = config('sms.sendpk.notification_template_id', '');

        if ($apiKey === '' || $templateId === '') {
            Logger::error('SendPK notification SMS configuration is incomplete', [
                'has_api_key' => $apiKey !== '',
                'has_template_id' => $templateId !== '',
            ]);
            return false;
        }

        $params = [
            'api_key' => $apiKey,
            'sender' => config('sms.sendpk.sender', '8584'),
            'mobile' => $this->toPakistanInternational($mobile),
            'template_id' => $templateId,
            'message' => $this->notificationMessageJson($title, $message),
            'format' => 'plain',
        ];

        $response = $this->post(config('sms.sendpk.api_url', 'https://sendpk.com/api/sms.php'), $params);
        $success = strpos(trim($response), 'OK') === 0;

        Logger::info('SendPK notification SMS attempted', [
            'mobile' => $this->maskMobile($mobile),
            'success' => $success,
            'response' => $this->safeResponse($response),
        ]);

        return $success;
    }

    private function messageJson(string $otp, string $mobile): string
    {
        $template = (string) config('sms.sendpk.otp_message_template', '{"otp":"{otp}"}');
        $template = str_replace(['{otp}', '{mobile}'], [$otp, $this->maskMobile($mobile)], $template);
        $decoded = json_decode($template, true);

        if (!is_array($decoded)) {
            $decoded = ['otp' => $otp];
        }

        return json_encode($decoded, JSON_UNESCAPED_SLASHES);
    }

    private function notificationMessageJson(string $title, string $message): string
    {
        $template = (string) config('sms.sendpk.notification_message_template', '{"title":"{title}","message":"{message}"}');
        $template = str_replace(['{title}', '{message}'], [$title, $message], $template);
        $decoded = json_decode($template, true);

        if (!is_array($decoded)) {
            $decoded = ['title' => $title, 'message' => $message];
        }

        return json_encode($decoded, JSON_UNESCAPED_SLASHES);
    }

    private function post(string $url, array $params): string
    {
        $timeout = max(3, (int) config('sms.sendpk.timeout_seconds', 15));

        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $response = curl_exec($curl);
            if ($response === false) {
                $error = curl_error($curl);
                curl_close($curl);
                throw new RuntimeException('SendPK request failed: ' . $error);
            }
            curl_close($curl);
            return (string) $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($params),
                'timeout' => $timeout,
            ],
        ]);

        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            throw new RuntimeException('SendPK request failed');
        }

        return (string) $response;
    }

    private function toPakistanInternational(string $mobile): string
    {
        $mobile = preg_replace('/[^0-9+]/', '', $mobile);
        if (strpos($mobile, '+92') === 0) {
            return substr($mobile, 1);
        }
        if (strpos($mobile, '92') === 0) {
            return $mobile;
        }
        if (strpos($mobile, '03') === 0) {
            return '92' . substr($mobile, 1);
        }
        return $mobile;
    }

    private function maskMobile(string $mobile): string
    {
        $digits = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($digits) < 6) {
            return '***';
        }
        return substr($digits, 0, 4) . '***' . substr($digits, -2);
    }

    private function safeResponse(string $response): string
    {
        $response = trim($response);
        return strlen($response) > 80 ? substr($response, 0, 80) : $response;
    }
}
