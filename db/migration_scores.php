-- Tạo bảng điểm cho học sinh
CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    score_type ENUM('mieng', '15p', '45p', 'hk') NOT NULL,
    score_value DECIMAL(4,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_student_semester (student_id, semester, school_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng môn học
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    grade INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm một số môn học mẫu
INSERT INTO subjects (name, code, grade) VALUES
('Toán', 'TOAN', 1),
('Tiếng Việt', 'TV', 1),
('Tự nhiên và Xã hội', 'TNXH', 1),
('Đạo đức', 'DD', 1),
('Thể dục', 'TD', 1),
('Âm nhạc', 'AN', 1),
('Mỹ thuật', 'MT', 1),
('Toán', 'TOAN', 2),
('Tiếng Việt', 'TV', 2),
('Tự nhiên và Xã hội', 'TNXH', 2),
('Toán', 'TOAN', 3),
('Tiếng Việt', 'TV', 3),
('Khoa học', 'KH', 3),
('Lịch sử và Địa lý', 'LS-DL', 3),
('Toán', 'TOAN', 4),
('Tiếng Việt', 'TV', 4),
('Khoa học', 'KH', 4),
('Lịch sử và Địa lý', 'LS-DL', 4),
('Toán', 'TOAN', 5),
('Tiếng Việt', 'TV', 5),
('Khoa học', 'KH', 5),
('Lịch sử và Địa lý', 'LS-DL', 5);
