-- Database: thanhtoanhocphi
-- Script tạo bảng và dữ liệu mẫu cho hệ thống thanh toán học phí
-- Chạy script này trong phpMyAdmin hoặc MySQL client

CREATE DATABASE IF NOT EXISTS thanhtoanhocphi DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE thanhtoanhocphi;

-- Bảng users (tài khoản admin)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'accountant') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng students (học sinh)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    grade VARCHAR(10),
    class VARCHAR(20) NOT NULL,
    dob DATE,
    address VARCHAR(255),
    parent_name VARCHAR(100),
    parent_phone VARCHAR(20),
    parent_email VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng fee_categories (các khoản thu)
CREATE TABLE IF NOT EXISTS fee_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    default_amount INT NOT NULL DEFAULT 0,
    unit ENUM('month', 'day', 'term', 'once') DEFAULT 'month',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng invoices (phiếu báo thu)
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_code VARCHAR(30) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE,
    total_amount INT NOT NULL DEFAULT 0,
    status ENUM('pending', 'paid', 'partial', 'cancelled') DEFAULT 'pending',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng invoice_items (chi tiết từng dòng trong phiếu)
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    fee_category_id INT,
    description VARCHAR(255) NOT NULL,
    amount INT NOT NULL DEFAULT 0,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng payments (lưu thông tin thanh toán)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    payment_method ENUM('vietqr', 'bank_transfer', 'cash', 'other') DEFAULT 'cash',
    amount INT NOT NULL DEFAULT 0,
    paid_at DATETIME NOT NULL,
    bank_ref VARCHAR(100),
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dữ liệu mẫu: tài khoản admin (username: admin, password: admin123)
-- Password hash cho 'admin123' là bcrypt
INSERT INTO users (username, password_hash, full_name, role) VALUES
('admin', '$2y$10$fzuPaQL928u7VPWhBzJdDOzB9GoE8XH1TiiAY7u6ExAVTaq6YKhze', 'Quản trị viên', 'admin');

-- Dữ liệu mẫu: học sinh
INSERT INTO students (student_code, full_name, class, dob, address, parent_name, parent_phone, status) VALUES
('HS001', 'Nguyễn Văn An', '1A', '2015-03-15', '123 Nguyễn Trãi, Quận 1, TP.HCM', 'Nguyễn Văn A', '0901234567', 'active'),
('HS002', 'Trần Thị Bảo', '1A', '2015-05-20', '456 Lê Lợi, Quận 1, TP.HCM', 'Trần Văn B', '0901234568', 'active'),
('HS003', 'Lê Hoàng Cường', '1B', '2015-07-10', '789 Nguyễn Huệ, Quận 1, TP.HCM', 'Lê Văn C', '0901234569', 'active'),
('HS004', 'Phạm Thị Duyên', '2A', '2014-02-28', '321 Điện Biên Phủ, Quận 3, TP.HCM', 'Phạm Văn D', '0901234570', 'active'),
('HS005', 'Võ Hoàng Hùng', '2A', '2014-08-14', '654 Pasteur, Quận 1, TP.HCM', 'Võ Văn H', '0901234571', 'active');

-- Dữ liệu mẫu: khoản thu
INSERT INTO fee_categories (name, description, default_amount, unit, is_active) VALUES
('Học phí tháng', 'Học phí hàng tháng', 850000, 'month', 1),
('Tiền bán trú', 'Tiền ăn + ngủ nghỉ tại trường', 650000, 'month', 1),
('Tiền máy lạnh phòng ngủ', 'Phòng ngủ có máy lạnh', 150000, 'month', 1),
('Tiền bảo hiểm', 'Bảo hiểm học sinh năm học', 120000, 'once', 1),
('Tiền sách vở', 'Sách giáo khoa và vở bài tập', 350000, 'once', 1),
('Tiền vệ sinh', 'Vệ sinh trường học', 50000, 'month', 1);
