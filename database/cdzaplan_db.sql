-- =====================================================================
--  C.D. Zaplan Trucking — database setup
--
--  HOW TO RUN (XAMPP):
--    1. Start Apache and MySQL in the XAMPP Control Panel.
--    2. Open http://localhost/phpmyadmin → "Import" tab → choose this file → Go.
--       (or from a terminal:  C:\xampp\mysql\bin\mysql -u root < cdzaplan_db.sql)
--
--  WARNING: re-importing this file DROPS and re-creates every table below,
--  so any data you added through the website will be reset to the samples.
--
--  Sample logins (change these passwords after your first login):
--    Admin  : carlito.zaplan@gmail.com   / Admin@12345
--    Client : sarah.jenkins@example.com  / Client@12345
--             michael.chen@example.com   / Client@12345
--             elena.rodriguez@example.com/ Client@12345
-- =====================================================================

CREATE DATABASE IF NOT EXISTS cdzaplan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cdzaplan_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifications, payroll_entries, employees, inquiries, invoices, bookings,
                     equipment_logs, equipment, equipment_types, users, settings;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Accounts (admins and clients share one table; `role` decides the portal)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name        VARCHAR(120) NOT NULL,
  email            VARCHAR(190) NOT NULL UNIQUE,
  password_hash    VARCHAR(255) NOT NULL,
  role             ENUM('admin','client') NOT NULL DEFAULT 'client',
  status           ENUM('Active','Suspended') NOT NULL DEFAULT 'Active',
  phone            VARCHAR(30)  NULL,
  company          VARCHAR(150) NULL,
  billing_address  VARCHAR(255) NULL,
  notify_booking   TINYINT(1) NOT NULL DEFAULT 1,
  notify_invoice   TINYINT(1) NOT NULL DEFAULT 1,
  notify_sms       TINYINT(1) NOT NULL DEFAULT 0,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Equipment types + pricing used by the landing-page estimator and bookings.
--   estimate = daily_rate × days  +  2 trips × MAX(min_trip_fee, km × per_km_rate)
--   (2 trips = delivery to site + pull-out back to the yard)
-- RATES BELOW ARE PLACEHOLDERS — confirm the real ones with the client.
-- Admins can edit them on the Fleet Management page.
-- ---------------------------------------------------------------------
CREATE TABLE equipment_types (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(80) NOT NULL UNIQUE,
  daily_rate    DECIMAL(10,2) NOT NULL,
  per_km_rate   DECIMAL(10,2) NOT NULL,
  min_trip_fee  DECIMAL(10,2) NOT NULL,
  description   VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE equipment (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_code  VARCHAR(20)  NOT NULL UNIQUE,
  plate_no    VARCHAR(30)  NULL,
  type_id     INT UNSIGNED NOT NULL,
  model       VARCHAR(100) NOT NULL,
  capacity    VARCHAR(60)  NOT NULL,
  status      ENUM('Available','Deployed','Maintenance') NOT NULL DEFAULT 'Available',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (type_id) REFERENCES equipment_types(id)
) ENGINE=InnoDB;

-- Every status change of a unit (feeds the "Equipment Availability Logs" report)
CREATE TABLE equipment_logs (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  equipment_id  INT UNSIGNED NOT NULL,
  from_status   VARCHAR(20) NOT NULL,
  to_status     VARCHAR(20) NOT NULL,
  note          VARCHAR(255) NULL,
  logged_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Rental bookings. Reference number shown to users is 'BK-' + id.
-- ---------------------------------------------------------------------
CREATE TABLE bookings (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id           INT UNSIGNED NOT NULL,
  contact_name      VARCHAR(120) NOT NULL,
  contact_phone     VARCHAR(30)  NOT NULL,
  type_id           INT UNSIGNED NOT NULL,
  equipment_id      INT UNSIGNED NULL,           -- unit assigned on approval
  site_address      VARCHAR(255) NOT NULL,
  site_lat          DECIMAL(9,6) NULL,
  site_lng          DECIMAL(9,6) NULL,
  distance_km       DECIMAL(7,2) NULL,
  start_date        DATE NOT NULL,
  end_date          DATE NOT NULL,
  rental_amount     DECIMAL(12,2) NOT NULL,
  mobilization_fee  DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount      DECIMAL(12,2) NOT NULL,
  valid_id_path     VARCHAR(255) NULL,           -- file inside uploads/ids/
  deposit_status    ENUM('Due','Paid') NOT NULL DEFAULT 'Due',
  status            ENUM('Pending Review','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending Review',
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)      REFERENCES users(id),
  FOREIGN KEY (type_id)      REFERENCES equipment_types(id),
  FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Created automatically when an admin approves a booking.
-- "Overdue" is not stored: it is a Pending invoice whose due_on has passed.
CREATE TABLE invoices (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no  VARCHAR(20) NOT NULL UNIQUE,
  booking_id  INT UNSIGNED NOT NULL UNIQUE,
  issued_on   DATE NOT NULL,
  due_on      DATE NOT NULL,
  amount      DECIMAL(12,2) NOT NULL,
  status      ENUM('Pending','Paid') NOT NULL DEFAULT 'Pending',
  paid_on     DATE NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Website inquiries (landing page form + estimator quote requests).
-- "Pending follow-up" = still New after 24 hours (computed in PHP).
-- ---------------------------------------------------------------------
CREATE TABLE inquiries (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          INT UNSIGNED NULL,
  full_name        VARCHAR(120) NOT NULL,
  email            VARCHAR(190) NOT NULL,
  phone            VARCHAR(30)  NULL,
  item             VARCHAR(150) NOT NULL,
  inquiry_type     VARCHAR(60)  NOT NULL,
  message          TEXT NOT NULL,
  type_id          INT UNSIGNED NULL,           -- filled when sent from the estimator
  rental_days      INT UNSIGNED NULL,
  site_lat         DECIMAL(9,6) NULL,
  site_lng         DECIMAL(9,6) NULL,
  distance_km      DECIMAL(7,2) NULL,
  estimate_amount  DECIMAL(12,2) NULL,
  status           ENUM('New','Replied','Archived') NOT NULL DEFAULT 'New',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (type_id) REFERENCES equipment_types(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Payroll
-- ---------------------------------------------------------------------
CREATE TABLE employees (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name    VARCHAR(120) NOT NULL,
  position     VARCHAR(60)  NOT NULL,
  hourly_rate  DECIMAL(10,2) NOT NULL,
  status       ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB;

CREATE TABLE payroll_entries (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id   INT UNSIGNED NOT NULL,
  period_start  DATE NOT NULL,
  period_end    DATE NOT NULL,
  hours         DECIMAL(6,2) NOT NULL,
  rate          DECIMAL(10,2) NOT NULL,   -- copied from employee at the time, so old payroll never changes
  total         DECIMAL(12,2) NOT NULL,
  status        ENUM('Pending','Paid') NOT NULL DEFAULT 'Pending',
  paid_on       DATE NULL,
  UNIQUE KEY one_entry_per_period (employee_id, period_start),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Key/value settings (yard location for the estimator, service radius)
CREATE TABLE settings (
  `key`   VARCHAR(50) PRIMARY KEY,
  `value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- In-app notifications (the bell in the portal top bar)
CREATE TABLE notifications (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  kind        VARCHAR(20)  NOT NULL,           -- booking, inquiry, invoice, payment, dispatch, cancel, account
  title       VARCHAR(150) NOT NULL,
  body        VARCHAR(255) NULL,
  link        VARCHAR(255) NULL,               -- page inside the site, e.g. booking.php?open=12
  ref_key     VARCHAR(60)  NULL,               -- set for one-off reminders so they aren't repeated
  read_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY one_per_ref (user_id, ref_key),
  KEY inbox (user_id, read_at, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- Sample data
-- =====================================================================

INSERT INTO settings (`key`, `value`) VALUES
  ('base_label',     'Ventinilla, Sta. Barbara, Pangasinan'),
  ('base_lat',       '15.997700'),
  ('base_lng',       '120.419000'),
  ('max_service_km', '150');

INSERT INTO users (id, full_name, email, password_hash, role, phone, company, billing_address, created_at) VALUES
  (1, 'Carlito Zaplan',  'carlito.zaplan@gmail.com',    '$2y$10$wcTvU4ma8CvU2.OdsXbCZOqrv2t/4Ji9ObkPZA.IWouyKbwSALSvi', 'admin',  '09176757888', 'C.D. Zaplan Trucking',            'Ventinilla, Sta. Barbara, Pangasinan', '2025-01-01 08:00:00'),
  (2, 'Sarah Jenkins',   'sarah.jenkins@example.com',   '$2y$10$vnP1n/56w/PRZs.TQuIgJefrMRf6Fi5xHCaS9dtn.iDjpqce3sjnC', 'client', '09171234567', 'Jenkins Commercial Development',  'Dagupan City, Pangasinan',             '2026-07-14 10:12:00'),
  (3, 'Michael Chen',    'michael.chen@example.com',    '$2y$10$vnP1n/56w/PRZs.TQuIgJefrMRf6Fi5xHCaS9dtn.iDjpqce3sjnC', 'client', '09189876543', 'Chen Builders',                   'San Fabian, Pangasinan',               '2026-10-18 14:30:00'),
  (4, 'Elena Rodriguez', 'elena.rodriguez@example.com', '$2y$10$vnP1n/56w/PRZs.TQuIgJefrMRf6Fi5xHCaS9dtn.iDjpqce3sjnC', 'client', '09201112233', NULL,                              'Urdaneta City, Pangasinan',            '2026-10-01 09:05:00');

INSERT INTO equipment_types (id, name, daily_rate, per_km_rate, min_trip_fee, description) VALUES
  (1, '10-Wheeler Dump Truck', 5000.00,  75.00, 1500.00, 'Hauling of soil, aggregates and debris. 20 tons / 15 cu.m.'),
  (2, 'Backhoe',               7500.00, 150.00, 3000.00, 'Excavation, trenching and site clearing. Moved by lowbed trailer.');

INSERT INTO equipment (id, asset_code, plate_no, type_id, model, capacity, status) VALUES
  (1, 'TRK-01', 'NBA 4821', 1, 'Isuzu Giga',      '20 Tons / 15 cu.m', 'Deployed'),
  (2, 'TRK-02', 'NCD 7730', 1, 'Hino 700 Series', '20 Tons / 15 cu.m', 'Available'),
  (3, 'TRK-03', 'NDF 1156', 1, 'Isuzu Giga',      '20 Tons / 15 cu.m', 'Available'),
  (4, 'BKH-01', NULL,       2, 'Caterpillar 420', '1.0 cu.m Bucket',   'Available'),
  (5, 'BKH-02', NULL,       2, 'JCB 3CX',         '1.0 cu.m Bucket',   'Maintenance');

INSERT INTO bookings (id, user_id, contact_name, contact_phone, type_id, equipment_id, site_address, site_lat, site_lng, distance_km,
                      start_date, end_date, rental_amount, mobilization_fee, total_amount, deposit_status, status, created_at) VALUES
  (1009, 2, 'Sarah Jenkins',   '09171234567', 1, 1,    'Dagupan Site A, Dagupan City',          16.043300, 120.340900, 10.71, '2026-08-04', '2026-08-15',  60000, 3000,  63000, 'Paid', 'Completed',      '2026-07-28 09:00:00'),
  (1031, 2, 'Sarah Jenkins',   '09171234567', 2, 4,    'Urdaneta Bypass Site, Urdaneta City',   15.976100, 120.571100, 17.39, '2026-09-18', '2026-09-21',  30000, 6000,  36000, 'Paid', 'Completed',      '2026-09-10 11:20:00'),
  (1045, 2, 'Sarah Jenkins',   '09171234567', 2, 4,    'San Fabian Commercial Build',           16.120500, 120.402900, 16.73, '2026-10-12', '2026-10-28', 127500, 6000, 133500, 'Paid', 'Completed',      '2026-10-05 15:45:00'),
  (1080, 4, 'Elena Rodriguez', '09201112233', 2, 5,    'Urdaneta Bypass Site, Urdaneta City',   15.976100, 120.571100, 17.39, '2026-10-15', '2026-10-22',  60000, 6000,  66000, 'Paid', 'Completed',      '2026-10-08 10:10:00'),
  (1082, 2, 'Sarah Jenkins',   '09171234567', 1, 1,    'Dagupan Site A, Dagupan City',          16.043300, 120.340900, 10.71, '2026-11-02', '2026-11-10',  45000, 3000,  48000, 'Paid', 'Confirmed',      '2026-10-26 13:00:00'),
  (1083, 3, 'Michael Chen',    '09189876543', 2, NULL, 'San Fabian Commercial Build',           16.120500, 120.402900, 16.73, '2026-11-05', '2026-11-12',  60000, 6000,  66000, 'Due',  'Pending Review', '2026-10-30 16:25:00'),
  (1099, 2, 'Sarah Jenkins',   '09171234567', 2, NULL, 'Urdaneta Bypass Site, Urdaneta City',   15.976100, 120.571100, 17.39, '2026-11-15', '2026-11-20',  45000, 6000,  51000, 'Due',  'Pending Review', '2026-11-01 08:40:00');
-- New bookings continue numbering from BK-1100
ALTER TABLE bookings AUTO_INCREMENT = 1100;

INSERT INTO invoices (invoice_no, booking_id, issued_on, due_on, amount, status, paid_on) VALUES
  ('INV-2026-017', 1009, '2026-08-04', '2026-08-18',  63000, 'Paid',    '2026-08-15'),
  ('INV-2026-024', 1031, '2026-09-18', '2026-10-02',  36000, 'Pending', NULL),
  ('INV-2026-029', 1045, '2026-10-12', '2026-10-26', 133500, 'Paid',    '2026-10-28'),
  ('INV-2026-030', 1080, '2026-10-15', '2026-10-29',  66000, 'Paid',    '2026-10-22'),
  ('INV-2026-031', 1082, '2026-10-28', '2026-11-11',  48000, 'Pending', NULL);

INSERT INTO equipment_logs (equipment_id, from_status, to_status, note, logged_at) VALUES
  (1, 'Available', 'Deployed',    'Dispatched to Dagupan Site A (BK-1009)',   '2026-08-04 07:00:00'),
  (1, 'Deployed',  'Available',   'Returned from Dagupan Site A (BK-1009)',   '2026-08-15 17:30:00'),
  (4, 'Available', 'Deployed',    'Dispatched to San Fabian (BK-1045)',        '2026-10-12 07:15:00'),
  (5, 'Available', 'Deployed',    'Dispatched to Urdaneta Bypass (BK-1080)',   '2026-10-15 06:45:00'),
  (5, 'Deployed',  'Maintenance', 'Hydraulic hose repair',                     '2026-10-22 16:00:00'),
  (4, 'Deployed',  'Available',   'Returned from San Fabian (BK-1045)',        '2026-10-28 17:00:00'),
  (1, 'Available', 'Deployed',    'Dispatched to Dagupan Site A (BK-1082)',   '2026-11-02 07:00:00');

INSERT INTO inquiries (full_name, email, phone, item, inquiry_type, message, status, created_at) VALUES
  ('Marco Santos',     'marco.santos@example.com',     '09175550142', 'Backhoe',                    'Availability Check',       'Hi, do you have a backhoe available for site clearing in Urdaneta starting Nov 10? We''ll need it for about two weeks. Please let me know the rate and whether mobilization is included.', 'New',      '2026-10-29 09:14:00'),
  ('Joanna Dela Cruz', 'joanna.delacruz@example.com',  '09189990231', '10-Wheeler Dump Truck',      'Quote & Pricing Request',  'Good afternoon. We''re preparing a commercial lot in San Fabian and need two 10-wheeler dump trucks for a week. Could you send a quote including delivery fees?', 'New',      '2026-10-28 16:40:00'),
  ('Ramon Villanueva', 'ramon.v@example.com',          '09201234876', 'Site Clearing & Excavation', 'General Inquiry',          'We own a 2-hectare lot in Sta. Barbara that needs clearing before construction. How do you usually start a project like this?', 'Replied',  '2026-10-27 13:05:00'),
  ('Kristine Lim',     'kristine.lim@example.com',     NULL,          'Backhoe',                    'Technical Specifications', 'What is the bucket size and digging depth of your backhoe? Our drainage trench needs to be about 3 meters deep.', 'New',      '2026-10-26 10:22:00'),
  ('Paolo Reyes',      'paolo.reyes@example.com',      '09175559981', 'Site Clearing & Excavation', 'Quote & Pricing Request',  'Looking for a quote on site clearing and foundation excavation for a 500 sqm commercial building in Dagupan.', 'Replied',  '2026-10-24 08:50:00'),
  ('Anna Mercado',     'anna.mercado@example.com',     '09184443210', '10-Wheeler Dump Truck',      'Availability Check',       'Is a dump truck available this coming weekend? We found another supplier, so please disregard.', 'Archived', '2026-10-18 15:12:00');

INSERT INTO employees (id, full_name, position, hourly_rate) VALUES
  (1, 'Daniel Reyes', 'Driver',   250.00),
  (2, 'Leo Mendoza',  'Operator', 300.00),
  (3, 'Ana Cruz',     'Driver',   240.00);

INSERT INTO payroll_entries (employee_id, period_start, period_end, hours, rate, total, status, paid_on) VALUES
  (1, '2026-10-14', '2026-10-20', 40, 250.00, 10000.00, 'Paid',    '2026-10-21'),
  (2, '2026-10-14', '2026-10-20', 38, 300.00, 11400.00, 'Paid',    '2026-10-21'),
  (3, '2026-10-14', '2026-10-20', 40, 240.00,  9600.00, 'Paid',    '2026-10-21'),
  (1, '2026-10-21', '2026-10-27', 42, 250.00, 10500.00, 'Paid',    '2026-10-28'),
  (2, '2026-10-21', '2026-10-27', 40, 300.00, 12000.00, 'Pending', NULL),
  (3, '2026-10-21', '2026-10-27', 36, 240.00,  8640.00, 'Pending', NULL);

-- Starting notifications for the sample data
-- Give admins a notification for everything already waiting for them
INSERT IGNORE INTO notifications (user_id, kind, title, body, link, ref_key, created_at)
SELECT a.id, 'booking', CONCAT('New booking request BK-', b.id), CONCAT(b.contact_name, ' · ', t.name, ' · ', DATE_FORMAT(b.start_date, '%b %e'), '–', DATE_FORMAT(b.end_date, '%b %e')),
       CONCAT('booking.php?open=', b.id), CONCAT('seed:booking:', b.id), b.created_at
FROM bookings b JOIN equipment_types t ON t.id = b.type_id JOIN users a ON a.role = 'admin'
WHERE b.status = 'Pending Review';

INSERT IGNORE INTO notifications (user_id, kind, title, body, link, ref_key, created_at)
SELECT a.id, 'inquiry', CONCAT('New inquiry from ', i.full_name), CONCAT(i.inquiry_type, ' · ', i.item),
       CONCAT('inquiries.php?open=', i.id), CONCAT('seed:inquiry:', i.id), i.created_at
FROM inquiries i JOIN users a ON a.role = 'admin'
WHERE i.status = 'New';

-- Clients: invoices that are already issued
INSERT IGNORE INTO notifications (user_id, kind, title, body, link, ref_key, created_at)
SELECT b.user_id, 'invoice', CONCAT('Invoice ', i.invoice_no, ' issued'), CONCAT('BK-', b.id, ' is confirmed. Amount ₱', FORMAT(i.amount, 2), ', due ', DATE_FORMAT(i.due_on, '%b %e, %Y'), '.'),
       CONCAT('invoice.php?no=', i.invoice_no), CONCAT('seed:invoice:', i.id), i.issued_on
FROM invoices i JOIN bookings b ON b.id = i.booking_id
WHERE i.status = 'Pending';
