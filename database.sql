CREATE DATABASE IF NOT EXISTS aura_clinic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aura_clinic;

DROP TABLE IF EXISTS staff_logs;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash CHAR(64) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL,
    min_quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NULL,
    service_name VARCHAR(120) NOT NULL,
    app_date DATETIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE staff_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_name VARCHAR(120) NOT NULL,
    action_text VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password_hash, full_name, role)
VALUES
('admin', SHA2('admin123', 256), 'Clinic Admin', 'admin');

INSERT INTO inventory (item_name, quantity, unit, min_quantity)
VALUES
('Botox 50U', 12, 'กล่อง', 5),
('Vitamin C Serum', 20, 'ขวด', 8),
('Disposable Syringe 5ml', 100, 'ชิ้น', 30),
('Medical Gloves', 40, 'กล่อง', 15),
('Cotton Pads', 75, 'แพ็ก', 20);

INSERT INTO appointments (customer_name, phone, service_name, app_date, status, notes)
VALUES
('Ananya S.', '0812345678', 'Facial Treatment', '2026-04-02 10:30:00', 'confirmed', 'นัดครั้งแรก'),
('Mali P.', '0898765432', 'Botox Consultation', '2026-04-02 14:00:00', 'pending', 'ต้องการปรึกษาก่อนทำ'),
('Kanya T.', '0861122334', 'IV Drip', '2026-04-03 11:00:00', 'completed', 'ลูกค้าเก่า'),
('Nida R.', '0839988776', 'Laser Treatment', '2026-04-04 16:30:00', 'cancelled', 'เลื่อนนัด');

INSERT INTO staff_logs (staff_name, action_text)
VALUES
('System', 'สร้างข้อมูลเริ่มต้นของระบบ Aura Clinic'),
('System', 'พร้อมใช้งานสำหรับ admin login');