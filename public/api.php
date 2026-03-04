<?php
declare(strict_types=1);

/**
 * REST API Entry Point
 * Base URL: http://localhost/Thanhtoanhocphi/public/api.php
 * Version: v1
 *
 * URL Structure: /v1/{resource}
 * Ví dụ:
 *   - /v1/students          → danh sách học sinh
 *   - /v1/invoices/1        → chi tiết phiếu #1
 *   - /v1/stats/overview    → thống kê tổng quan
 *
 * Xác thực: Gửi API Key trong header:
 *   X-API-Key: <your-api-key>
 *   hoặc Authorization: Bearer <your-api-key>
 *   hoặc query: ?api_key=<your-api-key>
 *
 * API Key cấu hình trong app/core/Config.php (API_KEY)
 *
 * ==================== DANH SÁCH API ====================
 *
 * --- STUDENTS (Học sinh) ---
 * GET    /v1/students               Danh sách học sinh (?class=&status=&q=&page=&limit=)
 * GET    /v1/students/{id}           Chi tiết học sinh
 * POST   /v1/students               Tạo học sinh mới
 * PUT    /v1/students/{id}          Cập nhật học sinh
 * DELETE /v1/students/{id}          Xóa học sinh
 *
 * --- FEE CATEGORIES (Khoản thu) ---
 * GET    /v1/feecategories          Danh sách khoản thu (?is_active=&q=&page=&limit=)
 * GET    /v1/feecategories/{id}      Chi tiết khoản thu
 * POST   /v1/feecategories          Tạo khoản thu mới
 * PUT    /v1/feecategories/{id}     Cập nhật khoản thu
 * DELETE /v1/feecategories/{id}     Xóa khoản thu
 *
 * --- INVOICES (Phiếu báo thu) ---
 * GET    /v1/invoices               Danh sách phiếu (?status=&student_id=&month=&year=&q=&page=&limit=)
 * GET    /v1/invoices/{id}          Chi tiết phiếu
 * POST   /v1/invoices               Tạo phiếu mới
 * PUT    /v1/invoices/{id}          Cập nhật phiếu
 * DELETE /v1/invoices/{id}          Xóa phiếu
 * POST   /v1/invoices/{id}/mark-paid Đánh dấu đã thanh toán
 *
 * --- PAYMENTS (Thanh toán) ---
 * GET    /v1/payments               Danh sách thanh toán (?invoice_id=&payment_method=&q=&page=&limit=)
 * GET    /v1/payments/{id}          Chi tiết thanh toán
 * POST   /v1/payments               Tạo thanh toán mới
 * PUT    /v1/payments/{id}          Cập nhật thanh toán
 * DELETE /v1/payments/{id}          Xóa thanh toán
 *
 * --- STATS (Thống kê) ---
 * GET    /v1/stats                  Thống kê tổng quan
 * GET    /v1/stats/overview        Thống kê chi tiết
 * GET    /v1/stats/monthly          Thống kê theo tháng (?year=YYYY)
 * GET    /v1/stats/by-fee-category  Thống kê theo khoản thu
 * GET    /v1/stats/by-class         Thống kê theo lớp
 *
 * --- ROOT ---
 * GET    /v1                        Danh sách tất cả endpoints
 *
 * ==================== HƯỚNG DẪN SỬ DỤNG ====================
 *
 * Test trên trình duyệt:
 *   http://localhost/Thanhtoanhocphi/public/api.php/v1/students?api_key=changeme-api-key
 *
 * Test với fetch (JavaScript):
 *   fetch('/Thanhtoanhocphi/public/api.php/v1/students', {
 *     headers: { 'X-API-Key': 'changeme-api-key' }
 *   })
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../app/core/Config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/number_to_words.php';
require_once __DIR__ . '/../app/helpers/vietqr.php';

spl_autoload_register(static function (string $class): void {
    if (strpos($class, 'App\\') !== 0) {
        return;
    }
    $relative = str_replace('App\\', '', $class);
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
    $file = __DIR__ . '/../app/' . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

/**
 * API Version 1 Router
 * Xử lý path: /v1/students, /v1/invoices, /v1/stats, ...
 */

use App\Controllers\ApiController;

// Lấy path từ query string hoặc PATH_INFO
$path = $_GET['path'] ?? '';
if ($path === '' && !empty($_SERVER['PATH_INFO'])) {
    $path = trim($_SERVER['PATH_INFO'], '/');
}

// Chuẩn hóa path: bỏ tiền tố "v1/" nếu có
// /v1/students -> students
// /v1/stats/overview -> stats/overview
if (str_starts_with($path, 'v1/')) {
    $path = substr($path, 3);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$apiKey = null;
if (!empty($_SERVER['HTTP_X_API_KEY'])) {
    $apiKey = trim($_SERVER['HTTP_X_API_KEY']);
} elseif (!empty($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/^Bearer\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
    $apiKey = trim($m[1]);
} elseif (!empty($_GET['api_key'])) {
    // Cho phép gửi API key qua query param để dễ test trên trình duyệt:
    // http://localhost/.../api.php?path=students&api_key=changeme-api-key
    $apiKey = trim((string)$_GET['api_key']);
}

if ($apiKey === null || $apiKey !== \App\Core\Config::API_KEY) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Unauthorized',
        'message' => 'Thiếu hoặc sai API Key. Gửi header X-API-Key hoặc Authorization: Bearer <key>',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $controller = new ApiController();
    $controller->run($path, $method);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server Error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
