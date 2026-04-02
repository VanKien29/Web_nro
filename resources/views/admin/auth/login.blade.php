<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập Admin — NRO HDPE</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 40px 32px;
        }

        .login-title {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            color: #fbbf24;
            margin-bottom: 8px;
        }

        .login-sub {
            text-align: center;
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 28px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 15px;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, .3);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 20px;
        }

        .form-check input {
            accent-color: #3b82f6;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
        }

        .btn-login:hover {
            background: #2563eb;
        }

        .error-msg {
            color: #fca5a5;
            font-size: 13px;
            margin-top: 4px;
        }

        .error-box {
            background: #7f1d1d;
            color: #fecaca;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <div class="login-title">🐉 NRO HDPE</div>
        <div class="login-sub">Đăng nhập quản trị</div>

        @if($errors->any())
        <div class="error-box">
            @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="username">Tên tài khoản</label>
                <input class="form-input" id="username" name="username" type="text" value="{{ old('username') }}"
                    required autofocus autocomplete="username">
                @error('username')
                <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Mật khẩu</label>
                <input class="form-input" id="password" name="password" type="password" required
                    autocomplete="current-password">
                @error('password')
                <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-login">Đăng nhập</button>
        </form>
    </div>
</body>

</html>