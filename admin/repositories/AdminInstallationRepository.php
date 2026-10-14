<?php

final class AdminInstallationRepository
{
    public function paginate(array $filters, int $page, int $limit): array
    {
        $this->ensureCommissionImportSchema();

        $where = ['i.deleted_at IS NULL'];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(i.invoice_number LIKE :search OR i.device_model LIKE :search OR i.customer_name LIKE :search OR i.customer_mobile LIKE :search OR i.customer_email LIKE :search OR i.serial_number LIKE :search OR installer.full_name LIKE :search OR installer.mobile LIKE :search OR s.invoice_number LIKE :search OR s.device_serial LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (($filters['installer_id'] ?? 0) > 0) {
            $where[] = 'i.installer_id = :installer_id';
            $params[':installer_id'] = (int) $filters['installer_id'];
        }
        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 'DATE(i.installed_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 'DATE(i.installed_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        if (($filters['match'] ?? '') === 'matched') {
            $where[] = 's.id IS NOT NULL';
        }
        if (($filters['match'] ?? '') === 'unmatched') {
            $where[] = 's.id IS NULL';
        }

        $whereSql = implode(' AND ', $where);
        $totalStatement = admin_db()->prepare(
            "SELECT COUNT(*)
             FROM installations i
             INNER JOIN users installer ON installer.id = i.installer_id
             LEFT JOIN sales s ON s.device_serial = i.serial_number AND s.device_model = i.device_model AND s.deleted_at IS NULL
             WHERE {$whereSql}"
        );
        $totalStatement->execute($params);
        $total = (int) $totalStatement->fetchColumn();

        $offset = ($page - 1) * $limit;
        $statement = admin_db()->prepare(
            "SELECT i.id,
                    i.customer_name,
                    i.customer_mobile,
                    i.customer_email,
                    i.invoice_number AS installation_invoice_number,
                    i.device_model AS installation_device_model,
                    i.serial_number,
                    i.installation_picture,
                    i.installed_at,
                    i.created_at,
                    i.updated_at,
                    installer.id AS installer_id,
                    installer.full_name AS installer_name,
                    installer.mobile AS installer_mobile,
                    installer.email AS installer_email,
                    cir.installer_commission,
                    ce.status AS installer_payout_status,
                    ce.amount AS installer_paid_amount,
                    s.id AS sale_id,
                    s.invoice_number,
                    s.sale_date,
                    s.device_model,
                    dealer.id AS dealer_id,
                    dealer.full_name AS dealer_name
             FROM installations i
             INNER JOIN users installer ON installer.id = i.installer_id
             LEFT JOIN sales s ON s.device_serial = i.serial_number AND s.device_model = i.device_model AND s.deleted_at IS NULL
             LEFT JOIN users dealer ON dealer.id = s.dealer_id
             LEFT JOIN commission_import_rows cir ON cir.invoice_number = i.serial_number AND cir.model_number = i.device_model
             LEFT JOIN commission_earnings ce ON ce.source_type = 'installation' AND ce.source_id = i.id AND ce.user_id = i.installer_id
             WHERE {$whereSql}
             ORDER BY i.installed_at DESC, i.id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $statement->execute($params);

        return ['total' => $total, 'items' => $statement->fetchAll()];
    }

    public function find(int $installationId): ?array
    {
        $this->ensureCommissionImportSchema();

        $statement = admin_db()->prepare(
            'SELECT i.id,
                    i.customer_name,
                    i.customer_mobile,
                    i.customer_email,
                    i.invoice_number,
                    i.device_model,
                    i.serial_number,
                    i.installation_picture,
                    i.installed_at,
                    i.created_at,
                    i.updated_at,
                    installer.id AS installer_id,
                    installer.full_name AS installer_name,
                    installer.mobile AS installer_mobile,
                    installer.email AS installer_email,
                    installer.cnic AS installer_cnic,
                    installer.is_active AS installer_is_active,
                    installer.is_verified AS installer_is_verified,
                    cir.installer_commission,
                    cir.dealer_commission,
                    ce.status AS installer_payout_status,
                    ce.amount AS installer_paid_amount
             FROM installations i
             INNER JOIN users installer ON installer.id = i.installer_id
             LEFT JOIN commission_import_rows cir ON cir.invoice_number = i.serial_number AND cir.model_number = i.device_model
             LEFT JOIN commission_earnings ce ON ce.source_type = "installation" AND ce.source_id = i.id AND ce.user_id = i.installer_id
             WHERE i.id = :id AND i.deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute([':id' => $installationId]);
        $installation = $statement->fetch();

        return $installation ?: null;
    }

    public function matchedSale(string $deviceSerial, string $deviceModel): ?array
    {
        $this->ensureCommissionImportSchema();

        $statement = admin_db()->prepare(
            'SELECT s.id,
                    s.invoice_number,
                    s.sale_date,
                    s.customer_name,
                    s.customer_mobile,
                    s.device_model,
                    s.device_serial,
                    dealer.id AS dealer_id,
                    dealer.full_name AS dealer_name,
                    dealer.mobile AS dealer_mobile,
                    dealer.email AS dealer_email
             FROM sales s
             INNER JOIN users dealer ON dealer.id = s.dealer_id
             WHERE s.device_serial = :device_serial AND s.device_model = :device_model AND s.deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute([':device_serial' => $deviceSerial, ':device_model' => $deviceModel]);
        $sale = $statement->fetch();

        return $sale ?: null;
    }

    public function relatedActivity(int $installationId, int $limit = 10): array
    {
        $limit = max(1, min($limit, 20));
        $statement = admin_db()->prepare(
            "SELECT user_type, user_id, action, module, record_id, description, ip_address, created_at
             FROM activity_logs
             WHERE module = 'installations' AND record_id = :record_id
             ORDER BY created_at DESC, id DESC
             LIMIT {$limit}"
        );
        $statement->execute([':record_id' => $installationId]);

        return $statement->fetchAll();
    }

    private function ensureCommissionImportSchema(): void
    {
        if (class_exists('AdminCommissionImportRepository')) {
            (new AdminCommissionImportRepository())->ensureSchema();
        }
    }
}
