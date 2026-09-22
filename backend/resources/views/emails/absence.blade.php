<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cảnh báo vắng mặt buổi thực hành</title>
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
            background: linear-gradient(135deg, #c53030 0%, #e53e3e 100%);
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
        .badge-danger {
            display: inline-block;
            background-color: #fff5f5;
            color: #c53030;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid #fed7d7;
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
        .alert-box {
            background-color: #fffaf0;
            border-left: 4px solid #dd6b20;
            padding: 14px 16px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 14px;
            color: #7b341e;
            line-height: 1.5;
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
                <h1>⚠️ NHẮC NHỞ ĐIỂM DANH XƯỞNG</h1>
                <p>Cảnh báo chuyên cần buổi thực hành</p>
            </div>
            <div class="content">
                <div class="badge-danger">CHƯA GHI NHẬN ĐIỂM DANH</div>

                <div class="greeting">
                    Kính gửi sinh viên: <strong>{{ $data['student_name'] ?? 'Sinh viên' }}</strong>,
                    <br>
                    Hệ thống ghi nhận bạn <strong>chưa thực hiện quét mã QR điểm danh</strong> cho buổi thực hành dưới đây:
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
                        <td class="info-label">Buổi thực hành:</td>
                        <td class="info-value">{{ $data['session_name'] ?? '--' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Môn học:</td>
                        <td class="info-value">{{ $data['mon_hoc'] ?? 'Chuyên ngành' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Xưởng thực hành:</td>
                        <td class="info-value">{{ $data['workshop_name'] ?? '--' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Thời gian buổi học:</td>
                        <td class="info-value">{{ $data['session_time'] ?? '--' }}</td>
                    </tr>
                </table>

                <div class="alert-box">
                    <strong>⚠️ Quy định chuyên cần:</strong>
                    <br>
                    Sinh viên vắng mặt không lý do hoặc không quét mã điểm danh đúng quy định sẽ bị tính là vắng buổi thực hành và có thể ảnh hưởng đến điều kiện dự thi/đánh giá học phần.
                    <br><br>
                    Nếu bạn đã có mặt tại xưởng mà chưa kịp quét mã hoặc gặp sự cố kỹ thuật, vui lòng báo ngay cho Giảng viên phụ trách để được hỗ trợ xác nhận điểm danh thủ công.
                </div>
            </div>
            <div class="footer">
                <p>Email này được gửi tự động từ Ban Quản Lý Xưởng Thực Hành.</p>
                <p>© {{ date('Y') }} Hệ Thống Điểm Danh Sinh Viên Thông Minh.</p>
            </div>
        </div>
    </div>
</body>
</html>
