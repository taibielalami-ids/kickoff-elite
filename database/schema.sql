CREATE DATABASE IF NOT EXISTS football_simple;
USE football_simple;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  date_of_birth DATE NOT NULL,
  city VARCHAR(80) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user',
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS auth_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  code_hash CHAR(64) NOT NULL,
  code_type VARCHAR(30) NOT NULL,
  expires_at DATETIME NOT NULL,
  is_used TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_playing_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  role_name VARCHAR(20) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pitches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  city VARCHAR(80) NOT NULL,
  address VARCHAR(200) NOT NULL,
  lat DECIMAL(10,7) NOT NULL,
  lng DECIMAL(10,7) NOT NULL,
  open_time TIME NOT NULL,
  close_time TIME NOT NULL,
  price_per_player DECIMAL(10,2) NOT NULL DEFAULT 50.00,
  team_size INT NOT NULL DEFAULT 10,
  status VARCHAR(30) NOT NULL DEFAULT 'available',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS pitch_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pitch_id INT NOT NULL,
  photo_url VARCHAR(255) NOT NULL,
  FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pitch_blocked_slots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pitch_id INT NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  reason VARCHAR(120),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pitch_id INT NOT NULL,
  creator_user_id INT NOT NULL,
  slot_start DATETIME NOT NULL,
  slot_end DATETIME NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  total_price DECIMAL(10,2) NOT NULL DEFAULT 500.00,
  payment_mode VARCHAR(20) NOT NULL DEFAULT 'none',
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  paid_tickets INT NOT NULL DEFAULT 0,
  is_refunded TINYINT(1) NOT NULL DEFAULT 0,
  refunded_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  refunded_tickets INT NOT NULL DEFAULT 0,
  cancelled_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pitch_id) REFERENCES pitches(id),
  FOREIGN KEY (creator_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS booking_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL UNIQUE,
  code VARCHAR(20) NOT NULL UNIQUE,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS booking_locks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pitch_id INT NOT NULL,
  user_id INT NOT NULL,
  slot_start DATETIME NOT NULL,
  slot_end DATETIME NOT NULL,
  lock_token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT NULL,
  target_user_id INT NULL,
  action_type VARCHAR(60) NOT NULL,
  entity_type VARCHAR(40),
  entity_id INT NULL,
  details VARCHAR(255),
  ip_address VARCHAR(45),
  user_agent VARCHAR(180),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS wallets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  ticket_balance INT NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS wallet_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  wallet_id INT NOT NULL,
  user_id INT NOT NULL,
  tx_type VARCHAR(40) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  tickets_change INT NOT NULL DEFAULT 0,
  reference_text VARCHAR(120),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS team_ads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  creator_user_id INT NOT NULL,
  pitch_id INT NOT NULL,
  match_start DATETIME NOT NULL,
  match_end DATETIME NOT NULL,
  team_size_target INT NOT NULL DEFAULT 10,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (creator_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS team_ad_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ad_id INT NOT NULL,
  user_id INT NOT NULL,
  role_name VARCHAR(20) NOT NULL DEFAULT 'midfielder',
  status VARCHAR(20) NOT NULL DEFAULT 'joined',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_team_ad_member (ad_id, user_id),
  FOREIGN KEY (ad_id) REFERENCES team_ads(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS team_ad_positions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ad_id INT NOT NULL,
  user_id INT NOT NULL,
  slot_key VARCHAR(20) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_team_ad_position_slot (ad_id, slot_key),
  UNIQUE KEY uq_team_ad_position_user (ad_id, user_id),
  FOREIGN KEY (ad_id) REFERENCES team_ads(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS backup_calls (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  requester_user_id INT NOT NULL,
  pitch_id INT NOT NULL,
  match_start DATETIME NOT NULL,
  needed_role VARCHAR(20) NOT NULL DEFAULT 'any',
  is_free TINYINT(1) NOT NULL DEFAULT 1,
  reward_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  message VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  expires_at DATETIME NOT NULL,
  selected_user_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (requester_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE CASCADE,
  FOREIGN KEY (selected_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS backup_call_responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  backup_call_id INT NOT NULL,
  user_id INT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  message VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_backup_call_response (backup_call_id, user_id),
  FOREIGN KEY (backup_call_id) REFERENCES backup_calls(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (username, email, password_hash, date_of_birth, city, role, email_verified, status)
VALUES ('admin', 'admin@kickoff.local', '$2y$10$u2mgNUV4GXLdOj/8WHrLHOdBE.cl9LL4BVzXGUGiKllI.bi7I/XCC', '1990-01-01', 'Rabat', 'admin', 1, 'active')
ON DUPLICATE KEY UPDATE username = username;
