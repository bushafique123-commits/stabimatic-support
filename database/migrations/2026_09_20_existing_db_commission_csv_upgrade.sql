-- Existing DB upgrade for Commission CSV invoice/model payouts.
-- Run this against the current STABIMATIC database.

CREATE TABLE IF NOT EXISTS commission_import_rows (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @schema_name = DATABASE();

SET @column_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'installations'
    AND COLUMN_NAME = 'invoice_number'
);
SET @sql = IF(@column_exists = 0,
  'ALTER TABLE installations ADD COLUMN invoice_number VARCHAR(60) NULL AFTER installer_id',
  'SELECT "installations.invoice_number already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'installations'
    AND COLUMN_NAME = 'device_model'
);
SET @sql = IF(@column_exists = 0,
  'ALTER TABLE installations ADD COLUMN device_model VARCHAR(120) NULL AFTER invoice_number',
  'SELECT "installations.device_model already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'installations'
    AND INDEX_NAME = 'idx_installations_invoice_model'
);
SET @sql = IF(@index_exists = 0,
  'ALTER TABLE installations ADD KEY idx_installations_invoice_model (invoice_number, device_model)',
  'SELECT "idx_installations_invoice_model already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Keep old installation records matchable when they previously matched sales by serial.
UPDATE installations i
INNER JOIN sales s
  ON s.device_serial = i.serial_number
 AND s.deleted_at IS NULL
SET i.invoice_number = COALESCE(NULLIF(i.invoice_number, ''), s.invoice_number),
    i.device_model = COALESCE(NULLIF(i.device_model, ''), s.device_model)
WHERE i.deleted_at IS NULL
  AND (
    i.invoice_number IS NULL OR i.invoice_number = ''
    OR i.device_model IS NULL OR i.device_model = ''
  );

SET @source_type = (
  SELECT COLUMN_TYPE
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'commission_earnings'
    AND COLUMN_NAME = 'source_type'
  LIMIT 1
);
SET @sql = IF(@source_type IS NOT NULL AND @source_type NOT LIKE '%''manual''%',
  'ALTER TABLE commission_earnings MODIFY source_type ENUM(''sale'', ''installation'', ''manual'') NOT NULL',
  'SELECT "commission_earnings.source_type already supports manual"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @status_type = (
  SELECT COLUMN_TYPE
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'commission_earnings'
    AND COLUMN_NAME = 'status'
  LIMIT 1
);
SET @sql = IF(@status_type IS NOT NULL AND @status_type NOT LIKE '%''approved''%',
  'ALTER TABLE commission_earnings MODIFY status ENUM(''pending'', ''approved'', ''paid'', ''cancelled'') NOT NULL DEFAULT ''pending''',
  'SELECT "commission_earnings.status already supports approved"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'commission_earnings'
    AND COLUMN_NAME = 'payout_id'
);
SET @sql = IF(@column_exists = 0,
  'ALTER TABLE commission_earnings ADD COLUMN payout_id BIGINT UNSIGNED NULL AFTER status',
  'SELECT "commission_earnings.payout_id already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'commission_earnings'
    AND COLUMN_NAME = 'approved_at'
);
SET @sql = IF(@column_exists = 0,
  'ALTER TABLE commission_earnings ADD COLUMN approved_at DATETIME NULL AFTER earned_at',
  'SELECT "commission_earnings.approved_at already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'commission_earnings'
    AND COLUMN_NAME = 'paid_at'
);
SET @sql = IF(@column_exists = 0,
  'ALTER TABLE commission_earnings ADD COLUMN paid_at DATETIME NULL AFTER approved_at',
  'SELECT "commission_earnings.paid_at already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
