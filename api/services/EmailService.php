<?php

final class EmailService
{
    public function sendOtp(string $email, string $otp): bool
    {
        $subject = 'Your STABIMATIC verification code';
        $body = "Your STABIMATIC verification code is {$otp}. It will expire in " . config('otp.expiry_minutes') . " minutes.";

        Logger::info('Email OTP send requested', [
            'email' => $this->maskEmail($email),
            'smtp_configured' => config('smtp.host') !== '',
            'otp' => config('otp.expose_in_response') ? $otp : '[hidden]',
        ]);

        if (config('smtp.host') === '') {
            return true;
        }

        return $this->sendViaSmtp($email, $subject, $body);
    }

    public function sendNotification(string $email, string $title, string $message): bool
    {
        Logger::info('Email notification send requested', [
            'email' => $this->maskEmail($email),
            'smtp_configured' => config('smtp.host') !== '',
            'title' => $title,
        ]);

        if (config('smtp.host') === '') {
            return true;
        }

        return $this->sendViaSmtp($email, $title, $message);
    }

    private function sendViaSmtp(string $to, string $subject, string $body): bool
    {
        $host = (string) config('smtp.host');
        $port = (int) config('smtp.port', 587);
        $username = (string) config('smtp.username');
        $password = (string) config('smtp.password');
        $fromEmail = (string) config('smtp.from_email');
        $fromName = (string) config('smtp.from_name', 'STABIMATIC');
        $timeout = 15;

        $remote = ($port === 465 ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($remote, $errno, $errstr, $timeout);
        if (!$socket) {
            Logger::error('SMTP connection failed', ['error' => $errstr, 'code' => $errno]);
            return false;
        }

        stream_set_timeout($socket, $timeout);

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);

            if ($port === 587) {
                $this->command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Unable to enable SMTP TLS');
                }
                $this->command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);
            }

            if ($username !== '' && $password !== '') {
                $this->command($socket, 'AUTH LOGIN', [334]);
                $this->command($socket, base64_encode($username), [334]);
                $this->command($socket, base64_encode($password), [235]);
            }

            $this->command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $headers = [
                'From: ' . $this->mailbox($fromName, $fromEmail),
                'To: <' . $to . '>',
                'Subject: ' . $subject,
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
            ];
            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
            $this->command($socket, $message, [250]);
            $this->command($socket, 'QUIT', [221]);
            fclose($socket);
            return true;
        } catch (Throwable $exception) {
            fclose($socket);
            Logger::error('SMTP send failed', ['detail' => $exception->getMessage()]);
            return false;
        }
    }

    private function command($socket, string $command, array $expected): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->expect($socket, $expected);
    }

    private function expect($socket, array $expected): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new RuntimeException('Unexpected SMTP response: ' . trim($response));
        }

        return $response;
    }

    private function mailbox(string $name, string $email): string
    {
        return sprintf('"%s" <%s>', addcslashes($name, '"\\'), $email);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }
        return substr($parts[0], 0, 1) . '***@' . $parts[1];
    }
}
