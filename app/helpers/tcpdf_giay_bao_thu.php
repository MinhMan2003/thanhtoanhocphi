<?php
/**
 * TCPDF - Giấy báo thu và thanh toán
 * A4 dọc, margin 12mm, font DejaVuSans, layout giống mẫu (table only, no flex/grid).
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/number_to_words.php';
require_once __DIR__ . '/vietqr.php';

/**
 * Tải file từ URL (hỗ trợ cả file_get_contents và curl)
 */
function tcpdfTryDownload(string $url): ?string
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    $data = null;
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $data = @file_get_contents($url, false, $context);
    }
    return $data;
}

/**
 * Tải ảnh từ URL và lưu vào thư mục temp
 */
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
 * Format số tiền với dấu phẩy phân cách hàng nghìn (12,000 / 709,500)
 */
function formatVNDComma(int $amount): string
{
    return number_format($amount, 0, '.', ',');
}

/**
 * Tạo PDF "Giấy báo thu và thanh toán"
 * @param array $data [ school_name, school_address, school_phone, student_name, class_name, dob, student_code, meal_days, items[], current_debt, prev_debt, prev_discount, current_discount, total, amount_in_words, print_date, creator, period_text?, status?, qrPayment? ]
 */
function generateGiayBaoThuPDF(array $data): void
{
    $schoolName = htmlspecialchars($data['school_name'] ?? 'Trường Thực Hành Sư Phạm', ENT_QUOTES, 'UTF-8');
    $schoolAddress = htmlspecialchars($data['school_address'] ?? 'Thành phố Trà Vinh', ENT_QUOTES, 'UTF-8');
    $schoolPhone = htmlspecialchars($data['school_phone'] ?? 'Chưa có thông tin', ENT_QUOTES, 'UTF-8');
    $studentName = htmlspecialchars($data['student_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $className = htmlspecialchars($data['class_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $dob = htmlspecialchars($data['dob'] ?? '', ENT_QUOTES, 'UTF-8');
    $studentCode = htmlspecialchars($data['student_code'] ?? '', ENT_QUOTES, 'UTF-8');
    $mealDays = (int)($data['meal_days'] ?? 0);
    $currentDebt = (int)($data['current_debt'] ?? 0);
    $prevDebt = (int)($data['prev_debt'] ?? 0);
    $prevDiscount = (int)($data['prev_discount'] ?? 0);
    $currentDiscount = (int)($data['current_discount'] ?? 0);
    $total = (int)($data['total'] ?? 0);
    $amountInWords = htmlspecialchars($data['amount_in_words'] ?? '', ENT_QUOTES, 'UTF-8');
    $printDate = htmlspecialchars($data['print_date'] ?? date('d/m/Y'), ENT_QUOTES, 'UTF-8');
    $creator = htmlspecialchars($data['creator'] ?? '', ENT_QUOTES, 'UTF-8');
    $periodText = htmlspecialchars($data['period_text'] ?? 'Cả Năm, Niên học 2025 - 2026', ENT_QUOTES, 'UTF-8');
    
    // Trạng thái thanh toán: 'paid' = đã thanh toán, các giá trị khác = chưa thu
    $status = $data['status'] ?? 'pending';
    $isPaid = ($status === 'paid');
    
    // Thông tin QR payment
    $qrPayment = $data['qrPayment'] ?? [];
    $hasQR = !$isPaid && !empty($qrPayment) && !empty($qrPayment['qr_image_url']);

    $items = $data['items'] ?? [];

    $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetCreator('School Payment System');
    $pdf->SetAuthor($schoolName);
    $pdf->SetTitle('Giấy báo thu và thanh toán');
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 12);
    $pdf->setFontSubsetting(true);
    $pdf->setCellHeightRatio(1.15);
    $pdf->setHtmlVSpace(['p' => [0, 0], 'table' => [0, 0], 'tr' => [0, 0], 'td' => [0, 0]]);
    $pdf->SetFont('dejavusans', '', 11, '', true);
    $pdf->AddPage();

    // ----- 1) Góc trái trên (3 dòng, 10-11pt) -----
    $headerHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td align=\"left\" style=\"font-size: 11pt;\">{$schoolName}</td></tr>
      <tr><td align=\"left\" style=\"font-size: 10pt;\">Địa chỉ: {$schoolAddress}</td></tr>
      <tr><td align=\"left\" style=\"font-size: 10pt;\">Điện thoại: {$schoolPhone}</td></tr>
      <tr><td height=\"5\"></td></tr>
    </table>
    ";
    $pdf->writeHTML($headerHtml, true, false, true, false, '');

    // ----- 2) Tiêu đề căn giữa, đậm, hoa (15-16pt) + dòng dưới nghiêng -----
    $titleHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td align=\"center\" style=\"font-size: 16pt; font-weight: bold;\">GIẤY BÁO THU VÀ THANH TOÁN</td></tr>
      <tr><td align=\"center\" style=\"font-size: 12pt; font-style: italic;\">{$periodText}</td></tr>
      <tr><td height=\"4\"></td></tr>
    </table>
    ";
    $pdf->writeHTML($titleHtml, true, false, true, false, '');

    // ----- 3) Thông tin học sinh dạng gạch đầu dòng, canh trái -----
    $studentHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td style=\"font-size: 11pt;\">- Học sinh: {$studentName}</td></tr>
      <tr><td style=\"font-size: 11pt;\">- Lớp học: {$className} &nbsp;&nbsp;&nbsp;&nbsp; Ngày sinh: {$dob}</td></tr>
      <tr><td style=\"font-size: 11pt;\">- Mã học sinh : {$studentCode}</td></tr>
      <tr><td height=\"3\"></td></tr>
    </table>
    ";
    $pdf->writeHTML($studentHtml, true, false, true, false, '');

    // ----- 4) Dòng giữa trang, nghiêng (chỉ hiển thị khi có meal_days > 0) -----
    $mealHtml = '';
    if ($mealDays > 0) {
        $mealHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td align=\"center\" style=\"font-size: 10pt; font-style: italic;\">(Số báo ngày ăn: {$mealDays} ngày)</td></tr>
      <tr><td height=\"4\"></td></tr>
    </table>
    ";
        $pdf->writeHTML($mealHtml, true, false, true, false, '');
    }

    // ----- 5) Bảng 4 cột: STT | Nội dung | Ghi chú | Số tiền (border 1px, colgroup) -----
    $rowsHtml = '';
    $i = 1;
    foreach ($items as $it) {
        $desc = htmlspecialchars($it['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $note = htmlspecialchars($it['note'] ?? '', ENT_QUOTES, 'UTF-8');
        $amt = formatVNDComma((int)($it['amount'] ?? 0));
        $rowsHtml .= "<tr>
          <td align=\"center\" style=\"border: 1px solid #000; padding: 5px; font-size: 11pt;\">{$i}</td>
          <td style=\"border: 1px solid #000; padding: 5px; font-size: 11pt;\">{$desc}</td>
          <td style=\"border: 1px solid #000; padding: 5px; font-size: 11pt;\">{$note}</td>
          <td align=\"right\" style=\"border: 1px solid #000; padding: 5px; font-size: 11pt;\">{$amt}</td>
        </tr>";
        $i++;
    }

    $tableHtml = "
    <table border=\"1\" cellpadding=\"5\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse: collapse;\">
    <colgroup>
      <col width=\"8%\">
      <col width=\"57%\">
      <col width=\"15%\">
      <col width=\"20%\">
    </colgroup>
      <tr>
        <td align=\"center\" style=\"border: 1px solid #000; padding: 5px; font-size: 11pt; font-weight: bold; background-color: #ffffff;\">STT</td>
        <td align=\"center\" style=\"border: 1px solid #000; padding: 5px; font-size: 11pt; font-weight: bold; background-color: #ffffff;\">Nội dung</td>
        <td align=\"center\" style=\"border: 1px solid #000; padding: 5px; font-size: 11pt; font-weight: bold; background-color: #ffffff;\">Ghi chú</td>
        <td align=\"center\" style=\"border: 1px solid #000; padding: 5px; font-size: 11pt; font-weight: bold; background-color: #ffffff;\">Số tiền</td>
      </tr>
      {$rowsHtml}
    </table>
    ";
    $pdf->writeHTML($tableHtml, true, false, true, false, '');

    // ----- 6) Phần tổng kết bên phải (table 2 cột) -----
    $currentDebtFmt = formatVNDComma($currentDebt);
    $prevDebtFmt = formatVNDComma($prevDebt);
    $prevDiscountFmt = formatVNDComma($prevDiscount);
    $currentDiscountFmt = formatVNDComma($currentDiscount);
    $totalFmt = formatVNDComma($total);

    $summaryHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td height=\"3\"></td><td></td></tr>
      <tr>
        <td align=\"right\" width=\"70%\" style=\"font-size: 11pt; padding-right: 8px;\">- Công nợ kỳ này:</td>
        <td align=\"right\" width=\"30%\" style=\"font-size: 11pt; font-weight: bold;\">{$currentDebtFmt}</td>
      </tr>
      <tr>
        <td align=\"right\" style=\"font-size: 11pt; padding-right: 8px;\">- Nợ kỳ trước:</td>
        <td align=\"right\" style=\"font-size: 11pt;\">{$prevDebtFmt}</td>
      </tr>
      <tr>
        <td align=\"right\" style=\"font-size: 11pt; padding-right: 8px;\">- Khấu trừ kỳ trước :</td>
        <td align=\"right\" style=\"font-size: 11pt;\">{$prevDiscountFmt}</td>
      </tr>
      <tr>
        <td align=\"right\" style=\"font-size: 11pt; padding-right: 8px;\">- Khấu trừ kỳ này:</td>
        <td align=\"right\" style=\"font-size: 11pt;\">{$currentDiscountFmt}</td>
      </tr>
      <tr>
        <td align=\"right\" style=\"font-size: 11pt; font-weight: bold; padding-right: 8px;\">- Tổng cộng:</td>
        <td align=\"right\" style=\"font-size: 11pt; font-weight: bold;\">{$totalFmt}</td>
      </tr>
      <tr><td height=\"4\"></td><td></td></tr>
    </table>
    ";
    $pdf->writeHTML($summaryHtml, true, false, true, false, '');

    // ----- 7) Viết bằng chữ (canh trái, "Viết bằng chữ:" đậm) -----
    $amountWordsHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td style=\"font-size: 11pt;\"><b>Viết bằng chữ:</b> {$amountInWords}</td></tr>
      <tr><td height=\"4\"></td></tr>
    </table>
    ";
    $pdf->writeHTML($amountWordsHtml, true, false, true, false, '');

    // ----- 8) Trạng thái thanh toán và QR Code -----
    // Nếu đã thanh toán: hiển thị "Đã thanh toán" (không có QR)
    // Nếu chưa thanh toán: hiển thị "Chưa thu" và QR code
    $statusHtml = '';
    $qrHtml = '';
    
    if ($isPaid) {
        // Đã thanh toán - hiển thị trạng thái xanh
        $statusHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td align=\"center\" style=\"font-size: 14pt; font-weight: bold; color: #1aa760;\">✓ ĐÃ THANH TOÁN</td></tr>
      <tr><td height=\"6\"></td></tr>
    </table>
    ";
    } elseif ($hasQR && $total > 0) {
        // Chưa thanh toán - hiển thị trạng thái đỏ + QR code
        $statusHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td align=\"center\" style=\"font-size: 14pt; font-weight: bold; color: #c62828;\">⚠ CHƯA THU</td></tr>
      <tr><td height=\"6\"></td></tr>
    </table>
    ";
        
        // Tải QR image tạm
        $qrTempPath = null;
        $qrImageSrc = $qrPayment['qr_image_url'] ?? '';
        if (!empty($qrImageSrc)) {
            $qrTempPath = tcpdfLocalTempImageFromUrl($qrImageSrc, 'qr_giaybaothu_');
        }
        
        if ($qrTempPath && file_exists($qrTempPath)) {
            $qrBase64 = base64_encode(file_get_contents($qrTempPath));
            $qrPx = 150;
            $qrAmount = formatVNDComma($qrPayment['amount'] ?? $total);
            $bankName = htmlspecialchars($qrPayment['bank_id'] ?? '', ENT_QUOTES, 'UTF-8');
            $accountNo = htmlspecialchars($qrPayment['account_number'] ?? '', ENT_QUOTES, 'UTF-8');
            $accountName = htmlspecialchars($qrPayment['account_name'] ?? '', ENT_QUOTES, 'UTF-8');
            
            $qrHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr>
        <td align=\"center\">
            <img src=\"data:image/png;base64,{$qrBase64}\" width=\"{$qrPx}\" height=\"{$qrPx}\" />
        </td>
      </tr>
      <tr><td height=\"4\"></td></tr>
      <tr>
        <td align=\"center\" style=\"font-size: 10pt; font-weight: bold; color: #1aa760;\">QUÉT MÃ QR THANH TOÁN</td>
      </tr>
      <tr><td height=\"2\"></td></tr>
      <tr>
        <td align=\"center\" style=\"font-size: 11pt;\">
            <b>Số tiền:</b> {$qrAmount} ₫
        </td>
      </tr>
      <tr><td height=\"4\"></td></tr>
    </table>
    ";
        }
        
        // Xóa file tạm
        if ($qrTempPath && is_file($qrTempPath)) {
            @unlink($qrTempPath);
        }
    }
    
    $pdf->writeHTML($statusHtml, true, false, true, false, '');
    $pdf->writeHTML($qrHtml, true, false, true, false, '');

    // ----- 9) Footer căn giữa, nghiêng nhỏ -----
    $footerHtml = "
    <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">
      <tr><td align=\"center\" style=\"font-size: 10pt; font-style: italic;\">In: {$printDate} : Người lập: {$creator}</td></tr>
    </table>
    ";
    $pdf->writeHTML($footerHtml, true, false, true, false, '');

    $filename = 'GiayBaoThu_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $studentCode) . '.pdf';
    $pdf->Output($filename, 'D');
}
