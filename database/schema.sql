CREATE DATABASE IF NOT EXISTS workhronolic
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE workhronolic;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS entry_breaks;
DROP TABLE IF EXISTS time_entries;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS companies;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE companies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  code VARCHAR(9) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner', 'manager', 'employee') NOT NULL DEFAULT 'employee',
  status ENUM('active', 'pending', 'inactive') NOT NULL DEFAULT 'pending',
  expected_hours SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON DELETE CASCADE,
  INDEX idx_users_company_status (company_id, status),
  INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE time_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NULL,
  break_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  hours DECIMAL(6,2) NULL,
  status ENUM('active', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  note TEXT NULL,
  review_comment TEXT NULL,
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_entries_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_entries_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_entries_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_entries_user_date (user_id, work_date),
  INDEX idx_entries_company_status (company_id, status),
  INDEX idx_entries_company_date (company_id, work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entry_breaks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL,
  break_start DATETIME NOT NULL,
  break_end DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_breaks_entry
    FOREIGN KEY (entry_id) REFERENCES time_entries(id)
    ON DELETE CASCADE,
  INDEX idx_breaks_entry (entry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO companies (id, name, code) VALUES
  (1, 'Aa Startup Studio', 'AA-7K2M9Q');

-- Seed password for every demo account: password
INSERT INTO users (id, company_id, name, email, password_hash, role, status, expected_hours) VALUES
  (1, 1, 'Dathan Ancheta', 'dathan@startup.io', '$2y$10$Zx2wHkFz84m7nURX2aS4pOcSqhjY.Fit399drtgN5lb/fuiooydoi', 'owner', 'active', 0),
  (2, 1, 'Mia Santos', 'mia@startup.io', '$2y$10$Zx2wHkFz84m7nURX2aS4pOcSqhjY.Fit399drtgN5lb/fuiooydoi', 'manager', 'active', 80),
  (3, 1, 'John Cris Antor', 'jc@startup.io', '$2y$10$Zx2wHkFz84m7nURX2aS4pOcSqhjY.Fit399drtgN5lb/fuiooydoi', 'employee', 'active', 60),
  (4, 1, 'Paolo Reyes', 'paolo@startup.io', '$2y$10$Zx2wHkFz84m7nURX2aS4pOcSqhjY.Fit399drtgN5lb/fuiooydoi', 'employee', 'pending', 40),
  (5, 1, 'Lea Villanueva', 'lea@freelance.ph', '$2y$10$Zx2wHkFz84m7nURX2aS4pOcSqhjY.Fit399drtgN5lb/fuiooydoi', 'manager', 'pending', 80);

INSERT INTO time_entries
  (id, company_id, user_id, work_date, start_time, end_time, break_seconds, hours, status, note, review_comment, reviewed_by, reviewed_at)
VALUES
  (1, 1, 3, '2026-07-03', '08:30:00', '12:30:00', 0, 4.00, 'rejected',
   'Reviewed pull requests and updated the deployment documentation.',
   'Please add which pull requests were reviewed before resubmitting this entry.', 2, '2026-07-04 09:00:00'),
  (2, 1, 3, '2026-07-04', '10:00:00', '14:00:00', 0, 4.00, 'approved',
   'Sprint planning with the team, then drafted the API contract for the reports endpoint.',
   'Approved. The accomplishment details match the sprint deliverables.', 2, '2026-07-05 09:00:00'),
  (3, 1, 3, '2026-07-06', '09:15:00', '12:45:00', 0, 3.50, 'approved',
   'Wrote unit tests for the invoice module and fixed two rounding bugs found along the way.', NULL, 2, '2026-07-07 09:00:00'),
  (4, 1, 3, '2026-07-07', '13:00:00', '17:30:00', 0, 4.50, 'pending',
   'Finished the pricing page revisions and pushed the responsive fixes for mobile breakpoints.', NULL, NULL, NULL),
  (5, 1, 2, '2026-07-06', '10:00:00', '13:00:00', 0, 3.00, 'pending',
   'Customer support rotation - closed 11 tickets and documented two recurring issues.', NULL, NULL, NULL),
  (6, 1, 2, '2026-07-07', '09:00:00', '15:00:00', 0, 6.00, 'pending',
   'Migrated the analytics dashboard to the new charting library and verified the weekly numbers.', NULL, NULL, NULL),
  (7, 1, 2, '2026-07-01', '09:00:00', '17:00:00', 3600, 7.00, 'approved',
   'Closed out weekly operations tasks and prepared status notes for the client review.', NULL, 1, '2026-07-02 09:00:00');
