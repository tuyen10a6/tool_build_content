@extends('layouts.app')

@section('title', $scene->name)

@section('content')
    <div class="header">
        <a class="btn btn-secondary" href="{{ route('contents.show', $scene->content) }}">← Quay lại content</a>
        <a class="btn btn-primary" href="{{ route('exports.scenes', $scene) }}">📦 Xuất phân cảnh</a>
    </div>
    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Xem trước phân cảnh</h3>
            </div>
            <div class="preview-screen" style="max-width: 100%;">
                @if ($scene->gif_url)
                    <img src="{{ $scene->gif_url }}" alt="{{ $scene->name }}">
                @else
                    <div class="muted">Chưa có GIF</div>
                @endif
            </div>
            @if ($scene->audio_url)
                <audio controls style="width: 100%; margin-top: 16px;">
                    <source src="{{ $scene->audio_url }}" type="{{ \Illuminate\Support\Facades\Storage::disk('public')->mimeType($scene->audio_path) ?: 'audio/mpeg' }}">
                    Trình duyệt không phát được audio này.
                </audio>
            @endif
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cập nhật phân cảnh</h3>
            </div>
            <form method="POST" action="{{ route('scenes.update', $scene) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Tên phân cảnh</label>
                    <input class="form-input" type="text" name="name" value="{{ old('name', $scene->name) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Thứ tự trong content</label>
                    <input class="form-input" type="number" min="1" name="position" value="{{ old('position', $scene->position) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">GIF hiện tại</label>
                    <div class="tag tag-primary">{{ $scene->gif_original_name ?: 'Chưa có' }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">GIF mới</label>
                    <input class="form-input" type="file" name="gif" accept=".gif,.jpg,.jpeg,.png,.webp">
                </div>
                <div class="form-group">
                    <label class="form-label">Audio hiện tại</label>
                    <div class="tag">{{ $scene->audio_original_name ?: 'Không có' }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Audio mới</label>
                    <input class="form-input" type="file" name="audio" accept="audio/*">
                </div>
                <div class="form-group">
                    <label class="form-label">Thời gian hiển thị GIF (giây)</label>
                    <input class="form-input" type="number" min="1" max="3600" name="duration_seconds" value="{{ old('duration_seconds', $scene->duration_seconds) }}">
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="remove_audio" value="1"> Xóa audio hiện tại nếu không upload audio mới</label>
                </div>
                <button class="btn btn-primary" type="submit">Lưu phân cảnh</button>
            </form>
            <form method="POST" action="{{ route('scenes.destroy', $scene) }}" style="margin-top: 12px;">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit" onclick="return confirm('Xóa phân cảnh này?')">Xóa phân cảnh</button>
            </form>
        </div>
    </div>
@endsection
