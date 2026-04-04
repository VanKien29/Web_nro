<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Cảnh báo IP lạ</title>
</head>

<body style="font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5;">
    <div
        style="max-width: 500px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 24px; border: 2px solid #e74c3c;">
        <h2 style="color: #e74c3c; margin-top: 0;">⚠️ Đăng nhập Admin từ IP lạ</h2>
        <p><strong>Tài khoản:</strong> {{ $account->username }}</p>
        <p><strong>IP mới:</strong> {{ $newIp }}</p>
        <p><strong>IP cũ (đã lưu):</strong> {{ $oldIp ?? 'Chưa có' }}</p>
        <p><strong>Thời gian:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        <hr style="border: none; border-top: 1px solid #eee;">
        <p style="color: #888; font-size: 13px;">
            Nếu không phải bạn đăng nhập, hãy đổi mật khẩu ngay lập tức.
        </p>
    </div>
</body>

</html>