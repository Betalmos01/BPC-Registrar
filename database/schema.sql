CREATE DATABASE IF NOT EXISTS bpc_registrar;
USE bpc_registrar;

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_no VARCHAR(50) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  program VARCHAR(120) DEFAULT '',
  year_level VARCHAR(20) DEFAULT '',
  status VARCHAR(20) DEFAULT 'Active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE instructors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_no VARCHAR(50) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  department VARCHAR(120) DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_code VARCHAR(50) NOT NULL UNIQUE,
  title VARCHAR(150) NOT NULL,
  course VARCHAR(120) DEFAULT '',
  units INT NOT NULL DEFAULT 3,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  day VARCHAR(50) DEFAULT '',
  time VARCHAR(80) DEFAULT '',
  room VARCHAR(60) DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE TABLE enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  class_id INT NOT NULL,
  status VARCHAR(20) DEFAULT 'Enrolled',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE TABLE grades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  class_id INT NOT NULL,
  grade VARCHAR(20) NOT NULL,
  remarks VARCHAR(120) DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE TABLE documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  doc_type VARCHAR(120) NOT NULL,
  status VARCHAR(20) DEFAULT 'Pending',
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  department VARCHAR(120) NOT NULL,
  status VARCHAR(20) DEFAULT 'Pending',
  due_date DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE academic_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  coverage VARCHAR(120) NOT NULL,
  status VARCHAR(20) DEFAULT 'Draft',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  module VARCHAR(120) NOT NULL,
  details VARCHAR(255) DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  message VARCHAR(255) NOT NULL,
  status VARCHAR(20) DEFAULT 'Unread',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO roles (name) VALUES ('Administrator'), ('Registrar Staff');

INSERT IGNORE INTO users (role_id, username, password_hash, first_name, last_name, is_active)
VALUES
  ((SELECT id FROM roles WHERE name = 'Administrator'), 'adminaccount@gmail.com', '$2y$10$tB00KeauThyVvcbvDRqHoeA3BO8aPPCMm.B/WxnqzOhKtns1uHl7O', 'Admin', 'Account', 1),
  ((SELECT id FROM roles WHERE name = 'Registrar Staff'), 'staffaccount@gmail.com', '$2y$10$tB00KeauThyVvcbvDRqHoeA3BO8aPPCMm.B/WxnqzOhKtns1uHl7O', 'Staff', 'Account', 1);

-- Demo seed data (safe to re-run on fresh installs)

INSERT IGNORE INTO students (student_no, first_name, last_name, program, year_level, status)
VALUES
  ('2026-0001', 'Trisha', 'Garcia', 'BSIT', '1', 'Active'),
  ('2026-0002', 'Maria', 'Santos', 'BSCS', '2', 'Active'),
  ('2026-0003', 'Anne', 'Dela Cruz', 'BSBA', '1', 'Active'),
  ('2026-0004', 'John', 'Reyes', 'BSED', '3', 'On Hold'),
  ('2026-0005', 'Paolo', 'Lim', 'BEED', '2', 'Active'),
  ('2026-0006', 'Kevin', 'Bautista', 'BSECE', '4', 'Inactive'),
  ('2026-0007', 'Alyssa', 'Navarro', 'BSIT', '1', 'Active'),
  ('2026-0008', 'Marco', 'Tan', 'BSCS', '2', 'Active'),
  ('2026-0009', 'Janine', 'Torres', 'BSBA', '3', 'Active'),
  ('2026-0010', 'Carlo', 'Villanueva', 'BSED', '4', 'Active'),
  ('2026-0011', 'Bea', 'Cruz', 'BEED', '1', 'Active'),
  ('2026-0012', 'Jasmine', 'Flores', 'BSECE', '2', 'Active');

INSERT IGNORE INTO instructors (employee_no, first_name, last_name, department)
VALUES
  ('EMP-1001', 'Alyssa', 'Santos', 'Computer Studies'),
  ('EMP-1002', 'Marco', 'Reyes', 'Business'),
  ('EMP-1003', 'Janine', 'Dela Cruz', 'Education'),
  ('EMP-1004', 'Paolo', 'Garcia', 'Engineering'),
  ('EMP-1005', 'Bea', 'Lim', 'Computer Studies'),
  ('EMP-1006', 'Carlo', 'Navarro', 'Business');

INSERT IGNORE INTO classes (class_code, title, course, units)
VALUES
  ('IT 101', 'Introduction to Computing', 'BSIT', 3),
  ('IT 102', 'Programming Fundamentals', 'BSIT', 3),
  ('IT 201', 'Data Structures', 'BSIT', 3),
  ('IT 202', 'Database Systems', 'BSIT', 3),
  ('CS 101', 'Discrete Mathematics', 'BSCS', 3),
  ('CS 201', 'Algorithms', 'BSCS', 3),
  ('BA 101', 'Principles of Management', 'BSBA', 3),
  ('ED 101', 'Foundations of Education', 'BSED', 3);

INSERT INTO schedules (class_id, day, time, room)
SELECT c.id, 'Monday', '7:00 AM - 9:00 AM', 'Room 101'
FROM classes c
WHERE c.class_code = 'IT 101'
  AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.class_id = c.id);

INSERT INTO schedules (class_id, day, time, room)
SELECT c.id, 'Tuesday', '9:00 AM - 11:00 AM', 'Lab 1'
FROM classes c
WHERE c.class_code = 'IT 102'
  AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.class_id = c.id);

INSERT INTO schedules (class_id, day, time, room)
SELECT c.id, 'Wednesday', '11:00 AM - 1:00 PM', 'Room 201'
FROM classes c
WHERE c.class_code = 'IT 201'
  AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.class_id = c.id);

INSERT INTO schedules (class_id, day, time, room)
SELECT c.id, 'Thursday', '1:00 PM - 3:00 PM', 'Room 202'
FROM classes c
WHERE c.class_code = 'IT 202'
  AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.class_id = c.id);

INSERT INTO schedules (class_id, day, time, room)
SELECT c.id, 'Friday', '3:00 PM - 5:00 PM', 'Room 102'
FROM classes c
WHERE c.class_code = 'CS 101'
  AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.class_id = c.id);

INSERT INTO schedules (class_id, day, time, room)
SELECT c.id, 'Saturday', '9:00 AM - 11:00 AM', 'Lab 2'
FROM classes c
WHERE c.class_code = 'CS 201'
  AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.class_id = c.id);

INSERT INTO enrollments (student_id, class_id, status)
SELECT s.id, c.id, 'Enrolled'
FROM students s
JOIN classes c ON c.class_code IN ('IT 101', 'IT 102', 'CS 101', 'BA 101')
WHERE s.student_no IN ('2026-0001', '2026-0002', '2026-0003', '2026-0005', '2026-0007', '2026-0008')
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e WHERE e.student_id = s.id AND e.class_id = c.id
  );

INSERT INTO enrollments (student_id, class_id, status)
SELECT s.id, c.id, 'Pending'
FROM students s
JOIN classes c ON c.class_code IN ('IT 201', 'IT 202')
WHERE s.student_no IN ('2026-0004', '2026-0009', '2026-0010')
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e WHERE e.student_id = s.id AND e.class_id = c.id
  );

INSERT INTO grades (student_id, class_id, grade, remarks)
SELECT s.id, c.id, '1.50', 'Passed'
FROM students s
JOIN classes c ON c.class_code = 'IT 101'
WHERE s.student_no IN ('2026-0001', '2026-0002')
  AND NOT EXISTS (
    SELECT 1 FROM grades g WHERE g.student_id = s.id AND g.class_id = c.id
  );

INSERT INTO documents (student_id, doc_type, status, requested_at, completed_at)
SELECT s.id, 'Transcript of Records', 'Pending', NOW(), NULL
FROM students s
WHERE s.student_no IN ('2026-0001', '2026-0003', '2026-0005')
  AND NOT EXISTS (
    SELECT 1 FROM documents d WHERE d.student_id = s.id AND d.doc_type = 'Transcript of Records'
  );

INSERT INTO reports (title, department, status, due_date)
SELECT 'Monthly Enrollment Summary', 'Registrar Office', 'In Review', DATE_ADD(CURDATE(), INTERVAL 7 DAY)
WHERE NOT EXISTS (SELECT 1 FROM reports r WHERE r.title = 'Monthly Enrollment Summary');

INSERT INTO academic_reports (title, coverage, status)
SELECT 'AY 2025-2026 Midyear Grade Release', 'AY 2025-2026', 'In Review'
WHERE NOT EXISTS (SELECT 1 FROM academic_reports ar WHERE ar.title = 'AY 2025-2026 Midyear Grade Release');

INSERT INTO notifications (title, message, status)
SELECT 'Pending Document Requests', 'Review and process pending document requests in the queue.', 'Unread'
WHERE NOT EXISTS (SELECT 1 FROM notifications n WHERE n.title = 'Pending Document Requests');

INSERT INTO audit_logs (user_id, action, module, details, created_at)
SELECT u.id, 'Seed', 'System', 'Initial schema seed applied.', NOW()
FROM users u
WHERE u.username = 'adminaccount@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM audit_logs a WHERE a.module = 'System' AND a.action = 'Seed');


