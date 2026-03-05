<?php
/**
 * TCPDF - Phiếu báo thu học phí
 * Template A4 dọc, margin 12mm, font DejaVuSans 11px
 * Bố cục giống mẫu: header căn giữa, bảng khoản thu, khối QR nét đứt xanh, chữ ký.
 * Chỉ dùng table layout (không flex/grid).
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
 * Format tiền VND với dấu chấm phân tách (850.000)
 */
function formatVND(int $amount): string
{
    return number_format($amount, 0, ',', '.');
}

/**
 * Tạo PDF phiếu báo thu học phí - Bố cục giống mẫu, toàn bộ bằng writeHTML + table
 */
function generateInvoicePDFNew(array $invoice, array $items, array $qrPayment = []): void
{
    $schoolName = \App\Core\Config::SCHOOL_NAME ?? 'TRƯỜNG THỰC HÀNH SƯ PHẠM';
    $schoolAddress = \App\Core\Config::SCHOOL_ADDRESS ?? 'Thành phố Trà Vinh';
    $schoolPhone = \App\Core\Config::SCHOOL_PHONE ?? 'Chưa có thông tin';

    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += (int)($item['amount'] ?? 0);
    }
    $totalFormatted = formatVND($totalAmount);
    $amountInWords = numberToVietnameseWords($totalAmount);

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
    $creatorName = $_SESSION['user_full_name'] ?? 'Quản trị viên';

    $invoiceCode = htmlspecialchars($invoiceCode, ENT_QUOTES, 'UTF-8');
    $issueDate = htmlspecialchars($issueDate, ENT_QUOTES, 'UTF-8');
    $dueDate = htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8');
    $studentName = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
    $studentCode = htmlspecialchars($studentCode, ENT_QUOTES, 'UTF-8');
    $grade = htmlspecialchars($grade, ENT_QUOTES, 'UTF-8');
    $className = htmlspecialchars($className, ENT_QUOTES, 'UTF-8');
    $periodText = htmlspecialchars($periodText, ENT_QUOTES, 'UTF-8');
    $creatorName = htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8');
    $schoolName = htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8');
    $schoolAddress = htmlspecialchars($schoolAddress, ENT_QUOTES, 'UTF-8');
    $schoolPhone = htmlspecialchars($schoolPhone, ENT_QUOTES, 'UTF-8');
    $amountInWords = htmlspecialchars($amountInWords, ENT_QUOTES, 'UTF-8');

    $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetCreator('School Payment System');
    $pdf->SetAuthor($schoolName);
    $pdf->SetTitle('Phiếu báo thu học phí - ' . $invoiceCode);
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->setFontSubsetting(true);
    $pdf->setCellHeightRatio(1.2);
    $pdf->setHtmlVSpace(['p' => [0, 0], 'table' => [0, 0], 'tr' => [0, 0], 'td' => [0, 0]]);
    $pdf->SetFont('dejavusans', '', 11, '', true);
    $pdf->AddPage();

    // ----- 1) Header căn giữa (table) -----
    $headerHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td align=\"center\" style=\"font-family: dejavusans; font-size: 13pt; font-weight: bold;\">{$schoolName}</td></tr>
      <tr><td align=\"center\" style=\"font-family: dejavusans; font-size: 10pt;\">Địa chỉ: {$schoolAddress} | Điện thoại: {$schoolPhone}</td></tr>
      <tr><td height=\"6\"></td></tr>
    </table>
    ";
    $pdf->writeHTML($headerHtml, true, false, true, false, '');

    // ----- 2) Tiêu đề -----
    $titleHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td align=\"center\" style=\"font-family: dejavusans; font-size: 15pt; font-weight: bold;\">PHIẾU BÁO THU HỌC PHÍ</td></tr>
      <tr><td align=\"center\" style=\"font-family: dejavusans; font-size: 11pt;\">Mã phiếu: {$invoiceCode} | Ngày lập: {$issueDate}</td></tr>
      <tr><td height=\"4\"></td></tr>
    </table>
    ";
    $pdf->writeHTML($titleHtml, true, false, true, false, '');

    // ----- 3) Thông tin học sinh (2 cột, table) -----
    $studentHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td width=\"40%\" style=\"font-size: 11pt;\">Họ và tên học sinh:</td><td width=\"60%\" style=\"font-size: 11pt; font-weight: bold;\">{$studentName}</td></tr>
      <tr><td height=\"3\"></td><td height=\"3\"></td></tr>
      <tr><td style=\"font-size: 11pt;\">Mã học sinh:</td><td style=\"font-size: 11pt;\">{$studentCode}</td></tr>
      <tr><td height=\"3\"></td><td height=\"3\"></td></tr>
      <tr><td style=\"font-size: 11pt;\">Khối/Lớp:</td><td style=\"font-size: 11pt;\">{$grade}/{$className}</td></tr>
      <tr><td height=\"3\"></td><td height=\"3\"></td></tr>
      <tr><td style=\"font-size: 11pt;\">Tháng:</td><td style=\"font-size: 11pt;\">{$periodText}</td></tr>
      <tr><td height=\"3\"></td><td height=\"3\"></td></tr>
      <tr><td style=\"font-size: 11pt;\">Hạn thanh toán:</td><td style=\"font-size: 11pt;\">{$dueDate}</td></tr>
      <tr><td height=\"4\"></td><td height=\"4\"></td></tr>
    </table>
    ";
    $pdf->writeHTML($studentHtml, true, false, true, false, '');

    // ----- 4) Bảng khoản thu: 4 cột, border 1px, STT 8%, Nội dung 52%, Ghi chú 20%, Số tiền 20% -----
    $rowsHtml = '';
    $i = 1;
    foreach ($items as $it) {
        $desc = htmlspecialchars($it['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $note = htmlspecialchars($it['note'] ?? '', ENT_QUOTES, 'UTF-8');
        $amt = formatVND((int)($it['amount'] ?? 0));
        $rowsHtml .= "<tr>
          <td width=\"8%\" align=\"center\" style=\"border: 1px solid #000000; padding: 6px; font-size: 11pt;\">{$i}</td>
          <td width=\"52%\" style=\"border: 1px solid #000000; padding: 6px; font-size: 11pt;\">{$desc}</td>
          <td width=\"20%\" style=\"border: 1px solid #000000; padding: 6px; font-size: 11pt;\">{$note}</td>
          <td width=\"20%\" align=\"right\" style=\"border: 1px solid #000000; padding: 6px; font-size: 11pt;\">{$amt}</td>
        </tr>";
        $i++;
    }
    $tableHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"border-collapse: collapse;\">
      <tr>
        <td width=\"8%\" align=\"center\" style=\"border: 1px solid #000000; padding: 6px; font-size: 11pt; font-weight: bold; background-color: #ffffff;\">STT</td>
        <td width=\"52%\" align=\"center\" style=\"border: 1px solid #000000; padding: 6px; font-size: 11pt; font-weight: bold; background-color: #ffffff;\">Nội dung thu</td>
        <td width=\"20%\" align=\"center\" style=\"border: 1px solid #000000; padding: 6px; font-size: 11pt; font-weight: bold; background-color: #ffffff;\">Ghi chú</td>
        <td width=\"20%\" align=\"center\" style=\"border: 1px solid #000000; padding: 6px; font-size: 11pt; font-weight: bold; background-color: #ffffff;\">Số tiền (đ)</td>
      </tr>
      {$rowsHtml}
      <tr>
        <td colspan=\"3\" align=\"right\" style=\"border: 1px solid #000000; padding: 6px; font-size: 11pt; font-weight: bold;\">CỘNG:</td>
        <td align=\"right\" style=\"border: 1px solid #000000; padding: 6px; font-size: 11pt; font-weight: bold;\">{$totalFormatted}</td>
      </tr>
    </table>
    ";
    $pdf->writeHTML($tableHtml, true, false, true, false, '');

    // ----- 5) Số tiền bằng chữ (nghiêng, 10pt) -----
    $amountHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td style=\"font-size: 10pt; font-style: italic;\">Số tiền bằng chữ: {$amountInWords}</td></tr>
      <tr><td height=\"4\"></td></tr>
    </table>
    ";
    $pdf->writeHTML($amountHtml, true, false, true, false, '');

    // ----- 6) Khối QR: khung nét đứt xanh #1aa760, trái ảnh ~45mm, phải thông tin -----
    if ($hasQR) {
        $qrAmount = formatVND($qrPayment['amount'] ?? $totalAmount);
        $bankName = htmlspecialchars($qrPayment['bank_id'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountNo = htmlspecialchars($qrPayment['account_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountName = htmlspecialchars($qrPayment['account_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $qrBase64 = '';
        if (!empty($qrImageSrc) && file_exists($qrImageSrc)) {
            $qrBase64 = base64_encode(file_get_contents($qrImageSrc));
        }
        if ($qrBase64 !== '') {
            // 45mm ≈ 127px (TCPDF ~2.83 px/mm)
            $qrPx = 127;
            $qrHtml = "
            <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"border: 1px dashed #1aa760; border-radius: 3px;\">
              <tr>
                <td width=\"28%\" valign=\"middle\" align=\"center\" style=\"border: none; padding: 8px; vertical-align: middle;\">
                  <img src=\"data:image/png;base64,{$qrBase64}\" width=\"{$qrPx}\" height=\"{$qrPx}\" />
                </td>
                <td width=\"72%\" valign=\"middle\" style=\"border: none; padding: 8px; color: #1aa760; font-size: 11pt; vertical-align: middle;\">
                  <span style=\"font-weight: bold; font-size: 12pt; color: #1aa760;\">QUÉT MÃ QR ĐỂ THANH TOÁN</span><br /><br />
                  <b style=\"color: #1aa760;\">Ngân hàng:</b> {$bankName}<br />
                  <b style=\"color: #1aa760;\">Số tài khoản:</b> {$accountNo}<br />
                  <b style=\"color: #1aa760;\">Tên tài khoản:</b> {$accountName}<br />
                  <b style=\"color: #1aa760;\">Số tiền:</b> {$qrAmount} ₫<br />
                  <b style=\"color: #1aa760;\">Nội dung:</b> {$invoiceCode}<br /><br />
                  <span style=\"font-size: 9pt; color: #000000;\">Quét mã QR bằng ứng dụng ngân hàng để thanh toán nhanh</span>
                </td>
              </tr>
            </table>
            ";
            $pdf->writeHTML($qrHtml, true, false, true, false, '');
        }
    }

    // ----- 7) Chữ ký: 2 cột (table) -----
    $sigHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td height=\"8\"></td><td height=\"8\"></td></tr>
      <tr>
        <td width=\"50%\" align=\"center\" style=\"font-size: 11pt; font-weight: bold;\">NGƯỜI LẬP PHIẾU</td>
        <td width=\"50%\" align=\"center\" style=\"font-size: 11pt; font-weight: bold;\">THỦ QUỸ</td>
      </tr>
      <tr><td height=\"18\"></td><td height=\"18\"></td></tr>
      <tr>
        <td align=\"center\"><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"80%\"><tr><td style=\"border-bottom: 1px solid #000;\">&nbsp;</td></tr></table></td>
        <td align=\"center\"><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"80%\"><tr><td style=\"border-bottom: 1px solid #000;\">&nbsp;</td></tr></table></td>
      </tr>
      <tr><td height=\"3\"></td><td height=\"3\"></td></tr>
      <tr>
        <td align=\"center\" style=\"font-size: 11pt;\">{$creatorName}</td>
        <td align=\"center\" style=\"font-size: 11pt;\">&nbsp;</td>
      </tr>
      <tr><td height=\"6\"></td><td height=\"6\"></td></tr>
      <tr><td colspan=\"2\" align=\"center\" style=\"font-size: 10pt; font-style: italic;\">Phiếu này có giá trị nếu có đủ chữ ký và đóng dấu</td></tr>
    </table>
    ";
    $pdf->writeHTML($sigHtml, true, false, true, false, '');

    $filename = 'PhieuThu_' . $invoiceCode . '.pdf';
    $pdf->Output($filename, 'D');

    if (!empty($qrTempPath) && is_file($qrTempPath)) {
        @unlink($qrTempPath);
    }
}

function generatePdfFromInvoiceNew($invoice): void
{
    $items = $invoice['items'] ?? [];
    $qrPayment = [];
    if (!empty($invoice['total_amount'])) {
        $qrPayment = getVietQRPaymentInfo((int)$invoice['total_amount'], $invoice['invoice_code'] ?? '');
    }
    generateInvoicePDFNew($invoice, $items, $qrPayment);
}
