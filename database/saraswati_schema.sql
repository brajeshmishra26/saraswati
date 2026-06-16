CREATE DATABASE IF NOT EXISTS cpzunhsysc
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cpzunhsysc;

CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  event_date VARCHAR(120) NOT NULL,
  location VARCHAR(255) NOT NULL,
  display_order INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS videos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  youtube_id VARCHAR(50) NOT NULL,
  description TEXT NULL,
  display_order INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category ENUM('Construction','Rituals','Events') NOT NULL DEFAULT 'Events',
  file_path VARCHAR(500) NOT NULL,
  title VARCHAR(255) NOT NULL,
  display_order INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NULL,
  message TEXT NOT NULL,
  ip_address VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_contact_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('member','adhyaksh','sachiv','admin') NOT NULL DEFAULT 'member',
  profile_image VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
  lock_until DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_password_resets_user (user_id),
  INDEX idx_password_resets_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS donations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  amount DECIMAL(12,2) NOT NULL,
  method VARCHAR(60) NOT NULL DEFAULT 'UPI',
  transaction_ref VARCHAR(120) NULL,
  status ENUM('pending','success','failed') NOT NULL DEFAULT 'success',
  notes VARCHAR(255) NULL,
  donor_name VARCHAR(150) NULL,
  donor_email VARCHAR(190) NULL,
  callback_source VARCHAR(60) NULL,
  payment_id VARCHAR(120) NULL,
  payload_json JSON NULL,
  donated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_donations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_donations_user (user_id),
  INDEX idx_donations_donated_at (donated_at),
  INDEX idx_donations_payment_id (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_attempts_email (email),
  INDEX idx_login_attempts_ip (ip_address),
  INDEX idx_login_attempts_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_credentials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT NULL,
  is_secret TINYINT(1) NOT NULL DEFAULT 0,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_admin_credentials_key (setting_key),
  INDEX idx_admin_credentials_updated_by (updated_by),
  CONSTRAINT fk_admin_credentials_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS lock_until DATETIME NULL;

ALTER TABLE donations
  MODIFY COLUMN user_id BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS donor_name VARCHAR(150) NULL,
  ADD COLUMN IF NOT EXISTS donor_email VARCHAR(190) NULL,
  ADD COLUMN IF NOT EXISTS callback_source VARCHAR(60) NULL,
  ADD COLUMN IF NOT EXISTS payment_id VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS payload_json JSON NULL;

CREATE INDEX IF NOT EXISTS idx_donations_payment_id ON donations (payment_id);

INSERT IGNORE INTO admin_credentials (setting_key, setting_value, is_secret)
VALUES
  ('MAIL_FROM_EMAIL', NULL, 0),
  ('MAIL_FROM_NAME', NULL, 0),
  ('SMTP_HOST', NULL, 0),
  ('SMTP_PORT', NULL, 0),
  ('SMTP_USER', NULL, 0),
  ('SMTP_PASS', NULL, 1),
  ('PAYMENT_CALLBACK_SECRET', NULL, 1);

INSERT INTO events (title, event_date, location, display_order)
SELECT * FROM (
  SELECT 'Bhoomi Pujan Smaran Samaroh', '19-20 Apr 2026', 'Lakhnadon', 1
  UNION ALL
  SELECT 'Basant Panchami Mahotsav', 'Yearly - February', 'Maa Saraswati Sansthan', 2
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM events);

INSERT INTO videos (title, youtube_id, description, display_order)
SELECT * FROM (
  SELECT 'Bhoomi Pujan Highlights', 'dQw4w9WgXcQ', 'Highlights from the Lakhnadon Bhoomi Pujan celebrations.', 1
  UNION ALL
  SELECT 'Saraswati Vandana Satsang', '3JZ_D3ELwOQ', 'Devotional chants and prayers dedicated to Maa Saraswati.', 2
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM videos);

INSERT INTO gallery_images (category, file_path, title, display_order)
SELECT * FROM (
  SELECT 'Construction', 'assets/images/sara1.jpeg', 'Temple Construction Progress', 1
  UNION ALL SELECT 'Construction', 'assets/images/sara2.jpeg', 'Lakhnadon Site Work', 2
  UNION ALL SELECT 'Construction', 'assets/images/sara3.jpeg', 'Foundation Work', 3
  UNION ALL SELECT 'Rituals', 'assets/images/sara10.jpeg', 'Sacred Ritual Ceremony', 4
  UNION ALL SELECT 'Rituals', 'assets/images/sara11.jpeg', 'Vedic Pujan Rituals', 5
  UNION ALL SELECT 'Rituals', 'assets/images/sara12.jpeg', 'Aarti and Vandana', 6
  UNION ALL SELECT 'Events', 'assets/images/sara14.jpeg', 'Devotee Participation', 7
  UNION ALL SELECT 'Events', 'assets/images/sara15.jpeg', 'Community Event', 8
  UNION ALL SELECT 'Events', 'assets/images/sara21.jpeg', 'Group Participation', 9
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM gallery_images);

INSERT INTO users (full_name, email, password_hash, role)
SELECT * FROM (
  SELECT 'Administrator', 'admin@maa-saraswati.co.in', '$2y$10$5Bi90FCXXen2LG/Hx.apnePtxkLvpEdqPLDP0NsBCIqa3oHPzYpq.', 'admin'
) AS seed
WHERE NOT EXISTS (
  SELECT 1 FROM users WHERE email = 'admin@maa-saraswati.co.in'
);
