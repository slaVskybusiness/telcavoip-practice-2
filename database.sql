-- ============================================================
-- Baza danych TelcaVoIP (MySQL)
-- Import przez phpMyAdmin: utworzy baze i wszystkie tabele.
-- ============================================================

CREATE DATABASE IF NOT EXISTS telcavoip
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE telcavoip;

-- Uzytkownicy panelu
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('administrator','operator','technician') NOT NULL DEFAULT 'operator',
  active TINYINT(1) NOT NULL DEFAULT 1
);

-- Klienci
CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  vat VARCHAR(50) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  address VARCHAR(255) DEFAULT '',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Konta VoIP (kazde nalezy do jednego klienta)
CREATE TABLE voip_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  number VARCHAR(80) NOT NULL,
  status ENUM('active','suspended','terminated') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Uslugi
CREATE TABLE services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  type VARCHAR(120) NOT NULL,
  start_date DATE NOT NULL,
  expiry_date DATE NOT NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Historia odnowien uslug
CREATE TABLE renewals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_id INT NOT NULL,
  old_expiry_date DATE NOT NULL,
  new_expiry_date DATE NOT NULL,
  renewal_date DATE NOT NULL,
  registered_by INT NOT NULL,
  FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Interwencje techniczne
CREATE TABLE interventions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  service_id INT DEFAULT NULL,
  type VARCHAR(120) NOT NULL,
  notes TEXT,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME DEFAULT NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Zalaczniki (metadane; sam plik lezy w folderze uploads)
CREATE TABLE attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervention_id INT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  size INT NOT NULL,
  uploaded_by INT NOT NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (intervention_id) REFERENCES interventions(id)
);

-- Dziennik zdarzen (audyt). Tylko dopisujemy - nie edytujemy i nie kasujemy.
CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_id INT NOT NULL,
  action VARCHAR(50) NOT NULL,
  entity VARCHAR(50) NOT NULL,
  entity_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Konto startowe administratora
-- login: admin@telcavoip.eu   haslo: admin123
INSERT INTO users (name, email, password_hash, role, active) VALUES
('Administrator', 'admin@telcavoip.eu', '$2y$10$Wz2glml6ADB5qLjOla0k4eCsGJFjULIcjkoP60gwfnRjus8waWpz2', 'administrator', 1);
