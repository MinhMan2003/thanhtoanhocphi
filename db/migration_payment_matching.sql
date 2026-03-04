-- Migration: Add Payment Auto-Matching System
-- Run this script to add payment webhook and audit log functionality

-- ============================================
-- Bảng payments (lưu trữ webhook từ ngân hàng)
-- ============================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trans_id VARCHAR(100) NOT NULL UNIQUE COMMENT 'Mã giao dịch từ ngân hàng',
    amount INT NOT NULL COMMENT 'Số tiền giao dịch',
    content VARCHAR(255) COMMENT 'Nội dung chuyển khoản',
    bank_time DATETIME COMMENT 'Thời gian giao dịch từ ngân hàng',
    account_no VARCHAR(50) COMMENT 'Số tài khoản người chuyển',
    account_name VARCHAR(100) COMMENT 'Tên người chuyển',
    bank_id VARCHAR(20) COMMENT 'Mã ngân hàng',
    raw_payload JSON COMMENT 'Dữ liệu thô từ webhook',
    matched_hoadon_id INT NULL COMMENT 'ID hóa đơn đã match',
    match_status ENUM('MATCHED', 'UNMATCHED', 'PENDING') DEFAULT 'PENDING' COMMENT 'Trạng thái match',
    matched_at DATETIME NULL COMMENT 'Thời điểm match',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_trans_id (trans_id),
    INDEX idx_match_status (match_status),
    INDEX idx_matched_hoadon_id (matched_hoadon_id),
    INDEX idx_bank_time (bank_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Bảng audit_log (lịch sử thay đổi)
-- ============================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL COMMENT 'Loại entity (hoadon, payment, student...)',
    entity_id INT NOT NULL COMMENT 'ID của entity',
    action VARCHAR(50) NOT NULL COMMENT 'Hành động (created, updated, deleted, status_changed...)',
    field_changed VARCHAR(50) COMMENT 'Tên trường thay đổi',
    old_value TEXT COMMENT 'Giá trị cũ',
    new_value TEXT COMMENT 'Giá trị mới',
    user_id INT NULL COMMENT 'ID user thực hiện (null nếu từ system/webhook)',
    user_name VARCHAR(100) COMMENT 'Tên user hoặc system',
    ip_address VARCHAR(45) COMMENT 'IP address',
    user_agent VARCHAR(255) COMMENT 'User agent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Cập nhật bảng invoices (thêm cột receipt_code)
-- ============================================
-- ALTER TABLE invoices ADD COLUMN IF NOT EXISTS receipt_code VARCHAR(50) NULL UNIQUE COMMENT 'Mã tra cứu cho phụ huynh';

-- ============================================
-- Test data mẫu
-- ============================================
-- INSERT INTO payments (trans_id, amount, content, bank_time, account_no, account_name, match_status)
-- VALUES 
-- ('TXN001', 1500000, 'PT2026010001', '2026-01-05 10:30:00', '1234567890', 'NGUYEN VAN A', 'UNMATCHED'),
-- ('TXN002', 500000, 'HS001', '2026-01-05 11:00:00', '0987654321', 'TRAN THI B', 'UNMATCHED');
