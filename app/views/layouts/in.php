<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        /* Font chuẩn Times New Roman - giống như file PDF */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Times New Roman", Times, serif;
        }

        body {
            font-size: 12px;
            line-height: 1.4;
            padding: 20px;
            background: #f5f5f5;
        }

        @media print {
            body { padding: 0; background: white; }
            .no-print { display: none !important; }
            .page { box-shadow: none; border: none; }
        }

        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 20px;
            padding: 15mm;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .page-break {
            page-break-after: always;
        }

        /* ==================== HEADER ==================== */
        .header {
            text-align: left;
            margin-bottom: 10px;
            padding-bottom: 4px;
        }

        .school-info {
            font-size: 11px;
        }

        .school-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .school-address, .school-phone {
            font-size: 10px;
            color: #333;
        }

        /* ==================== TITLE ==================== */
        .title {
            text-align: center;
            margin-bottom: 10px;
        }

        .title h2 {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .period {
            font-size: 11px;
            color: #333;
        }

        /* ==================== STUDENT INFO ==================== */
        .invoice-info {
            margin-bottom: 8px;
        }

        .student-row {
            font-size: 11px;
            margin-bottom: 2px;
        }

        .student-row .label {
            font-weight: bold;
        }

        .note-month {
            font-size: 10px;
            font-style: italic;
            text-align: center;
            margin-bottom: 8px;
        }

        /* ==================== FEE TABLE ==================== */
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .fee-table th,
        .fee-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 10px;
        }

        .fee-table th {
            background: #f0f0f0;
            text-align: center;
            font-weight: bold;
        }

        .fee-table .stt {
            width: 35px;
            text-align: center;
        }

        .fee-table .content {
            width: auto;
        }

        .fee-table .note {
            width: 100px;
        }

        .fee-table .amount {
            width: 80px;
            text-align: right;
        }

        /* ==================== SUMMARY ==================== */
        .summary-wrapper {
            margin-top: 8px;
            width: 60%;
            margin-left: auto;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .summary-table td {
            padding: 2px 4px;
        }

        .summary-table td.label {
            width: 65%;
        }

        .summary-table td.value {
            text-align: right;
        }

        .summary-table tr.total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
        }

        /* ==================== AMOUNT TEXT ==================== */
        .amount-text {
            font-size: 11px;
            font-weight: bold;
            margin-top: 6px;
        }

        /* ==================== NOTE BOX ==================== */
        .note-box {
            font-size: 10px;
            font-style: italic;
            margin-top: 6px;
        }

        /* ==================== QR PAYMENT ==================== */
        .qr-payment {
            margin: 15px 0;
            padding: 25px 10px;
            border: 1px dashed #198754;
            border-radius: 5px;
            text-align: center;
        }

        .qr-payment img {
            width: 100px;
            height: 100px;
        }

        .qr-placeholder {
            width: 100px;
            height: 100px;
            background: #f0f0f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #999;
        }

        .qr-label {
            margin-top: 8px;
            font-weight: bold;
            color: #198754;
            font-size: 11px;
        }

        /* ==================== NOTE RED ==================== */
        .note-red {
            font-size: 10px;
            color: #ff0000;
            font-style: italic;
            text-align: center;
        }

        /* ==================== PAGE 2 ==================== */
        .page2 {
            padding-top: 40px;
        }

        .bank-info {
            font-size: 11px;
            margin-top: 30px;
        }

        .bank-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
            font-size: 12px;
        }

        .bank-detail-table {
            width: 80%;
            margin: 0 auto;
        }

        .bank-detail-table td {
            padding: 6px 10px;
        }

        .bank-detail-table .label {
            font-weight: bold;
            width: 120px;
        }

        /* ==================== FOOTER ==================== */
        .footer {
            margin-top: 50px;
            font-size: 10px;
            text-align: right;
        }

        /* ==================== NO PRINT ==================== */
        .no-print {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .no-print button {
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            margin: 0 5px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <!-- Nút in và quay lại -->
    <div class="no-print">
        <button onclick="printPage()">🖨️ In phiếu</button>
        <button onclick="back()">← Quay lại</button>
    </div>

    <?php
    // Sử dụng biến $viewFile được chuẩn bị trong BaseController::renderPrint
    // để include đúng file view cần in.
    if (isset($viewFile) && file_exists($viewFile)) {
        require $viewFile;
    } else {
        echo 'View không tồn tại.';
    }
    ?>

    <script>
        function printPage() {
            window.print();
        }
        function back() {
            window.history.back();
        }
    </script>
</body>
</html>
