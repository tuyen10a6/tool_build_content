@extends('layouts.app')

@section('title', $scene->name)

@section('content')
    <div class="header">
        <a class="btn btn-secondary" href="{{ route('contents.show', $scene->content) }}">← Quay lại nội dung</a>
        <a class="btn btn-primary" href="{{ route('exports.scenes', $scene) }}">📦 Xuất phân cảnh</a>
    </div>
    <div class="tabs" style="margin-bottom: 20px;">
        <a class="tab tab-link" href="{{ route('contents.index') }}">Danh sách nội dung</a>
        <a class="tab tab-link" href="{{ route('transition-templates.index') }}">Mẫu chuyển tiếp</a>
    </div>
    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Xem trước phân cảnh</h3>
            </div>
            <div class="preview-screen" id="scene-preview-screen" style="max-width: 100%;">
                @if ($scene->gif_url)
                    <img src="{{ $scene->gif_url }}" alt="{{ $scene->name }}">
                @elseif ($scene->isMediaPending())
                    <div class="muted">Đang xử lý media...</div>
                @elseif ($scene->hasMediaFailed())
                    <div class="muted">{{ $scene->media_error ?: 'Xử lý media thất bại.' }}</div>
                @else
                    <div class="muted">Chưa có GIF</div>
                @endif
            </div>
            <div class="preview-controls">
                <button class="btn btn-primary" type="button" id="scene-preview-play">▶ Chạy</button>
                <button class="btn btn-secondary" type="button" id="scene-preview-stop">⏹ Dừng</button>
            </div>
            <div class="muted" style="margin-top: 12px; text-align: center;">
                {{ $scene->duration_seconds }} giây{{ $scene->audio_url ? ' | Có audio' : ' | Không có audio' }}
                @if ($scene->isMediaPending())
                    | Đang xử lý media...
                @elseif ($scene->hasMediaFailed())
                    | Xử lý media thất bại
                @endif
            </div>
        </div>
        <div class="card">
            @if ($scene->isTransition())
                <div class="card-header">
                    <h3 class="card-title">Thông tin phân cảnh chuyển tiếp</h3>
                </div>
                <div class="stack">
                    <div class="list-item">
                        <div class="list-item-title">{{ $scene->name }}</div>
                        <div class="list-item-desc">Nhãn thư mục: {{ $scene->position_label }}</div>
                    </div>
                    <div class="list-item">
                        <div class="list-item-title">Nguồn mẫu</div>
                        <div class="list-item-desc">{{ $scene->transitionTemplate?->name ?: 'Không xác định' }}</div>
                    </div>
                    <div class="list-item">
                        <div class="list-item-title">Nối từ / đến</div>
                        <div class="list-item-desc">{{ $scene->fromScene?->name }} → {{ $scene->toScene?->name }}</div>
                    </div>
                    <div class="list-item">
                        <div class="list-item-title">Thời lượng</div>
                        <div class="list-item-desc">{{ $scene->duration_seconds }} giây</div>
                    </div>
                </div>
            @else
                <div class="card-header">
                    <h3 class="card-title">Cập nhật phân cảnh chính</h3>
                </div>
                <form method="POST" action="{{ route('scenes.update', $scene) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label class="form-label">Tên phân cảnh</label>
                        <input class="form-input" type="text" name="name" value="{{ old('name', $scene->name) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nội dung phân cảnh</label>
                        <textarea class="form-input" name="scene_text" rows="6">{{ old('scene_text', $scene->scene_text) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Thứ tự trong nội dung</label>
                        <input class="form-input" type="number" min="1" name="position" value="{{ old('position', $scene->position) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ảnh hiện tại</label>
                        <div class="tag">{{ $scene->image_original_name ?: 'Không có' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ảnh mới</label>
                        <input class="form-input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Video MP4 mới</label>
                        <input class="form-input" type="file" name="video" accept=".mp4,video/mp4">
                        <div class="muted" style="margin-top: 8px;">Nếu upload video mới, hệ thống sẽ xử lý nền để convert lại sang GIF và thay thế GIF đang hiển thị ở khung xem trước.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Audio hiện tại</label>
                        <div class="tag">{{ $scene->audio_original_name ?: 'Không có' }}</div>
                        <div class="muted" style="margin-top: 8px;">Audio sẽ được tự động tạo lại từ nội dung phân cảnh sau khi bạn bấm lưu. Trong lúc xử lý nền, phân cảnh vẫn hiển thị bình thường với trạng thái đang xử lý media.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mẫu chuyển tiếp sau cảnh này</label>
                        <select class="form-input" name="next_transition_template_id">
                            <option value="">Không dùng chuyển tiếp</option>
                            @foreach ($transitionTemplates as $template)
                                <option value="{{ $template->id }}" @selected(old('next_transition_template_id', $scene->next_transition_template_id) == $template->id)>{{ $template->name }} ({{ $template->duration_seconds }} giây)</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Lưu phân cảnh</button>
                </form>

                <form method="POST" action="{{ route('scenes.destroy', $scene) }}" style="margin-top: 12px;">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit" onclick="return confirm('Xóa phân cảnh này?')">Xóa phân cảnh</button>
                </form>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const scenePreview = @json($scene);
        const scenePreviewPlay = document.getElementById('scene-preview-play');
        const scenePreviewStop = document.getElementById('scene-preview-stop');
        let scenePreviewAudio = null;
        let scenePreviewTimer = null;
        let scenePreviewRemainingMs = Math.max(1, (scenePreview.duration_seconds || 3) * 1000);
        let scenePreviewStartedAt = null;

        window.togglePreviewButtons(scenePreviewPlay, scenePreviewStop, false);

        function clearScenePreviewPlayback() {
            if (scenePreviewTimer) {
                clearTimeout(scenePreviewTimer);
                scenePreviewTimer = null;
            }

            if (scenePreviewStartedAt && !scenePreview.audio_url) {
                scenePreviewRemainingMs = Math.max(0, scenePreviewRemainingMs - (Date.now() - scenePreviewStartedAt));
            }

            scenePreviewStartedAt = null;

            if (scenePreviewAudio) {
                scenePreviewAudio.pause();
            }
        }

        function stopScenePreview(reset = false) {
            clearScenePreviewPlayback();

            if (reset) {
                scenePreviewRemainingMs = Math.max(1, (scenePreview.duration_seconds || 3) * 1000);

                if (scenePreviewAudio) {
                    scenePreviewAudio.currentTime = 0;
                }
            }

            window.togglePreviewButtons(scenePreviewPlay, scenePreviewStop, false);
        }

        function playScenePreview() {
            clearScenePreviewPlayback();
            window.togglePreviewButtons(scenePreviewPlay, scenePreviewStop, true);

            if (scenePreview.audio_url) {
                if (!scenePreviewAudio) {
                    scenePreviewAudio = new Audio(scenePreview.audio_url);
                    scenePreviewAudio.loop = false;
                    scenePreviewAudio.onended = () => stopScenePreview(true);
                }

                scenePreviewAudio.play().catch(() => stopScenePreview());
                return;
            }

            scenePreviewStartedAt = Date.now();
            scenePreviewTimer = window.setTimeout(() => stopScenePreview(true), scenePreviewRemainingMs);
        }

        scenePreviewPlay?.addEventListener('click', () => playScenePreview());
        scenePreviewStop?.addEventListener('click', () => stopScenePreview());
    </script>
@endsection
