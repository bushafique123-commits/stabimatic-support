<?php

final class AdminReportRepository
{
    public function summary(array $filters): array
    {
        $this->ensureCommissionImportSchema();

        return [
            'sales' => $this->countSales($filters),
            'installations' => $this->countInstallations($filters),
            'matched_installations' => $this->countInstallationsByMatch($filters, true),
            'unmatched_installations' => $this->countInstallationsByMatch($filters, false),
            'dealer_activity' => $this->countActivity($filters, 'dealer'),
            'installer_activity' => $this->countActivity($filters, 'installer'),
            'admin_activity' => $this->countActivity($filters, 'admin'),
        ];
    }

    public function dailySales(array $filters): array
    {
        $params = [];
        $where = $this->saleDateWhere($filters, $params);
        $statement = admin_db()->prepare(
            "SELECT s.sale_date AS report_date, COUNT(*) AS total
             FROM sales s
             WHERE s.deleted_at IS NULL {$where}
             GROUP BY s.sale_date
             ORDER BY s.sale_date DESC
             LIMIT 14"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function dailyInstallations(array $filters): array
    {
        $params = [];
        $where = $this->installationDateWhere($filters, $params);
        $statement = admin_db()->prepare(
            "SELECT DATE(i.installed_at) AS report_date, COUNT(*) AS total
             FROM installations i
             WHERE i.deleted_at IS NULL {$where}
             GROUP BY DATE(i.installed_at)
             ORDER BY DATE(i.installed_at) DESC
             LIMIT 14"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function topDealers(array $filters, int $limit = 8): array
    {
        $limit = max(1, min($limit, 20));
        $params = [];
        $where = $this->saleDateWhere($filters, $params);
        $statement = admin_db()->prepare(
            "SELECT u.id, u.full_name, u.mobile, COUNT(s.id) AS sales_count
             FROM users u
             INNER JOIN sales s ON s.dealer_id = u.id AND s.deleted_at IS NULL
             WHERE u.role = 'dealer' {$where}
             GROUP BY u.id
             ORDER BY sales_count DESC, u.full_name ASC
             LIMIT {$limit}"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function topInstallers(array $filters, int $limit = 8): array
    {
        $limit = max(1, min($limit, 20));
        $params = [];
        $where = $this->installationDateWhere($filters, $params);
        $statement = admin_db()->prepare(
            "SELECT u.id, u.full_name, u.mobile, COUNT(i.id) AS installations_count
             FROM users u
             INNER JOIN installations i ON i.installer_id = u.id AND i.deleted_at IS NULL
             WHERE u.role = 'installer' {$where}
             GROUP BY u.id
             ORDER BY installations_count DESC, u.full_name ASC
             LIMIT {$limit}"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function unmatchedInstallations(array $filters, int $limit = 10): array
    {
        $this->ensureCommissionImportSchema();

        $limit = max(1, min($limit, 20));
        $params = [];
        $where = $this->installationDateWhere($filters, $params);
        $statement = admin_db()->prepare(
            "SELECT i.id,
                    i.invoice_number,
                    i.device_model,
                    i.serial_number,
                    i.customer_name,
                    i.customer_mobile,
                    i.installed_at,
                    installer.full_name AS installer_name
             FROM installations i
             INNER JOIN users installer ON installer.id = i.installer_id
             LEFT JOIN sales s ON s.device_serial = i.serial_number AND s.device_model = i.device_model AND s.deleted_at IS NULL
             WHERE i.deleted_at IS NULL
               AND s.id IS NULL
               {$where}
             ORDER BY i.installed_at DESC, i.id DESC
             LIMIT {$limit}"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function activityBreakdown(array $filters): array
    {
        $params = [];
        $where = $this->activityDateWhere($filters, $params);
        $statement = admin_db()->prepare(
            "SELECT user_type, module, action, COUNT(*) AS total
             FROM activity_logs
             WHERE 1 = 1 {$where}
             GROUP BY user_type, module, action
             ORDER BY total DESC, user_type ASC, module ASC
             LIMIT 20"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    private function countSales(array $filters): int
    {
        $params = [];
        $where = $this->saleDateWhere($filters, $params);
        $statement = admin_db()->prepare("SELECT COUNT(*) FROM sales s WHERE s.deleted_at IS NULL {$where}");
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function countInstallations(array $filters): int
    {
        $params = [];
        $where = $this->installationDateWhere($filters, $params);
        $statement = admin_db()->prepare("SELECT COUNT(*) FROM installations i WHERE i.deleted_at IS NULL {$where}");
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function countInstallationsByMatch(array $filters, bool $matched): int
    {
        $params = [];
        $where = $this->installationDateWhere($filters, $params);
        $operator = $matched ? 'IS NOT NULL' : 'IS NULL';
        $statement = admin_db()->prepare(
            "SELECT COUNT(*)
             FROM installations i
             LEFT JOIN sales s ON s.device_serial = i.serial_number AND s.device_model = i.device_model AND s.deleted_at IS NULL
             WHERE i.deleted_at IS NULL
               AND s.id {$operator}
               {$where}"
        );
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function countActivity(array $filters, string $userType): int
    {
        $params = [':user_type' => $userType];
        $where = $this->activityDateWhere($filters, $params);
        $statement = admin_db()->prepare("SELECT COUNT(*) FROM activity_logs WHERE user_type = :user_type {$where}");
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function saleDateWhere(array $filters, array &$params): string
    {
        $where = '';
        if (($filters['date_from'] ?? '') !== '') {
            $where .= ' AND s.sale_date >= :sale_date_from';
            $params[':sale_date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where .= ' AND s.sale_date <= :sale_date_to';
            $params[':sale_date_to'] = $filters['date_to'];
        }

        return $where;
    }

    private function installationDateWhere(array $filters, array &$params): string
    {
        $where = '';
        if (($filters['date_from'] ?? '') !== '') {
            $where .= ' AND DATE(i.installed_at) >= :installation_date_from';
            $params[':installation_date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where .= ' AND DATE(i.installed_at) <= :installation_date_to';
            $params[':installation_date_to'] = $filters['date_to'];
        }

        return $where;
    }

    private function activityDateWhere(array $filters, array &$params): string
    {
        $where = '';
        if (($filters['date_from'] ?? '') !== '') {
            $where .= ' AND DATE(created_at) >= :activity_date_from';
            $params[':activity_date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where .= ' AND DATE(created_at) <= :activity_date_to';
            $params[':activity_date_to'] = $filters['date_to'];
        }

        return $where;
    }

    private function ensureCommissionImportSchema(): void
    {
        if (class_exists('AdminCommissionImportRepository')) {
            (new AdminCommissionImportRepository())->ensureSchema();
        }
    }
}
