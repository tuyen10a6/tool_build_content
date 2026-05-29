<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: "Segoe UI", sans-serif; }
        body { min-height: 100vh; display: grid; place-items: center; padding: 24px; background: linear-gradient(180deg, #ffffff 0%, #eef4fa 100%); color: #101828; }
        .shell { width: min(460px, 100%); background: rgba(255, 255, 255, 0.96); border: 1px solid rgba(217, 119, 6, 0.18); border-radius: 24px; padding: 28px; box-shadow: 0 24px 48px rgba(15, 23, 42, 0.12); }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .brand-icon { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: linear-gradient(135deg, #d97706, #f59e0b); }
        h1 { font-size: 28px; margin: 10px 0px; }
        p { color: #475467; margin-bottom: 24px; line-height: 1.5; }
        .form-group { display: grid; gap: 8px; margin-bottom: 16px; }
        label { font-size: 14px; font-weight: 600; }
        input { width: 100%; border: 1px solid #d7dee8; border-radius: 12px; padding: 12px 14px; background: #ffffff; color: #101828; }
        input:focus { outline: none; border-color: #d97706; box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.12); }
        .error { margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.18); color: #b42318; }
        .actions { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-top: 20px; }
        button { border: 0; border-radius: 12px; padding: 12px 18px; font-weight: 700; color: #fff; background: linear-gradient(135deg, #d97706, #f59e0b); cursor: pointer; }
        .muted { color: #667085; font-size: 13px; }
    </style>
</head>
<body>
    <form class="shell" method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="brand">
            <div class="brand-icon">🎬</div>
            <div>
                <strong>Công cụ nội dung</strong>
                <div style="margin-top: 6px" class="muted">Quản lý content và phân cảnh theo user</div>
            </div>
        </div>
        <h1 style="text-align: center;">Đăng nhập</h1>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <div class="form-group">
            <label for="username">Tên đăng nhập</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input id="password" type="password" name="password" required>
        </div>
        <div class="actions">
            <label class="muted" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="remember" value="1" style="width:auto;">
                <span>Ghi nhớ đăng nhập</span>
            </label>
            <button type="submit">Vào hệ thống</button>
        </div>
    </form>
</body>
</html>
