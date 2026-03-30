CREATE DATABASE IF NOT EXISTS aura_clinic_db;
USE aura_clinic_db;

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100),
    service_name VARCHAR(100),
    app_date DATE,
    status ENUM('Pending', 'Confirmed', 'Cancelled') DEFAULT 'Pending'
);

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100),
    quantity INT DEFAULT 0,
    unit VARCHAR(50)
);

CREATE TABLE staff_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_name VARCHAR(100),
    action_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);