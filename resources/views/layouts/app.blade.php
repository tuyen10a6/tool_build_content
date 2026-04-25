<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Build Content Tool')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
        :root { --primary: #d97706; --primary-light: #f59e0b; --bg-dark: #111315; --bg-card: #1b1f22; --bg-card-hover: #252b30; --border: #31393f; --text: #f6f3ef; --text-muted: #a8b0b7; --danger: #ef4444; --preview-bg: #000000; --preview-fg: #f6f3ef; }
        body { background: radial-gradient(circle at top, #20262b 0%, var(--bg-dark) 55%); color: var(--text); min-height: 100vh; transition: background 0.2s ease, color 0.2s ease; }
        body[data-app-theme="light"] { --bg-dark: #f3f4f6; --bg-card: #ffffff; --bg-card-hover: #f9fafb; --border: #d1d5db; --text: #111827; --text-muted: #6b7280; --preview-bg: #ffffff; --preview-fg: #111827; background: linear-gradient(180deg, #ffffff 0%, #eef2f7 100%); }
        a { color: inherit; text-decoration: none; }
        .app { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: rgba(27, 31, 34, 0.94); border-right: 1px solid var(--border); padding: 20px 0; flex-shrink: 0; position: sticky; top: 0; height: 100vh; }
        .sidebar-header { padding: 0 20px 20px; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .logo { font-size: 18px; font-weight: 700; color: #ffd08a; display: flex; align-items: center; gap: 10px; }
        .logo-icon { width: 34px; height: 34px; background: linear-gradient(135deg, var(--primary), #fb923c); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .nav-item { padding: 12px 20px; display: flex; align-items: center; gap: 12px; color: var(--text-muted); border-left: 3px solid transparent; }
        .nav-item.active, .nav-item:hover { background: var(--bg-card-hover); color: var(--text); border-left-color: var(--primary); }
        .main-content { flex: 1; padding: 28px; }
        .header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
        .page-title, .detail-title { font-size: 28px; font-weight: 700; }
        .detail-desc, .muted { color: var(--text-muted); }
        .card { background: rgba(27, 31, 34, 0.92); border-radius: 16px; padding: 20px; border: 1px solid var(--border); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2); }
        .card + .card { margin-top: 20px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .card-title { font-size: 16px; font-weight: 700; }
        .grid { display: grid; gap: 16px; }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .btn { padding: 10px 18px; border-radius: 10px; border: none; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: #fff; }
        .btn-secondary { background: #13171a; color: var(--text); border: 1px solid var(--border); }
        .btn-danger { background: var(--danger); color: white; }
        .list-item, .scene-item { background: var(--bg-card); border: 1px solid var(--border); border-radius: 14px; padding: 16px; }
        .list-item:hover, .scene-item:hover { background: var(--bg-card-hover); border-color: var(--primary-light); }
        .list-item-title, .scene-name { font-weight: 700; margin-bottom: 6px; }
        .list-item-desc, .scene-details { color: var(--text-muted); font-size: 13px; }
        .list-item-meta, .scene-details { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .tag { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; background: #14181b; color: var(--text-muted); border: 1px solid var(--border); }
        .tag-primary { background: rgba(245, 158, 11, 0.15); color: #ffd08a; border-color: rgba(245, 158, 11, 0.35); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; }
        .form-input { width: 100%; padding: 12px 14px; background: #101315; border: 1px solid var(--border); border-radius: 10px; color: var(--text); font-size: 14px; }
        textarea.form-input { min-height: 108px; resize: vertical; }
        .flash { padding: 14px 16px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(34, 197, 94, 0.25); background: rgba(34, 197, 94, 0.12); color: #bbf7d0; }
        .errors { padding: 14px 16px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.25); background: rgba(239, 68, 68, 0.12); color: #fecaca; }
        .preview-screen { width: 100%; max-width: 800px; aspect-ratio: 5 / 3; background: var(--preview-bg); color: var(--preview-fg); border-radius: 16px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin: 0 auto; transition: background 0.2s ease, color 0.2s ease; }
        .preview-screen img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .preview-controls { display: flex; justify-content: center; gap: 12px; margin-top: 16px; flex-wrap: wrap; }
        .scene-item { display: flex; justify-content: space-between; gap: 14px; align-items: center; margin-bottom: 10px; }
        .scene-main { display: flex; gap: 12px; align-items: center; flex: 1; min-width: 0; }
        .scene-number { width: 32px; height: 32px; border-radius: 8px; background: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .tabs { display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; }
        .tab { padding: 10px 16px; border: 1px solid var(--border); border-radius: 999px; color: var(--text-muted); background: transparent; }
        .tab.active { border-color: rgba(245, 158, 11, 0.5); background: rgba(245, 158, 11, 0.15); color: #ffd08a; }
        .detail-stats { display: flex; gap: 20px; margin-top: 16px; flex-wrap: wrap; }
        .stat-item { display: flex; align-items: baseline; gap: 8px; }
        .stat-value { font-size: 20px; font-weight: 700; }
        .empty-state { padding: 40px 20px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border); border-radius: 14px; }
        .stack { display: grid; gap: 16px; }
        @media (max-width: 1100px) { .grid-2 { grid-template-columns: 1fr; } .sidebar { display: none; } .main-content { padding: 20px; } }
    </style>
</head>
<body data-app-theme="{{ $appTheme ?? 'dark' }}">
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
            <a href="{{ route('preview.index') }}" class="nav-item {{ request()->routeIs('preview.*') ? 'active' : '' }}">▶️ Xem trước</a>
            <a href="{{ route('exports.index') }}" class="nav-item {{ request()->routeIs('exports.*') ? 'active' : '' }}">📦 Xuất file</a>
            <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">⚙️ Setting</a>
        </aside>
        <main class="main-content">
            @if (session('status'))
                <div class="flash">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="errors">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
            @yield('content')
        </main>
    </div>
    @yield('scripts')
</body>
</html>
