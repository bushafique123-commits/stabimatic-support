<?php

final class AdminDealerRepository
{
    public function paginate(array $filters, int $page, int $limit): array
    {
        $where = ['u.role = "dealer"'];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(u.full_name LIKE :search OR u.mobile LIKE :search OR u.email LIKE :search OR u.cnic LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (($filters['status'] ?? '') === 'active') {
            $where[] = 'u.is_active = 1';
        }
        if (($filters['status'] ?? '') === 'inactive') {
            $where[] = 'u.is_active = 0';
        }
        if (($filters['verification'] ?? '') === 'verified') {
            $where[] = 'u.is_verified = 1';
        }
        if (($filters['verification'] ?? '') === 'pending') {
            $where[] = 'u.is_verified = 0';
        }

        $whereSql = implode(' AND ', $where);
        $totalStatement = admin_db()->prepare("SELECT COUNT(*) FROM users u WHERE {$whereSql}");
        $totalStatement->execute($params);
        $total = (int) $totalStatement->fetchColumn();

        $offset = ($page - 1) * $limit;
        $statement = admin_db()->prepare(
            "SELECT u.id,
                    u.full_name,
                    u.mobile,
                    u.email,
                    u.cnic,
                    u.is_verified,
                    u.is_active,
                    u.mobile_verified_at,
                    u.email_verified_at,
                    u.created_at,
                    COUNT(s.id) AS sales_count
             FROM users u
             LEFT JOIN sales s ON s.dealer_id = u.id AND s.deleted_at IS NULL
             WHERE {$whereSql}
             GROUP BY u.id
             ORDER BY u.created_at DESC, u.id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $statement->execute($params);

        return ['total' => $total, 'items' => $statement->fetchAll()];
    }

    public function find(int $id): ?array
    {
        $statement = admin_db()->prepare(
            'SELECT id, full_name, mobile, email, cnic, bank_name, bank_account_title, bank_account_number, iban,
                    is_verified, is_active, mobile_verified_at, email_verified_at, created_at, updated_at
             FROM users
             WHERE id = :id AND role = "dealer"
             LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $dealer = $statement->fetch();

        return $dealer ?: null;
    }

    public function stats(int $dealerId): array
    {
        $this->ensureCommissionImportSchema();

        return [
            'sales' => $this->count('SELECT COUNT(*) FROM sales WHERE dealer_id = :dealer_id AND deleted_at IS NULL', $dealerId),
            'sales_today' => $this->count('SELECT COUNT(*) FROM sales WHERE dealer_id = :dealer_id AND deleted_at IS NULL AND sale_date = CURDATE()', $dealerId),
            'installations_matched' => $this->count(
                'SELECT COUNT(*)
                 FROM installations i
                 INNER JOIN sales s ON s.device_serial = i.serial_number AND s.device_model = i.device_model AND s.deleted_at IS NULL
                 WHERE s.dealer_id = :dealer_id AND i.deleted_at IS NULL',
                $dealerId
            ),
        ];
    }

    public function recentSales(int $dealerId, int $limit = 8): array
    {
        $limit = max(1, min($limit, 20));
        $statement = admin_db()->prepare(
            "SELECT id, invoice_number, sale_date, customer_name, customer_mobile, device_model, device_serial
             FROM sales
             WHERE dealer_id = :dealer_id AND deleted_at IS NULL
             ORDER BY sale_date DESC, id DESC
             LIMIT {$limit}"
        );
        $statement->execute([':dealer_id' => $dealerId]);

        return $statement->fetchAll();
    }

    public function setActive(int $dealerId, bool $active): void
    {
        $statement = admin_db()->prepare('UPDATE users SET is_active = :active WHERE id = :id AND role = "dealer"');
        $statement->execute([':id' => $dealerId, ':active' => $active ? 1 : 0]);
    }

    public function setVerified(int $dealerId, bool $verified): void
    {
        $statement = admin_db()->prepare('UPDATE users SET is_verified = :verified WHERE id = :id AND role = "dealer"');
        $statement->execute([':id' => $dealerId, ':verified' => $verified ? 1 : 0]);
    }

    public function logActivity(int $adminId, int $dealerId, string $action, string $description): void
    {
        $statement = admin_db()->prepare(
            'INSERT INTO activity_logs (user_type, user_id, action, module, record_id, description, ip_address, user_agent)
             VALUES ("admin", :user_id, :action, "dealers", :record_id, :description, :ip_address, :user_agent)'
        );
        $statement->execute([
            ':user_id' => $adminId,
            ':action' => $action,
            ':record_id' => $dealerId,
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    private function count(string $sql, int $dealerId): int
    {
        $statement = admin_db()->prepare($sql);
        $statement->execute([':dealer_id' => $dealerId]);
        return (int) $statement->fetchColumn();
    }

    private function ensureCommissionImportSchema(): void
    {
        if (class_exists('AdminCommissionImportRepository')) {
            (new AdminCommissionImportRepository())->ensureSchema();
        }
    }
}
