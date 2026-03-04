<?php
declare(strict_types=1);

namespace App\Core;

class Config
{
    // Thông tin kết nối database
    public const DB_HOST = '127.0.0.1';
    public const DB_NAME = 'thanhtoanhocphi';
    public const DB_USER = 'root';
    public const DB_PASS = '';
    public const DB_CHARSET = 'utf8mb4';

    // Thông tin trường học
    public const SCHOOL_NAME = 'Trường Thực Hành Sư Phạm';
    public const SCHOOL_ADDRESS = 'Thành phố Trà Vinh';
    public const SCHOOL_PHONE = 'Chưa có thông tin';

    // Cấu hình ngân hàng để tạo VietQR
    public const BANK_ID = 'BIDV';
    public const BANK_ACCOUNT = '73510001284830';
    public const BANK_ACCOUNT_NAME = 'PHAM MINH MAN';

    // Khóa đơn giản bảo vệ API
    public const API_KEY = 'changeme-api-key';
    
    // Cấu hình SMTP cho gửi email
    // Điền thông tin nếu muốn gửi email thật
    public const SMTP_HOST = '';
    public const SMTP_PORT = 587;
    public const SMTP_USER = '';
    public const SMTP_PASS = '';

    public static function baseUrl(): string
    {
        // Giả sử project đặt tại http://localhost/Thanhtoanhocphi/public
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');

        return $scheme . '://' . $host . $path;
    }
}

