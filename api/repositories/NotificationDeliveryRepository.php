<?php

final class NotificationDeliveryRepository extends BaseRepository
{
    public function queued(int $limit): array
    {
        $limit = max(1, min($limit, 200));

        return $this->fetchAll(
            "SELECT n.id,
                    n.user_id,
                    n.channel,
                    n.notification_type,
                    n.title,
                    n.message,
                    n.payload,
                    n.attempt_count,
                    u.full_name,
                    u.mobile,
                    u.email,
                    u.role,
                    (
                        SELECT GROUP_CONCAT(dt.device_token SEPARATOR '\n')
                        FROM device_tokens dt
                        WHERE dt.user_id = n.user_id AND dt.is_active = 1
                    ) AS device_tokens
             FROM notifications n
             INNER JOIN users u ON u.id = n.user_id
             WHERE n.status = 'queued'
             ORDER BY n.queued_at ASC, n.id ASC
             LIMIT {$limit}"
        );
    }

    public function recordAttempt(int $notificationId): void
    {
        $this->execute(
            'UPDATE notifications
             SET attempt_count = attempt_count + 1,
                 last_attempt_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id',
            [':id' => $notificationId]
        );
    }

    public function markSent(int $notificationId): void
    {
        $this->execute(
            'UPDATE notifications
             SET status = "sent",
                 sent_at = NOW(),
                 error_message = NULL,
                 updated_at = NOW()
             WHERE id = :id',
            [':id' => $notificationId]
        );
    }

    public function markFailed(int $notificationId, string $errorMessage): void
    {
        $this->execute(
            'UPDATE notifications
             SET status = "failed",
                 error_message = :error_message,
                 updated_at = NOW()
             WHERE id = :id',
            [
                ':id' => $notificationId,
                ':error_message' => substr($errorMessage, 0, 1000),
            ]
        );
    }
}
