@extends('layouts.app')

@section('title', 'Nội dung')

@php
    $statusLabels = [
        'draft' => 'Mới',
        'pending_review' => 'Chờ duyệt',
        'needs_revision' => 'Cần sửa',
        'approved' => 'Đã duyệt',
        'completed' => 'Hoàn thành',
    ];

    $statusClasses = [
        'draft' => 'status-pill-draft',
        'pending_review' => 'status-pill-pending-review',
        'needs_revision' => 'status-pill-needs-revision',
        'approved' => 'status-pill-approved',
        'completed' => 'status-pill-completed',
    ];
@endphp

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Danh sách nội dung</h1>
                <p class="muted">Theo dõi content theo người tạo, tình trạng duyệt và số lượng phân cảnh.</p>
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

            <div class="card">
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
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">User tạo</label>
                            @if (auth()->user()->canReviewContent())
                                <select class="form-input" name="user_ids[]" multiple size="5">
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected(in_array($user->id, $selectedUserIds, true))>{{ $user->display_name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input class="form-input" type="text" value="{{ auth()->user()->display_name }}" disabled>
                            @endif
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tình trạng</label>
                            <select class="form-input" name="approval_status">
                                <option value="">Tất cả trạng thái</option>
                                @foreach ($statusLabels as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}" @selected($approvalStatus === $statusValue)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn btn-primary" type="submit">Lọc</button>
                        <a class="btn btn-secondary" href="{{ route('contents.index') }}">Xóa lọc</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title">Danh sách content</h3>
            </div>

            @if ($contents->isEmpty())
                <div class="empty-state">Chưa có nội dung nào khớp bộ lọc.</div>
            @else
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 400px; min-width: 400px;">Tên content</th>
                                <th>Người tạo - Danh mục</th>
                                <th>Ngày tạo</th>
                                <th>Cập nhật gần nhất</th>
                                <th>Tình trạng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contents as $content)
                                <tr>
                                    <td style="width: 400px; min-width: 400px;">
                                        <div style="font-weight: 700;">{{ $content->name }}</div>
                                    </td>
                                    <td>{{ ($content->created_by_name ?: 'Không rõ').' - '.($content->category?->name ?: 'Không có') }}</td>
                                    <td>{{ $content->created_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                    <td>{{ $content->updated_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                    <td>
                                        <span class="status-pill {{ $statusClasses[$content->approval_status] ?? 'status-pill-draft' }}">
                                            {{ $statusLabels[$content->approval_status] ?? $content->approval_status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a class="btn btn-secondary" href="{{ route('contents.show', $content) }}">
                                                {{ auth()->user()->canReviewContent() ? 'Preview' : 'Chi tiết' }}
                                            </a>

                                            @if (auth()->user()->role === 'user' && in_array($content->approval_status, ['draft', 'needs_revision'], true))
                                                <form method="POST" action="{{ route('contents.submit-review', $content) }}">
                                                    @csrf
                                                    <button class="btn btn-primary" type="submit">Gửi duyệt</button>
                                                </form>
                                            @endif

                                            @if (auth()->user()->canReviewContent())
                                                <a class="btn btn-secondary" href="{{ route('contents.show', $content) }}#review-panel">Nhận xét</a>
                                            @endif

                                            @if (auth()->user()->isAdmin())
                                                <a class="btn btn-primary" href="{{ route('exports.contents', $content) }}">Xuất folder</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endsection
