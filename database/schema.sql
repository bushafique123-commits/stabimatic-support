

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('dealer', 'installer') NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  mobile VARCHAR(20) NOT NULL,
  mobile_verified_at DATETIME NULL,
  email VARCHAR(190) NOT NULL,
  email_verified_at DATETIME NULL,
  cnic VARCHAR(20) NULL,
  bank_name VARCHAR(150) NOT NULL,
  bank_account_title VARCHAR(150) NOT NULL,
  bank_account_number VARCHAR(50) NOT NULL,
  iban VARCHAR(34) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  profile_image VARCHAR(255) NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_mobile (mobile),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_cnic (cnic),
  KEY idx_users_role (role),
  KEY idx_users_verified_active (is_verified, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS otp_verifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  mobile VARCHAR(20) NULL,
  email VARCHAR(190) NULL,
  otp VARCHAR(255) NOT NULL,
  type ENUM('mobile', 'email') NOT NULL,
  purpose ENUM('registration', 'forgot_password', 'change_mobile', 'change_email') NOT NULL,
  expires_at DATETIME NOT NULL,
  verified_at DATETIME NULL,
  attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  KEY idx_otp_user_purpose (user_id, purpose),
  KEY idx_otp_mobile_purpose (mobile, purpose),
  KEY idx_otp_email_purpose (email, purpose),
  KEY idx_otp_expires_verified (expires_at, verified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dealer_id BIGINT UNSIGNED NOT NULL,
  invoice_number VARCHAR(120) NOT NULL,
  sale_date DATE NOT NULL,
  customer_name VARCHAR(150) NOT NULL,
  customer_mobile VARCHAR(20) NOT NULL,
  device_model VARCHAR(120) NOT NULL,
  device_serial VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  active_device_serial VARCHAR(120) GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN device_serial ELSE NULL END) STORED,
  CONSTRAINT fk_sales_dealer FOREIGN KEY (dealer_id) REFERENCES users(id) ON DELETE RESTRICT,
  UNIQUE KEY uq_sales_device_serial_active (active_device_serial),
  KEY idx_sales_dealer_id (dealer_id),
  KEY idx_sales_invoice_number (invoice_number),
  KEY idx_sales_device_serial (device_serial),
  KEY idx_sales_sale_date (sale_date),
  KEY idx_sales_customer_mobile (customer_mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS installations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  installer_id BIGINT UNSIGNED NOT NULL,
  invoice_number VARCHAR(60) NULL,
  device_model VARCHAR(120) NULL,
  customer_name VARCHAR(150) NOT NULL,
  customer_mobile VARCHAR(20) NOT NULL,
  customer_email VARCHAR(190) NULL,
  serial_number VARCHAR(120) NOT NULL,
  installation_picture VARCHAR(255) NOT NULL,
  installed_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_installations_installer FOREIGN KEY (installer_id) REFERENCES users(id) ON DELETE RESTRICT,
  KEY idx_installations_installer_id (installer_id),
  KEY idx_installations_invoice_model (invoice_number, device_model),
  KEY idx_installations_serial_number (serial_number),
  KEY idx_installations_installed_at (installed_at),
  KEY idx_installations_customer_mobile (customer_mobile),
  KEY idx_installations_customer_email (customer_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admin_users_email (email),
  KEY idx_admin_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commission_import_rows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(60) NOT NULL,
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

CREATE TABLE IF NOT EXISTS activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_type ENUM('dealer', 'installer', 'admin') NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(80) NOT NULL,
  module VARCHAR(80) NOT NULL,
  record_id BIGINT UNSIGNED NULL,
  description TEXT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_activity_user (user_type, user_id),
  KEY idx_activity_action (action),
  KEY idx_activity_module_record (module, record_id),
  KEY idx_activity_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT NULL,
  value_type ENUM('string', 'integer', 'boolean', 'json') NOT NULL DEFAULT 'string',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_id VARCHAR(64) NOT NULL,
  role ENUM('dealer', 'installer') NOT NULL,
  issued_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_auth_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_auth_tokens_token_id (token_id),
  KEY idx_auth_tokens_user (user_id),
  KEY idx_auth_tokens_expires_revoked (expires_at, revoked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS device_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  role ENUM('dealer', 'installer') NOT NULL,
  platform ENUM('ios', 'android', 'web') NOT NULL,
  device_token VARCHAR(255) NOT NULL,
  device_name VARCHAR(150) NULL,
  app_version VARCHAR(40) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_device_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_device_tokens_token (device_token),
  KEY idx_device_tokens_user_active (user_id, is_active),
  KEY idx_device_tokens_platform (platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_preferences (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  sales_notifications TINYINT(1) NOT NULL DEFAULT 1,
  installation_notifications TINYINT(1) NOT NULL DEFAULT 1,
  account_notifications TINYINT(1) NOT NULL DEFAULT 1,
  marketing_notifications TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_notification_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_notification_preferences_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_id VARCHAR(32) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  recipient_role ENUM('dealer', 'installer') NOT NULL,
  created_by_admin_id BIGINT UNSIGNED NULL,
  audience ENUM('all', 'dealer', 'installer') NOT NULL,
  channel ENUM('push', 'sms', 'email') NOT NULL DEFAULT 'push',
  notification_type ENUM('general', 'sales', 'installation', 'account', 'marketing') NOT NULL DEFAULT 'general',
  title VARCHAR(120) NOT NULL,
  message TEXT NOT NULL,
  payload JSON NULL,
  status ENUM('queued', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'queued',
  attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
  last_attempt_at DATETIME NULL,
  queued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  read_at DATETIME NULL,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
  KEY idx_notifications_batch (batch_id),
  KEY idx_notifications_user_status (user_id, status),
  KEY idx_notifications_user_read (user_id, read_at),
  KEY idx_notifications_status_channel (status, channel),
  KEY idx_notifications_type (notification_type),
  KEY idx_notifications_queued_at (queued_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commission_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('dealer', 'installer') NOT NULL,
  event_type ENUM('sale', 'installation') NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'PKR',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_commission_rules_role_event (role, event_type),
  KEY idx_commission_rules_lookup (role, event_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_commission_rules (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commission_payouts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  role ENUM('dealer', 'installer') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
  payment_reference VARCHAR(120) NULL,
  notes TEXT NULL,
  created_by_admin_id BIGINT UNSIGNED NULL,
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_commission_payouts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_commission_payouts_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
  KEY idx_commission_payouts_user_status (user_id, status),
  KEY idx_commission_payouts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commission_earnings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  role ENUM('dealer', 'installer') NOT NULL,
  source_type ENUM('sale', 'installation', 'manual') NOT NULL,
  source_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'PKR',
  status ENUM('pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
  payout_id BIGINT UNSIGNED NULL,
  earned_at DATETIME NOT NULL,
  approved_at DATETIME NULL,
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_commission_earnings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_commission_earnings_payout FOREIGN KEY (payout_id) REFERENCES commission_payouts(id) ON DELETE SET NULL,
  UNIQUE KEY uq_commission_earning_source (user_id, source_type, source_id),
  KEY idx_commission_earnings_user_status (user_id, status),
  KEY idx_commission_earnings_status (status),
  KEY idx_commission_earnings_earned_at (earned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rate_key VARCHAR(190) NOT NULL,
  action VARCHAR(80) NOT NULL,
  attempt_count INT UNSIGNED NOT NULL DEFAULT 1,
  window_started_at DATETIME NOT NULL,
  blocked_until DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rate_limits_key_action (rate_key, action),
  KEY idx_rate_limits_blocked_until (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value, value_type) VALUES
('company_name', 'STABIMATIC', 'string'),
('company_logo', '', 'string'),
('support_phone', '', 'string'),
('support_email', '', 'string'),
('otp_expiry_minutes', '10', 'integer'),
('otp_resend_seconds', '60', 'integer'),
('maximum_otp_attempts', '5', 'integer'),
('maximum_image_size', '5242880', 'integer'),
('require_sale_before_installation', '1', 'boolean')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO commission_rules (role, event_type, amount, currency, is_active) VALUES
('dealer', 'sale', 0.00, 'PKR', 1),
('installer', 'installation', 0.00, 'PKR', 1)
ON DUPLICATE KEY UPDATE amount = VALUES(amount), currency = VALUES(currency), is_active = VALUES(is_active);
