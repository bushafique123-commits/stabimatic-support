CREATE TABLE IF NOT EXISTS commission_import_rows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(120) NOT NULL,
  model_number VARCHAR(120) NOT NULL,
  dealer_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  installer_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  uploaded_by_admin_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_commission_import_rows_admin FOREIGN KEY (uploaded_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_commission_import_invoice_model (invoice_number, model_number),
  KEY idx_commission_import_invoice_number (invoice_number),
  KEY idx_commission_import_model_number (model_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE installations
  ADD COLUMN invoice_number VARCHAR(60) NULL AFTER installer_id,
  ADD COLUMN device_model VARCHAR(120) NULL AFTER invoice_number,
  ADD KEY idx_installations_invoice_model (invoice_number, device_model);
