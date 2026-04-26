@extends('layouts.app')

@section('title', 'Content')

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Danh sách content</h1>
                <p class="muted">Mỗi content thuộc một danh mục và chứa nhiều phân cảnh.</p>
            </div>
        </div>
        <div class="tabs" style="margin-top: 20px;">
            <a class="tab tab-link active" href="{{ route('contents.index') }}">Danh sách content</a>
            <a class="tab tab-link" href="{{ route('transition-templates.index') }}">Mẫu chuyển tiếp</a>
        </div>
        <div class="grid grid-2" style="margin-top: 20px;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tạo content mới</h3>
                </div>
                <form method="POST" action="{{ route('contents.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Danh mục</label>
                        <select class="form-input" name="category_id">
                            <option value="">Chọn danh mục</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tên content</label>
                        <input class="form-input" type="text" name="name" value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-input" name="description">{{ old('description') }}</textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">+ Tạo content</button>
                </form>
            </div>
            <div>
                <div class="tabs">
                    <button class="tab active" type="button" data-filter="all">Tất cả</button>
                    @foreach ($categories as $category)
                        <button class="tab" type="button" data-filter="{{ $category->id }}">{{ $category->name }}</button>
                    @endforeach
                </div>
                <div class="grid grid-2">
                    @forelse ($contents as $content)
                        <a class="list-item content-card" data-category="{{ $content->category_id }}" href="{{ route('contents.show', $content) }}">
                            <div class="list-item-title">{{ $content->name }}</div>
                            <div class="list-item-desc">{{ $content->description ?: 'Không có mô tả' }}</div>
                            <div class="list-item-meta">
                                <span class="tag">{{ $content->category?->name }}</span>
                                <span class="tag tag-primary">{{ $content->scenes_count }} phân cảnh</span>
                            </div>
                        </a>
                    @empty
                        <div class="empty-state">Chưa có content nào.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('.tab[data-filter]').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab[data-filter]').forEach(item => item.classList.remove('active'));
                tab.classList.add('active');
                const filter = tab.dataset.filter;
                document.querySelectorAll('.content-card').forEach(card => {
                    card.style.display = filter === 'all' || card.dataset.category === filter ? 'block' : 'none';
                });
            });
        });
    </script>
@endsection
