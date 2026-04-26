@extends('layouts.app')

@section('title', $content->name)

@section('content')
    <div class="header">
        <a class="btn btn-secondary" href="{{ route('contents.index') }}">← Quay lại</a>
        <a class="btn btn-primary" href="{{ route('exports.contents', $content) }}">📦 Xuất content</a>
    </div>
    <div class="card">
        <div class="detail-title">{{ $content->name }}</div>
        <p class="detail-desc" style="margin-top: 8px;">{{ $content->description ?: 'Không có mô tả' }}</p>
        <div class="detail-stats">
            <div class="stat-item">
                <span class="stat-value">{{ $content->category?->name }}</span>
                <span class="muted">danh mục</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">{{ $content->mainScenes->count() }}</span>
                <span class="muted">phân cảnh chính</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">{{ $content->scenes->count() }}</span>
                <span class="muted">tổng thư mục xuất</span>
            </div>
        </div>
    </div>
    <div class="tabs" style="margin-top: 20px;">
        <a class="tab tab-link active" href="{{ route('contents.index') }}">Danh sách content</a>
        <a class="tab tab-link" href="{{ route('transition-templates.index') }}">Mẫu chuyển tiếp</a>
    </div>
    <div class="grid grid-2" style="margin-top: 20px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cập nhật content</h3>
            </div>
            <form method="POST" action="{{ route('contents.update', $content) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Danh mục</label>
                    <select class="form-input" name="category_id">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $content->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tên content</label>
                    <input class="form-input" type="text" name="name" value="{{ old('name', $content->name) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-input" name="description">{{ old('description', $content->description) }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Lưu content</button>
            </form>
            <form method="POST" action="{{ route('contents.destroy', $content) }}" style="margin-top: 12px;">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit" onclick="return confirm('Xóa content này?')">Xóa content</button>
            </form>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tạo phân cảnh chính mới</h3>
            </div>
            <form method="POST" action="{{ route('scenes.store', $content) }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Tên phân cảnh</label>
                    <input class="form-input" type="text" name="name">
                </div>
                <div class="form-group">
                    <label class="form-label">GIF</label>
                    <input class="form-input" type="file" name="gif" accept=".gif,.jpg,.jpeg,.png,.webp" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Audio</label>
                    <input class="form-input" type="file" name="audio" accept="audio/*">
                    <div class="muted" style="margin-top: 8px;">Nếu có audio, Duration sẽ tự lấy bằng thời lượng audio.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Duration fallback (khi không có audio)</label>
                    <input class="form-input" type="number" min="1" max="3600" name="duration_seconds" value="3">
                </div>
                <div class="form-group">
                    <label class="form-label">Phân cảnh chuyển tiếp sau cảnh này</label>
                    <select class="form-input" name="next_transition_template_id">
                        <option value="">Không dùng chuyển tiếp</option>
                        @foreach ($transitionTemplates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->duration_seconds }}s)</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">+ Tạo phân cảnh</button>
            </form>
        </div>
    </div>
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">Phân cảnh chính</h3>
        </div>
        <div class="stack">
            @forelse ($content->mainScenes as $scene)
                <div class="scene-item">
                    <div class="scene-main">
                        <div class="scene-number">{{ $scene->position }}</div>
                        <div>
                            <div class="scene-name">{{ $scene->name }}</div>
                            <div class="scene-details">
                                <span>⏱️ {{ $scene->duration_seconds }} giây</span>
                                <span>🖼️ {{ $scene->gif_original_name ?: 'Chưa có GIF' }}</span>
                                <span>🎵 {{ $scene->audio_original_name ?: 'Không có audio' }}</span>
                                <span>🔀 {{ $scene->nextTransitionTemplate?->name ?: 'Không có chuyển tiếp sau cảnh này' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn btn-secondary" href="{{ route('scenes.show', $scene) }}">Chi tiết</a>
                        <form method="POST" action="{{ route('scenes.duplicate', $scene) }}">
                            @csrf
                            <button class="btn btn-secondary" type="submit">Nhân bản</button>
                        </form>
                        <a class="btn btn-secondary" href="{{ route('exports.scenes', $scene) }}">Xuất</a>
                    </div>
                </div>
            @empty
                <div class="empty-state">Content này chưa có phân cảnh chính nào.</div>
            @endforelse
        </div>
    </div>
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">Chuỗi preview / export</h3>
        </div>
        <div class="stack">
            @forelse ($content->scenes->sortBy([['sort_order', 'asc'], ['position', 'asc']]) as $scene)
                <div class="scene-item">
                    <div class="scene-main">
                        <div class="scene-number">{{ $scene->position_label ?: $scene->position }}</div>
                        <div>
                            <div class="scene-name">{{ $scene->name }}</div>
                            <div class="scene-details">
                                <span>{{ $scene->scene_type === 'transition' ? 'Chuyển tiếp' : 'Phân cảnh chính' }}</span>
                                <span>⏱️ {{ $scene->duration_seconds }} giây</span>
                                <span>📁 folder: {{ $scene->position_label ?: $scene->position }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn btn-secondary" href="{{ route('scenes.show', $scene) }}">Xem</a>
                    </div>
                </div>
            @empty
                <div class="empty-state">Chưa có chuỗi export nào.</div>
            @endforelse
        </div>
    </div>
@endsection
