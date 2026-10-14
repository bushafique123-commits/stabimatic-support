ALTER TABLE commission_earnings
  MODIFY source_type ENUM('sale', 'installation', 'manual') NOT NULL;
