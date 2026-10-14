<?php

final class AdminCommissionImportRepository
{
    public function importRows(array $rows, int $adminId): int
    {
        $this->ensureSchema();

        if (!$rows) {
            return 0;
        }

        $statement = admin_db()->prepare(
            'INSERT INTO commission_import_rows
             (invoice_number, model_number, dealer_commission, installer_commission, uploaded_by_admin_id)
             VALUES (:invoice_number, :model_number, :dealer_commission, :installer_commission, :uploaded_by_admin_id)
             ON DUPLICATE KEY UPDATE
               dealer_commission = VALUES(dealer_commission),
               installer_commission = VALUES(installer_commission),
               uploaded_by_admin_id = VALUES(uploaded_by_admin_id),
               updated_at = NOW()'
        );

        $count = 0;
        foreach ($rows as $row) {
            $statement->execute([
                ':invoice_number' => $row['invoice_number'],
                ':model_number' => $row['model_number'],
                ':dealer_commission' => number_format((float) $row['dealer_commission'], 2, '.', ''),
                ':installer_commission' => number_format((float) $row['installer_commission'], 2, '.', ''),
                ':uploaded_by_admin_id' => $adminId,
            ]);
            $count++;
        }

        return $count;
    }

    public function paginate(array $filters, int $page, int $limit): array
    {
        $this->ensureSchema();

        $where = [];
        $params = [];
        if (($filters['search'] ?? '') !== '') {
            $where[] = '(invoice_number LIKE :search OR model_number LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $totalStatement = admin_db()->prepare("SELECT COUNT(*) FROM commission_import_rows {$whereSql}");
        $totalStatement->execute($params);
        $total = (int) $totalStatement->fetchColumn();

        $offset = ($page - 1) * $limit;
        $statement = admin_db()->prepare(
            "SELECT id, invoice_number, model_number, dealer_commission, installer_commission, uploaded_by_admin_id, created_at, updated_at
             FROM commission_import_rows
             {$whereSql}
             ORDER BY updated_at DESC, id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $statement->execute($params);

        return ['total' => $total, 'items' => $statement->fetchAll()];
    }

    public function stats(): array
    {
        $this->ensureSchema();

        $row = admin_db()->query(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(dealer_commission), 0) AS dealer_total,
                    COALESCE(SUM(installer_commission), 0) AS installer_total,
                    MAX(updated_at) AS latest_upload
             FROM commission_import_rows'
        )->fetch();

        return $row ?: ['total' => 0, 'dealer_total' => 0, 'installer_total' => 0, 'latest_upload' => null];
    }

    public function findByInvoiceModel(string $invoiceNumber, string $modelNumber): ?array
    {
        $this->ensureSchema();

        $statement = admin_db()->prepare(
            'SELECT id, invoice_number, model_number, dealer_commission, installer_commission
             FROM commission_import_rows
             WHERE invoice_number = :invoice_number AND model_number = :model_number
             LIMIT 1'
        );
        $statement->execute([
            ':invoice_number' => strtoupper(trim($invoiceNumber)),
            ':model_number' => strtoupper(trim($modelNumber)),
        ]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function ensureSchema(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        admin_db()->exec(
            "CREATE TABLE IF NOT EXISTS commission_import_rows (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              invoice_number VARCHAR(120) NOT NULL,
              model_number VARCHAR(120) NOT NULL,
              dealer_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
              installer_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
              uploaded_by_admin_id BIGINT UNSIGNED NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY uq_commission_import_invoice_model (invoice_number, model_number),
              KEY idx_commission_import_invoice_number (invoice_number),
              KEY idx_commission_import_model_number (model_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        admin_db()->exec('ALTER TABLE commission_import_rows MODIFY invoice_number VARCHAR(120) NOT NULL');

        $this->ensureColumn('installations', 'invoice_number', 'invoice_number VARCHAR(60) NULL AFTER installer_id');
        $this->ensureColumn('installations', 'device_model', 'device_model VARCHAR(120) NULL AFTER invoice_number');
        $this->ensureIndex('installations', 'idx_installations_invoice_model', 'ADD KEY idx_installations_invoice_model (invoice_number, device_model)');

        $ready = true;
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $pdo = admin_db();
        $database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        $statement = $pdo->prepare(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name"
        );
        $statement->execute([':schema' => $database, ':table_name' => $table, ':column_name' => $column]);

        if ((int) $statement->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$definition}");
        }
    }

    private function ensureIndex(string $table, string $index, string $definition): void
    {
        $pdo = admin_db();
        $database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        $statement = $pdo->prepare(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table_name AND INDEX_NAME = :index_name"
        );
        $statement->execute([':schema' => $database, ':table_name' => $table, ':index_name' => $index]);

        if ((int) $statement->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE {$table} {$definition}");
        }
    }
}
