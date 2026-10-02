-- =============================================================
-- Employee Management System (EMS)
-- Database schema + seed data
-- Import via phpMyAdmin or:  mysql -u root -p < employee_management.sql
-- Default logins:  admin / admin123   |   employee users / emp123
-- =============================================================

CREATE DATABASE IF NOT EXISTS employee_management
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE employee_management;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notices;
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS salary;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS employee;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- ROLES
-- -------------------------------------------------------------
CREATE TABLE roles (
    role_id   INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- USERS
-- -------------------------------------------------------------
CREATE TABLE users (
    user_id  INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80)  NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id  INT NOT NULL,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id)
        REFERENCES roles (role_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- EMPLOYEE
-- -------------------------------------------------------------
CREATE TABLE employee (
    emp_id     INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    contact    VARCHAR(30)  DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    position   VARCHAR(100) DEFAULT NULL,
    user_id    INT NOT NULL UNIQUE,
    CONSTRAINT fk_employee_user FOREIGN KEY (user_id)
        REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- ATTENDANCE
-- -------------------------------------------------------------
CREATE TABLE attendance (
    attendance_id   INT AUTO_INCREMENT PRIMARY KEY,
    emp_id          INT NOT NULL,
    attendance_date DATE NOT NULL,
    status          ENUM('Present','Absent','Leave') NOT NULL DEFAULT 'Present',
    UNIQUE KEY uq_attendance_emp_date (emp_id, attendance_date),
    CONSTRAINT fk_attendance_emp FOREIGN KEY (emp_id)
        REFERENCES employee (emp_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- SALARY
-- -------------------------------------------------------------
CREATE TABLE salary (
    salary_id    INT AUTO_INCREMENT PRIMARY KEY,
    emp_id       INT NOT NULL,
    salary_month DATE NOT NULL,
    basic_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    allowance    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    deduction    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    net_salary   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_salary_emp FOREIGN KEY (emp_id)
        REFERENCES employee (emp_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- LEAVE REQUESTS
-- -------------------------------------------------------------
CREATE TABLE leave_requests (
    leave_id  INT AUTO_INCREMENT PRIMARY KEY,
    emp_id    INT NOT NULL,
    from_date DATE NOT NULL,
    to_date   DATE NOT NULL,
    reason    TEXT NOT NULL,
    status    ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_leave_emp FOREIGN KEY (emp_id)
        REFERENCES employee (emp_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- NOTICES
-- -------------------------------------------------------------
CREATE TABLE notices (
    notice_id  INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(200) NOT NULL,
    body       TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================
-- SEED DATA
-- =============================================================

INSERT INTO roles (role_id, role_name) VALUES
    (1, 'Admin'),
    (2, 'Employee');

-- Admin account:  admin / admin123   (bcrypt hash)
INSERT INTO users (user_id, username, password, role_id) VALUES
    (1, 'admin', '$2y$10$Jg8Uw/O3o0nI7GzrofTA6OVMst8jyLB8U7nHb6K5KMJ7yWgTGRgo6', 1);

-- Employee accounts:  password = emp123  (bcrypt hash)
INSERT INTO users (user_id, username, password, role_id) VALUES
    (2, 'rsharma', '$2y$10$p71EKHZ/zc00DvEAgMCQFOXxV3E8OseCtkrv4h53NJjBnZoAWkuw2', 2),
    (3, 'amehta',  '$2y$10$p71EKHZ/zc00DvEAgMCQFOXxV3E8OseCtkrv4h53NJjBnZoAWkuw2', 2),
    (4, 'skhan',   '$2y$10$p71EKHZ/zc00DvEAgMCQFOXxV3E8OseCtkrv4h53NJjBnZoAWkuw2', 2);

INSERT INTO employee (emp_id, name, email, contact, department, position, user_id) VALUES
    (1, 'Rahul Sharma', 'rahul.sharma@ems.local', '+91 98765 43210', 'Engineering', 'Software Developer', 2),
    (2, 'Anita Mehta',  'anita.mehta@ems.local',  '+91 98123 45678', 'Human Resources', 'HR Executive', 3),
    (3, 'Salman Khan',  'salman.khan@ems.local',  '+91 99001 12233', 'Finance', 'Accountant', 4);

INSERT INTO attendance (emp_id, attendance_date, status) VALUES
    (1, CURDATE() - INTERVAL 2 DAY, 'Present'),
    (1, CURDATE() - INTERVAL 1 DAY, 'Present'),
    (1, CURDATE(),                  'Present'),
    (2, CURDATE() - INTERVAL 2 DAY, 'Present'),
    (2, CURDATE() - INTERVAL 1 DAY, 'Leave'),
    (2, CURDATE(),                  'Absent'),
    (3, CURDATE() - INTERVAL 2 DAY, 'Absent'),
    (3, CURDATE() - INTERVAL 1 DAY, 'Present'),
    (3, CURDATE(),                  'Present');

INSERT INTO salary (emp_id, salary_month, basic_salary, allowance, deduction, net_salary) VALUES
    (1, DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01'), 55000.00, 5000.00, 2000.00, 58000.00),
    (1, DATE_FORMAT(CURDATE(), '%Y-%m-01'),                    55000.00, 5500.00, 1500.00, 59000.00),
    (2, DATE_FORMAT(CURDATE(), '%Y-%m-01'),                    48000.00, 4000.00, 1200.00, 50800.00),
    (3, DATE_FORMAT(CURDATE(), '%Y-%m-01'),                    52000.00, 3000.00, 2500.00, 52500.00);

INSERT INTO leave_requests (emp_id, from_date, to_date, reason, status) VALUES
    (1, CURDATE() + INTERVAL 7 DAY,  CURDATE() + INTERVAL 9 DAY,  'Family function at home town.', 'Pending'),
    (2, CURDATE() - INTERVAL 1 DAY,  CURDATE() - INTERVAL 1 DAY,  'Medical appointment.',            'Approved'),
    (3, CURDATE() + INTERVAL 15 DAY, CURDATE() + INTERVAL 20 DAY, 'Vacation with family.',           'Rejected');

INSERT INTO notices (title, body) VALUES
    ('Welcome to the Employee Management System',
     'All employees are requested to keep their profile information up to date and check this notice board regularly for announcements.'),
    ('Salary Processing Schedule',
     'Salaries for the current month will be processed on the last working day. Please verify your attendance records before the 25th.'),
    ('Office Timing Reminder',
     'Office hours are 9:30 AM to 6:00 PM, Monday to Friday. Kindly mark your attendance daily and apply for leave in advance.');
