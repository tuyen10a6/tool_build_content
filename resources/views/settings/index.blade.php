@extends('layouts.app')

@section('title', 'Setting')

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Setting</h1>
                <p class="muted">Cài đặt hệ thống dùng chung cho toàn bộ project.</p>
            </div>
        </div>
        <div class="grid grid-2" style="margin-top: 20px;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Giao diện hệ thống</h2>
                </div>
                <form method="POST" action="{{ route('settings.theme') }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label class="form-label">Màu nền hệ thống</label>
                        <select class="form-input" name="app_theme">
                            <option value="dark" @selected(($appTheme ?? 'dark') === 'dark')>Nền đen</option>
                            <option value="light" @selected(($appTheme ?? 'dark') === 'light')>Nền trắng</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Lưu setting</button>
                </form>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Hiệu lực</h2>
                </div>
                <div class="stack">
                    <div class="list-item">
                        <div class="list-item-title">Toàn bộ hệ thống</div>
                        <div class="list-item-desc">Danh mục, content, preview, export và các trang chi tiết sẽ cùng đổi theo theme.</div>
                    </div>
                    <div class="list-item">
                        <div class="list-item-title">Lưu trong database</div>
                        <div class="list-item-desc">Không phụ thuộc trình duyệt, máy hay localStorage.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
