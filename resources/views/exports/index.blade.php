@extends('layouts.app')

@section('title', 'Xuất file')

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Xuất file</h1>
                <p class="muted">Export dữ liệu được giới hạn theo quyền và tự động ghi lịch sử export.</p>
            </div>
        </div>
        <div class="grid grid-2" style="margin-top: 20px;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Xuất phân cảnh</h3>
                </div>
                <div class="form-group">
                    <label class="form-label">Chọn phân cảnh</label>
                    <select class="form-input" id="export-scene-select">
                        <option value="">Chọn phân cảnh</option>
                        @foreach ($contents as $content)
                            @foreach ($content->scenes as $scene)
                                <option value="{{ $scene->id }}">{{ $content->name }} - {{ $scene->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <a class="btn btn-primary" id="export-scene-link" href="#" style="pointer-events: none; opacity: .5;">📦 Xuất phân cảnh</a>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Xuất nội dung</h3>
                </div>
                <div class="form-group">
                    <label class="form-label">Chọn nội dung</label>
                    <select class="form-input" id="export-content-select">
                        <option value="">Chọn nội dung</option>
                        @foreach ($contents as $content)
                            <option value="{{ $content->id }}">{{ $content->name }}</option>
                        @endforeach
                    </select>
                </div>
                <a class="btn btn-primary" id="export-content-link" href="#" style="pointer-events: none; opacity: .5;">📦 Xuất nội dung</a>
            </div>
        </div>
    </section>

    <section class="card" style="margin-top: 20px;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Lịch sử export</h2>
                <p class="muted">{{ auth()->user()->isAdmin() ? 'Admin xem toàn hệ thống.' : 'Bạn chỉ thấy dữ liệu export của mình.' }}</p>
            </div>
        </div>

        <form method="GET" class="grid grid-2" style="margin-bottom: 20px;">
            <div class="form-group">
                <label class="form-label">Từ ngày</label>
                <input class="form-input" type="date" name="from_date" value="{{ $fromDate }}">
            </div>
            <div class="form-group">
                <label class="form-label">Đến ngày</label>
                <input class="form-input" type="date" name="to_date" value="{{ $toDate }}">
            </div>
            <div class="form-group">
                <label class="form-label">Loại export</label>
                <select class="form-input" name="export_type">
                    <option value="">Tất cả</option>
                    <option value="SCENE_ZIP" @selected($exportType === 'SCENE_ZIP')>Scene ZIP</option>
                    <option value="CONTENT_ZIP" @selected($exportType === 'CONTENT_ZIP')>Content ZIP</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">User</label>
                @if (auth()->user()->isAdmin())
                    <select class="form-input" name="user_ids[]" multiple size="4">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(in_array($user->id, $selectedUserIds, true))>{{ $user->display_name }}</option>
                        @endforeach
                    </select>
                @else
                    <input class="form-input" type="text" value="{{ auth()->user()->display_name }}" disabled>
                @endif
            </div>
            <div class="actions" style="grid-column: 1 / -1;">
                <button class="btn btn-primary" type="submit">Lọc lịch sử</button>
                <a class="btn btn-secondary" href="{{ route('exports.index') }}">Xóa lọc</a>
            </div>
        </form>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>File</th>
                        <th>Type</th>
                        <th>Phạm vi</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->username }}</td>
                            <td>{{ $log->file_name }}</td>
                            <td>{{ $log->export_type }}</td>
                            <td>{{ $log->data_scope }}</td>
                            <td>{{ $log->exported_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">Chưa có lịch sử export.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        const exportSceneSelect = document.getElementById('export-scene-select');
        const exportSceneLink = document.getElementById('export-scene-link');
        const exportContentSelect = document.getElementById('export-content-select');
        const exportContentLink = document.getElementById('export-content-link');

        exportSceneSelect?.addEventListener('change', event => {
            const value = event.target.value;
            exportSceneLink.href = value ? `/exports/scenes/${value}` : '#';
            exportSceneLink.style.pointerEvents = value ? 'auto' : 'none';
            exportSceneLink.style.opacity = value ? '1' : '.5';
        });

        exportContentSelect?.addEventListener('change', event => {
            const value = event.target.value;
            exportContentLink.href = value ? `/exports/contents/${value}` : '#';
            exportContentLink.style.pointerEvents = value ? 'auto' : 'none';
            exportContentLink.style.opacity = value ? '1' : '.5';
        });
    </script>
@endsection
