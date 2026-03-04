<?php
/**
 * TCPDF - Giấy báo thu học phí 2 trang
 * Trang 1: Thông tin phiếu thu, bảng phí, tổng kết
 * Trang 2: Thanh toán QR Code
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/number_to_words.php';
require_once __DIR__ . '/vietqr.php';

function generateInvoicePDF(array $invoice, array $items, array $qrPayment = []): void
{
    // Thông tin trường
    $schoolName = \App\Core\Config::SCHOOL_NAME ?? 'Trường học';
    $schoolAddress = \App\Core\Config::SCHOOL_ADDRESS ?? '';
    $schoolPhone = \App\Core\Config::SCHOOL_PHONE ?? '';
    
    // Tính tổng tiền
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += (int)($item['amount'] ?? 0);
    }
    $totalFormatted = number_format($totalAmount, 0, ',', '.');
    $totalText = numberToVietnameseWords($totalAmount);
    
    $hasQR = !empty($qrPayment) && !empty($qrPayment['qr_image_url']);

    // Khởi tạo TCPDF
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetCreator('School Payment System');
    $pdf->SetAuthor($schoolName);
    $pdf->SetTitle('Giấy báo thu học phí - ' . ($invoice['invoice_code'] ?? ''));
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->setFontSubsetting(true);
    // DejaVu Serif giống Times New Roman, hỗ trợ tiếng Việt
    $pdf->SetFont('dejavuserif', '', 11, '', true);

    // ========== TRANG 1 (bố cục giống trang HTML pdfAction) ==========
    $pdf->AddPage();

    $html = '
    <style>
        .header { margin-bottom: 12px; padding-bottom: 8px; }
        .school-name { font-size: 14px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        .school-info { font-size: 11px; }

        .title { text-align: center; margin-bottom: 12px; }
        .title h2 { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        .title .subtitle { font-size: 12px; font-style: italic; }

        .info { margin-bottom: 10px; font-size: 11px; }
        .info-row { display: table; width: 100%; }
        .info-cell { display: table-cell; padding: 2px 0; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 11px; }
        table.items th, table.items td { border: 1px solid #000; padding: 5px 6px; }
        table.items th { background: #eee; text-align: center; font-weight: bold; }
        table.items td:nth-child(1) { text-align: center; width: 30px; }
        table.items td:nth-child(2) { text-align: left; }
        table.items td:nth-child(3) { text-align: left; width: 80px; }
        table.items td:nth-child(4) { text-align: right; width: 80px; }

        .summary { margin-bottom: 8px; font-size: 11px; }
        .summary-row { display: table; width: 100%; }
        .summary-cell { display: table-cell; padding: 2px 0; }
        .summary-cell:first-child { text-align: right; width: 140px; }
        .summary-cell:last-child { text-align: right; }
        .summary-total { border-top: 1px solid #000; font-weight: bold; }

        .amount-text { font-size: 11px; margin-bottom: 5px; }
        .note-red { font-size: 10px; color: #cc0000; font-style: italic; margin-bottom: 10px; }

        .qr-section { border: 1px dashed #198754; border-radius: 4px; padding: 10px; margin-bottom: 10px; }
        .qr-title { font-weight: bold; color: #198754; font-size: 11px; text-align: center; margin-bottom: 8px; text-transform: uppercase; }
        .qr-content { display: table; width: 100%; }
        .qr-cell { display: table-cell; vertical-align: middle; }
        .qr-cell img { width: 150px; height: 150px; }
        .qr-cell:last-child { padding-left: 15px; font-size: 10px; }
        .qr-row { margin-bottom: 3px; }

        .footer { display: table; width: 100%; margin-top: 20px; font-size: 10px; }
        .footer-cell { display: table-cell; width: 50%; text-align: center; vertical-align: bottom; }
        .sign-line { border-top: 1px solid #000; padding-top: 25px; }
    </style>

    <!-- HEADER - Căn trái -->
    <div class="header">
        <div class="school-name">' . htmlspecialchars($schoolName) . '</div>
        <div class="school-info">Địa chỉ: ' . htmlspecialchars($schoolAddress) . '</div>
        <div class="school-info">Điện thoại: ' . htmlspecialchars($schoolPhone) . '</div>
    </div>

    <!-- TITLE - Căn giữa -->
    <div class="title">
        <h2>GIẤY BÁO THU HỌC PHÍ</h2>
        <div class="subtitle">Tháng ' . ($invoice['month'] ?? date('m')) . '/' . ($invoice['year'] ?? date('Y')) . ' - Niên học ' . ($invoice['year'] ?? date('Y')) . '-' . ((int)($invoice['year'] ?? date('Y')) + 1) . '</div>
    </div>

    <!-- INFO - Thông tin học sinh -->
    <div class="info">
        <div class="info-row">
            <div class="info-cell"><strong>Họ tên:</strong> ' . htmlspecialchars($invoice['student_name'] ?? '') . '</div>
            <div class="info-cell"><strong>Mã HS:</strong> ' . htmlspecialchars($invoice['student_code'] ?? '') . '</div>
        </div>
        <div class="info-row">
            <div class="info-cell"><strong>Lớp:</strong> ' . htmlspecialchars($invoice['class'] ?? '') . '</div>
            <div class="info-cell"><strong>Mã phiếu:</strong> ' . htmlspecialchars($invoice['invoice_code'] ?? '') . '</div>
        </div>
    </div>

    <!-- TABLE - Bảng phí -->
    <table class="items">
        <thead>
            <tr>
                <th>STT</th>
                <th>Nội dung</th>
                <th>Ghi chú</th>
                <th>Số tiền</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($items as $index => $item) {
        $desc = htmlspecialchars($item['description'] ?? $item['fee_category_name'] ?? '');
        $amount = number_format((int)($item['amount'] ?? 0), 0, ',', '.');
        $noteItem = htmlspecialchars($item['note'] ?? '');
        $html .= '
            <tr>
                <td>' . ($index + 1) . '</td>
                <td>' . $desc . '</td>
                <td>' . $noteItem . '</td>
                <td>' . $amount . '</td>
            </tr>';
    }

    $html .= '
        </tbody>
    </table>

    <!-- SUMMARY - Tổng kết -->
    <div class="summary">
        <div class="summary-row">
            <div class="summary-cell">Công nợ kỳ này:</div>
            <div class="summary-cell">' . $totalFormatted . '</div>
        </div>
        <div class="summary-row">
            <div class="summary-cell">Nợ kỳ trước:</div>
            <div class="summary-cell">0</div>
        </div>
        <div class="summary-row">
            <div class="summary-cell">Khấu trừ:</div>
            <div class="summary-cell">0</div>
        </div>
        <div class="summary-row summary-total">
            <div class="summary-cell">TỔNG CỘNG:</div>
            <div class="summary-cell">' . $totalFormatted . '</div>
        </div>
    </div>

    <!-- VIẾT BẰNG CHỮ -->
    <div class="amount-text"><strong>Viết bằng chữ:</strong> ' . htmlspecialchars($totalText) . '</div>';

    if (!empty($invoice['note'])) {
        $html .= '<div class="amount-text"><strong>Ghi chú:</strong> ' . htmlspecialchars($invoice['note']) . '</div>';
    }

    $html .= '<div class="note-red">* Vui lòng nhập đúng số tiền khi thanh toán qua QRCode</div>';

    // QR Payment (nếu có)
    if ($hasQR) {
        $html .= '
    <div class="qr-section">
        <div class="qr-title">THANH TOÁN QUA QR CODE</div>
        <div class="qr-content">
            <div class="qr-cell"><img src="' . htmlspecialchars($qrPayment['qr_image_url']) . '" alt="QR"></div>
            <div class="qr-cell">
                <div class="qr-row"><strong>Ngân hàng:</strong> ' . htmlspecialchars($qrPayment['bank_id'] ?? '') . '</div>
                <div class="qr-row"><strong>Số TK:</strong> ' . htmlspecialchars($qrPayment['account_number'] ?? '') . '</div>
                <div class="qr-row"><strong>Chủ TK:</strong> ' . htmlspecialchars($qrPayment['account_name'] ?? '') . '</div>
                <div class="qr-row"><strong>Số tiền:</strong> <strong style="color:#198754">' . $totalFormatted . '</strong></div>
                <div class="qr-row"><strong>Nội dung:</strong> ' . htmlspecialchars($invoice['invoice_code'] ?? '') . '</div>
            </div>
        </div>
    </div>';
    }

    // FOOTER - Chữ ký
    $html .= '
    <div class="footer">
        <div class="footer-cell"><div class="sign-line">Ngày...... tháng...... năm......<br><em>KT. HIỆU TRƯỞNG</em></div></div>
        <div class="footer-cell"><div class="sign-line">Ngày...... tháng...... năm......<br><em>NGƯỜI LẬP PHIẾU</em></div></div>
    </div>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Xuất PDF
    $filename = 'PhieuThu_' . ($invoice['invoice_code'] ?? date('Ymd')) . '.pdf';
    $pdf->Output($filename, 'D');
}

/**
 * Hàm tiện ích để tạo PDF từ model invoice
 */
function generatePdfFromInvoice($invoice): void
{
    $items = $invoice['items'] ?? [];
    $qrPayment = [];
    
    if (!empty($invoice['total_amount'])) {
        $qrPayment = getVietQRPaymentInfo((int)$invoice['total_amount'], $invoice['invoice_code'] ?? '');
    }
    
    generateInvoicePDF($invoice, $items, $qrPayment);
}
