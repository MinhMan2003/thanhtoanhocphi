<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\HocSinh;
use App\Models\LoaiKhoanThu;
use App\Models\HoaDon;
use App\Models\ThanhToan;
use App\Models\User;

/**
 * REST API Controller - Version 2 (Standardized)
 * ==============================================
 * Xử lý các endpoint API cho hệ thống Thanh toán học phí
 *
 * Base URL: /v1
 *
 * Resources:
 *   - students        : Quản lý học sinh
 *   - feecategories  : Quản lý khoản thu
 *   - invoices        : Quản lý phiếu báo thu
 *   - payments        : Quản lý thanh toán
 *   - stats           : Thống kê báo cáo
 *
 * Response Format:
 *   Success: { "success": true, "data": {...}, "meta": {...} }
 *   Error:   { "success": false, "error": { "code": "ERR_CODE", "message": "...", "details": {...} } }
 *
 * HTTP Status Codes:
 *   200 - OK, 201 - Created, 400 - Bad Request, 401 - Unauthorized
 *   403 - Forbidden, 404 - Not Found, 409 - Conflict, 500 - Server Error
 *
 * Authentication:
 *   Header: X-API-Key: <api_key>
 *   Hoặc:  Authorization: Bearer <token>
 *
 * Roles: admin, ketoan, giaovien, phuhuynh
 */
class ApiController
{
    private const VERSION = '2.0';
    
    // Error codes
    const ERR_INVALID_REQUEST = 'INVALID_REQUEST';
    const ERR_UNAUTHORIZED = 'UNAUTHORIZED';
    const ERR_FORBIDDEN = 'FORBIDDEN';
    const ERR_NOT_FOUND = 'NOT_FOUND';
    const ERR_CONFLICT = 'CONFLICT';
    const ERR_VALIDATION = 'VALIDATION';
    const ERR_INTERNAL = 'INTERNAL_ERROR';

    private ?array $currentUser = null;
    private ?string $currentRole = null;

    // Role permissions
    private const PERMISSIONS = [
        'admin' => ['*'],
        'ketoan' => ['students:read', 'students:write', 'feecategories:read', 'feecategories:write', 'invoices:read', 'invoices:write', 'payments:read', 'payments:write', 'stats:read'],
        'giaovien' => ['students:read', 'invoices:read', 'payments:read', 'stats:read'],
        'phuhuynh' => ['students:read:own', 'invoices:read:own', 'payments:read:own'],
        'public' => ['invoices:lookup'],
    ];

    private function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function getRequestBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Standardized error response
     */
    private function error(string $code, string $message, int $httpCode = 400, array $details = null): void
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ]
        ];
        if ($details !== null) {
            $response['error']['details'] = $details;
        }
        $this->json($response, $httpCode);
    }

    /**
     * Standardized success response
     */
    private function success(array $data, int $code = 200, array $meta = null): void
    {
        $response = [
            'success' => true,
            'data' => $data,
        ];
        if ($meta !== null) {
            $response['meta'] = $meta;
        }
        $this->json($response, $code);
    }

    /**
     * Authentication - Check API Key
     */
    private function authenticate(): bool
    {
        // Check API Key header
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
        
        // Check Bearer token
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($authHeader, 'Bearer ')) {
            $apiKey = substr($authHeader, 7);
        }

        // Public endpoints don't require auth
        if (empty($apiKey)) {
            // Try to check if public access is allowed
            return false;
        }

        // Validate API key (use user table for now)
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, username, full_name, role FROM users WHERE api_key = :api_key LIMIT 1');
        $stmt->execute(['api_key' => $apiKey]);
        $user = $stmt->fetch();

        if ($user) {
            $this->currentUser = $user;
            $this->currentRole = $user['role'];
            return true;
        }

        return false;
    }

    /**
     * Check if current user has permission
     */
    private function hasPermission(string $permission): bool
    {
        // Public lookup permission
        if ($this->currentRole === null && str_ends_with($permission, ':lookup')) {
            return true;
        }

        if ($this->currentRole === null) {
            return false;
        }

        $perms = self::PERMISSIONS[$this->currentRole] ?? [];
        
        // Admin has all permissions
        if (in_array('*', $perms)) {
            return true;
        }

        return in_array($permission, $perms);
    }

    /**
     * Require authentication
     */
    private function requireAuth(string $permission = null): void
    {
        if (!$this->authenticate()) {
            $this->error(self::ERR_UNAUTHORIZED, 'Yêu cầu xác thực. Vui lòng cung cấp API Key.', 401);
            exit;
        }

        if ($permission && !$this->hasPermission($permission)) {
            $this->error(self::ERR_FORBIDDEN, 'Bạn không có quyền truy cập tài nguyên này.', 403);
            exit;
        }
    }

    public function run(string $path, string $method): void
    {
        $segments = array_values(array_filter(explode('/', $path)));
        $resource = $segments[0] ?? '';
        $id = isset($segments[1]) && ctype_digit($segments[1]) ? (int)$segments[1] : null;
        $sub = $segments[2] ?? null;

        // Parse query params
        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));

        // Try to authenticate but don't fail immediately for public endpoints
        $this->authenticate();

        switch ($resource) {
            case 'students':
                $this->handleStudents($method, $id, $q, $page, $limit);
                break;
            case 'feecategories':
                $this->handleFeeCategories($method, $id, $q, $page, $limit);
                break;
            case 'invoices':
                if ($id !== null && $sub === 'mark-paid') {
                    $this->markInvoicePaid($id);
                } elseif ($id !== null && $sub === 'lookup') {
                    $this->publicLookupInvoice($id);
                } else {
                    $this->handleInvoices($method, $id, $q, $page, $limit);
                }
                break;
            case 'payments':
                $this->handlePayments($method, $id, $q, $page, $limit);
                break;
            case 'stats':
                $this->handleStats($segments, $method);
                break;
            case '':
                $this->apiInfo();
                break;
            default:
                $this->error(self::ERR_NOT_FOUND, 'Endpoint không tồn tại', 404);
        }
    }

    /**
     * API Info
     */
    private function apiInfo(): void
    {
        $this->success([
            'name' => 'API Thanh toán học phí',
            'version' => self::VERSION,
            'base_url' => '/v1',
            'authentication' => [
                'type' => 'API Key',
                'header' => 'X-API-Key: <api_key>',
                'roles' => array_keys(self::PERMISSIONS),
            ],
            'endpoints' => $this->getEndpointDocs(),
        ]);
    }

    private function getEndpointDocs(): array
    {
        return [
            'GET    /v1/students' => 'Danh sách học sinh',
            'GET    /v1/students/{id}' => 'Chi tiết học sinh',
            'POST   /v1/students' => 'Tạo học sinh mới',
            'PUT    /v1/students/{id}' => 'Cập nhật học sinh',
            'DELETE /v1/students/{id}' => 'Xóa học sinh',
            'GET    /v1/feecategories' => 'Danh sách khoản thu',
            'GET    /v1/feecategories/{id}' => 'Chi tiết khoản thu',
            'POST   /v1/feecategories' => 'Tạo khoản thu mới',
            'PUT    /v1/feecategories/{id}' => 'Cập nhật khoản thu',
            'DELETE /v1/feecategories/{id}' => 'Xóa khoản thu',
            'GET    /v1/invoices' => 'Danh sách phiếu (filter: status, student_id, month, year, grade, class, from_date, to_date)',
            'GET    /v1/invoices/{id}' => 'Chi tiết phiếu',
            'GET    /v1/invoices/lookup/{code}' => 'Tra cứu công khai phiếu',
            'POST   /v1/invoices' => 'Tạo phiếu mới',
            'PUT    /v1/invoices/{id}' => 'Cập nhật phiếu',
            'DELETE /v1/invoices/{id}' => 'Xóa phiếu',
            'POST   /v1/invoices/{id}/mark-paid' => 'Đánh dấu đã thanh toán',
            'GET    /v1/payments' => 'Danh sách thanh toán',
            'GET    /v1/payments/{id}' => 'Chi tiết thanh toán',
            'POST   /v1/payments' => 'Tạo thanh toán mới',
            'PUT    /v1/payments/{id}' => 'Cập nhật thanh toán',
            'DELETE /v1/payments/{id}' => 'Xóa thanh toán',
            'GET    /v1/stats' => 'Thống kê tổng quan',
            'GET    /v1/stats/overview' => 'Thống kê chi tiết',
            'GET    /v1/stats/monthly' => 'Thống kê theo tháng',
            'GET    /v1/stats/by-fee-category' => 'Thống kê theo khoản thu',
            'GET    /v1/stats/by-class' => 'Thống kê theo lớp',
        ];
    }

    // ============================================================
    // STUDENTS / HỌC SINH
    // ============================================================
    private function handleStudents(string $method, ?int $id, string $q, int $page, int $limit): void
    {
        $filters = [
            'class' => trim($_GET['class'] ?? ''),
            'grade' => trim($_GET['grade'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
        ];

        // Check permission
        if ($method !== 'GET') {
            $this->requireAuth('students:write');
        } else {
            $this->requireAuth('students:read');
        }

        switch ($method) {
            case 'GET':
                if ($id !== null) {
                    $student = HocSinh::find($id);
                    if (!$student) {
                        $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy học sinh', 404);
                        return;
                    }
                    $this->success($student);
                } else {
                    $result = HocSinh::paginate($q, $page, $limit, $filters);
                    $this->success(
                        $result['data'],
                        200,
                        ['total' => $result['total'], 'page' => $result['page'], 'limit' => $result['limit']]
                    );
                }
                break;
            case 'POST':
                $body = $this->getRequestBody();
                $data = $this->validateStudentData($body);
                if ($data['error']) {
                    $this->error(self::ERR_VALIDATION, 'Dữ liệu không hợp lệ', 400, $data['errors']);
                    return;
                }
                $newId = HocSinh::create($data['data']);
                $data['data']['id'] = $newId;
                $this->success($data['data'], 201);
                break;
            case 'PUT':
                if ($id === null) {
                    $this->error(self::ERR_INVALID_REQUEST, 'Thiếu id học sinh', 400);
                    return;
                }
                if (!HocSinh::find($id)) {
                    $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy học sinh', 404);
                    return;
                }
                $body = $this->getRequestBody();
                $existing = HocSinh::find($id);
                $data = $this->validateStudentData($body, $existing);
                if ($data['error']) {
                    $this->error(self::ERR_VALIDATION, 'Dữ liệu không hợp lệ', 400, $data['errors']);
                    return;
                }
                HocSinh::update($id, $data['data']);
                $data['data']['id'] = $id;
                $this->success($data['data']);
                break;
            case 'DELETE':
                if ($id === null) {
                    $this->error(self::ERR_INVALID_REQUEST, 'Thiếu id học sinh', 400);
                    return;
                }
                if (!HocSinh::find($id)) {
                    $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy học sinh', 404);
                    return;
                }
                HocSinh::delete($id);
                $this->success(['message' => 'Đã xóa học sinh']);
                break;
            default:
                $this->error(self::ERR_INVALID_REQUEST, 'Method không được hỗ trợ', 405);
        }
    }

    private function validateStudentData(array $body, array $existing = null): array
    {
        $errors = [];
        $data = [];

        $data['student_code'] = trim($body['student_code'] ?? $existing['student_code'] ?? '');
        $data['full_name'] = trim($body['full_name'] ?? $existing['full_name'] ?? '');
        $data['grade'] = trim($body['grade'] ?? $existing['grade'] ?? '');
        $data['class'] = trim($body['class'] ?? $existing['class'] ?? '');
        $data['dob'] = trim($body['dob'] ?? $existing['dob'] ?? '');
        $data['parent_name'] = trim($body['parent_name'] ?? $existing['parent_name'] ?? '');
        $data['parent_phone'] = trim($body['parent_phone'] ?? $existing['parent_phone'] ?? '');
        $data['parent_email'] = trim($body['parent_email'] ?? $existing['parent_email'] ?? '');
        $data['status'] = in_array($body['status'] ?? $existing['status'] ?? 'active', ['active', 'inactive'], true) 
            ? ($body['status'] ?? $existing['status'] ?? 'active') 
            : 'active';

        if (empty($data['student_code'])) {
            $errors['student_code'] = 'Mã học sinh không được để trống';
        }
        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Họ tên không được để trống';
        }
        if (empty($data['class'])) {
            $errors['class'] = 'Lớp không được để trống';
        }

        return ['error' => !empty($errors), 'errors' => $errors, 'data' => $data];
    }

    // ============================================================
    // FEE CATEGORIES / KHOẢN THU
    // ============================================================
    private function handleFeeCategories(string $method, ?int $id, string $q, int $page, int $limit): void
    {
        $filters = [
            'is_active' => $_GET['is_active'] ?? null,
        ];

        if ($method !== 'GET') {
            $this->requireAuth('feecategories:write');
        } else {
            $this->requireAuth('feecategories:read');
        }

        switch ($method) {
            case 'GET':
                if ($id !== null) {
                    $row = LoaiKhoanThu::find($id);
                    if (!$row) {
                        $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy khoản thu', 404);
                        return;
                    }
                    $this->success($row);
                } else {
                    $result = LoaiKhoanThu::paginate($q, $page, $limit, $filters);
                    $this->success(
                        $result['data'],
                        200,
                        ['total' => $result['total'], 'page' => $result['page'], 'limit' => $result['limit']]
                    );
                }
                break;
            case 'POST':
                $body = $this->getRequestBody();
                $data = $this->validateFeeCategoryData($body);
                if ($data['error']) {
                    $this->error(self::ERR_VALIDATION, 'Dữ liệu không hợp lệ', 400, $data['errors']);
                    return;
                }
                $newId = LoaiKhoanThu::create($data['data']);
                $data['data']['id'] = $newId;
                $this->success($data['data'], 201);
                break;
            case 'PUT':
                if ($id === null) {
                    $this->error(self::ERR_INVALID_REQUEST, 'Thiếu id khoản thu', 400);
                    return;
                }
                if (!LoaiKhoanThu::find($id)) {
                    $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy khoản thu', 404);
                    return;
                }
                $body = $this->getRequestBody();
                $existing = LoaiKhoanThu::find($id);
                $data = $this->validateFeeCategoryData($body, $existing);
                if ($data['error']) {
                    $this->error(self::ERR_VALIDATION, 'Dữ liệu không hợp lệ', 400, $data['errors']);
                    return;
                }
                LoaiKhoanThu::update($id, $data['data']);
                $data['data']['id'] = $id;
                $this->success($data['data']);
                break;
            case 'DELETE':
                if ($id === null) {
                    $this->error(self::ERR_INVALID_REQUEST, 'Thiếu id khoản thu', 400);
                    return;
                }
                if (!LoaiKhoanThu::find($id)) {
                    $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy khoản thu', 404);
                    return;
                }
                LoaiKhoanThu::delete($id);
                $this->success(['message' => 'Đã xóa khoản thu']);
                break;
            default:
                $this->error(self::ERR_INVALID_REQUEST, 'Method không được hỗ trợ', 405);
        }
    }

    private function validateFeeCategoryData(array $body, array $existing = null): array
    {
        $errors = [];
        $data = [];

        $data['name'] = trim($body['name'] ?? $existing['name'] ?? '');
        $data['description'] = trim($body['description'] ?? $existing['description'] ?? '');
        $data['default_amount'] = (int)($body['default_amount'] ?? $existing['default_amount'] ?? 0);
        $data['unit'] = in_array($body['unit'] ?? $existing['unit'] ?? 'month', ['month', 'day', 'term', 'once'], true) 
            ? ($body['unit'] ?? $existing['unit'] ?? 'month') 
            : 'month';
        $data['is_active'] = isset($body['is_active']) ? ((int)$body['is_active'] ? 1 : 0) : (int)($existing['is_active'] ?? 1);

        if (empty($data['name'])) {
            $errors['name'] = 'Tên khoản thu không được để trống';
        }

        return ['error' => !empty($errors), 'errors' => $errors, 'data' => $data];
    }

    // ============================================================
    // INVOICES / PHIẾU BÁO THU
    // ============================================================
    private function handleInvoices(string $method, ?int $id, string $q, int $page, int $limit): void
    {
        // Extended filters for invoices
        $filters = [
            'status' => trim($_GET['status'] ?? ''),
            'student_id' => (int)($_GET['student_id'] ?? 0),
            'month' => (int)($_GET['month'] ?? 0),
            'year' => (int)($_GET['year'] ?? 0),
            'grade' => trim($_GET['grade'] ?? ''),
            'class' => trim($_GET['class'] ?? ''),
            'from_date' => trim($_GET['from_date'] ?? ''),
            'to_date' => trim($_GET['to_date'] ?? ''),
        ];

        if ($method !== 'GET') {
            $this->requireAuth('invoices:write');
        } else {
            $this->requireAuth('invoices:read');
        }

        switch ($method) {
            case 'GET':
                if ($id !== null) {
                    $invoice = HoaDon::find($id);
                    if (!$invoice) {
                        $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy phiếu báo thu', 404);
                        return;
                    }
                    $this->success($invoice);
                } else {
                    $result = HoaDon::paginate($q, $page, $limit, $filters);
                    $this->success(
                        $result['items'],
                        200,
                        [
                            'total' => $result['total'],
                            'page' => $result['page'],
                            'limit' => $result['limit'],
                            'totalPages' => $result['totalPages'],
                        ]
                    );
                }
                break;
            case 'POST':
                $body = $this->getRequestBody();
                $data = $this->validateInvoiceData($body);
                if ($data['error']) {
                    $this->error(self::ERR_VALIDATION, 'Dữ liệu không hợp lệ', 400, $data['errors']);
                    return;
                }
                $newId = HoaDon::create($data['data']);
                $data['data']['id'] = $newId;
                $this->success($data['data'], 201);
                break;
            case 'PUT':
            case 'PATCH':
                if ($id === null) {
                    $this->error(self::ERR_INVALID_REQUEST, 'Thiếu id phiếu', 400);
                    return;
                }
                $invoice = HoaDon::find($id);
                if (!$invoice) {
                    $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy phiếu báo thu', 404);
                    return;
                }
                $body = $this->getRequestBody();
                $data = $this->validateInvoiceData($body, $invoice);
                if ($data['error']) {
                    $this->error(self::ERR_VALIDATION, 'Dữ liệu không hợp lệ', 400, $data['errors']);
                    return;
                }
                HoaDon::update($id, $data['data']);
                $data['data']['id'] = $id;
                $this->success($data['data']);
                break;
            case 'DELETE':
                if ($id === null) {
                    $this->error(self::ERR_INVALID_REQUEST, 'Thiếu id phiếu', 400);
                    return;
                }
                if (!HoaDon::find($id)) {
                    $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy phiếu báo thu', 404);
                    return;
                }
                HoaDon::delete($id);
                $this->success(['message' => 'Đã xóa phiếu báo thu']);
                break;
            default:
                $this->error(self::ERR_INVALID_REQUEST, 'Method không được hỗ trợ', 405);
        }
    }

    private function validateInvoiceData(array $body, array $existing = null): array
    {
        $errors = [];
        $data = [];

        $studentId = (int)($body['student_id'] ?? $existing['student_id'] ?? 0);
        $items = $body['items'] ?? $existing['items'] ?? [];

        if ($studentId <= 0) {
            $errors['student_id'] = 'ID học sinh không hợp lệ';
        }

        if (empty($items)) {
            $errors['items'] = 'Cần ít nhất một dòng khoản thu';
        }

        $totalAmount = 0;
        $normalizedItems = [];
        foreach ($items as $item) {
            $amount = (int)($item['amount'] ?? 0);
            if ($amount > 0) {
                $normalizedItems[] = [
                    'fee_category_id' => !empty($item['fee_category_id']) ? (int)$item['fee_category_id'] : null,
                    'description' => trim((string)($item['description'] ?? '')),
                    'amount' => $amount,
                ];
                $totalAmount += $amount;
            }
        }

        if (empty($normalizedItems)) {
            $errors['items'] = 'Số tiền phải lớn hơn 0';
        }

        $data = [
            'invoice_code' => $existing['invoice_code'] ?? HoaDon::generateCode(),
            'student_id' => $studentId,
            'month' => (int)($body['month'] ?? $existing['month'] ?? date('n')),
            'year' => (int)($body['year'] ?? $existing['year'] ?? date('Y')),
            'issue_date' => trim($body['issue_date'] ?? $existing['issue_date'] ?? date('Y-m-d')),
            'due_date' => trim($body['due_date'] ?? $existing['due_date'] ?? '') ?: null,
            'total_amount' => $totalAmount,
            'status' => in_array($body['status'] ?? $existing['status'] ?? 'pending', ['pending', 'paid', 'partial', 'cancelled'], true)
                ? ($body['status'] ?? $existing['status'] ?? 'pending')
                : 'pending',
            'note' => trim($body['note'] ?? $existing['note'] ?? ''),
            'items' => $normalizedItems,
        ];

        return ['error' => !empty($errors), 'errors' => $errors, 'data' => $data];
    }

    /**
     * Public lookup invoice by code
     */
    private function publicLookupInvoice(int $id): void
    {
        $invoice = HoaDon::find($id);
        if (!$invoice) {
            $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy phiếu báo thu', 404);
            return;
        }

        // Return limited info for public
        $this->success([
            'invoice_code' => $invoice['invoice_code'],
            'student_name' => $invoice['student_name'],
            'total_amount' => $invoice['total_amount'],
            'status' => $invoice['status'],
            'month' => $invoice['month'],
            'year' => $invoice['year'],
        ]);
    }

    /**
     * Mark invoice as paid
     */
    private function markInvoicePaid(int $id): void
    {
        $this->requireAuth('invoices:write');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error(self::ERR_INVALID_REQUEST, 'Method không được hỗ trợ', 405);
            return;
        }

        $invoice = HoaDon::find($id);
        if (!$invoice) {
            $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy phiếu báo thu', 404);
            return;
        }

        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare('UPDATE invoices SET status = :status WHERE id = :id');
        $stmt->execute(['status' => 'paid', 'id' => $id]);

        $this->success(['message' => 'Đã đánh dấu phiếu đã thanh toán']);
    }

    // ============================================================
    // PAYMENTS / THANH TOÁN
    // ============================================================
    private function handlePayments(string $method, ?int $id, string $q, int $page, int $limit): void
    {
        $filters = [
            'invoice_id' => (int)($_GET['invoice_id'] ?? 0),
            'payment_method' => trim($_GET['payment_method'] ?? ''),
            'from_date' => trim($_GET['from_date'] ?? ''),
            'to_date' => trim($_GET['to_date'] ?? ''),
        ];

        if ($method !== 'GET') {
            $this->requireAuth('payments:write');
        } else {
            $this->requireAuth('payments:read');
        }

        switch ($method) {
            case 'GET':
                if ($id !== null) {
                    $payment = ThanhToan::find($id);
                    if (!$payment) {
                        $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy thanh toán', 404);
                        return;
                    }
                    $this->success($payment);
                } else {
                    $result = ThanhToan::paginate($q, $page, $limit, $filters);
                    $this->success(
                        $result['items'],
                        200,
                        [
                            'total' => $result['total'],
                            'page' => $result['page'],
                            'limit' => $result['limit'],
                            'totalPages' => $result['totalPages'],
                        ]
                    );
                }
                break;
            case 'POST':
                $body = $this->getRequestBody();
                $data = $this->validatePaymentData($body);
                if ($data['error']) {
                    $this->error(self::ERR_VALIDATION, 'Dữ liệu không hợp lệ', 400, $data['errors']);
                    return;
                }
                $newId = ThanhToan::create($data['data']);
                $data['data']['id'] = $newId;
                $this->success($data['data'], 201);
                break;
            case 'PUT':
            case 'PATCH':
                if ($id === null) {
                    $this->error(self::ERR_INVALID_REQUEST, 'Thiếu id thanh toán', 400);
                    return;
                }
                $payment = ThanhToan::find($id);
                if (!$payment) {
                    $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy thanh toán', 404);
                    return;
                }
                $body = $this->getRequestBody();
                $data = $this->validatePaymentData($body, $payment);
                if ($data['error']) {
                    $this->error(self::ERR_VALIDATION, 'Dữ liệu không hợp lệ', 400, $data['errors']);
                    return;
                }
                ThanhToan::update($id, $data['data']);
                $data['data']['id'] = $id;
                $this->success($data['data']);
                break;
            case 'DELETE':
                if ($id === null) {
                    $this->error(self::ERR_INVALID_REQUEST, 'Thiếu id thanh toán', 400);
                    return;
                }
                if (!ThanhToan::find($id)) {
                    $this->error(self::ERR_NOT_FOUND, 'Không tìm thấy thanh toán', 404);
                    return;
                }
                ThanhToan::delete($id);
                $this->success(['message' => 'Đã xóa thanh toán']);
                break;
            default:
                $this->error(self::ERR_INVALID_REQUEST, 'Method không được hỗ trợ', 405);
        }
    }

    private function validatePaymentData(array $body, array $existing = null): array
    {
        $errors = [];
        $data = [];

        $invoiceId = (int)($body['invoice_id'] ?? $existing['invoice_id'] ?? 0);
        $amount = (int)($body['amount'] ?? $existing['amount'] ?? 0);

        if ($invoiceId <= 0) {
            $errors['invoice_id'] = 'ID phiếu không hợp lệ';
        }
        if ($amount <= 0) {
            $errors['amount'] = 'Số tiền phải lớn hơn 0';
        }

        $data = [
            'invoice_id' => $invoiceId,
            'payment_method' => in_array($body['payment_method'] ?? $existing['payment_method'] ?? 'cash', ['vietqr', 'bank_transfer', 'cash', 'other'], true)
                ? ($body['payment_method'] ?? $existing['payment_method'] ?? 'cash')
                : 'cash',
            'amount' => $amount,
            'paid_at' => trim($body['paid_at'] ?? $existing['paid_at'] ?? date('Y-m-d H:i:s')),
            'bank_ref' => trim($body['bank_ref'] ?? $existing['bank_ref'] ?? ''),
            'note' => trim($body['note'] ?? $existing['note'] ?? ''),
        ];

        return ['error' => !empty($errors), 'errors' => $errors, 'data' => $data];
    }

    // ============================================================
    // STATS / THỐNG KÊ
    // ============================================================
    private function handleStats(array $segments, string $method): void
    {
        $this->requireAuth('stats:read');

        $sub = $segments[1] ?? '';

        switch ($sub) {
            case 'monthly':
                $this->statsMonthly();
                break;
            case 'by-fee-category':
                $this->statsByFeeCategory();
                break;
            case 'by-class':
                $this->statsByClass();
                break;
            case 'overview':
                $this->statsOverview();
                break;
            default:
                $this->statsGeneral();
        }
    }

    private function statsGeneral(): void
    {
        $pdo = \App\Core\Database::getConnection();
        $studentCount = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
        $invoicePending = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE status IN ('pending','partial')")->fetchColumn();
        $invoicePaid = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'paid'")->fetchColumn();
        $stats = ThanhToan::getStats();

        $this->success([
            'student_count' => $studentCount,
            'invoice_pending' => $invoicePending,
            'invoice_paid' => $invoicePaid,
            'payment_total' => $stats['total'],
            'payment_today' => $stats['today'],
            'payment_this_month' => $stats['month'],
        ]);
    }

    private function statsMonthly(): void
    {
        $pdo = \App\Core\Database::getConnection();
        $year = (int)($_GET['year'] ?? date('Y'));

        $stmt = $pdo->prepare("
            SELECT MONTH(paid_at) as month, COALESCE(SUM(amount), 0) as total
            FROM payments WHERE YEAR(paid_at) = :year
            GROUP BY MONTH(paid_at) ORDER BY month
        ");
        $stmt->execute(['year' => $year]);
        $payments = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT i.month, COALESCE(SUM(i.total_amount), 0) as total
            FROM invoices i WHERE i.year = :year
            GROUP BY i.month ORDER BY i.month
        ");
        $stmt->execute(['year' => $year]);
        $invoices = $stmt->fetchAll();

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = ['month' => $m, 'payment' => 0, 'invoice' => 0];
        }
        foreach ($payments as $row) {
            $months[(int)$row['month']]['payment'] = (int)$row['total'];
        }
        foreach ($invoices as $row) {
            $months[(int)$row['month']]['invoice'] = (int)$row['total'];
        }

        $this->success(['year' => $year, 'months' => array_values($months)]);
    }

    private function statsByFeeCategory(): void
    {
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->query("
            SELECT fc.id, fc.name as fee_category_name, COALESCE(SUM(ii.amount), 0) as total
            FROM fee_categories fc
            LEFT JOIN invoice_items ii ON fc.id = ii.fee_category_id
            LEFT JOIN invoices i ON ii.invoice_id = i.id AND i.status = 'paid'
            GROUP BY fc.id, fc.name ORDER BY total DESC
        ");
        $this->success($stmt->fetchAll());
    }

    private function statsByClass(): void
    {
        $pdo = \App\Core\Database::getConnection();
        $year = (int)($_GET['year'] ?? date('Y'));

        $stmt = $pdo->prepare("
            SELECT s.grade, s.class,
                COUNT(DISTINCT s.id) as student_count,
                COALESCE(SUM(i.total_amount), 0) as total_invoice,
                COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total_amount ELSE 0 END), 0) as total_paid
            FROM students s
            LEFT JOIN invoices i ON s.id = i.student_id AND i.year = :year
            WHERE s.status = 'active'
            GROUP BY s.grade, s.class
            ORDER BY s.grade, s.class
        ");
        $stmt->execute(['year' => $year]);
        $this->success($stmt->fetchAll());
    }

    private function statsOverview(): void
    {
        $pdo = \App\Core\Database::getConnection();

        $totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status = 'active'")->fetchColumn();
        $totalInvoices = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
        $pendingInvoices = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'pending'")->fetchColumn();
        $partialInvoices = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'partial'")->fetchColumn();
        $paidInvoices = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'paid'")->fetchColumn();
        $totalInvoiceAmount = (int)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM invoices")->fetchColumn();
        $totalPaidAmount = (int)$pdo->query("SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN invoices i ON p.invoice_id = i.id")->fetchColumn();
        $feeCategoryCount = (int)$pdo->query("SELECT COUNT(*) FROM fee_categories WHERE is_active = 1")->fetchColumn();

        $this->success([
            'students' => ['total' => $totalStudents],
            'invoices' => [
                'total' => $totalInvoices,
                'pending' => $pendingInvoices,
                'partial' => $partialInvoices,
                'paid' => $paidInvoices,
            ],
            'amounts' => [
                'total_invoiced' => $totalInvoiceAmount,
                'total_paid' => $totalPaidAmount,
                'unpaid' => $totalInvoiceAmount - $totalPaidAmount,
            ],
            'fee_categories' => $feeCategoryCount,
        ]);
    }
}
