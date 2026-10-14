ALTER TABLE commission_earnings
  MODIFY status ENUM('pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'pending';
