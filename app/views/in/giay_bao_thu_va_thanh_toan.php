<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giấy báo thu và thanh toán</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Segoe UI', Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
            background: #f5f5f5;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 10mm auto;
            padding: 15mm;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .content-frame {
            border: 2px solid #000;
            padding: 10mm;
            min-height: 267mm;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8mm;
        }

        .header-left {
            text-align: left;
        }

        .header-left .school-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .header-left .school-info {
            font-size: 11pt;
            color: #333;
        }

        .header-right {
            text-align: right;
            font-size: 11pt;
        }

        .title {
            text-align: center;
            margin-bottom: 3mm;
        }

        .title h1 {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .title .subtitle {
            font-style: italic;
            font-size: 12pt;
            color: #444;
        }

        .student-info {
            margin: 5mm 0;
            font-size: 12pt;
        }

        .student-info p {
            margin: 2mm 0;
        }

        .meal-info {
            text-align: center;
            font-style: italic;
            font-size: 12pt;
            margin: 3mm 0;
        }

        .table-container {
            margin: 5mm 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 6mm 3mm;
            text-align: left;
            vertical-align: middle;
        }

        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        table .col-stt {
            width: 8%;
            text-align: center;
        }

        table .col-content {
            width: 52%;
        }

        table .col-note {
            width: 20%;
        }

        table .col-amount {
            width: 20%;
            text-align: right;
        }

        table .amount {
            text-align: right;
        }

        table .total-row td {
            font-weight: bold;
            background-color: #f8f8f8;
        }

        .summary {
            margin-top: 5mm;
            text-align: right;
        }

        .summary .summary-row {
            display: flex;
            justify-content: flex-end;
            margin: 2mm 0;
            font-size: 11pt;
        }

        .summary .label {
            width: 140mm;
            text-align: right;
            padding-right: 5mm;
        }

        .summary .value {
            width: 50mm;
            text-align: right;
        }

        .summary .total-row {
            font-weight: bold;
            font-size: 12pt;
            border-top: 1px solid #000;
            padding-top: 2mm;
        }

        .amount-in-words {
            margin: 5mm 0;
            font-size: 11pt;
            font-style: italic;
        }

        .warning {
            color: #d32f2f;
            font-size: 11pt;
            font-style: italic;
            text-align: center;
            margin: 5mm 0;
            padding: 3mm;
            border: 1px solid #d32f2f;
            background-color: #ffebee;
        }

        .footer {
            margin-top: 8mm;
            text-align: right;
            font-size: 10pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 3mm;
        }

        /* Print Styles */
        @media print {
            body {
                background: #fff;
            }

            .page {
                width: 100%;
                margin: 0;
                padding: 10mm 15mm;
                box-shadow: none;
            }

            .content-frame {
                border: 2px solid #000;
            }

            @page {
                size: A4;
                margin: 10mm;
            }

            table th {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .warning {
                background-color: #ffebee !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @page {
            size: A4;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="content-frame">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <div class="school-name">Trường Thực Hành Sư Phạm</div>
                    <div class="school-info">Địa chỉ: Thành phố Trà Vinh</div>
                    <div class="school-info">Điện thoại: Chưa có thông tin</div>
                </div>
            </div>

            <!-- Title -->
            <div class="title">
                <h1>GIẤY BÁO THU VÀ THANH TOÁN</h1>
                <div class="subtitle">Cả Năm, Niên học 2025 - 2026</div>
            </div>

            <!-- Student Info -->
            <div class="student-info">
                <p>- Học sinh: Trần Nguyễn Kiện Bách</p>
                <p>- Lớp học: 1A&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ngày sinh: 20/03/2019</p>
                <p>- Mã học sinh: 8600000323</p>
            </div>

            <!-- Meal Info -->
            <div class="meal-info">(Số báo ngày ăn: 9 ngày)</div>

            <!-- Table -->
            <div class="table-container">
                <table>
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
                            <td>Học phí tháng 01/2026</td>
                            <td></td>
                            <td class="amount">350.000</td>
                        </tr>
                        <tr>
                            <td class="col-stt">2</td>
                            <td>Học phí tháng 02/2026</td>
                            <td></td>
                            <td class="amount">350.000</td>
                        </tr>
                        <tr>
                            <td class="col-stt">3</td>
                            <td>Học phí tháng 03/2026</td>
                            <td></td>
                            <td class="amount">350.000</td>
                        </tr>
                        <tr>
                            <td class="col-stt">4</td>
                            <td>Tiền ăn tháng 01/2026 (20 ngày)</td>
                            <td></td>
                            <td class="amount">600.000</td>
                        </tr>
                        <tr>
                            <td class="col-stt">5</td>
                            <td>Tiền ăn tháng 02/2026 (19 ngày)</td>
                            <td></td>
                            <td class="amount">570.000</td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;"><strong>CỘNG:</strong></td>
                            <td class="amount"><strong>2.220.000</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="summary">
                <div class="summary-row">
                    <span class="label">Công nợ kỳ này:</span>
                    <span class="value">2.220.000</span>
                </div>
                <div class="summary-row">
                    <span class="label">Nợ kỳ trước:</span>
                    <span class="value">0</span>
                </div>
                <div class="summary-row">
                    <span class="label">Khấu trừ kỳ trước:</span>
                    <span class="value">0</span>
                </div>
                <div class="summary-row">
                    <span class="label">Khấu trừ kỳ này:</span>
                    <span class="value">0</span>
                </div>
                <div class="summary-row total-row">
                    <span class="label">TỔNG CỘNG:</span>
                    <span class="value">2.220.000</span>
                </div>
            </div>

            <!-- Amount in Words -->
            <div class="amount-in-words">
                Viết bằng chữ: Hai triệu hai trăm hai mươi ngàn đồng
            </div>

            <!-- Warning -->
            <div class="warning">
                Vui lòng nhập đúng số tiền ghi trên phiếu vào tài khoản ngân hàng. Đối chiếu biên lai sau khi thanh toán.
            </div>

            <!-- Footer -->
            <div class="footer">
                In: 02/3/2026&nbsp;&nbsp;|&nbsp;&nbsp;Người lập: Admin trường Trường Thực Hành Sư Phạm
            </div>
        </div>
    </div>
</body>
</html>
