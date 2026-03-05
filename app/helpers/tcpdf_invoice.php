<?php
/**
 * TCPDF - Phiếu báo thu học phí
 * Template mới - A4 dọc, margin 12mm, font DejaVuSans 11px
 * Bảng khoản thu dùng TCPDF Cell/MultiCell
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/number_to_words.php';
require_once __DIR__ . '/vietqr.php';

function tcpdfTryDownload(string $url): ?string
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }

    $data = null;

    if (filter_var($url, FILTER_VALIDATE_URL)) {
        if ((bool)ini_get('allow_url_fopen')) {
            $context = stream_context_create([
                'http' => ['timeout' => 5, 'header' => "User-Agent: Thanhtoanhocphi/1.0\r\n"],
                'https' => ['timeout' => 5, 'header' => "User-Agent: Thanhtoanhocphi/1.0\r\n"],
            ]);
            $data = @file_get_contents($url, false, $context);
        } elseif (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Thanhtoanhocphi/1.0');
            $data = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode < 200 || $httpCode >= 300) {
                $data = null;
            }
        }
    } else {
        if (is_file($url) && is_readable($url)) {
            $data = @file_get_contents($url);
        }
    }

    if ($data === false || $data === null || $data === '') {
        return null;
    }

    return $data;
}

function tcpdfLocalTempImageFromUrl(string $url, string $prefix = 'qr_'): ?string
{
    $raw = tcpdfTryDownload($url);
    if ($raw === null) {
        return null;
    }

    $tmpDir = sys_get_temp_dir();
    if ($tmpDir === '' || !is_dir($tmpDir) || !is_writable($tmpDir)) {
        return null;
    }

    $path = rtrim($tmpDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(8)) . '.png';
    $ok = @file_put_contents($path, $raw);
    if ($ok === false) {
        return null;
    }

    return $path;
}

/**
 * Format tiền VND với dấu chấm phân tách
 */
function formatVND(int $amount): string
{
    return number_format($amount, 0, ',', '.');
}

/**
 * Tạo PDF phiếu báo thu học phí - Dùng TCPDF Cell/MultiCell
 */
function generateInvoicePDFNew(array $invoice, array $items, array $qrPayment = []): void
{
    // Thông tin trường
    $schoolName = \App\Core\Config::SCHOOL_NAME ?? 'TRƯỜNG THỰC HÀNH SƯ PHẠM';
    $schoolAddress = \App\Core\Config::SCHOOL_ADDRESS ?? 'Thành phố Trà Vinh';
    $schoolPhone = \App\Core\Config::SCHOOL_PHONE ?? 'Chưa có thông tin';
    
    // Tính tổng tiền
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += (int)($item['amount'] ?? 0);
    }
    $totalFormatted = formatVND($totalAmount);
    $totalText = numberToVietnameseWords($totalAmount);
    
    // QR Payment
    $hasQR = !empty($qrPayment) && !empty($qrPayment['qr_image_url']);
    $qrImageSrc = $qrPayment['qr_image_url'] ?? '';
    $qrTempPath = null;
    if ($hasQR) {
        $qrTempPath = tcpdfLocalTempImageFromUrl((string)$qrImageSrc);
        if (!empty($qrTempPath)) {
            $qrImageSrc = $qrTempPath;
        } else {
            $hasQR = false;
        }
    }
    
    // Tháng bằng chữ
    $monthNames = [
        1 => 'Tháng Một', 2 => 'Tháng Hai', 3 => 'Tháng Ba', 4 => 'Tháng Tư',
        5 => 'Tháng Năm', 6 => 'Tháng Sáu', 7 => 'Tháng Bảy', 8 => 'Tháng Tám',
        9 => 'Tháng Chín', 10 => 'Tháng Mười', 11 => 'Tháng Mười Một', 12 => 'Tháng Mười Hai'
    ];
    $monthName = $monthNames[(int)($invoice['month'] ?? date('m'))] ?? $invoice['month'];
    $year = $invoice['year'] ?? date('Y');
    $periodText = $monthName . ' năm ' . $year;
    
    $invoiceCode = $invoice['invoice_code'] ?? '';
    $issueDate = !empty($invoice['issue_date']) ? date('d/m/Y', strtotime($invoice['issue_date'])) : date('d/m/Y');
    $dueDate = !empty($invoice['due_date']) ? date('d/m/Y', strtotime($invoice['due_date'])) : '';
    $studentName = $invoice['student_name'] ?? '';
    $studentCode = $invoice['student_code'] ?? '';
    $grade = $invoice['grade'] ?? '';
    $className = $invoice['class'] ?? '';
    $note = $invoice['note'] ?? '';
    $creatorName = $_SESSION['user_full_name'] ?? 'Quản trị viên';

    // Khởi tạo TCPDF
    $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetCreator('School Payment System');
    $pdf->SetAuthor($schoolName);
    $pdf->SetTitle('Phiếu báo thu học phí - ' . $invoiceCode);
    
    // Margin 12mm
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->setFontSubsetting(true);
    
    // Cài đặt ổn định layout
    $pdf->setCellHeightRatio(1.2);
    $pdf->setHtmlVSpace(['p' => [0, 0], 'table' => [0, 0], 'tr' => [0, 0], 'td' => [0, 0]]);
    
    // Font DejaVuSans 11px (Vietnamese supported)
    $pdf->SetFont('dejavusans', '', 11, '', true);

    // Thêm trang
    $pdf->AddPage();

    // ===== HEADER =====
    $pdf->SetFont('dejavusans', 'B', 13);
    $pdf->Cell(0, 5, $schoolName, 0, 1, 'C');
    
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 4, 'Địa chỉ: ' . $schoolAddress . ' | Điện thoại: ' . $schoolPhone, 0, 1, 'C');
    
    $pdf->Ln(4);

    // ===== TITLE =====
    $pdf->SetFont('dejavusans', 'B', 15);
    $pdf->Cell(0, 6, 'PHIẾU BÁO THU HỌC PHÍ', 0, 1, 'C');
    
    $pdf->SetFont('dejavusans', '', 11);
    $pdf->Cell(0, 5, 'Mã phiếu: ' . $invoiceCode . ' | Ngày lập: ' . $issueDate, 0, 1, 'C');
    
    $pdf->Ln(3);

    // ===== STUDENT INFO =====
    $pdf->SetFont('dejavusans', '', 11);
    
    $pdf->Cell(50, 5, 'Họ và tên học sinh:', 0, 0, 'L');
    $pdf->SetFont('dejavusans', 'B', 11);
    $pdf->Cell(0, 5, $studentName, 0, 1, 'L');
    
    $pdf->SetFont('dejavusans', '', 11);
    $pdf->Cell(50, 5, 'Mã học sinh:', 0, 0, 'L');
    $pdf->Cell(0, 5, $studentCode, 0, 1, 'L');
    
    $pdf->Cell(50, 5, 'Khối/Lớp:', 0, 0, 'L');
    $pdf->Cell(0, 5, $grade . ' / ' . $className, 0, 1, 'L');
    
    $pdf->Cell(50, 5, 'Tháng:', 0, 0, 'L');
    $pdf->Cell(0, 5, $periodText, 0, 1, 'L');
    
    $pdf->Cell(50, 5, 'Hạn thanh toán:', 0, 0, 'L');
    $pdf->Cell(0, 5, $dueDate, 0, 1, 'L');
    
    $pdf->Ln(3);

    // ===== ITEMS TABLE (Dùng HTML với attribute truyền thống) Tạo rows =====
    // HTML với đúng cột
    $rowsHtml = '';
    $i = 1;
    foreach ($items as $it) {
        $desc = htmlspecialchars($it['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $note = htmlspecialchars($it['note'] ?? '', ENT_QUOTES, 'UTF-8');
        $amt  = number_format((int)($it['amount'] ?? 0), 0, ',', '.');
        $rowsHtml .= "
          <tr>
            <td align='center'>{$i}</td>
            <td>{$desc}</td>
            <td>{$note}</td>
            <td align='right'>{$amt}</td>
          </tr>
        ";
        $i++;
    }
    $totalFmt = number_format((int)$totalAmount, 0, ',', '.');

    $html = "
    <table border='1' cellpadding='5' cellspacing='0' width='100%'>
      <tr>
        <td width='8%' align='center'><b>STT</b></td>
        <td width='52%' align='center'><b>Nội dung thu</b></td>
        <td width='20%' align='center'><b>Ghi chú</b></td>
        <td width='20%' align='center'><b>Số tiền (đ)</b></td>
      </tr>
      {$rowsHtml}
      <tr>
        <td colspan='3' align='right'><b>CỘNG:</b></td>
        <td align='right'><b>{$totalFmt}</b></td>
      </tr>
    </table>
    ";

    // Ghi HTML vào PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->Ln(3);

    // ===== AMOUNT IN WORDS =====
    $pdf->SetFont('dejavusans', 'I', 10);
    $pdf->Cell(0, 5, 'Số tiền bằng chữ: ' . $totalText, 0, 1, 'L');
    
    $pdf->Ln(3);

    // ===== QR SECTION (Dùng HTML img base64) =====
    if ($hasQR) {
        $qrAmount = formatVND($qrPayment['amount'] ?? $totalAmount);
        $bankName = $qrPayment['bank_id'] ?? '';
        $accountNo = $qrPayment['account_number'] ?? '';
        $accountName = $qrPayment['account_name'] ?? '';
        
        // Convert QR image to base64
        $qrBase64 = '';
        if (!empty($qrImageSrc) && file_exists($qrImageSrc)) {
            $qrData = base64_encode(file_get_contents($qrImageSrc));
            $qrBase64 = $qrData;
        }
        
        if (!empty($qrBase64)) {
            $qrHtml = "
            <table border='1' cellpadding='5' cellspacing='0' width='100%' style='border-color: #1aa760;'>
              <tr>
                <td width='30%' valign='middle' align='center'>
                  <img src='data:image/png;base64,{$qrBase64}' width='120' height='120' />
                </td>
                <td width='70%' valign='middle'>
                  <b><font color='#1aa760' size='12'>QUÉT MÃ QR ĐỂ THANH TOÁN</font></b><br /><br />
                  <b>Ngân hàng:</b> {$bankName}<br />
                  <b>Số tài khoản:</b> {$accountNo}<br />
                  <b>Tên tài khoản:</b> {$accountName}<br />
                  <b>Số tiền:</b> {$qrAmount}<br />
                  <b>Nội dung:</b> {$invoiceCode}<br /><br />
                  <i><font size='9'>Quét mã QR bằng ứng dụng ngân hàng để thanh toán nhanh</font></i>
                </td>
              </tr>
            </table>
            ";
            $pdf->writeHTML($qrHtml, true, false, true, false, '');
        }
    }

    // ===== NOTE =====
    if (!empty($note)) {
        $pdf->SetDrawColor(255, 193, 7); // #ffc107
        $pdf->SetFillColor(255, 253, 231); // #fffde7
        $pdf->Rect($pdf->GetX(), $pdf->GetY(), 186, 10, 'FD');
        
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 5, 'Ghi chú: ' . $note, 0, 1, 'L');
        
        $pdf->Ln(3);
    }

    // ===== SIGNATURES =====
    $pdf->Ln(5);
    
    // Cột NGƯỜI LẬP PHIẾU
    $pdf->SetFont('dejavusans', 'B', 11);
    $pdf->Cell(93, 5, 'NGƯỜI LẬP PHIẾU', 0, 0, 'C');
    
    // Cột THỦ QUỸ
    $pdf->Cell(93, 5, 'THỦ QUỸ', 0, 1, 'C');
    
    $pdf->Ln(15);
    
    // Dòng ký
    $pdf->Cell(93, 5, '', 0, 0, 'C');
    $pdf->Cell(93, 5, '', 0, 1, 'C');
    
    // Đường kẻ
    $pdf->Cell(93, 0, '', 'T', 0, 'C');
    $pdf->Cell(93, 0, '', 'T', 1, 'C');
    
    $pdf->Ln(2);
    
    // Tên người lập
    $pdf->SetFont('dejavusans', '', 11);
    $pdf->Cell(93, 5, $creatorName, 0, 0, 'C');
    $pdf->Cell(93, 5, '', 0, 1, 'C');

    // ===== FOOTER NOTE =====
    $pdf->Ln(8);
    $pdf->SetFont('dejavusans', 'I', 10);
    $pdf->Cell(0, 5, 'Phiếu này có giá trị nếu có đủ chữ ký và đóng dấu', 0, 1, 'C');

    // Xuất PDF
    $filename = 'PhieuThu_' . $invoiceCode . '.pdf';
    $pdf->Output($filename, 'D');

    if (!empty($qrTempPath) && is_file($qrTempPath)) {
        @unlink($qrTempPath);
    }
}

/**
 * Hàm tiện ích để tạo PDF từ model invoice
 */
function generatePdfFromInvoiceNew($invoice): void
{
    $items = $invoice['items'] ?? [];
    $qrPayment = [];
    
    if (!empty($invoice['total_amount'])) {
        $qrPayment = getVietQRPaymentInfo((int)$invoice['total_amount'], $invoice['invoice_code'] ?? '');
    }
    
    generateInvoicePDFNew($invoice, $items, $qrPayment);
}
