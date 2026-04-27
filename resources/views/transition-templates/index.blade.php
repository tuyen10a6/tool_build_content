@extends('layouts.app')

@section('title', 'Mẫu chuyển tiếp')

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Mẫu chuyển tiếp</h1>
                <p class="muted">Quản lý thư viện phân cảnh chuyển tiếp mẫu và xem trước trực tiếp tại đây.</p>
            </div>
        </div>
        <div class="tabs" style="margin-top: 20px;">
            <a class="tab tab-link" href="{{ route('contents.index') }}">Danh sách content</a>
            <a class="tab tab-link active" href="{{ route('transition-templates.index') }}">Mẫu chuyển tiếp</a>
        </div>
        <div class="grid grid-2" style="margin-top: 20px;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Tạo mẫu chuyển tiếp</h2>
                </div>
                <form method="POST" action="{{ route('transition-templates.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Tên mẫu</label>
                        <input class="form-input" type="text" name="name" value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-input" name="description">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">GIF</label>
                        <input class="form-input" type="file" name="gif" accept=".gif,.jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Audio</label>
                        <input class="form-input" type="file" name="audio" accept="audio/*">
                    
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration khi không có audio</label>
                        <input class="form-input" type="number" name="duration_seconds" min="1" max="3600" value="{{ old('duration_seconds', 3) }}">
                    </div>
                    <button class="btn btn-primary" type="submit">+ Tạo mẫu chuyển tiếp</button>
                </form>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Preview mẫu chuyển tiếp</h2>
                </div>
                <div class="preview-screen" id="template-preview-screen">
                    <div class="muted" style="text-align: center;">Chọn mẫu ở danh sách bên dưới để xem trước</div>
                </div>
                <div class="preview-controls">
                    <button class="btn btn-primary" type="button" id="template-preview-play">▶ Chạy</button>
                    <button class="btn btn-secondary" type="button" id="template-preview-stop">⏹ Dừng</button>
                </div>
                <div class="muted" id="template-preview-meta" style="margin-top: 12px; text-align: center;">Chưa chọn mẫu chuyển tiếp.</div>
            </div>
        </div>
    </section>
    <section class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h2 class="card-title">Danh sách mẫu chuyển tiếp</h2>
            <span class="tag tag-primary">{{ $transitionTemplates->count() }} mẫu</span>
        </div>
        <div class="stack">
            @forelse ($transitionTemplates as $template)
                <div class="scene-item">
                    <div class="scene-main">
                        <div class="scene-number">🔀</div>
                        <div>
                            <div class="scene-name">{{ $template->name }}</div>
                            <div class="scene-details">
                                <span>⏱️ {{ $template->duration_seconds }} giây</span>
                                <span>🖼️ {{ $template->gif_original_name ?: 'Không có GIF' }}</span>
                                <span>🎵 {{ $template->audio_original_name ?: 'Không có audio' }}</span>
                            </div>
                            <div class="list-item-desc" style="margin-top: 6px;">{{ $template->description ?: 'Không có mô tả' }}</div>
                        </div>
                    </div>
                    <div class="actions">
                        <button
                            class="btn btn-secondary template-preview-select"
                            type="button"
                            data-template-id="{{ $template->id }}">Xem trước</button>
                        <form method="POST" action="{{ route('transition-templates.destroy', $template) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit" onclick="return confirm('Xóa mẫu chuyển tiếp này?')">Xóa mẫu</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty-state">Chưa có mẫu chuyển tiếp nào.</div>
            @endforelse
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        const transitionTemplates = @json($transitionTemplates->keyBy('id'));
        const templateState = {
            current: null,
            audio: null,
            timer: null,
            playing: false,
            remainingMs: 0,
            startedAt: null,
        };

        const templatePreviewScreen = document.getElementById('template-preview-screen');
        const templatePreviewMeta = document.getElementById('template-preview-meta');
        const templatePreviewPlay = document.getElementById('template-preview-play');
        const templatePreviewStop = document.getElementById('template-preview-stop');

        window.togglePreviewButtons(templatePreviewPlay, templatePreviewStop, false);

        function resetTemplateProgress() {
            templateState.remainingMs = Math.max(1, ((templateState.current?.duration_seconds) || 3) * 1000);
            templateState.startedAt = null;

            if (templateState.audio) {
                templateState.audio.pause();
                templateState.audio.currentTime = 0;
                templateState.audio = null;
            }
        }

        function clearTemplatePlayback() {
            if (templateState.timer) {
                clearTimeout(templateState.timer);
                templateState.timer = null;
            }

            if (templateState.startedAt && !templateState.current?.audio_url) {
                templateState.remainingMs = Math.max(0, templateState.remainingMs - (Date.now() - templateState.startedAt));
            }

            templateState.startedAt = null;

            if (templateState.audio) {
                templateState.audio.pause();
            }
        }

        function renderTemplatePreview() {
            const template = templateState.current;

            if (!template) {
                templatePreviewScreen.innerHTML = '<div class="muted" style="text-align: center;">Chọn mẫu ở danh sách bên dưới để xem trước</div>';
                templatePreviewMeta.textContent = 'Chưa chọn mẫu chuyển tiếp.';
                return;
            }

            templatePreviewScreen.innerHTML = template.gif_url
                ? `<img src="${template.gif_url}" alt="${template.name}">`
                : `<div class="muted" style="text-align:center;">${template.name}<br>Chưa có GIF</div>`;

            templatePreviewMeta.textContent = `${template.name} | ${template.duration_seconds || 3} giây`;
        }

        function stopTemplatePreview(reset = false) {
            templateState.playing = false;
            clearTemplatePlayback();

            if (reset) {
                resetTemplateProgress();
            }

            window.togglePreviewButtons(templatePreviewPlay, templatePreviewStop, false);
            renderTemplatePreview();
        }

        function playTemplatePreview() {
            if (!templateState.current) {
                return;
            }

            templateState.playing = true;
            clearTemplatePlayback();
            window.togglePreviewButtons(templatePreviewPlay, templatePreviewStop, true);
            renderTemplatePreview();

            if (templateState.current.audio_url) {
                if (!templateState.audio) {
                    templateState.audio = new Audio(templateState.current.audio_url);
                    templateState.audio.loop = false;
                    templateState.audio.onended = () => stopTemplatePreview(true);
                }

                templateState.audio.play().catch(() => stopTemplatePreview());
                return;
            }

            templateState.startedAt = Date.now();
            templateState.timer = window.setTimeout(() => stopTemplatePreview(true), templateState.remainingMs || (templateState.current.duration_seconds || 3) * 1000);
        }

        document.querySelectorAll('.template-preview-select').forEach((button) => {
            button.addEventListener('click', () => {
                templateState.current = transitionTemplates[button.dataset.templateId] || null;
                resetTemplateProgress();
                stopTemplatePreview();
            });
        });

        templatePreviewPlay?.addEventListener('click', () => playTemplatePreview());
        templatePreviewStop?.addEventListener('click', () => stopTemplatePreview());
    </script>
@endsection
