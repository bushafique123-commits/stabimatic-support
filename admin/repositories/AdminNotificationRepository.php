<?php

final class AdminNotificationRepository
{
    public function stats(): array
    {
        $statusStatement = admin_db()->query(
            "SELECT status, COUNT(*) AS total
             FROM notifications
             GROUP BY status"
        );
        $statuses = ['queued' => 0, 'sent' => 0, 'failed' => 0, 'cancelled' => 0];
        foreach ($statusStatement->fetchAll() as $row) {
            $statuses[$row['status']] = (int) $row['total'];
        }

        $deviceStatement = admin_db()->query(
            "SELECT COUNT(*) AS total
             FROM device_tokens
             WHERE is_active = 1"
        );
        $preferenceStatement = admin_db()->query(
            "SELECT COUNT(*) AS total
             FROM notification_preferences"
        );

        return [
            'queued' => $statuses['queued'],
            'sent' => $statuses['sent'],
            'failed' => $statuses['failed'],
            'cancelled' => $statuses['cancelled'],
            'active_devices' => (int) $deviceStatement->fetchColumn(),
            'custom_preferences' => (int) $preferenceStatement->fetchColumn(),
        ];
    }

    public function paginateBatches(array $filters, int $page, int $limit): array
    {
        [$whereSql, $params] = $this->where($filters);

        $countStatement = admin_db()->prepare(
            "SELECT COUNT(*)
             FROM (
               SELECT n.batch_id
               FROM notifications n
               {$whereSql}
               GROUP BY n.batch_id
             ) batches"
        );
        $countStatement->execute($params);
        $total = (int) $countStatement->fetchColumn();

        $offset = ($page - 1) * $limit;
        $statement = admin_db()->prepare(
            "SELECT MIN(n.id) AS id,
                    n.batch_id,
                    n.audience,
                    n.channel,
                    n.notification_type,
                    n.title,
                    COUNT(*) AS recipient_count,
                    SUM(n.status = 'queued') AS queued_count,
                    SUM(n.status = 'sent') AS sent_count,
                    SUM(n.status = 'failed') AS failed_count,
                    SUM(n.status = 'cancelled') AS cancelled_count,
                    MIN(n.queued_at) AS queued_at,
                    MAX(n.updated_at) AS updated_at,
                    admins.name AS created_by
             FROM notifications n
             LEFT JOIN admin_users admins ON admins.id = n.created_by_admin_id
             {$whereSql}
             GROUP BY n.batch_id, n.audience, n.channel, n.notification_type, n.title, admins.name
             ORDER BY MIN(n.queued_at) DESC, MIN(n.id) DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $statement->execute($params);

        return ['total' => $total, 'items' => $statement->fetchAll()];
    }

    public function createBroadcast(int $adminId, string $batchId, array $data): int
    {
        [$audienceWhere, $audienceParams] = $this->audienceWhere($data['audience']);
        $preferenceWhere = $this->preferenceWhere($data['notification_type']);
        $deviceWhere = $data['channel'] === 'push'
            ? "AND EXISTS (
                SELECT 1
                FROM device_tokens dt
                WHERE dt.user_id = u.id AND dt.is_active = 1
              )"
            : '';

        $params = array_merge($audienceParams, [
            ':batch_id' => $batchId,
            ':created_by_admin_id' => $adminId,
            ':audience' => $data['audience'],
            ':channel' => $data['channel'],
            ':notification_type' => $data['notification_type'],
            ':title' => $data['title'],
            ':message' => $data['message'],
            ':payload' => $data['payload_json'],
        ]);

        $statement = admin_db()->prepare(
            "INSERT INTO notifications
               (batch_id, user_id, recipient_role, created_by_admin_id, audience, channel, notification_type, title, message, payload, status, queued_at)
             SELECT
               :batch_id,
               u.id,
               u.role,
               :created_by_admin_id,
               :audience,
               :channel,
               :notification_type,
               :title,
               :message,
               :payload,
               'queued',
               NOW()
             FROM users u
             LEFT JOIN notification_preferences np ON np.user_id = u.id
             WHERE u.is_active = 1
               AND u.is_verified = 1
               {$audienceWhere}
               {$preferenceWhere}
               {$deviceWhere}"
        );
        $statement->execute($params);

        return $statement->rowCount();
    }

    public function findBatch(string $batchId): ?array
    {
        $statement = admin_db()->prepare(
            "SELECT MIN(n.id) AS id,
                    n.batch_id,
                    n.audience,
                    n.channel,
                    n.notification_type,
                    n.title,
                    n.message,
                    n.payload,
                    COUNT(*) AS recipient_count,
                    SUM(n.status = 'queued') AS queued_count,
                    SUM(n.status = 'sent') AS sent_count,
                    SUM(n.status = 'failed') AS failed_count,
                    SUM(n.status = 'cancelled') AS cancelled_count,
                    MIN(n.queued_at) AS queued_at,
                    MAX(n.updated_at) AS updated_at,
                    admins.name AS created_by
             FROM notifications n
             LEFT JOIN admin_users admins ON admins.id = n.created_by_admin_id
             WHERE n.batch_id = :batch_id
             GROUP BY n.batch_id, n.audience, n.channel, n.notification_type, n.title, n.message, n.payload, admins.name
             LIMIT 1"
        );
        $statement->execute([':batch_id' => $batchId]);
        $batch = $statement->fetch();

        return $batch ?: null;
    }

    public function recipients(string $batchId, int $limit = 200): array
    {
        $statement = admin_db()->prepare(
            "SELECT n.id,
                    n.user_id,
                    n.status,
                    n.sent_at,
                    n.error_message,
                    n.updated_at,
                    users.full_name,
                    users.mobile,
                    users.email,
                    users.role
             FROM notifications n
             INNER JOIN users ON users.id = n.user_id
             WHERE n.batch_id = :batch_id
             ORDER BY n.id ASC
             LIMIT {$limit}"
        );
        $statement->execute([':batch_id' => $batchId]);

        return $statement->fetchAll();
    }

    public function updateQueuedStatus(string $batchId, string $status, string $errorMessage = ''): int
    {
        $sentSql = $status === 'sent' ? 'sent_at = NOW(),' : 'sent_at = NULL,';
        $statement = admin_db()->prepare(
            "UPDATE notifications
             SET status = :status,
                 {$sentSql}
                 error_message = :error_message,
                 updated_at = NOW()
             WHERE batch_id = :batch_id AND status = 'queued'"
        );
        $statement->execute([
            ':batch_id' => $batchId,
            ':status' => $status,
            ':error_message' => $errorMessage !== '' ? $errorMessage : null,
        ]);

        return $statement->rowCount();
    }

    public function logActivity(int $adminId, string $action, string $description): void
    {
        $statement = admin_db()->prepare(
            'INSERT INTO activity_logs (user_type, user_id, action, module, description, ip_address, user_agent)
             VALUES ("admin", :user_id, :action, "notifications", :description, :ip_address, :user_agent)'
        );
        $statement->execute([
            ':user_id' => $adminId,
            ':action' => $action,
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    private function where(array $filters): array
    {
        $where = [];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(n.title LIKE :search OR n.message LIKE :search OR n.batch_id LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (($filters['audience'] ?? '') !== '') {
            $where[] = 'n.audience = :audience_filter';
            $params[':audience_filter'] = $filters['audience'];
        }
        if (($filters['channel'] ?? '') !== '') {
            $where[] = 'n.channel = :channel_filter';
            $params[':channel_filter'] = $filters['channel'];
        }
        if (($filters['notification_type'] ?? '') !== '') {
            $where[] = 'n.notification_type = :type_filter';
            $params[':type_filter'] = $filters['notification_type'];
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'n.status = :status_filter';
            $params[':status_filter'] = $filters['status'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function audienceWhere(string $audience): array
    {
        if ($audience === 'dealer') {
            return ['AND u.role = :recipient_role', [':recipient_role' => 'dealer']];
        }
        if ($audience === 'installer') {
            return ['AND u.role = :recipient_role', [':recipient_role' => 'installer']];
        }

        return ['', []];
    }

    private function preferenceWhere(string $type): string
    {
        if ($type === 'sales') {
            return 'AND COALESCE(np.sales_notifications, 1) = 1';
        }
        if ($type === 'installation') {
            return 'AND COALESCE(np.installation_notifications, 1) = 1';
        }
        if ($type === 'account') {
            return 'AND COALESCE(np.account_notifications, 1) = 1';
        }
        if ($type === 'marketing') {
            return 'AND COALESCE(np.marketing_notifications, 0) = 1';
        }

        return '';
    }
}
