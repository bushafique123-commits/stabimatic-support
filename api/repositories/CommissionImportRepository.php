<?php

final class CommissionImportRepository extends BaseRepository
{
    public function findByInvoice(string $invoiceNumber): ?array
    {
        $this->ensureSchema();

        return $this->fetchOne(
            'SELECT id, invoice_number, model_number, dealer_commission, installer_commission
             FROM commission_import_rows
             WHERE invoice_number = :invoice_number
             ORDER BY updated_at DESC, id DESC
             LIMIT 1',
            [
                ':invoice_number' => strtoupper(trim($invoiceNumber)),
            ]
        );
    }

    public function findByDeviceSerial(string $deviceSerial): ?array
    {
        return $this->findByInvoice($deviceSerial);
    }

    public function findByDeviceSerialModel(string $deviceSerial, string $modelNumber): ?array
    {
        return $this->findByInvoiceModel($deviceSerial, $modelNumber);
    }

    public function findByInvoiceModel(string $invoiceNumber, string $modelNumber): ?array
    {
        $this->ensureSchema();

        return $this->fetchOne(
            'SELECT id, invoice_number, model_number, dealer_commission, installer_commission
             FROM commission_import_rows
             WHERE invoice_number = :invoice_number AND model_number = :model_number
             LIMIT 1',
            [
                ':invoice_number' => strtoupper(trim($invoiceNumber)),
                ':model_number' => strtoupper(trim($modelNumber)),
            ]
        );
    }

    public function ensureSchema(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        $this->connection()->exec(
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
        $this->connection()->exec('ALTER TABLE commission_import_rows MODIFY invoice_number VARCHAR(120) NOT NULL');

        $this->ensureColumn('installations', 'invoice_number', 'invoice_number VARCHAR(60) NULL AFTER installer_id');
        $this->ensureColumn('installations', 'device_model', 'device_model VARCHAR(120) NULL AFTER invoice_number');
        $this->ensureIndex('installations', 'idx_installations_invoice_model', 'ADD KEY idx_installations_invoice_model (invoice_number, device_model)');

        $ready = true;
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $pdo = $this->connection();
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
        $pdo = $this->connection();
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
