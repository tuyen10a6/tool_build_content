@extends('layouts.app')

@section('title', 'Danh mục')

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Danh sách danh mục</h1>
                <p class="muted">Quản lý danh mục riêng biệt, không trộn với content.</p>
            </div>
            <a class="btn btn-secondary mobile-only" href="#create-category">+ Tạo nhanh</a>
        </div>
        <div class="grid grid-2" style="margin-top: 20px;">
            <div class="desktop-only">
                <div class="card" id="create-category">
                    <div class="card-header">
                        <h2 class="card-title">Tạo danh mục mới</h2>
                    </div>
                    <form method="POST" action="{{ route('categories.store') }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Tên danh mục</label>
                            <input class="form-input" type="text" name="name" value="{{ old('name') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-input" name="description">{{ old('description') }}</textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">+ Tạo danh mục</button>
                    </form>
                </div>
            </div>
            <div>
                <div class="card-header" style="padding: 0; margin-bottom: 12px;">
                    <h2 class="card-title">Danh mục hiện có</h2>
                    <span class="tag tag-primary">{{ $categories->count() }} danh mục</span>
                </div>
                <div class="grid grid-2">
                    @forelse ($categories as $category)
                        <a class="list-item" href="{{ route('categories.show', $category) }}">
                            <div class="list-item-title">{{ $category->name }}</div>
                            <div class="list-item-desc">{{ $category->description ?: 'Không có mô tả' }}</div>
                            <div class="list-item-meta">
                                <span class="tag">{{ $category->contents_count }} content</span>
                            </div>
                        </a>
                    @empty
                        <div class="empty-state">Chưa có danh mục nào.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <details class="card details-card mobile-only" id="create-category" style="margin-top: 16px;" {{ $errors->hasAny(['name', 'description']) ? 'open' : '' }}>
            <summary>Tạo danh mục mới</summary>
            <div class="details-card-body">
                <form method="POST" action="{{ route('categories.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Tên danh mục</label>
                        <input class="form-input" type="text" name="name" value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-input" name="description">{{ old('description') }}</textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">+ Tạo danh mục</button>
                </form>
            </div>
        </details>
    </section>
@endsection
