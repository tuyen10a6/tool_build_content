@extends('layouts.app')

@section('title', 'Báo cáo quản trị')

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Báo cáo quản trị</h1>
                <p class="muted">Theo dõi số content và phân cảnh chính do từng user tạo trong khoảng thời gian chọn.</p>
            </div>
        </div>

        <form method="GET" class="grid grid-2" style="margin-top: 20px;">
            <div class="form-group">
                <label class="form-label">Từ ngày</label>
                <input class="form-input" type="date" name="from_date" value="{{ $fromDate }}">
            </div>
            <div class="form-group">
                <label class="form-label">Đến ngày</label>
                <input class="form-input" type="date" name="to_date" value="{{ $toDate }}">
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">User</label>
                <select class="form-input" name="user_ids[]" multiple size="6">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(in_array($user->id, $selectedUserIds, true))>{{ $user->display_name }} ({{ $user->username }})</option>
                    @endforeach
                </select>
            </div>
            <div class="actions" style="grid-column: 1 / -1;">
                <button class="btn btn-primary" type="submit">Lọc báo cáo</button>
                <a class="btn btn-secondary" href="{{ route('reports.index') }}">Xóa lọc</a>
            </div>
        </form>
    </section>

    <section class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h2 class="card-title">Tổng hợp theo user</h2>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Số content tạo</th>
                        <th>Số phân cảnh tạo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportRows as $row)
                        <tr>
                            <td>{{ $row['user']->display_name }}</td>
                            <td>{{ $row['content_count'] }}</td>
                            <td>{{ $row['scene_count'] }}</td>
                            <td>
                                <a class="btn btn-secondary" href="{{ route('reports.index', array_filter(['from_date' => $fromDate, 'to_date' => $toDate, 'detail_user_id' => $row['user']->id, 'user_ids' => $selectedUserIds])) }}">Xem chi tiết</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">Không có dữ liệu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($detailUser)
        <section class="card" style="margin-top: 20px;">
            <div class="card-header">
                <div>
                    <h2 class="card-title">{{ $detailUser->display_name }}</h2>
                    <p class="muted">Chi tiết content và phân cảnh chính đã tạo.</p>
                </div>
            </div>
            <div class="grid grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Danh sách Content</h3>
                    </div>
                    <div class="stack">
                        @forelse ($contentDetails as $content)
                            <a class="list-item" href="{{ route('contents.show', $content) }}">
                                <div class="list-item-title">{{ $content->name }}</div>
                                <div class="muted">Tạo lúc {{ $content->created_at?->format('d/m/Y H:i') }}</div>
                            </a>
                        @empty
                            <div class="empty-state">Không có content trong khoảng lọc.</div>
                        @endforelse
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Danh sách Scene</h3>
                    </div>
                    <div class="stack">
                        @forelse ($sceneDetails as $scene)
                            <a class="list-item" href="{{ route('scenes.show', $scene) }}">
                                <div class="list-item-title">{{ $scene->name }}</div>
                                <div class="muted">{{ $scene->content?->name ?: 'Không có content' }} · {{ $scene->created_at?->format('d/m/Y H:i') }}</div>
                            </a>
                        @empty
                            <div class="empty-state">Không có phân cảnh trong khoảng lọc.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
