<?php
declare(strict_types=1);

/**
 * Tạo URL VietQR cho thanh toán
 * 
 * @param string $bankId Mã ngân hàng (VD: VCB, BIDV, MB...)
 * @param string $accountNumber Số tài khoản
 * @param int $amount Số tiền thanh toán
 * @param string $accountName Tên tài khoản (đã chuẩn hóa)
 * @param string $addInfo Thông tin thêm (mã phiếu)
 * @return string URL QR code
 */
function generateVietQRUrl(string $bankId, string $accountNumber, int $amount, string $accountName, string $addInfo = ''): string
{
    $bankId = strtoupper(trim($bankId));
    $accountNumber = preg_replace('/[^0-9]/', '', $accountNumber);
    $accountName = rawurlencode($addInfo ?: 'Thanhtoanhocphi');
    $addInfoEncoded = rawurlencode($addInfo);
    
    // Sử dụng API VietQR.io
    $url = sprintf(
        'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s',
        $bankId,
        $accountNumber,
        $amount,
        $addInfoEncoded
    );
    
    return $url;
}

/**
 * Tạo thông tin thanh toán VietQR đầy đủ
 * 
 * @param int $amount Số tiền
 * @param string $invoiceCode Mã phiếu
 * @return array Thông tin thanh toán
 */
function getVietQRPaymentInfo(int $amount, string $invoiceCode = ''): array
{
    $bankId = \App\Core\Config::BANK_ID;
    $accountNumber = \App\Core\Config::BANK_ACCOUNT;
    $accountName = \App\Core\Config::BANK_ACCOUNT_NAME;
    
    $qrImageUrl = generateVietQRUrl($bankId, $accountNumber, $amount, $accountName, $invoiceCode);
    
    return [
        'bank_id' => $bankId,
        'account_number' => $accountNumber,
        'account_name' => $accountName,
        'amount' => $amount,
        'invoice_code' => $invoiceCode,
        'qr_image_url' => $qrImageUrl,
    ];
}
