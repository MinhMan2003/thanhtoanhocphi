<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giấy báo thu và thanh toán</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', 'Segoe UI', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: #f5f5f5;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 10mm auto;
            padding: 12mm 15mm;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }

        /* Header góc trái */
        .header {
            text-align: left;
            margin-bottom: 6mm;
        }
        .header .school-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        .header .school-info {
            font-size: 10pt;
            color: #333;
        }

        /* Tiêu đề giữa */
        .title {
            text-align: center;
            margin-bottom: 5mm;
        }
        .title h1 {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .title .subtitle {
            font-style: italic;
            font-size: 11pt;
            color: #333;
            margin-top: 2mm;
        }

        /* Thông tin học sinh - list dấu gạch */
        .student-info {
            margin: 4mm 0;
            font-size: 11pt;
            line-height: 1.6;
        }
        .student-info p {
            margin: 1mm 0;
        }

        /* Dòng giữa (Số báo ngày ăn) */
        .meal-info {
            text-align: center;
            font-style: italic;
            font-size: 11pt;
            margin: 3mm 0;
        }

        /* Bảng 4 cột */
        .table-container { margin: 4mm 0; }
        table.fee-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }
        table.fee-table th,
        table.fee-table td {
            border: 1px solid #000;
            padding: 4mm 3mm;
            text-align: left;
            vertical-align: middle;
        }
        table.fee-table th {
            background-color: #fff;
            font-weight: bold;
            text-align: center;
        }
        table.fee-table .col-stt { width: 6%; text-align: center; }
        table.fee-table .col-content { width: 52%; }
        table.fee-table .col-note { width: 18%; }
        table.fee-table .col-amount { width: 24%; text-align: right; }
        table.fee-table .col-amount { font-variant-numeric: tabular-nums; }

        /* Khối tổng kết - căn phải */
        .summary {
            margin-top: 4mm;
            text-align: right;
            font-size: 11pt;
        }
        .summary .summary-row {
            margin: 1.5mm 0;
        }
        .summary .total-row {
            font-weight: bold;
            margin-top: 2mm;
        }

        /* Viết bằng chữ */
        .amount-in-words {
            margin: 4mm 0;
            font-size: 11pt;
            font-style: italic;
            text-align: left;
        }

        /* Cảnh báo đỏ */
        .warning {
            color: #c62828;
            font-size: 11pt;
            font-style: italic;
            font-weight: bold;
            text-align: left;
            margin: 4mm 0;
        }

        /* Footer góc trái */
        .footer {
            margin-top: 8mm;
            text-align: left;
            font-size: 10pt;
            color: #333;
        }

        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        @media print {
            body { background: #fff; }
            .page {
                width: 100%;
                margin: 0;
                padding: 12mm 15mm;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header góc trái -->
        <div class="header">
            <div class="school-name">Trường Thực Hành Sư Phạm</div>
            <div class="school-info">Địa chỉ: Tỉnh Vĩnh Long</div>
            <div class="school-info">&nbsp;Điện thoại: Chưa có thông tin</div>
        </div>

        <!-- Tiêu đề giữa -->
        <div class="title">
            <h1>GIẤY BÁO THU VÀ THANH TOÁN</h1>
            <div class="subtitle">Cả Năm, Niên học 2025 - 2026</div>
        </div>

        <!-- Thông tin học sinh (list dấu gạch) -->
        <div class="student-info">
            <p>- Học sinh: Trần Nguyễn Kiện Bách</p>
            <p>- Lớp học: 1A &nbsp;&nbsp;&nbsp; Ngày sinh: 20/03/2019</p>
            <p>- Mã học sinh : 8600000323</p>
        </div>

        <!-- Số báo ngày ăn (nghiêng, căn giữa) -->
        <div class="meal-info">(Số báo ngày ăn: 9 ngày)</div>

        <!-- Bảng -->
        <div class="table-container">
            <table class="fee-table">
                <thead>
                    <tr>
                        <th class="col-stt">STT</th>
                        <th class="col-content">Nội dung</th>
                        <th class="col-note">Ghi chú</th>
                        <th class="col-amount">Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="col-stt">1</td>
                        <td class="col-content">Tiền máy lạnh ở phòng ngủ bán trú (Tháng 2)</td>
                        <td class="col-note"></td>
                        <td class="col-amount">12.000</td>
                    </tr>
                    <tr>
                        <td class="col-stt">2</td>
                        <td class="col-content">Bán trú cho học sinh (bao gồm tiền ăn, sinh hoạt bán trú, phục vụ bán trú) (Tháng 2)</td>
                        <td class="col-note"></td>
                        <td class="col-amount">540.000</td>
                    </tr>
                    <tr>
                        <td class="col-stt">3</td>
                        <td class="col-content">Tiền giữ trẻ ngoài giờ (Tháng 2)</td>
                        <td class="col-note"></td>
                        <td class="col-amount">112.500</td>
                    </tr>
                    <tr>
                        <td class="col-stt">4</td>
                        <td class="col-content">Tiền máy lạnh phòng học (Tháng 2)</td>
                        <td class="col-note"></td>
                        <td class="col-amount">15.000</td>
                    </tr>
                    <tr>
                        <td class="col-stt">5</td>
                        <td class="col-content">Tiền thuê lao công vệ sinh trường lớp, công trình vệ sinh (Tháng 2)</td>
                        <td class="col-note"></td>
                        <td class="col-amount">30.000</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Khối tổng kết (căn phải) -->
        <div class="summary">
            <div class="summary-row">- Công nợ kỳ này: <strong>709.500</strong></div>
            <div class="summary-row">- Nợ kỳ trước: 0</div>
            <div class="summary-row">- Khấu trừ kỳ trước : 0</div>
            <div class="summary-row">- Khấu trừ kỳ này: 0</div>
            <div class="summary-row total-row">- Tổng cộng: <strong>709.500</strong></div>
        </div>

        <!-- Viết bằng chữ -->
        <div class="amount-in-words">
            - Viết bằng chữ: Bảy trăm linh chín nghìn, năm trăm đồng
        </div>

        <!-- Cảnh báo đỏ (trái, nghiêng đậm) -->
        <div class="warning">
            Vui lòng nhập đúng số tiền khi thanh toán liên ngân hàng qua QrCode
        </div>

        <!-- Footer góc trái -->
        <div class="footer">
            In: 02/3/2026 &nbsp;:&nbsp; Người lập:
        </div>
    </div>
</body>
</html>
