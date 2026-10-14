<?php

final class AdminSaleRepository
{
    public function paginate(array $filters, int $page, int $limit): array
    {
        $this->ensureCommissionImportSchema();

        $where = ['s.deleted_at IS NULL'];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(s.invoice_number LIKE :search OR s.customer_name LIKE :search OR s.customer_mobile LIKE :search OR s.device_model LIKE :search OR s.device_serial LIKE :search OR u.full_name LIKE :search OR u.mobile LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (($filters['dealer_id'] ?? 0) > 0) {
            $where[] = 's.dealer_id = :dealer_id';
            $params[':dealer_id'] = (int) $filters['dealer_id'];
        }
        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 's.sale_date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 's.sale_date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        if (($filters['match'] ?? '') === 'matched') {
            $where[] = 'EXISTS (SELECT 1 FROM installations ix WHERE ix.serial_number = s.device_serial AND ix.device_model = s.device_model AND ix.deleted_at IS NULL)';
        }
        if (($filters['match'] ?? '') === 'unmatched') {
            $where[] = 'NOT EXISTS (SELECT 1 FROM installations ix WHERE ix.serial_number = s.device_serial AND ix.device_model = s.device_model AND ix.deleted_at IS NULL)';
        }

        $whereSql = implode(' AND ', $where);
        $totalStatement = admin_db()->prepare(
            "SELECT COUNT(*)
             FROM sales s
             INNER JOIN users u ON u.id = s.dealer_id
             WHERE {$whereSql}"
        );
        $totalStatement->execute($params);
        $total = (int) $totalStatement->fetchColumn();

        $offset = ($page - 1) * $limit;
        $statement = admin_db()->prepare(
            "SELECT s.id,
                    s.invoice_number,
                    s.sale_date,
                    s.customer_name,
                    s.customer_mobile,
                    s.device_model,
                    s.device_serial,
                    s.created_at,
                    s.updated_at,
                    u.id AS dealer_id,
                    u.full_name AS dealer_name,
                    u.mobile AS dealer_mobile,
                    u.email AS dealer_email,
                    cir.dealer_commission,
                    ce.status AS dealer_payout_status,
                    ce.amount AS dealer_paid_amount,
                    COUNT(i.id) AS installations_count,
                    MAX(i.installed_at) AS latest_installation_at
             FROM sales s
             INNER JOIN users u ON u.id = s.dealer_id
             LEFT JOIN installations i ON i.serial_number = s.device_serial AND i.device_model = s.device_model AND i.deleted_at IS NULL
             LEFT JOIN commission_import_rows cir ON cir.invoice_number = s.device_serial AND cir.model_number = s.device_model
             LEFT JOIN commission_earnings ce ON ce.source_type = 'sale' AND ce.source_id = s.id AND ce.user_id = s.dealer_id
             WHERE {$whereSql}
             GROUP BY s.id
             ORDER BY s.sale_date DESC, s.id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $statement->execute($params);

        return ['total' => $total, 'items' => $statement->fetchAll()];
    }

    public function find(int $saleId): ?array
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
                    s.created_at,
                    s.updated_at,
                    u.id AS dealer_id,
                    u.full_name AS dealer_name,
                    u.mobile AS dealer_mobile,
                    u.email AS dealer_email,
                    u.cnic AS dealer_cnic,
                    u.is_active AS dealer_is_active,
                    u.is_verified AS dealer_is_verified,
                    cir.dealer_commission,
                    cir.installer_commission,
                    ce.status AS dealer_payout_status,
                    ce.amount AS dealer_paid_amount
             FROM sales s
             INNER JOIN users u ON u.id = s.dealer_id
             LEFT JOIN commission_import_rows cir ON cir.invoice_number = s.device_serial AND cir.model_number = s.device_model
             LEFT JOIN commission_earnings ce ON ce.source_type = "sale" AND ce.source_id = s.id AND ce.user_id = s.dealer_id
             WHERE s.id = :id AND s.deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute([':id' => $saleId]);
        $sale = $statement->fetch();

        return $sale ?: null;
    }

    public function matchedInstallations(int $saleId, int $limit = 20): array
    {
        $sale = $this->find($saleId);
        if (!$sale) {
            return [];
        }

        $limit = max(1, min($limit, 50));
        $statement = admin_db()->prepare(
            "SELECT i.id,
                    i.customer_name,
                    i.customer_mobile,
                    i.customer_email,
                    i.serial_number,
                    i.installation_picture,
                    i.installed_at,
                    i.created_at,
                    u.id AS installer_id,
                    u.full_name AS installer_name,
                    u.mobile AS installer_mobile,
                    u.email AS installer_email
             FROM installations i
             INNER JOIN users u ON u.id = i.installer_id
             WHERE i.serial_number = :device_serial AND i.device_model = :device_model AND i.deleted_at IS NULL
             ORDER BY i.installed_at DESC, i.id DESC
             LIMIT {$limit}"
        );
        $statement->execute([':device_serial' => $sale['device_serial'], ':device_model' => $sale['device_model']]);

        return $statement->fetchAll();
    }

    private function ensureCommissionImportSchema(): void
    {
        if (class_exists('AdminCommissionImportRepository')) {
            (new AdminCommissionImportRepository())->ensureSchema();
        }
    }

    public function relatedActivity(int $saleId, int $limit = 10): array
    {
        $limit = max(1, min($limit, 20));
        $statement = admin_db()->prepare(
            "SELECT user_type, user_id, action, module, record_id, description, ip_address, created_at
             FROM activity_logs
             WHERE module = 'sales' AND record_id = :record_id
             ORDER BY created_at DESC, id DESC
             LIMIT {$limit}"
        );
        $statement->execute([':record_id' => $saleId]);

        return $statement->fetchAll();
    }
}
