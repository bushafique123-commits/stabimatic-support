<?php

final class NotificationRepository extends BaseRepository
{
    public function upsertDeviceToken(int $userId, string $role, array $data): array
    {
        $this->execute(
            'INSERT INTO device_tokens (user_id, role, platform, device_token, device_name, app_version, is_active, last_seen_at)
             VALUES (:user_id, :role, :platform, :device_token, :device_name, :app_version, 1, NOW())
             ON DUPLICATE KEY UPDATE
               user_id = VALUES(user_id),
               role = VALUES(role),
               platform = VALUES(platform),
               device_name = VALUES(device_name),
               app_version = VALUES(app_version),
               is_active = 1,
               last_seen_at = NOW(),
               updated_at = NOW()',
            [
                'user_id' => $userId,
                'role' => $role,
                'platform' => $data['platform'],
                'device_token' => $data['device_token'],
                'device_name' => $data['device_name'] ?: null,
                'app_version' => $data['app_version'] ?: null,
            ]
        );

        return $this->findDeviceToken($data['device_token']) ?: [];
    }

    public function deactivateDeviceToken(int $userId, string $deviceToken): bool
    {
        return $this->execute(
            'UPDATE device_tokens
             SET is_active = 0, updated_at = NOW()
             WHERE user_id = :user_id AND device_token = :device_token',
            [
                'user_id' => $userId,
                'device_token' => $deviceToken,
            ]
        );
    }

    public function findDeviceToken(string $deviceToken): ?array
    {
        return $this->fetchOne(
            'SELECT id, user_id, role, platform, device_token, device_name, app_version, is_active, last_seen_at, created_at, updated_at
             FROM device_tokens
             WHERE device_token = :device_token',
            ['device_token' => $deviceToken]
        );
    }

    public function preferencesForUser(int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT user_id, sales_notifications, installation_notifications, account_notifications, marketing_notifications, created_at, updated_at
             FROM notification_preferences
             WHERE user_id = :user_id',
            ['user_id' => $userId]
        );
    }

    public function savePreferences(int $userId, array $preferences): array
    {
        $this->execute(
            'INSERT INTO notification_preferences
               (user_id, sales_notifications, installation_notifications, account_notifications, marketing_notifications)
             VALUES
               (:user_id, :sales_notifications, :installation_notifications, :account_notifications, :marketing_notifications)
             ON DUPLICATE KEY UPDATE
               sales_notifications = VALUES(sales_notifications),
               installation_notifications = VALUES(installation_notifications),
               account_notifications = VALUES(account_notifications),
               marketing_notifications = VALUES(marketing_notifications),
               updated_at = NOW()',
            [
                'user_id' => $userId,
                'sales_notifications' => (int) $preferences['sales_notifications'],
                'installation_notifications' => (int) $preferences['installation_notifications'],
                'account_notifications' => (int) $preferences['account_notifications'],
                'marketing_notifications' => (int) $preferences['marketing_notifications'],
            ]
        );

        return $this->preferencesForUser($userId) ?: [];
    }

    public function paginateForUser(int $userId, array $filters, int $page, int $limit): array
    {
        $where = ['user_id = :user_id'];
        $params = [':user_id' => $userId];

        if (($filters['status'] ?? '') === 'unread') {
            $where[] = 'read_at IS NULL';
        } elseif (($filters['status'] ?? '') === 'read') {
            $where[] = 'read_at IS NOT NULL';
        }

        if (($filters['notification_type'] ?? '') !== '') {
            $where[] = 'notification_type = :notification_type';
            $params[':notification_type'] = $filters['notification_type'];
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) $this->fetchOne(
            "SELECT COUNT(*) AS total FROM notifications WHERE {$whereSql}",
            $params
        )['total'];

        $offset = Paginator::offset($page, $limit);
        $items = $this->fetchAll(
            "SELECT id, batch_id, audience, channel, notification_type, title, message, payload, status, queued_at, sent_at, read_at, created_at, updated_at
             FROM notifications
             WHERE {$whereSql}
             ORDER BY queued_at DESC, id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        return ['total' => $total, 'items' => $items];
    }

    public function findForUser(int $userId, int $notificationId): ?array
    {
        return $this->fetchOne(
            'SELECT id, batch_id, audience, channel, notification_type, title, message, payload, status, queued_at, sent_at, read_at, created_at, updated_at
             FROM notifications
             WHERE id = :id AND user_id = :user_id
             LIMIT 1',
            [':id' => $notificationId, ':user_id' => $userId]
        );
    }

    public function unreadCountForUser(int $userId): int
    {
        return (int) $this->fetchOne(
            'SELECT COUNT(*) AS total
             FROM notifications
             WHERE user_id = :user_id AND read_at IS NULL',
            [':user_id' => $userId]
        )['total'];
    }

    public function markRead(int $userId, int $notificationId): bool
    {
        return $this->execute(
            'UPDATE notifications
             SET read_at = COALESCE(read_at, NOW()), updated_at = NOW()
             WHERE id = :id AND user_id = :user_id',
            [':id' => $notificationId, ':user_id' => $userId]
        );
    }

    public function markAllRead(int $userId): int
    {
        $statement = $this->connection()->prepare(
            'UPDATE notifications
             SET read_at = NOW(), updated_at = NOW()
             WHERE user_id = :user_id AND read_at IS NULL'
        );
        $statement->execute([':user_id' => $userId]);

        return $statement->rowCount();
    }
}
