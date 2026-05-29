@extends('layouts.app')

@section('title', 'Nội dung')

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Danh sách nội dung</h1>
                <p class="muted">Mỗi nội dung thuộc một danh mục và được theo dõi theo user tạo.</p>
            </div>
        </div>
        <div class="tabs" style="margin-top: 20px;">
            <a class="tab tab-link active" href="{{ route('contents.index') }}">Danh sách nội dung</a>
            <a class="tab tab-link" href="{{ route('transition-templates.index') }}">Mẫu chuyển tiếp</a>
        </div>
        <div class="grid grid-2" style="margin-top: 20px;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tạo nội dung mới</h3>
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
                        <label class="form-label">Tên nội dung</label>
                        <input class="form-input" type="text" name="name" value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-input" name="description">{{ old('description') }}</textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">+ Tạo nội dung</button>
                </form>
            </div>
            <div>
                <div class="card" style="margin-bottom: 16px;">
                    <div class="card-header">
                        <h3 class="card-title">Bộ lọc</h3>
                    </div>
                    <form method="GET">
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label">Từ ngày</label>
                                <input class="form-input" type="date" name="from_date" value="{{ $fromDate }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Đến ngày</label>
                                <input class="form-input" type="date" name="to_date" value="{{ $toDate }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">User tạo</label>
                            @if (auth()->user()->isAdmin())
                                <select class="form-input" name="user_ids[]" multiple size="5">
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected(in_array($user->id, $selectedUserIds, true))>{{ $user->display_name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input class="form-input" type="text" value="{{ auth()->user()->display_name }}" disabled>
                            @endif
                        </div>
                        <div class="actions">
                            <button class="btn btn-primary" type="submit">Lọc</button>
                            <a class="btn btn-secondary" href="{{ route('contents.index') }}">Xóa lọc</a>
                        </div>
                    </form>
                </div>
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
                                <span class="tag">{{ $content->created_by_name }}</span>
                                <span class="tag">{{ $content->created_at?->format('d/m/Y') }}</span>
                                <span class="tag tag-primary">{{ $content->scenes_count }} phân cảnh</span>
                            </div>
                        </a>
                    @empty
                        <div class="empty-state">Chưa có nội dung nào khớp bộ lọc.</div>
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
