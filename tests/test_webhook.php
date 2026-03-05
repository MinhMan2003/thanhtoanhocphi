<?php
/**
 * Test Case - Webhook Payment Matching
 * ====================================
 * Script giả lập webhook từ ngân hàng để test auto-matching
 * 
 * Chạy: php test_webhook.php
 * Hoặc gọi API trực tiếp qua curl
 */

// ============================================
// Test 1: Gọi API webhook bằng cURL
// ============================================

echo "=== Test 1: Gọi Webhook API ===\n\n";

// Dữ liệu test - VietQR format
$testCases = [
    // Test case 1: Match thành công theo mã phiếu
    [
        'name' => 'Test 1: Match thành công theo mã phiếu',
        'data' => [
            'transId' => 'TXN' . time() . '001',
            'amount' => 1500000,
            'description' => 'PT2026010001',
            'transDate' => time() * 1000, // milliseconds
            'accountNumber' => '1234567890',
            'accountName' => 'NGUYEN VAN A',
            'bankId' => '970436',
        ],
    ],
    
    // Test case 2: Match thành công theo mã học sinh
    [
        'name' => 'Test 2: Match thành công theo mã HS',
        'data' => [
            'transId' => 'TXN' . time() . '002',
            'amount' => 500000,
            'description' => 'HS001',
            'transDate' => time() * 1000,
            'accountNumber' => '0987654321',
            'accountName' => 'TRAN THI B',
            'bankId' => '970436',
        ],
    ],
    
    // Test case 3: Không match được (sai mã)
    [
        'name' => 'Test 3: Không match được',
        'data' => [
            'transId' => 'TXN' . time() . '003',
            'amount' => 999999,
            'description' => 'INVALID123',
            'transDate' => time() * 1000,
            'accountNumber' => '1111111111',
            'accountName' => 'TEST USER',
            'bankId' => '970436',
        ],
    ],
    
    // Test case 4: Idempotent - gọi lại
    [
        'name' => 'Test 4: Idempotent - gọi lại',
        'data' => [
            'transId' => 'TXN' . time() . '001', // Same as test 1
            'amount' => 1500000,
            'description' => 'PT2026010001',
            'transDate' => time() * 1000,
            'accountNumber' => '1234567890',
            'accountName' => 'NGUYEN VAN A',
            'bankId' => '970436',
        ],
    ],
];

foreach ($testCases as $test) {
    echo "--- {$test['name']} ---\n";
    
    $ch = curl_init('http://localhost/thanhtoanhocphi/public/index.php?controller=payment-matching&action=webhook');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n\n";
}

// ============================================
// Test 2: Gọi bằng command line
// ============================================

echo "\n=== Test Command Line ===\n\n";

$cmd = 'curl -X POST "http://localhost/thanhtoanhocphi/public/index.php?controller=payment-matching&action=webhook" \
  -H "Content-Type: application/json" \
  -d \'{
    "transId": "TXN999999",
    "amount": 100000,
    "description": "PT999999",
    "transDate": ' . (time() * 1000) . ',
    "accountNumber": "9999999999",
    "accountName": "TEST COMMAND LINE",
    "bankId": "970436"
  }\'';

echo "Command:\n$cmd\n\n";

// ============================================
// Test 3: Kiểm tra database
// ============================================

echo "\n=== Test Database Check ===\n\n";

/*
-- Sau khi chạy test, kiểm tra:
SELECT * FROM payments ORDER BY created_at DESC LIMIT 10;
SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10;
SELECT * FROM invoices WHERE invoice_code = 'PT2026010001';
*/

echo "Sau khi chạy test, chạy các lệnh SQL sau để kiểm tra:\n\n";
echo "1. Xem payments vừa tạo:\n";
echo "   SELECT * FROM payments ORDER BY created_at DESC LIMIT 10;\n\n";
echo "2. Xem audit log:\n";
echo "   SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10;\n\n";
echo "3. Xem trạng thái hóa đơn:\n";
echo "   SELECT * FROM invoices WHERE invoice_code = 'PT2026010001';\n\n";

// ============================================
// Test 4: Manual Match
// ============================================

echo "\n=== Test Manual Match ===\n\n";

echo "Để test manual match:\n";
echo "1. Vào admin: http://localhost/thanhtoanhocphi/public/index.php?controller=payment-matching&action=unmatched\n";
echo "2. Click 'Khớp' để chọn hóa đơn\n";
echo "3. Hoặc gọi API: POST /payment-matching/match?payment_id=1&hoadon_id=1\n\n";
