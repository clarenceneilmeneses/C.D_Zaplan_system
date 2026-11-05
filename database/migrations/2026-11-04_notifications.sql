-- Adds in-app notifications to an existing cdzaplan_db without resetting your data.
-- New installs don't need this: database/cdzaplan_db.sql already includes the table.
-- Import it in phpMyAdmin (select cdzaplan_db → Import), or run:
--   mysql -u root cdzaplan_db < database/migrations/2026-11-04_notifications.sql

CREATE TABLE IF NOT EXISTS notifications (
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
