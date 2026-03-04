-- Migration: Add Public Lookup Security Features
-- Run this script to add rate limiting and audit logging for public invoice lookup

-- ============================================
-- Bảng log tra cứu công khai (rate limit + audit)
-- ============================================
CREATE TABLE IF NOT EXISTS public_lookup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL COMMENT 'IP của client',
    session_id VARCHAR(64) COMMENT 'Session ID',
    receipt_code VARCHAR(50) NOT NULL COMMENT 'Mã phiếu thu được tra cứu',
    lookup_result ENUM('SUCCESS', 'FAILED_INVALID_CODE', 'FAILED_INVALID_VERIFY', 'RATE_LIMITED') NOT NULL,
    student_id INT NULL COMMENT 'ID học sinh nếu tra cứu thành công',
    user_agent VARCHAR(255) COMMENT 'User agent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, created_at),
    INDEX idx_session_time (session_id, created_at),
    INDEX idx_receipt_code (receipt_code),
    INDEX idx_student_time (student_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Thêm cột receipt_code vào invoices (nếu chưa có)
-- ============================================
-- ALTER TABLE invoices ADD COLUMN IF NOT EXISTS receipt_code VARCHAR(50) NULL UNIQUE COMMENT 'Mã tra cứu cho phụ huynh';
-- ALTER TABLE invoices ADD INDEX idx_receipt_code (receipt_code);

-- ============================================
-- Cập nhật receipt_code cho các hóa đơn hiện có (chạy 1 lần)
-- ============================================
-- UPDATE invoices SET receipt_code = invoice_code WHERE receipt_code IS NULL;
