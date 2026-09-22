<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['subject'] ?? 'Thông báo từ Ban Quản Lý Xưởng' }}</title>
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
            background: linear-gradient(135deg, #4c51bf 0%, #667eea 100%);
            padding: 30px 25px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 22px;
            font-weight: 700;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            opacity: 0.95;
        }
        .content {
            padding: 30px 25px;
        }
        .badge-info {
            display: inline-block;
            background-color: #ebf8ff;
            color: #2b6cb0;
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
        .message-body {
            font-size: 15px;
            line-height: 1.7;
            color: #2d3748;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            white-space: pre-line;
            margin: 20px 0;
        }
        .sender-info {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #edf2f7;
            font-size: 13px;
            color: #718096;
        }
        .sender-info strong {
            color: #2d3748;
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
                <h1>📢 THÔNG BÁO XƯỞNG THỰC HÀNH</h1>
                <p>Thông tin từ Giảng viên / Ban Quản trị</p>
            </div>
            <div class="content">
                <div class="badge-info">THÔNG BÁO MỚI</div>

                <div class="greeting">
                    Kính gửi: <strong>{{ $data['recipient_name'] ?? 'Sinh viên' }}</strong>,
                </div>

                <div class="message-body">{{ $data['content'] ?? '' }}</div>

                <div class="sender-info">
                    Người gửi: <strong>{{ $data['sender_name'] ?? 'Giảng viên phụ trách' }}</strong><br>
                    Thời gian gửi: <strong>{{ $data['sent_at'] ?? date('H:i d/m/Y') }}</strong>
                </div>
            </div>
            <div class="footer">
                <p>Hệ Thống Điểm Danh Sinh Viên Thông Minh Bằng QR Code.</p>
                <p>© {{ date('Y') }} Phòng Quản lý Xưởng Thực Hành.</p>
            </div>
        </div>
    </div>
</body>
</html>
