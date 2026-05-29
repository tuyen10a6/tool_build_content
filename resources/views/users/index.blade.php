@extends('layouts.app')

@section('title', 'Quản lý tài khoản')

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Quản lý tài khoản</h1>
                <p class="muted">Admin tạo, chỉnh sửa, khoá hoặc reset mật khẩu cho từng người dùng.</p>
            </div>
        </div>
        <div class="grid grid-2" style="margin-top: 20px;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Tạo tài khoản mới</h2>
                </div>
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Tên đăng nhập</label>
                        <input class="form-input" type="text" name="username" value="{{ old('username') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Họ tên</label>
                        <input class="form-input" type="text" name="full_name" value="{{ old('full_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input class="form-input" type="text" name="phone" value="{{ old('phone') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ghi chú</label>
                        <textarea class="form-input" name="note">{{ old('note') }}</textarea>
                    </div>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select class="form-input" name="role">
                                <option value="user" @selected(old('role') === 'user')>User</option>
                                <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-input" name="status">
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="locked" @selected(old('status') === 'locked')>Locked</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mật khẩu</label>
                        <input class="form-input" type="password" name="password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nhập lại mật khẩu</label>
                        <input class="form-input" type="password" name="password_confirmation">
                    </div>
                    <button class="btn btn-primary" type="submit">+ Tạo tài khoản</button>
                </form>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Danh sách tài khoản</h2>
                    <span class="tag tag-primary">{{ $users->count() }} tài khoản</span>
                </div>
                <div class="stack">
                    @foreach ($users as $user)
                        <details class="card details-card" @if(old('username') === $user->username) open @endif>
                            <summary>
                                <span>{{ $user->display_name }} · {{ $user->username }}</span>
                                <span class="status-pill {{ $user->status === 'active' ? 'status-pill-active' : 'status-pill-locked' }}">
                                    {{ $user->status }}
                                </span>
                            </summary>
                            <div class="details-card-body">
                                <form method="POST" action="{{ route('users.update', $user) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-2" style="margin-top: 16px;">
                                        <div class="form-group">
                                            <label class="form-label">Tên đăng nhập</label>
                                            <input class="form-input" type="text" name="username" value="{{ old('username', $user->username) }}">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Họ tên</label>
                                            <input class="form-input" type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}">
                                        </div>
                                    </div>
                                    <div class="grid grid-2">
                                        <div class="form-group">
                                            <label class="form-label">Số điện thoại</label>
                                            <input class="form-input" type="text" name="phone" value="{{ old('phone', $user->phone) }}">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Role</label>
                                            <select class="form-input" name="role">
                                                <option value="user" @selected(old('role', $user->role) === 'user')>User</option>
                                                <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Ghi chú</label>
                                        <textarea class="form-input" name="note">{{ old('note', $user->note) }}</textarea>
                                    </div>
                                    <input type="hidden" name="status" value="{{ $user->status }}">
                                    <button class="btn btn-primary" type="submit">Lưu thông tin</button>
                                </form>

                                <form method="POST" action="{{ route('users.status.update', $user) }}" style="margin-top: 12px;">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="{{ $user->status === 'active' ? 'locked' : 'active' }}">
                                    <button class="btn {{ $user->status === 'active' ? 'btn-danger' : 'btn-secondary' }}" type="submit">
                                        {{ $user->status === 'active' ? 'Khoá tài khoản' : 'Mở khoá tài khoản' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('users.password.reset', $user) }}" style="margin-top: 12px;">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-2">
                                        <div class="form-group">
                                            <label class="form-label">Mật khẩu mới</label>
                                            <input class="form-input" type="password" name="password">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Nhập lại</label>
                                            <input class="form-input" type="password" name="password_confirmation">
                                        </div>
                                    </div>
                                    <button class="btn btn-secondary" type="submit">Reset mật khẩu</button>
                                </form>
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
