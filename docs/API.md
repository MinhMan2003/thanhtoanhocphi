# API Documentation - Thanh toán học phí

## Overview

REST API v2.0 cho hệ thống quản lý thanh toán học phí trường học.

**Base URL:** `/v1`

## Authentication

API sử dụng API Key authentication. Include API key trong header:

```
X-API-Key: <your-api-key>
```

Hoặc sử dụng Bearer token:

```
Authorization: Bearer <your-api-key>
```

Hoặc query parameter:

```
GET /v1/students?api_key=<your-api-key>
```

## Response Format

### Success Response

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "total": 100,
    "page": 1,
    "limit": 20,
    "totalPages": 5
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "ERR_CODE",
    "message": "Mô tả lỗi",
    "details": {
      "field": "Chi tiết lỗi"
    }
  }
}
```

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK - Thành công |
| 201 | Created - Tạo mới thành công |
| 400 | Bad Request - Request không hợp lệ |
| 401 | Unauthorized - Chưa xác thực |
| 403 | Forbidden - Không có quyền truy cập |
| 404 | Not Found - Không tìm thấy tài nguyên |
| 409 | Conflict - Xung đột dữ liệu |
| 500 | Internal Server Error - Lỗi server |

## Error Codes

| Code | Description |
|------|-------------|
| `INVALID_REQUEST` | Request không hợp lệ |
| `UNAUTHORIZED` | Chưa xác thực |
| `FORBIDDEN` | Không có quyền |
| `NOT_FOUND` | Không tìm thấy |
| `CONFLICT` | Xung đột dữ liệu |
| `VALIDATION` | Validation lỗi |
| `INTERNAL_ERROR` | Lỗi server |

## Roles & Permissions

| Role | Permissions |
|------|-------------|
| `admin` | Toàn quyền |
| `ketoan` | Quản lý tài chính (đọc/ghi học sinh, khoản thu, phiếu, thanh toán) |
| `giaovien` | Giáo viên (chỉ đọc) |
| `phuhuynh` | Phụ huynh (đọc thông tin con cái) |
| `public` | Tra cứu công khai (chỉ đọc phiếu) |

## Common Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `q` | string | Tìm kiếm text |
| `page` | int | Số trang (mặc định: 1) |
| `limit` | int | Số item/trang (mặc định: 20, tối đa: 100) |
| `status` | string | Lọc theo trạng thái |
| `grade` | string | Lọc theo khối |
| `class` | string | Lọc theo lớp |
| `from_date` | date | Lọc từ ngày (YYYY-MM-DD) |
| `to_date` | date | Lọc đến ngày (YYYY-MM-DD) |

---

## Endpoints

### 1. Học sinh (Students)

#### GET /v1/students

Danh sách học sinh với phân trang và lọc.

**Query Parameters:**
- `q` - Tìm kiếm theo tên, mã học sinh
- `class` - Lọc theo lớp
- `grade` - Lọc theo khối
- `status` - Lọc theo trạng thái (active/inactive)
- `page`, `limit` - Phân trang

**Example Request:**
```
GET /v1/students?grade=10&class=10A1&page=1&limit=20
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "student_code": "HS001",
      "full_name": "Nguyễn Văn A",
      "grade": "10",
      "class": "10A1",
      "dob": "2010-01-15",
      "parent_name": "Nguyễn Văn B",
      "parent_phone": "0912345678",
      "parent_email": "parent@example.com",
      "status": "active",
      "created_at": "2024-01-01 00:00:00"
    }
  ],
  "meta": {
    "total": 100,
    "page": 1,
    "limit": 20
  }
}
```

#### GET /v1/students/{id}

Chi tiết một học sinh.

**Example Request:**
```
GET /v1/students/1
```

#### POST /v1/students

Tạo học sinh mới.

**Request Body:**
```json
{
  "student_code": "HS001",
  "full_name": "Nguyễn Văn A",
  "grade": "10",
  "class": "10A1",
  "dob": "2010-01-15",
  "parent_name": "Nguyễn Văn B",
  "parent_phone": "0912345678",
  "parent_email": "parent@example.com",
  "status": "active"
}
```

#### PUT /v1/students/{id}

Cập nhật học sinh.

#### DELETE /v1/students/{id}

Xóa học sinh.

---

### 2. Khoản thu (Fee Categories)

#### GET /v1/feecategories

Danh sách khoản thu.

**Query Parameters:**
- `q` - Tìm kiếm
- `is_active` - Lọc theo trạng thái (0/1)
- `page`, `limit` - Phân trang

**Example Request:**
```
GET /v1/feecategories?is_active=1
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Học phí hàng tháng",
      "description": "Học phí tháng",
      "default_amount": 500000,
      "unit": "month",
      "is_active": 1,
      "created_at": "2024-01-01 00:00:00"
    }
  ],
  "meta": {
    "total": 5,
    "page": 1,
    "limit": 20
  }
}
```

#### POST /v1/feecategories

Tạo khoản thu mới.

**Request Body:**
```json
{
  "name": "Học phí hàng tháng",
  "description": "Học phí tháng",
  "default_amount": 500000,
  "unit": "month",
  "is_active": 1
}
```

#### PUT /v1/feecategories/{id}

Cập nhật khoản thu.

#### DELETE /v1/feecategories/{id}

Xóa khoản thu.

---

### 3. Phiếu báo thu (Invoices)

#### GET /v1/invoices

Danh sách phiếu báo thu.

**Query Parameters:**
- `q` - Tìm kiếm theo mã phiếu, tên học sinh
- `status` - Lọc (pending/paid/partial/cancelled)
- `student_id` - Lọc theo học sinh
- `month` - Lọc theo tháng
- `year` - Lọc theo năm
- `grade` - Lọc theo khối
- `class` - Lọc theo lớp
- `from_date` - Lọc từ ngày lập
- `to_date` - Lọc đến ngày lập
- `page`, `limit` - Phân trang

**Example Request:**
```
GET /v1/invoices?status=pending&grade=10&year=2026&page=1&limit=20
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "invoice_code": "PT2026010001",
      "student_id": 1,
      "student_name": "Nguyễn Văn A",
      "student_code": "HS001",
      "khoi": "10",
      "class": "10A1",
      "month": 1,
      "year": 2026,
      "issue_date": "2026-01-01",
      "due_date": "2026-01-15",
      "total_amount": 1500000,
      "status": "pending",
      "note": "",
      "created_at": "2026-01-01 00:00:00"
    }
  ],
  "meta": {
    "total": 50,
    "page": 1,
    "limit": 20,
    "totalPages": 3
  }
}
```

#### GET /v1/invoices/{id}

Chi tiết phiếu báo thu.

**Example Request:**
```
GET /v1/invoices/1
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "invoice_code": "PT2026010001",
    "student_id": 1,
    "student_name": "Nguyễn Văn A",
    "student_code": "HS001",
    "khoi": "10",
    "class": "10A1",
    "month": 1,
    "year": 2026,
    "issue_date": "2026-01-01",
    "due_date": "2026-01-15",
    "total_amount": 1500000,
    "status": "pending",
    "note": "",
    "items": [
      {
        "id": 1,
        "fee_category_id": 1,
        "fee_category_name": "Học phí hàng tháng",
        "description": "Học phí tháng 1/2026",
        "amount": 500000
      },
      {
        "id": 2,
        "fee_category_id": 2,
        "fee_category_name": "Bảo hiểm",
        "description": "Bảo hiểm học sinh",
        "amount": 1000000
      }
    ],
    "created_at": "2026-01-01 00:00:00"
  }
}
```

#### GET /v1/invoices/lookup/{code}

Tra cứu công khai phiếu theo mã (không cần auth).

**Example Request:**
```
GET /v1/invoices/lookup/PT2026010001
```

#### POST /v1/invoices

Tạo phiếu báo thu mới.

**Request Body:**
```json
{
  "student_id": 1,
  "month": 1,
  "year": 2026,
  "issue_date": "2026-01-01",
  "due_date": "2026-01-15",
  "note": "Học phí tháng 1",
  "items": [
    {
      "fee_category_id": 1,
      "description": "Học phí tháng 1/2026",
      "amount": 500000
    },
    {
      "fee_category_id": 2,
      "description": "Bảo hiểm học sinh",
      "amount": 1000000
    }
  ]
}
```

#### PUT /v1/invoices/{id}

Cập nhật phiếu báo thu.

#### DELETE /v1/invoices/{id}

Xóa phiếu báo thu.

#### POST /v1/invoices/{id}/mark-paid

Đánh dấu phiếu đã thanh toán đủ.

---

### 4. Thanh toán (Payments)

#### GET /v1/payments

Danh sách thanh toán.

**Query Parameters:**
- `q` - Tìm kiếm
- `invoice_id` - Lọc theo phiếu
- `payment_method` - Lọc (vietqr/bank_transfer/cash/other)
- `from_date` - Lọc từ ngày
- `to_date` - Lọc đến ngày
- `page`, `limit` - Phân trang

**Example Request:**
```
GET /v1/payments?invoice_id=1&page=1&limit=20
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "invoice_id": 1,
      "invoice_code": "PT2026010001",
      "student_name": "Nguyễn Văn A",
      "payment_method": "bank_transfer",
      "amount": 1500000,
      "paid_at": "2026-01-05 10:30:00",
      "bank_ref": "TXN123456",
      "note": "Chuyển khoản",
      "created_at": "2026-01-05 10:30:00"
    }
  ],
  "meta": {
    "total": 100,
    "page": 1,
    "limit": 20,
    "totalPages": 5
  }
}
```

#### GET /v1/payments/{id}

Chi tiết thanh toán.

#### POST /v1/payments

Tạo thanh toán mới.

**Request Body:**
```json
{
  "invoice_id": 1,
  "payment_method": "bank_transfer",
  "amount": 1500000,
  "paid_at": "2026-01-05 10:30:00",
  "bank_ref": "TXN123456",
  "note": "Chuyển khoản"
}
```

#### PUT /v1/payments/{id}

Cập nhật thanh toán.

#### DELETE /v1/payments/{id}

Xóa thanh toán.

---

### 5. Thống kê (Stats)

#### GET /v1/stats

Thống kê tổng quan.

**Example Response:**
```json
{
  "success": true,
  "data": {
    "student_count": 500,
    "invoice_pending": 100,
    "invoice_paid": 400,
    "payment_total": 500000000,
    "payment_today": 5000000,
    "payment_this_month": 100000000
  }
}
```

#### GET /v1/stats/overview

Thống kê chi tiết.

**Example Response:**
```json
{
  "success": true,
  "data": {
    "students": {
      "total": 500
    },
    "invoices": {
      "total": 600,
      "pending": 100,
      "partial": 50,
      "paid": 450
    },
    "amounts": {
      "total_invoiced": 900000000,
      "total_paid": 750000000,
      "unpaid": 150000000
    },
    "fee_categories": 10
  }
}
```

#### GET /v1/stats/monthly?year=2026

Thống kê theo tháng.

**Example Response:**
```json
{
  "success": true,
  "data": {
    "year": 2026,
    "months": [
      { "month": 1, "payment": 100000000, "invoice": 90000000 },
      { "month": 2, "payment": 95000000, "invoice": 90000000 },
      ...
    ]
  }
}
```

#### GET /v1/stats/by-class?year=2026

Thống kê theo lớp.

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "grade": "10",
      "class": "10A1",
      "student_count": 40,
      "total_invoice": 72000000,
      "total_paid": 60000000
    }
  ]
}
```

#### GET /v1/stats/by-fee-category

Thống kê theo khoản thu.

---

## Error Examples

### 400 - Validation Error

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION",
    "message": "Dữ liệu không hợp lệ",
    "details": {
      "student_code": "Mã học sinh không được để trống",
      "full_name": "Họ tên không được để trống"
    }
  }
}
```

### 401 - Unauthorized

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Yêu cầu xác thực. Vui lòng cung cấp API Key."
  }
}
```

### 403 - Forbidden

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Bạn không có quyền truy cập tài nguyên này."
  }
}
```

### 404 - Not Found

```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Không tìm thấy học sinh"
  }
}
```

---

## Database Migration

Để sử dụng API v2, chạy migration:

```sql
-- Thêm cột api_key và cập nhật roles
ALTER TABLE users ADD COLUMN api_key VARCHAR(64) NULL UNIQUE;
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'ketoan', 'giaovien', 'phuhuynh') DEFAULT 'admin';
```

File: `db/migration_api_v2.sql`

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 2.0 | 2026-03-04 | Chuẩn hóa error format, thêm auth/role, thêm filter nâng cao |
| 1.0 | - | Phiên bản đầu tiên |
