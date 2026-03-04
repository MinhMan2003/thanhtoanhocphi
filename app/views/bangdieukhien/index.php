<?php
/** @var int $studentCount */
/** @var int $invoicePending */
/** @var int $invoicePaid */

$pageTitle = 'Bảng điều khiển';
$apiKey = \App\Core\Config::API_KEY ?? 'changeme-api-key';
$baseUrl = '/Thanhtoanhocphi/public/api.php';
?>

<h1 class="page-title">Bảng điều khiển</h1>

<div class="cards">
    <div class="card">
        <div class="card__label">Tổng số học sinh</div>
        <div class="card__value"><?= (int)$studentCount ?></div>
    </div>
    <div class="card">
        <div class="card__label">Phiếu báo thu đang chờ</div>
        <div class="card__value"><?= (int)$invoicePending ?></div>
    </div>
    <div class="card">
        <div class="card__label">Phiếu báo thu đã thanh toán</div>
        <div class="card__value"><?= (int)$invoicePaid ?></div>
    </div>
</div>

<!-- API Documentation Section -->
<div class="card" style="margin-top: 2rem;">
    <h2 class="card__label" style="font-size: 1.25rem; margin-bottom: 1rem;">📚 Tài liệu API</h2>

    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <p style="margin: 0 0 0.5rem 0;"><strong>Base URL:</strong> <code><?= $baseUrl ?>/v1</code></p>
        <p style="margin: 0 0 0.5rem 0;"><strong>API Key:</strong> <code id="apiKeyDisplay"><?= $apiKey ?></code></p>
        <p style="margin: 0;">
            <button onclick="testApi()" class="btn btn--primary" style="padding: 0.25rem 0.75rem; font-size: 0.875rem;">Test API</button>
            <button onclick="copyApiKey()" class="btn" style="padding: 0.25rem 0.75rem; font-size: 0.875rem; margin-left: 0.5rem;">Copy API Key</button>
        </p>
    </div>

    <details style="margin-bottom: 1rem;">
        <summary style="cursor: pointer; font-weight: 600; color: #2563eb;">▼ STUDENTS - Học sinh</summary>
        <table style="width: 100%; margin-top: 0.5rem; font-size: 0.875rem; border-collapse: collapse;">
            <tr style="background: #f1f5f9;"><th style="padding: 0.5rem; text-align: left;">Method</th><th style="padding: 0.5rem; text-align: left;">Endpoint</th><th style="padding: 0.5rem; text-align: left;">Mô tả</th></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/students</code></td><td>Danh sách học sinh</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/students/{id}</code></td><td>Chi tiết học sinh</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #f59e0b; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">POST</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/students</code></td><td>Tạo học sinh mới</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #3b82f6; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">PUT</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/students/{id}</code></td><td>Cập nhật học sinh</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #ef4444; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">DEL</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/students/{id}</code></td><td>Xóa học sinh</td></tr>
        </table>
    </details>

    <details style="margin-bottom: 1rem;">
        <summary style="cursor: pointer; font-weight: 600; color: #2563eb;">▼ FEE CATEGORIES - Khoản thu</summary>
        <table style="width: 100%; margin-top: 0.5rem; font-size: 0.875rem; border-collapse: collapse;">
            <tr style="background: #f1f5f9;"><th style="padding: 0.5rem; text-align: left;">Method</th><th style="padding: 0.5rem; text-align: left;">Endpoint</th><th style="padding: 0.5rem; text-align: left;">Mô tả</th></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/feecategories</code></td><td>Danh sách khoản thu</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/feecategories/{id}</code></td><td>Chi tiết khoản thu</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #f59e0b; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">POST</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/feecategories</code></td><td>Tạo khoản thu mới</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #3b82f6; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">PUT</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/feecategories/{id}</code></td><td>Cập nhật khoản thu</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #ef4444; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">DEL</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/feecategories/{id}</code></td><td>Xóa khoản thu</td></tr>
        </table>
    </details>

    <details style="margin-bottom: 1rem;">
        <summary style="cursor: pointer; font-weight: 600; color: #2563eb;">▼ INVOICES - Phiếu báo thu</summary>
        <table style="width: 100%; margin-top: 0.5rem; font-size: 0.875rem; border-collapse: collapse;">
            <tr style="background: #f1f5f9;"><th style="padding: 0.5rem; text-align: left;">Method</th><th style="padding: 0.5rem; text-align: left;">Endpoint</th><th style="padding: 0.5rem; text-align: left;">Mô tả</th></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/invoices</code></td><td>Danh sách phiếu</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/invoices/{id}</code></td><td>Chi tiết phiếu</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #f59e0b; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">POST</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/invoices</code></td><td>Tạo phiếu mới</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #3b82f6; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">PUT</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/invoices/{id}</code></td><td>Cập nhật phiếu</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #f59e0b; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">POST</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/invoices/{id}/mark-paid</code></td><td>Đánh dấu đã thanh toán</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #ef4444; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">DEL</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/invoices/{id}</code></td><td>Xóa phiếu</td></tr>
        </table>
    </details>

    <details style="margin-bottom: 1rem;">
        <summary style="cursor: pointer; font-weight: 600; color: #2563eb;">▼ PAYMENTS - Thanh toán</summary>
        <table style="width: 100%; margin-top: 0.5rem; font-size: 0.875rem; border-collapse: collapse;">
            <tr style="background: #f1f5f9;"><th style="padding: 0.5rem; text-align: left;">Method</th><th style="padding: 0.5rem; text-align: left;">Endpoint</th><th style="padding: 0.5rem; text-align: left;">Mô tả</th></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/payments</code></td><td>Danh sách thanh toán</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/payments/{id}</code></td><td>Chi tiết thanh toán</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #f59e0b; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">POST</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/payments</code></td><td>Tạo thanh toán mới</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #3b82f6; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">PUT</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/payments/{id}</code></td><td>Cập nhật thanh toán</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #ef4444; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">DEL</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/payments/{id}</code></td><td>Xóa thanh toán</td></tr>
        </table>
    </details>

    <details style="margin-bottom: 1rem;">
        <summary style="cursor: pointer; font-weight: 600; color: #2563eb;">▼ STATS - Thống kê</summary>
        <table style="width: 100%; margin-top: 0.5rem; font-size: 0.875rem; border-collapse: collapse;">
            <tr style="background: #f1f5f9;"><th style="padding: 0.5rem; text-align: left;">Method</th><th style="padding: 0.5rem; text-align: left;">Endpoint</th><th style="padding: 0.5rem; text-align: left;">Mô tả</th></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/stats</code></td><td>Thống kê tổng quan</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/stats/overview</code></td><td>Thống kê chi tiết</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/stats/monthly?year=2026</code></td><td>Thống kê theo tháng</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/stats/by-fee-category</code></td><td>Thống kê theo khoản thu</td></tr>
            <tr><td style="padding: 0.25rem 0.5rem;"><span style="background: #22c55e; color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;">GET</span></td><td style="padding: 0.25rem 0.5rem;"><code>/v1/stats/by-class</code></td><td>Thống kê theo lớp</td></tr>
        </table>
    </details>

    <div style="background: #fef3c7; padding: 1rem; border-radius: 8px; border-left: 4px solid #f59e0b;">
        <strong style="color: #92400e;">💡 Hướng dẫn:</strong>
        <ul style="margin: 0.5rem 0 0 1rem; color: #92400e; font-size: 0.875rem;">
            <li>Gửi header <code>X-API-Key: &lt;api-key&gt;</code> hoặc dùng query <code>?api_key=&lt;api-key&gt;</code></li>
            <li>Filter: thêm query params như <code>?status=pending&student_id=1</code></li>
            <li>Phân trang: <code>?page=1&limit=20</code></li>
        </ul>
    </div>

    <div id="apiTestResult" style="display: none; margin-top: 1rem; padding: 1rem; border-radius: 8px; background: #1e293b; color: #22c55e; font-family: monospace; font-size: 0.875rem; white-space: pre-wrap; overflow-x: auto;"></div>
</div>

<script>
function testApi() {
    const resultDiv = document.getElementById('apiTestResult');
    resultDiv.style.display = 'block';
    resultDiv.textContent = 'Đang gọi API...';
    resultDiv.style.background = '#1e293b';
    resultDiv.style.color = '#fbbf24';

    fetch('<?= $baseUrl ?>?path=students&api_key=<?= $apiKey ?>', {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        resultDiv.textContent = JSON.stringify(data, null, 2);
        resultDiv.style.background = '#1e293b';
        resultDiv.style.color = '#22c55e';
    })
    .catch(err => {
        resultDiv.textContent = 'Lỗi: ' + err.message;
        resultDiv.style.background = '#1e293b';
        resultDiv.style.color = '#ef4444';
    });
}

function copyApiKey() {
    const apiKey = '<?= $apiKey ?>';
    navigator.clipboard.writeText(apiKey).then(() => {
        alert('Đã copy API Key!');
    });
}
</script>

<p class="text-muted" style="margin-top:1rem;">
    Sử dụng menu bên trái để quản lý học sinh, khoản thu, tạo phiếu báo thu và xem thông tin thanh toán.
</p>

