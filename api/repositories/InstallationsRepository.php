<?php

final class InstallationsRepository extends BaseRepository
{
    public function paginateForInstaller(int $installerId, array $filters, int $page, int $limit): array
    {
        $this->ensureInvoiceModelColumns();

        $where = ['installer_id = :installer_id', 'deleted_at IS NULL'];
        $params = [':installer_id' => $installerId];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(invoice_number LIKE :search OR device_model LIKE :search OR customer_name LIKE :search OR customer_mobile LIKE :search OR customer_email LIKE :search OR serial_number LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) $this->fetchOne(
            "SELECT COUNT(*) AS total FROM installations WHERE {$whereSql}",
            $params
        )['total'];

        $offset = Paginator::offset($page, $limit);
        $items = $this->fetchAll(
            "SELECT id, invoice_number, device_model, customer_name, customer_mobile, customer_email, serial_number, installation_picture, installed_at, created_at, updated_at
             FROM installations
             WHERE {$whereSql}
             ORDER BY installed_at DESC, id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        return ['total' => $total, 'items' => $items];
    }

    public function findForInstaller(int $installerId, int $installationId): ?array
    {
        $this->ensureInvoiceModelColumns();

        return $this->fetchOne(
            'SELECT id, invoice_number, device_model, customer_name, customer_mobile, customer_email, serial_number, installation_picture, installed_at, created_at, updated_at
             FROM installations
             WHERE id = :id AND installer_id = :installer_id AND deleted_at IS NULL
             LIMIT 1',
            [':id' => $installationId, ':installer_id' => $installerId]
        );
    }

    public function create(int $installerId, array $data): int
    {
        $this->ensureInvoiceModelColumns();

        $this->execute(
            'INSERT INTO installations
             (installer_id, invoice_number, device_model, customer_name, customer_mobile, customer_email, serial_number, installation_picture, installed_at)
             VALUES
             (:installer_id, :invoice_number, :device_model, :customer_name, :customer_mobile, :customer_email, :serial_number, :installation_picture, :installed_at)',
            [
                ':installer_id' => $installerId,
                ':invoice_number' => $data['invoice_number'],
                ':device_model' => $data['device_model'],
                ':customer_name' => $data['customer_name'],
                ':customer_mobile' => $data['customer_mobile'],
                ':customer_email' => $data['customer_email'] ?: null,
                ':serial_number' => $data['serial_number'],
                ':installation_picture' => $data['installation_picture'],
                ':installed_at' => $data['installed_at'],
            ]
        );

        return $this->lastInsertId();
    }

    public function update(int $installerId, int $installationId, array $data): bool
    {
        $this->ensureInvoiceModelColumns();

        return $this->execute(
            'UPDATE installations
             SET invoice_number = :invoice_number,
                 device_model = :device_model,
                 customer_name = :customer_name,
                 customer_mobile = :customer_mobile,
                 customer_email = :customer_email,
                 serial_number = :serial_number,
                 installation_picture = :installation_picture,
                 installed_at = :installed_at
             WHERE id = :id AND installer_id = :installer_id AND deleted_at IS NULL',
            [
                ':id' => $installationId,
                ':installer_id' => $installerId,
                ':invoice_number' => $data['invoice_number'],
                ':device_model' => $data['device_model'],
                ':customer_name' => $data['customer_name'],
                ':customer_mobile' => $data['customer_mobile'],
                ':customer_email' => $data['customer_email'] ?: null,
                ':serial_number' => $data['serial_number'],
                ':installation_picture' => $data['installation_picture'],
                ':installed_at' => $data['installed_at'],
            ]
        );
    }

    public function softDelete(int $installerId, int $installationId): bool
    {
        return $this->execute(
            'UPDATE installations SET deleted_at = NOW() WHERE id = :id AND installer_id = :installer_id AND deleted_at IS NULL',
            [':id' => $installationId, ':installer_id' => $installerId]
        );
    }

    public function countForInstaller(int $installerId): int
    {
        return (int) $this->fetchOne(
            'SELECT COUNT(*) AS total FROM installations WHERE installer_id = :installer_id AND deleted_at IS NULL',
            [':installer_id' => $installerId]
        )['total'];
    }

    public function countForInstallerToday(int $installerId): int
    {
        return (int) $this->fetchOne(
            'SELECT COUNT(*) AS total
             FROM installations
             WHERE installer_id = :installer_id
               AND deleted_at IS NULL
               AND DATE(installed_at) = CURDATE()',
            [':installer_id' => $installerId]
        )['total'];
    }

    public function countForInstallerThisMonth(int $installerId): int
    {
        return (int) $this->fetchOne(
            'SELECT COUNT(*) AS total
             FROM installations
             WHERE installer_id = :installer_id
               AND deleted_at IS NULL
               AND installed_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")
               AND installed_at < DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY)',
            [':installer_id' => $installerId]
        )['total'];
    }

    public function recentForInstaller(int $installerId, int $limit = 5): array
    {
        $limit = max(1, min($limit, 10));

        return $this->fetchAll(
            "SELECT id, invoice_number, device_model, customer_name, customer_mobile, customer_email, serial_number, installation_picture, installed_at, created_at
             FROM installations
             WHERE installer_id = :installer_id
               AND deleted_at IS NULL
             ORDER BY installed_at DESC, id DESC
             LIMIT {$limit}",
            [':installer_id' => $installerId]
        );
    }

    private function ensureInvoiceModelColumns(): void
    {
        if (class_exists('CommissionImportRepository')) {
            (new CommissionImportRepository())->ensureSchema();
        }
    }
}
