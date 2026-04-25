@extends('layouts.app')

@section('title', $category->name)

@section('content')
    <div class="header">
        <a class="btn btn-secondary" href="{{ route('dashboard') }}">← Quay lại</a>
    </div>
    <div class="card">
        <div class="detail-title">{{ $category->name }}</div>
        <p class="detail-desc" style="margin-top: 8px;">{{ $category->description ?: 'Không có mô tả' }}</p>
        <div class="detail-stats">
            <div class="stat-item">
                <span class="stat-value">{{ $category->contents->count() }}</span>
                <span class="muted">content</span>
            </div>
        </div>
    </div>
    <div class="grid grid-2" style="margin-top: 20px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cập nhật danh mục</h3>
            </div>
            <form method="POST" action="{{ route('categories.update', $category) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Tên danh mục</label>
                    <input class="form-input" type="text" name="name" value="{{ old('name', $category->name) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-input" name="description">{{ old('description', $category->description) }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
            </form>
            <form method="POST" action="{{ route('categories.destroy', $category) }}" style="margin-top: 12px;">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit" onclick="return confirm('Xóa danh mục này?')">Xóa danh mục</button>
            </form>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Danh sách content</h3>
            </div>
            <div class="stack">
                @forelse ($category->contents as $content)
                    <a class="list-item" href="{{ route('contents.show', $content) }}">
                        <div class="list-item-title">{{ $content->name }}</div>
                        <div class="list-item-desc">{{ $content->description ?: 'Không có mô tả' }}</div>
                        <div class="list-item-meta">
                            <span class="tag tag-primary">{{ $content->scenes_count }} phân cảnh</span>
                        </div>
                    </a>
                @empty
                    <div class="empty-state">Danh mục này chưa có content.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
