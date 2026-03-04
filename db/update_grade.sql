-- Script cập nhật: Thêm cột grade (khối) vào bảng students
-- Chạy script này trong phpMyAdmin hoặc MySQL client

ALTER TABLE students ADD COLUMN grade VARCHAR(10) AFTER full_name;
