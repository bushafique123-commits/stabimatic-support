<?php

final class AdminCommissionRepository
{
    public function stats(): array
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->query(
            "SELECT status, COUNT(*) AS total, COALESCE(SUM(amount), 0) AS amount
             FROM commission_earnings
             GROUP BY status"
        );

        $stats = [
            'pending_count' => 0,
            'pending_amount' => 0.0,
            'approved_count' => 0,
            'approved_amount' => 0.0,
            'paid_count' => 0,
            'paid_amount' => 0.0,
            'cancelled_count' => 0,
            'cancelled_amount' => 0.0,
        ];

        foreach ($statement->fetchAll() as $row) {
            $status = $row['status'];
            $stats[$status . '_count'] = (int) $row['total'];
            $stats[$status . '_amount'] = (float) $row['amount'];
        }

        $payoutStatement = admin_db()->query(
            "SELECT status, COUNT(*) AS total, COALESCE(SUM(amount), 0) AS amount
             FROM commission_payouts
             GROUP BY status"
        );

        $stats['pending_payout_count'] = 0;
        $stats['pending_payout_amount'] = 0.0;
        foreach ($payoutStatement->fetchAll() as $row) {
            if ($row['status'] === 'pending') {
                $stats['pending_payout_count'] = (int) $row['total'];
                $stats['pending_payout_amount'] = (float) $row['amount'];
            }
        }

        return $stats;
    }

    public function rules(): array
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->query(
            "SELECT id, role, event_type, amount, currency, is_active, created_at, updated_at
             FROM commission_rules
             WHERE is_active = 1
             ORDER BY role, event_type"
        );

        return $statement->fetchAll();
    }

    public function saveRule(string $role, string $eventType, float $amount): void
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->prepare(
            'INSERT INTO commission_rules (role, event_type, amount, currency, is_active)
             VALUES (:role, :event_type, :amount, "PKR", 1)
             ON DUPLICATE KEY UPDATE amount = VALUES(amount), currency = VALUES(currency), is_active = 1'
        );
        $statement->execute([
            ':role' => $role,
            ':event_type' => $eventType,
            ':amount' => number_format($amount, 2, '.', ''),
        ]);
    }

    public function dealerRates(): array
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->query(
            "SELECT u.id,
                    u.full_name,
                    u.mobile,
                    u.email,
                    u.is_active,
                    u.is_verified,
                    ucr.amount AS custom_amount,
                    ucr.is_active AS custom_is_active
             FROM users u
             LEFT JOIN user_commission_rules ucr
               ON ucr.user_id = u.id
              AND ucr.role = 'dealer'
              AND ucr.event_type = 'sale'
              AND ucr.is_active = 1
             WHERE u.role = 'dealer'
             ORDER BY u.full_name, u.id"
        );

        return $statement->fetchAll();
    }

    public function installers(): array
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->query(
            "SELECT id, full_name, mobile, email, is_active, is_verified
             FROM users
             WHERE role = 'installer'
             ORDER BY full_name, id"
        );

        return $statement->fetchAll();
    }

    public function saveDealerRates(array $rates): int
    {
        if (!$rates) {
            return 0;
        }

        $this->ensureCommissionSchema();

        $pdo = admin_db();
        $pdo->beginTransaction();

        try {
            $upsert = $pdo->prepare(
                'INSERT INTO user_commission_rules (user_id, role, event_type, amount, currency, is_active)
                 VALUES (:user_id, "dealer", "sale", :amount, "PKR", 1)
                 ON DUPLICATE KEY UPDATE amount = VALUES(amount), currency = VALUES(currency), is_active = 1'
            );
            $deactivate = $pdo->prepare(
                'UPDATE user_commission_rules
                 SET is_active = 0, updated_at = NOW()
                 WHERE user_id = :user_id AND role = "dealer" AND event_type = "sale"'
            );

            $updated = 0;
            foreach ($rates as $dealerId => $amount) {
                if ($amount === null) {
                    $deactivate->execute([':user_id' => (int) $dealerId]);
                    $updated += $deactivate->rowCount();
                    continue;
                }

                $upsert->execute([
                    ':user_id' => (int) $dealerId,
                    ':amount' => number_format((float) $amount, 2, '.', ''),
                ]);
                $updated += $upsert->rowCount();
            }

            $pdo->commit();
            return $updated;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function dealerExists(int $dealerId): bool
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->prepare(
            'SELECT COUNT(*)
             FROM users
             WHERE id = :id AND role = "dealer"'
        );
        $statement->execute([':id' => $dealerId]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function installerExists(int $installerId): bool
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->prepare(
            'SELECT COUNT(*)
             FROM users
             WHERE id = :id AND role = "installer"'
        );
        $statement->execute([':id' => $installerId]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function generateDealerEarnings(int $dealerId, float $amount): array
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->prepare(
            "INSERT INTO commission_earnings (user_id, role, source_type, source_id, amount, currency, earned_at)
             VALUES (:dealer_id, 'dealer', 'manual', :source_id, :amount, 'PKR', NOW())"
        );
        $statement->execute([
            ':dealer_id' => $dealerId,
            ':source_id' => $this->manualSourceId(),
            ':amount' => number_format($amount, 2, '.', ''),
        ]);

        return ['eligible' => 1, 'locked' => 0, 'affected' => $statement->rowCount()];
    }

    public function generateInstallerEarnings(int $installerId, float $amount): array
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->prepare(
            "INSERT INTO commission_earnings (user_id, role, source_type, source_id, amount, currency, earned_at)
             VALUES (:installer_id, 'installer', 'manual', :source_id, :amount, 'PKR', NOW())"
        );
        $statement->execute([
            ':installer_id' => $installerId,
            ':source_id' => $this->manualSourceId(),
            ':amount' => number_format($amount, 2, '.', ''),
        ]);

        return ['eligible' => 1, 'locked' => 0, 'affected' => $statement->rowCount()];
    }

    public function markSalePaid(int $saleId, float $amount): bool
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->prepare(
            "INSERT INTO commission_earnings
             (user_id, role, source_type, source_id, amount, currency, status, earned_at, approved_at, paid_at)
             SELECT dealer_id, 'dealer', 'sale', id, :amount, 'PKR', 'paid', CONCAT(sale_date, ' 00:00:00'), NOW(), NOW()
             FROM sales
             WHERE id = :source_id AND deleted_at IS NULL
             ON DUPLICATE KEY UPDATE
               amount = VALUES(amount),
               status = 'paid',
               payout_id = NULL,
               approved_at = COALESCE(commission_earnings.approved_at, NOW()),
               paid_at = NOW(),
               updated_at = NOW()"
        );
        $statement->execute([
            ':source_id' => $saleId,
            ':amount' => number_format($amount, 2, '.', ''),
        ]);

        return $statement->rowCount() > 0;
    }

    public function markInstallationPaid(int $installationId, float $amount): bool
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->prepare(
            "INSERT INTO commission_earnings
             (user_id, role, source_type, source_id, amount, currency, status, earned_at, approved_at, paid_at)
             SELECT installer_id, 'installer', 'installation', id, :amount, 'PKR', 'paid', installed_at, NOW(), NOW()
             FROM installations
             WHERE id = :source_id AND deleted_at IS NULL
             ON DUPLICATE KEY UPDATE
               amount = VALUES(amount),
               status = 'paid',
               payout_id = NULL,
               approved_at = COALESCE(commission_earnings.approved_at, NOW()),
               paid_at = NOW(),
               updated_at = NOW()"
        );
        $statement->execute([
            ':source_id' => $installationId,
            ':amount' => number_format($amount, 2, '.', ''),
        ]);

        return $statement->rowCount() > 0;
    }

    public function generateEarnings(): array
    {
        $this->ensureCommissionSchema();

        $dealerStatement = admin_db()->prepare(
            "INSERT INTO commission_earnings (user_id, role, source_type, source_id, amount, currency, earned_at)
             SELECT s.dealer_id,
                    'dealer',
                    'sale',
                    s.id,
                    COALESCE(custom_rule.amount, rule.amount),
                    COALESCE(custom_rule.currency, rule.currency),
                    CONCAT(s.sale_date, ' 00:00:00')
             FROM sales s
             INNER JOIN users dealer ON dealer.id = s.dealer_id AND dealer.role = 'dealer'
             INNER JOIN (
               SELECT amount, currency
               FROM commission_rules
               WHERE role = 'dealer' AND event_type = 'sale' AND is_active = 1
               ORDER BY id DESC
               LIMIT 1
             ) rule
             LEFT JOIN user_commission_rules custom_rule
               ON custom_rule.user_id = s.dealer_id
              AND custom_rule.role = 'dealer'
              AND custom_rule.event_type = 'sale'
              AND custom_rule.is_active = 1
             WHERE s.deleted_at IS NULL AND COALESCE(custom_rule.amount, rule.amount) > 0
             ON DUPLICATE KEY UPDATE
               amount = IF(commission_earnings.status = 'pending', VALUES(amount), commission_earnings.amount),
               updated_at = NOW()"
        );
        $dealerStatement->execute();

        $installerStatement = admin_db()->prepare(
            "INSERT INTO commission_earnings (user_id, role, source_type, source_id, amount, currency, earned_at)
             SELECT i.installer_id, 'installer', 'installation', i.id, rule.amount, rule.currency, i.installed_at
             FROM installations i
             INNER JOIN (
               SELECT amount, currency
               FROM commission_rules
               WHERE role = 'installer' AND event_type = 'installation' AND is_active = 1
               ORDER BY id DESC
               LIMIT 1
             ) rule
             WHERE i.deleted_at IS NULL AND rule.amount > 0
             ON DUPLICATE KEY UPDATE
               amount = IF(commission_earnings.status = 'pending', VALUES(amount), commission_earnings.amount),
               updated_at = NOW()"
        );
        $installerStatement->execute();

        return [
            'dealer' => $dealerStatement->rowCount(),
            'installer' => $installerStatement->rowCount(),
        ];
    }

    public function paginateEarnings(array $filters, int $page, int $limit): array
    {
        $this->ensureCommissionSchema();

        [$whereSql, $params] = $this->earningWhere($filters);
        $totalStatement = admin_db()->prepare(
            "SELECT COUNT(*)
             FROM commission_earnings ce
             INNER JOIN users u ON u.id = ce.user_id
             LEFT JOIN sales s ON ce.source_type = 'sale' AND s.id = ce.source_id
             LEFT JOIN installations i ON ce.source_type = 'installation' AND i.id = ce.source_id
             {$whereSql}"
        );
        $totalStatement->execute($params);
        $total = (int) $totalStatement->fetchColumn();

        $offset = ($page - 1) * $limit;
        $statement = admin_db()->prepare(
            "SELECT ce.id,
                    ce.user_id,
                    ce.role,
                    ce.source_type,
                    ce.source_id,
                    ce.amount,
                    ce.currency,
                    ce.status,
                    ce.payout_id,
                    ce.earned_at,
                    ce.approved_at,
                    ce.paid_at,
                    u.full_name,
                    u.mobile,
                    CASE
                      WHEN ce.source_type = 'manual' THEN CONCAT('Manual #', ce.id)
                      WHEN ce.source_type = 'installation' THEN COALESCE(i.serial_number, i.invoice_number)
                      ELSE COALESCE(s.device_serial, s.invoice_number)
                    END AS source_label
             FROM commission_earnings ce
             INNER JOIN users u ON u.id = ce.user_id
             LEFT JOIN sales s ON ce.source_type = 'sale' AND s.id = ce.source_id
             LEFT JOIN installations i ON ce.source_type = 'installation' AND i.id = ce.source_id
             {$whereSql}
             ORDER BY ce.earned_at DESC, ce.id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $statement->execute($params);

        return ['total' => $total, 'items' => $statement->fetchAll()];
    }

    public function approvePending(): int
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->prepare(
            "UPDATE commission_earnings
             SET status = 'approved', approved_at = NOW(), updated_at = NOW()
             WHERE status = 'pending'"
        );
        $statement->execute();

        return $statement->rowCount();
    }

    public function createPayoutsForApproved(int $adminId): int
    {
        $this->ensureCommissionSchema();

        $pdo = admin_db();
        $pdo->beginTransaction();

        try {
            $groups = $pdo->query(
                "SELECT user_id, role, COALESCE(SUM(amount), 0) AS amount
                 FROM commission_earnings
                 WHERE status = 'approved' AND payout_id IS NULL
                 GROUP BY user_id, role
                 HAVING amount > 0"
            )->fetchAll();

            $created = 0;
            $insert = $pdo->prepare(
                'INSERT INTO commission_payouts (user_id, role, amount, status, created_by_admin_id)
                 VALUES (:user_id, :role, :amount, "pending", :created_by_admin_id)'
            );
            $update = $pdo->prepare(
                "UPDATE commission_earnings
                 SET payout_id = :payout_id, updated_at = NOW()
                 WHERE user_id = :user_id AND role = :role AND status = 'approved' AND payout_id IS NULL"
            );

            foreach ($groups as $group) {
                $insert->execute([
                    ':user_id' => (int) $group['user_id'],
                    ':role' => $group['role'],
                    ':amount' => number_format((float) $group['amount'], 2, '.', ''),
                    ':created_by_admin_id' => $adminId,
                ]);
                $payoutId = (int) $pdo->lastInsertId();
                $update->execute([
                    ':payout_id' => $payoutId,
                    ':user_id' => (int) $group['user_id'],
                    ':role' => $group['role'],
                ]);
                $created++;
            }

            $pdo->commit();
            return $created;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function recentPayouts(int $limit = 20): array
    {
        $this->ensureCommissionSchema();

        $limit = max(1, min($limit, 50));
        $statement = admin_db()->query(
            "SELECT cp.id,
                    cp.user_id,
                    cp.role,
                    cp.amount,
                    cp.status,
                    cp.payment_reference,
                    cp.notes,
                    cp.paid_at,
                    cp.created_at,
                    u.full_name,
                    u.mobile,
                    COUNT(ce.id) AS earning_count
             FROM commission_payouts cp
             INNER JOIN users u ON u.id = cp.user_id
             LEFT JOIN commission_earnings ce ON ce.payout_id = cp.id
             GROUP BY cp.id
             ORDER BY cp.created_at DESC, cp.id DESC
             LIMIT {$limit}"
        );

        return $statement->fetchAll();
    }

    public function setPayoutStatus(int $payoutId, string $status, string $reference, string $notes): bool
    {
        $this->ensureCommissionSchema();

        $pdo = admin_db();
        $pdo->beginTransaction();

        try {
            if ($status === 'paid') {
                $statement = $pdo->prepare(
                    "UPDATE commission_payouts
                     SET status = 'paid',
                         payment_reference = :payment_reference,
                         notes = :notes,
                         paid_at = NOW(),
                         updated_at = NOW()
                     WHERE id = :id AND status = 'pending'"
                );
                $statement->execute([
                    ':id' => $payoutId,
                    ':payment_reference' => $reference !== '' ? $reference : null,
                    ':notes' => $notes !== '' ? $notes : null,
                ]);

                $earnings = $pdo->prepare(
                    "UPDATE commission_earnings
                     SET status = 'paid', paid_at = NOW(), updated_at = NOW()
                     WHERE payout_id = :payout_id AND status = 'approved'"
                );
                $earnings->execute([':payout_id' => $payoutId]);
            } else {
                $statement = $pdo->prepare(
                    "UPDATE commission_payouts
                     SET status = 'cancelled',
                         notes = :notes,
                         updated_at = NOW()
                     WHERE id = :id AND status = 'pending'"
                );
                $statement->execute([
                    ':id' => $payoutId,
                    ':notes' => $notes !== '' ? $notes : null,
                ]);

                $earnings = $pdo->prepare(
                    "UPDATE commission_earnings
                     SET payout_id = NULL, updated_at = NOW()
                     WHERE payout_id = :payout_id AND status = 'approved'"
                );
                $earnings->execute([':payout_id' => $payoutId]);
            }

            $updated = $statement->rowCount() > 0;
            $pdo->commit();
            return $updated;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function logActivity(int $adminId, string $action, string $description): void
    {
        $this->ensureCommissionSchema();

        $statement = admin_db()->prepare(
            'INSERT INTO activity_logs (user_type, user_id, action, module, description, ip_address, user_agent)
             VALUES ("admin", :user_id, :action, "commissions", :description, :ip_address, :user_agent)'
        );
        $statement->execute([
            ':user_id' => $adminId,
            ':action' => $action,
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    private function earningWhere(array $filters): array
    {
        $where = [];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(u.full_name LIKE :search OR u.mobile LIKE :search OR s.invoice_number LIKE :search OR s.device_serial LIKE :search OR i.invoice_number LIKE :search OR i.device_model LIKE :search OR i.serial_number LIKE :search OR CAST(ce.source_id AS CHAR) LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (($filters['role'] ?? '') !== '') {
            $where[] = 'ce.role = :role';
            $params[':role'] = $filters['role'];
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'ce.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 'DATE(ce.earned_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 'DATE(ce.earned_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function dealerSaleEarningSummary(int $dealerId): array
    {
        $eligible = $this->countBySql(
            'SELECT COUNT(*) FROM sales WHERE deleted_at IS NULL AND dealer_id = :dealer_id',
            [':dealer_id' => $dealerId]
        );
        $locked = $this->countBySql(
            "SELECT COUNT(*)
             FROM commission_earnings ce
             INNER JOIN sales s ON s.id = ce.source_id AND ce.source_type = 'sale'
             WHERE s.deleted_at IS NULL
               AND s.dealer_id = :dealer_id
               AND ce.status <> 'pending'",
            [':dealer_id' => $dealerId]
        );

        return [
            'eligible' => $eligible,
            'locked' => $locked,
            'affected' => 0,
        ];
    }

    private function installerInstallationEarningSummary(int $installerId): array
    {
        $eligible = $this->countBySql(
            'SELECT COUNT(*) FROM installations WHERE deleted_at IS NULL AND installer_id = :installer_id',
            [':installer_id' => $installerId]
        );
        $locked = $this->countBySql(
            "SELECT COUNT(*)
             FROM commission_earnings ce
             INNER JOIN installations i ON i.id = ce.source_id AND ce.source_type = 'installation'
             WHERE i.deleted_at IS NULL
               AND i.installer_id = :installer_id
               AND ce.status <> 'pending'",
            [':installer_id' => $installerId]
        );

        return [
            'eligible' => $eligible,
            'locked' => $locked,
            'affected' => 0,
        ];
    }

    private function countBySql(string $sql, array $params): int
    {
        $statement = admin_db()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function manualSourceId(): int
    {
        return ((int) floor(microtime(true) * 1000000)) + random_int(0, 999);
    }

    private function ensureUserCommissionRulesTable(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        admin_db()->exec(
            "CREATE TABLE IF NOT EXISTS user_commission_rules (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              user_id BIGINT UNSIGNED NOT NULL,
              role ENUM('dealer', 'installer') NOT NULL,
              event_type ENUM('sale', 'installation') NOT NULL,
              amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
              currency CHAR(3) NOT NULL DEFAULT 'PKR',
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              CONSTRAINT fk_user_commission_rules_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
              UNIQUE KEY uq_user_commission_rules_user_event (user_id, role, event_type),
              KEY idx_user_commission_rules_lookup (role, event_type, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $ready = true;
    }

    private function ensureCommissionSchema(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        $pdo = admin_db();
        $database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

        $statusStatement = $pdo->prepare(
            "SELECT COLUMN_TYPE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = :schema
               AND TABLE_NAME = 'commission_earnings'
               AND COLUMN_NAME = 'status'
             LIMIT 1"
        );
        $statusStatement->execute([':schema' => $database]);
        $statusType = (string) $statusStatement->fetchColumn();

        if ($statusType !== '' && strpos($statusType, "'approved'") === false) {
            $pdo->exec(
                "ALTER TABLE commission_earnings
                 MODIFY status ENUM('pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'pending'"
            );
        }

        $sourceStatement = $pdo->prepare(
            "SELECT COLUMN_TYPE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = :schema
               AND TABLE_NAME = 'commission_earnings'
               AND COLUMN_NAME = 'source_type'
             LIMIT 1"
        );
        $sourceStatement->execute([':schema' => $database]);
        $sourceType = (string) $sourceStatement->fetchColumn();

        if ($sourceType !== '' && strpos($sourceType, "'manual'") === false) {
            $pdo->exec(
                "ALTER TABLE commission_earnings
                 MODIFY source_type ENUM('sale', 'installation', 'manual') NOT NULL"
            );
        }

        $this->ensureColumn('commission_earnings', 'approved_at', 'approved_at DATETIME NULL AFTER earned_at');
        $this->ensureColumn('commission_earnings', 'paid_at', 'paid_at DATETIME NULL AFTER approved_at');
        $this->ensureColumn('commission_earnings', 'payout_id', 'payout_id BIGINT UNSIGNED NULL AFTER status');
        $this->ensureUserCommissionRulesTable();
        if (class_exists('AdminCommissionImportRepository')) {
            (new AdminCommissionImportRepository())->ensureSchema();
        }

        $ready = true;
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $pdo = admin_db();
        $database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        $statement = $pdo->prepare(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = :schema
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name"
        );
        $statement->execute([
            ':schema' => $database,
            ':table_name' => $table,
            ':column_name' => $column,
        ]);

        if ((int) $statement->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$definition}");
        }
    }
}
