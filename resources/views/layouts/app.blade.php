<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Build Content Tool')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
        :root { --primary: #d97706; --primary-light: #f59e0b; --bg-dark: #111315; --bg-card: #1b1f22; --bg-card-hover: #252b30; --border: #31393f; --text: #f6f3ef; --text-muted: #a8b0b7; --danger: #ef4444; --success: #16a34a; --warning: #f59e0b; --preview-bg: #000000; --preview-fg: #f6f3ef; --sidebar-bg: rgba(27, 31, 34, 0.94); --card-bg: rgba(27, 31, 34, 0.92); --input-bg: #101315; --tag-bg: #14181b; --accent-soft: rgba(245, 158, 11, 0.15); --accent-border: rgba(245, 158, 11, 0.35); --accent-text: #ffd08a; --shadow: 0 20px 40px rgba(0, 0, 0, 0.2); }
        body { background: radial-gradient(circle at top, #20262b 0%, var(--bg-dark) 55%); color: var(--text); min-height: 100vh; transition: background 0.2s ease, color 0.2s ease; }
        body[data-app-theme="light"] { --bg-dark: #f4f7fb; --bg-card: #ffffff; --bg-card-hover: #eef3f8; --border: #d7dee8; --text: #101828; --text-muted: #475467; --preview-bg: #ffffff; --preview-fg: #101828; --sidebar-bg: rgba(255, 255, 255, 0.96); --card-bg: rgba(255, 255, 255, 0.96); --input-bg: #ffffff; --tag-bg: #eef2f6; --accent-soft: rgba(217, 119, 6, 0.10); --accent-border: rgba(217, 119, 6, 0.22); --accent-text: #b45309; --shadow: 0 16px 36px rgba(15, 23, 42, 0.10); background: linear-gradient(180deg, #ffffff 0%, #eef4fa 100%); }
        a { color: inherit; text-decoration: none; }
        .app { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: var(--sidebar-bg); border-right: 1px solid var(--border); padding: 20px 0; flex-shrink: 0; position: sticky; top: 0; height: 100vh; backdrop-filter: blur(10px); }
        .mobile-topbar { display: none; padding: 14px 16px; border-bottom: 1px solid var(--border); background: var(--sidebar-bg); position: sticky; top: 0; z-index: 20; backdrop-filter: blur(10px); }
        .mobile-topbar .logo { font-size: 16px; }
        .sidebar-header { padding: 0 20px 20px; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .logo { font-size: 18px; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 10px; }
        .logo-icon { width: 34px; height: 34px; background: linear-gradient(135deg, var(--primary), #fb923c); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .nav-item { padding: 12px 20px; display: flex; align-items: center; gap: 12px; color: var(--text-muted); border-left: 3px solid transparent; }
        .nav-item.active, .nav-item:hover { background: var(--bg-card-hover); color: var(--text); border-left-color: var(--primary); }
        .main-content { flex: 1; padding: 28px; }
        .mobile-nav { display: none; position: fixed; left: 12px; right: 12px; bottom: 12px; z-index: 30; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 8px; padding: 8px; border: 1px solid var(--border); background: var(--sidebar-bg); border-radius: 18px; box-shadow: var(--shadow); backdrop-filter: blur(10px); }
        .mobile-nav-item { min-width: 0; padding: 10px 6px; border-radius: 12px; display: grid; justify-items: center; gap: 4px; color: var(--text-muted); font-size: 11px; font-weight: 600; text-align: center; }
        .mobile-nav-item.active { background: var(--bg-card-hover); color: var(--text); }
        .mobile-nav-icon { font-size: 16px; line-height: 1; }
        .header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
        .page-title, .detail-title { font-size: 28px; font-weight: 700; }
        .detail-desc, .muted { color: var(--text-muted); }
        .card { background: var(--card-bg); border-radius: 16px; padding: 20px; border: 1px solid var(--border); box-shadow: var(--shadow); }
        .card + .card { margin-top: 20px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .card-title { font-size: 16px; font-weight: 700; }
        .grid { display: grid; gap: 16px; }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .btn { padding: 10px 18px; border-radius: 10px; border: none; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: #fff; }
        .btn-secondary { background: var(--input-bg); color: var(--text); border: 1px solid var(--border); }
        .btn-danger { background: var(--danger); color: white; }
        .list-item, .scene-item { background: var(--bg-card); border: 1px solid var(--border); border-radius: 14px; padding: 16px; }
        .list-item:hover, .scene-item:hover { background: var(--bg-card-hover); border-color: var(--primary-light); }
        .list-item-title, .scene-name { font-weight: 700; margin-bottom: 6px; }
        .list-item-desc, .scene-details { color: var(--text-muted); font-size: 13px; }
        .list-item-meta, .scene-details { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .tag { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; background: var(--tag-bg); color: var(--text-muted); border: 1px solid var(--border); }
        .tag-primary { background: var(--accent-soft); color: var(--primary); border-color: var(--accent-border); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; }
        .form-input { width: 100%; padding: 12px 14px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); font-size: 14px; }
        .form-input::placeholder { color: var(--text-muted); }
        .form-input:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18); }
        textarea.form-input { min-height: 108px; resize: vertical; }
        .preview-screen { width: 100%; max-width: 800px; aspect-ratio: 5 / 3; background: var(--preview-bg); color: var(--preview-fg); border-radius: 16px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin: 0 auto; transition: background 0.2s ease, color 0.2s ease; }
        .preview-screen img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .preview-controls { display: flex; justify-content: center; gap: 12px; margin-top: 16px; flex-wrap: wrap; }
        .scene-item { display: flex; justify-content: space-between; gap: 14px; align-items: center; margin-bottom: 10px; }
        .scene-main { display: flex; gap: 12px; align-items: center; flex: 1; min-width: 0; }
        .scene-number { width: 32px; height: 32px; border-radius: 8px; background: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .tabs { display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; }
        .tab { padding: 10px 16px; border: 1px solid var(--border); border-radius: 999px; color: var(--text-muted); background: transparent; }
        .tab.active { border-color: var(--accent-border); background: var(--accent-soft); color: var(--accent-text); }
        .tab-link { display: inline-flex; align-items: center; justify-content: center; }
        .detail-stats { display: flex; gap: 20px; margin-top: 16px; flex-wrap: wrap; }
        .stat-item { display: flex; align-items: baseline; gap: 8px; }
        .stat-value { font-size: 20px; font-weight: 700; }
        .empty-state { padding: 40px 20px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border); border-radius: 14px; }
        .stack { display: grid; gap: 16px; }
        .mobile-only { display: none; }
        .desktop-only { display: block; }
        .details-card { padding: 0; overflow: hidden; }
        .details-card summary { list-style: none; cursor: pointer; padding: 18px 20px; font-weight: 700; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .details-card summary::-webkit-details-marker { display: none; }
        .details-card summary::after { content: "▾"; color: var(--text-muted); transition: transform 0.2s ease; }
        .details-card[open] summary::after { transform: rotate(180deg); }
        .details-card-body { padding: 0 20px 20px; border-top: 1px solid var(--border); }
        .toast-stack { position: fixed; top: 20px; right: 20px; z-index: 2000; display: grid; gap: 12px; width: min(380px, calc(100vw - 32px)); }
        .toast { padding: 14px 16px; border-radius: 14px; border: 1px solid var(--border); background: var(--card-bg); color: var(--text); box-shadow: var(--shadow); display: grid; gap: 6px; animation: toast-in 0.18s ease; }
        .toast-title { font-size: 14px; font-weight: 700; }
        .toast-message { font-size: 13px; color: var(--text-muted); line-height: 1.45; }
        .toast-success { border-color: rgba(22, 163, 74, 0.35); }
        .toast-success .toast-title { color: var(--success); }
        .toast-error { border-color: rgba(239, 68, 68, 0.35); }
        .toast-error .toast-title { color: var(--danger); }
        .toast-hide { opacity: 0; transform: translateY(-8px); transition: opacity 0.2s ease, transform 0.2s ease; }
        @keyframes toast-in { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1100px) { .grid-2 { grid-template-columns: 1fr; } .sidebar { display: none; } .mobile-topbar { display: block; } .mobile-nav { display: grid; } .main-content { padding: 20px 20px 96px; } }
        @media (max-width: 640px) { .main-content { padding: 16px 16px 104px; } .card { padding: 16px; border-radius: 14px; } .page-title, .detail-title { font-size: 24px; line-height: 1.15; } .header { margin-bottom: 18px; } .btn { width: 100%; justify-content: center; } .header .btn { width: auto; } .mobile-nav-item { font-size: 10px; } .mobile-only { display: block; } .desktop-only { display: none; } }
    </style>
</head>
<body data-app-theme="{{ $appTheme ?? 'dark' }}">
    <div class="mobile-topbar">
        <div class="logo">
            <div class="logo-icon">🎬</div>
            <span>Build Content</span>
        </div>
    </div>
    <div class="app">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">🎬</div>
                    <span>Build Content</span>
                </div>
            </div>
            <a href="{{ route('categories.index') }}" class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">🗂️ Danh mục</a>
            <a href="{{ route('contents.index') }}" class="nav-item {{ request()->routeIs('contents.*', 'scenes.*') ? 'active' : '' }}">📄 Content</a>
            <a href="{{ route('transition-templates.index') }}" class="nav-item {{ request()->routeIs('transition-templates.*') ? 'active' : '' }}">🔀 Phân cảnh chuyển tiếp</a>
            <a href="{{ route('preview.index') }}" class="nav-item {{ request()->routeIs('preview.*') ? 'active' : '' }}">▶️ Xem trước</a>
            <a href="{{ route('exports.index') }}" class="nav-item {{ request()->routeIs('exports.*') ? 'active' : '' }}">📦 Xuất file</a>
            <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">⚙️ Setting</a>
        </aside>
        <main class="main-content">
            @yield('content')
        </main>
    </div>
    <nav class="mobile-nav" aria-label="Mobile navigation">
        <a href="{{ route('categories.index') }}" class="mobile-nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
            <span class="mobile-nav-icon">🗂️</span>
            <span>Danh mục</span>
        </a>
        <a href="{{ route('contents.index') }}" class="mobile-nav-item {{ request()->routeIs('contents.*', 'scenes.*') ? 'active' : '' }}">
            <span class="mobile-nav-icon">📄</span>
            <span>Content</span>
        </a>
        <a href="{{ route('transition-templates.index') }}" class="mobile-nav-item {{ request()->routeIs('transition-templates.*') ? 'active' : '' }}">
            <span class="mobile-nav-icon">🔀</span>
            <span>Chuyển tiếp</span>
        </a>
        <a href="{{ route('preview.index') }}" class="mobile-nav-item {{ request()->routeIs('preview.*') ? 'active' : '' }}">
            <span class="mobile-nav-icon">▶️</span>
            <span>Xem trước</span>
        </a>
        <a href="{{ route('exports.index') }}" class="mobile-nav-item {{ request()->routeIs('exports.*') ? 'active' : '' }}">
            <span class="mobile-nav-icon">📦</span>
            <span>Xuất file</span>
        </a>
        <a href="{{ route('settings.index') }}" class="mobile-nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <span class="mobile-nav-icon">⚙️</span>
            <span>Setting</span>
        </a>
    </nav>
    <div id="toast-stack" class="toast-stack" aria-live="polite" aria-atomic="true"></div>
    <script>
        (() => {
            const stack = document.getElementById('toast-stack');
            const messages = [];
            const successMessage = @json(session('status'));
            const errorMessages = @json($errors->all());

            if (successMessage) {
                messages.push({ type: 'success', title: 'Thành công', message: successMessage });
            }

            if (Array.isArray(errorMessages)) {
                errorMessages.forEach((message) => {
                    if (message) {
                        messages.push({ type: 'error', title: 'Có lỗi xảy ra', message });
                    }
                });
            }

            const showToast = ({ type, title, message }) => {
                const item = document.createElement('div');
                item.className = `toast toast-${type}`;
                item.innerHTML = `<div class="toast-title">${title}</div><div class="toast-message">${message}</div>`;
                stack.appendChild(item);

                window.setTimeout(() => {
                    item.classList.add('toast-hide');
                    window.setTimeout(() => item.remove(), 220);
                }, 3200);
            };

            messages.forEach(showToast);
        })();

        window.togglePreviewButtons = (playButton, stopButton, isPlaying) => {
            if (!playButton || !stopButton) {
                return;
            }

            playButton.style.display = isPlaying ? 'none' : 'inline-flex';
            stopButton.style.display = isPlaying ? 'inline-flex' : 'none';
        };
    </script>
    @yield('scripts')
</body>
</html>
