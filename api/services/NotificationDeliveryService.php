<?php

final class NotificationDeliveryService
{
    private $deliveries;
    private $sms;
    private $email;
    private $push;

    public function __construct(
        ?NotificationDeliveryRepository $deliveries = null,
        ?SmsProviderInterface $sms = null,
        ?EmailService $email = null,
        ?PushProviderInterface $push = null
    ) {
        $this->deliveries = $deliveries ?: new NotificationDeliveryRepository();
        $this->sms = $sms ?: SmsProviderFactory::make();
        $this->email = $email ?: new EmailService();
        $this->push = $push ?: PushProviderFactory::make();
    }

    public function process(int $limit): array
    {
        $items = $this->deliveries->queued($limit);
        $summary = [
            'ok' => true,
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($items as $item) {
            $summary['processed']++;
            $notificationId = (int) $item['id'];
            $this->deliveries->recordAttempt($notificationId);

            try {
                [$sent, $error] = $this->dispatch($item);
                if ($sent) {
                    $this->deliveries->markSent($notificationId);
                    $summary['sent']++;
                    continue;
                }

                $this->deliveries->markFailed($notificationId, $error ?: 'Delivery provider returned failure');
                $summary['failed']++;
            } catch (Throwable $exception) {
                $this->deliveries->markFailed($notificationId, $exception->getMessage());
                $summary['failed']++;
                Logger::error('Notification delivery failed', [
                    'notification_id' => $notificationId,
                    'detail' => $exception->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    private function dispatch(array $notification): array
    {
        $channel = (string) $notification['channel'];
        if ($channel === 'push') {
            return $this->sendPush($notification);
        }
        if ($channel === 'sms') {
            return $this->sendSms($notification);
        }
        if ($channel === 'email') {
            return $this->sendEmail($notification);
        }

        return [false, 'Unsupported notification channel'];
    }

    private function sendPush(array $notification): array
    {
        $tokens = array_values(array_filter(explode("\n", (string) ($notification['device_tokens'] ?? ''))));
        if (!$tokens) {
            return [false, 'Recipient has no active device tokens'];
        }

        $sent = 0;
        foreach ($tokens as $token) {
            if ($this->push->send($token, $notification['title'], $notification['message'], $this->payload($notification['payload'] ?? null))) {
                $sent++;
            }
        }

        return $sent > 0 ? [true, ''] : [false, 'Push provider failed for all active device tokens'];
    }

    private function sendSms(array $notification): array
    {
        $mobile = (string) ($notification['mobile'] ?? '');
        if ($mobile === '') {
            return [false, 'Recipient mobile number is missing'];
        }

        return $this->sms->sendNotification($mobile, $notification['title'], $notification['message'])
            ? [true, '']
            : [false, 'SMS provider returned failure'];
    }

    private function sendEmail(array $notification): array
    {
        $email = (string) ($notification['email'] ?? '');
        if ($email === '') {
            return [false, 'Recipient email address is missing'];
        }

        return $this->email->sendNotification($email, $notification['title'], $notification['message'])
            ? [true, '']
            : [false, 'Email provider returned failure'];
    }

    private function payload($payload): array
    {
        if (!$payload) {
            return [];
        }

        $decoded = json_decode((string) $payload, true);
        return is_array($decoded) ? $decoded : [];
    }
}
