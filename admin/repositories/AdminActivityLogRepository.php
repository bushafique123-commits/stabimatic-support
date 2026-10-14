<?php

final class AdminActivityLogRepository
{
    public function paginate(array $filters, int $page, int $limit): array
    {
        $where = [];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(a.action LIKE :search OR a.module LIKE :search OR a.description LIKE :search OR a.ip_address LIKE :search OR a.user_agent LIKE :search OR CAST(a.user_id AS CHAR) LIKE :search OR CAST(a.record_id AS CHAR) LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (($filters['user_type'] ?? '') !== '') {
            $where[] = 'a.user_type = :user_type';
            $params[':user_type'] = $filters['user_type'];
        }
        if (($filters['module'] ?? '') !== '') {
            $where[] = 'a.module = :module';
            $params[':module'] = $filters['module'];
        }
        if (($filters['action'] ?? '') !== '') {
            $where[] = 'a.action = :action';
            $params[':action'] = $filters['action'];
        }
        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 'DATE(a.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 'DATE(a.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $totalStatement = admin_db()->prepare("SELECT COUNT(*) FROM activity_logs a {$whereSql}");
        $totalStatement->execute($params);
        $total = (int) $totalStatement->fetchColumn();

        $offset = ($page - 1) * $limit;
        $statement = admin_db()->prepare(
            "SELECT a.id,
                    a.user_type,
                    a.user_id,
                    a.action,
                    a.module,
                    a.record_id,
                    a.description,
                    a.ip_address,
                    a.user_agent,
                    a.created_at,
                    COALESCE(admins.name, users.full_name) AS actor_name,
                    COALESCE(admins.email, users.email) AS actor_email,
                    users.mobile AS actor_mobile
             FROM activity_logs a
             LEFT JOIN admin_users admins ON a.user_type = 'admin' AND admins.id = a.user_id
             LEFT JOIN users ON a.user_type IN ('dealer', 'installer') AND users.id = a.user_id
             {$whereSql}
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $statement->execute($params);

        return ['total' => $total, 'items' => $statement->fetchAll()];
    }

    public function find(int $activityLogId): ?array
    {
        $statement = admin_db()->prepare(
            "SELECT a.id,
                    a.user_type,
                    a.user_id,
                    a.action,
                    a.module,
                    a.record_id,
                    a.description,
                    a.ip_address,
                    a.user_agent,
                    a.created_at,
                    COALESCE(admins.name, users.full_name) AS actor_name,
                    COALESCE(admins.email, users.email) AS actor_email,
                    users.mobile AS actor_mobile
             FROM activity_logs a
             LEFT JOIN admin_users admins ON a.user_type = 'admin' AND admins.id = a.user_id
             LEFT JOIN users ON a.user_type IN ('dealer', 'installer') AND users.id = a.user_id
             WHERE a.id = :id
             LIMIT 1"
        );
        $statement->execute([':id' => $activityLogId]);
        $activity = $statement->fetch();

        return $activity ?: null;
    }

    public function modules(): array
    {
        $statement = admin_db()->query(
            "SELECT DISTINCT module
             FROM activity_logs
             WHERE module <> ''
             ORDER BY module ASC
             LIMIT 100"
        );

        return array_column($statement->fetchAll(), 'module');
    }

    public function actions(): array
    {
        $statement = admin_db()->query(
            "SELECT DISTINCT action
             FROM activity_logs
             WHERE action <> ''
             ORDER BY action ASC
             LIMIT 100"
        );

        return array_column($statement->fetchAll(), 'action');
    }
}
