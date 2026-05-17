-- Migracja 092: Split payments — klub jako merchant, ClubDesk jako platforma
-- (Sendormeco Holding Sp. z o.o.). Platform fee jest auto-deducted z każdej
-- transakcji online (Stripe Connect: application_fee_amount; P24: marketplace).

CREATE TABLE IF NOT EXISTS platform_payment_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  provider ENUM('stripe_connect','p24_marketplace') NOT NULL,
  external_account_id VARCHAR(100) NOT NULL,
  account_type ENUM('express','standard','custom') DEFAULT 'express',
  kyc_status ENUM('pending','verified','rejected','restricted') DEFAULT 'pending',
  charges_enabled TINYINT(1) DEFAULT 0,
  payouts_enabled TINYINT(1) DEFAULT 0,
  capabilities JSON,
  country CHAR(2) DEFAULT 'PL',
  default_currency CHAR(3) DEFAULT 'PLN',
  onboarding_complete TINYINT(1) DEFAULT 0,
  onboarded_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_club_provider (club_id, provider),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS platform_fee_rules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scope ENUM('global','plan','club_override') DEFAULT 'global',
  plan_code VARCHAR(50) NULL,
  club_id INT UNSIGNED NULL,
  fee_percent DECIMAL(5,2) NOT NULL DEFAULT 2.00,
  fee_fixed_cents INT NOT NULL DEFAULT 0,
  min_fee_cents INT DEFAULT 0,
  max_fee_cents INT NULL,
  effective_from DATE NOT NULL,
  effective_until DATE NULL,
  active TINYINT(1) DEFAULT 1,
  KEY idx_scope (scope, active),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS platform_fee_charges (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  payment_id INT UNSIGNED NULL,
  online_payment_id INT UNSIGNED NULL,
  provider ENUM('stripe_connect','p24_marketplace') NOT NULL,
  transaction_id VARCHAR(100) NOT NULL,
  gross_amount_cents INT NOT NULL,
  platform_fee_cents INT NOT NULL,
  club_net_amount_cents INT NOT NULL,
  currency CHAR(3) DEFAULT 'PLN',
  charged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_club_date (club_id, charged_at DESC),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed: 2% global platform fee (effective 2026-01-01)
INSERT INTO platform_fee_rules (scope, fee_percent, effective_from)
VALUES ('global', 2.00, '2026-01-01');
