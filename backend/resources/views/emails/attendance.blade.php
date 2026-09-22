<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận điểm danh xưởng thực hành</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f7fb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #2d3748;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f4f7fb;
            padding: 30px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 30px 25px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .content {
            padding: 30px 25px;
        }
        .badge-success {
            display: inline-block;
            background-color: #e6fffa;
            color: #047481;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .badge-warning {
            display: inline-block;
            background-color: #fffaf0;
            color: #c05621;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 18px;
            line-height: 1.6;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fafbfc;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #edf2f7;
        }
        .info-table td {
            padding: 12px 16px;
            font-size: 14px;
            border-bottom: 1px solid #edf2f7;
        }
        .info-table tr:last-child td {
            border-bottom: none;
        }
        .info-label {
            color: #718096;
            width: 40%;
            font-weight: 500;
        }
        .info-value {
            color: #1a202c;
            font-weight: 600;
        }
        .footer {
            background-color: #f7fafc;
            padding: 20px 25px;
            text-align: center;
            font-size: 12px;
            color: #a0aec0;
            border-top: 1px solid #edf2f7;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>🏭 HỆ THỐNG ĐIỂM DANH XƯỞNG</h1>
                <p>Xác nhận điểm danh thời gian thực bằng QR Code</p>
            </div>
            <div class="content">
                @if(($data['action'] ?? '') === 'VÀO')
                    <div class="badge-success">✓ GHI NHẬN GIỜ VÀO XƯỞNG</div>
                @else
                    <div class="badge-success">✓ HOÀN THÀNH ĐIỂM DANH GIỜ RA</div>
                @endif

                <div class="greeting">
                    Xin chào <strong>{{ $data['student_name'] ?? 'Sinh viên' }}</strong>,
                    <br>
                    Hệ thống điểm danh xưởng thực hành xin gửi thông báo xác nhận kết quả điểm danh của bạn như sau:
                </div>

                <table class="info-table">
                    <tr>
                        <td class="info-label">Mã sinh viên:</td>
                        <td class="info-value">{{ $data['ma_sinh_vien'] ?? '--' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Lớp:</td>
                        <td class="info-value">{{ $data['lop'] ?? '--' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Hành động:</td>
                        <td class="info-value">
                            @if(($data['action'] ?? '') === 'VÀO')
                                <span style="color: #2b6cb0;">Check-in (Giờ Vào)</span>
                            @else
                                <span style="color: #2f855a;">Check-out (Giờ Ra)</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="info-label">Thời gian:</td>
                        <td class="info-value">{{ $data['time'] ?? '--' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Buổi thực hành:</td>
                        <td class="info-value">{{ $data['session_name'] ?? 'Buổi thực hành chung' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Xưởng thực hành:</td>
                        <td class="info-value">{{ $data['workshop_name'] ?? 'Xưởng thực hành' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Trạng thái:</td>
                        <td class="info-value">
                            @if(($data['status'] ?? '') === 'dung_gio')
                                <span style="color: #2f855a;">✓ Đúng giờ</span>
                            @elseif(($data['status'] ?? '') === 'muon')
                                <span style="color: #c53030;">⚠️ Đi muộn</span>
                            @elseif(($data['status'] ?? '') === 'hoan_thanh')
                                <span style="color: #2b6cb0;">✓ Đã hoàn thành buổi</span>
                            @else
                                <span>{{ $data['status'] ?? 'Ghi nhận' }}</span>
                            @endif
                        </td>
                    </tr>
                </table>

                <p style="font-size: 13px; color: #718096; line-height: 1.5; margin-top: 20px;">
                    💡 <em>Lưu ý: Nếu thông tin điểm danh có bất kỳ sự sai lệch nào, vui lòng liên hệ ngay với Cán bộ quản lý xưởng hoặc Giảng viên phụ trách để được kiểm tra và xử lý kịp thời.</em>
                </p>
            </div>
            <div class="footer">
                <p>Email này được tạo và gửi tự động từ Hệ thống Điểm danh QR Code Xưởng Thực Hành.</p>
                <p>© {{ date('Y') }} Phòng Quản lý Xưởng & Đào tạo. Vui lòng không trả lời trực tiếp email này.</p>
            </div>
        </div>
    </div>
</body>
</html>
