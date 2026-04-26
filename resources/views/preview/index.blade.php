@extends('layouts.app')

@section('title', 'Xem trước')

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Xem trước</h1>
                <p class="muted">Preview theo đúng chuỗi export: 1, 1-2, 2, 2-3...</p>
            </div>
        </div>
        <div class="grid grid-2" style="margin-top: 20px;">
            <div class="card">
                <div class="form-group">
                    <label class="form-label">Chọn content</label>
                    <select class="form-input" id="preview-content-select">
                        <option value="">Chọn content</option>
                        @foreach ($contents as $content)
                            <option value="{{ $content->id }}">{{ $content->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="preview-scenes-list" class="stack"></div>
            </div>
            <div class="card">
                <div class="preview-screen" id="preview-screen">
                    <div class="muted" style="text-align: center;">Chọn content để xem trước</div>
                </div>
                <div class="preview-controls">
                    <button class="btn btn-secondary" type="button" id="preview-prev">◀ Trước</button>
                    <button class="btn btn-primary" type="button" id="preview-play">▶ Chạy</button>
                    <button class="btn btn-secondary" type="button" id="preview-stop">⏹ Dừng</button>
                    <button class="btn btn-secondary" type="button" id="preview-next">Sau ▶</button>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        const contents = @json($contents);
        const previewState = {
            content: null,
            sequence: [],
            index: 0,
            timer: null,
            audio: null,
            playing: false,
            remainingMs: 0,
            startedAt: null,
            pausedAudioTime: 0,
        };
        const previewContentSelect = document.getElementById('preview-content-select');
        const previewSceneList = document.getElementById('preview-scenes-list');
        const previewScreen = document.getElementById('preview-screen');
        const previewPlay = document.getElementById('preview-play');
        const previewStop = document.getElementById('preview-stop');
        window.togglePreviewButtons(previewPlay, previewStop, false);

        function currentScene() {
            return previewState.sequence[previewState.index] || null;
        }

        function currentSceneRemainingSeconds(scene = currentScene()) {
            if (!scene) {
                return 0;
            }

            if (scene.audio_url) {
                return Math.max(0, Math.ceil((scene.duration_seconds || 3) - (previewState.pausedAudioTime || 0)));
            }

            return Math.max(0, Math.ceil((previewState.remainingMs || (scene.duration_seconds || 3) * 1000) / 1000));
        }

        function resetCurrentSceneProgress() {
            const scene = currentScene();
            previewState.remainingMs = Math.max(1, (scene?.duration_seconds || 3) * 1000);
            previewState.startedAt = null;
            previewState.pausedAudioTime = 0;

            if (previewState.audio) {
                previewState.audio.pause();
                previewState.audio.currentTime = 0;
                previewState.audio = null;
            }
        }

        function clearPlayback() {
            if (previewState.timer) {
                clearTimeout(previewState.timer);
                previewState.timer = null;
            }

            if (previewState.startedAt && previewState.playing && !previewState.audio) {
                previewState.remainingMs = Math.max(0, previewState.remainingMs - (Date.now() - previewState.startedAt));
            }

            previewState.startedAt = null;

            if (previewState.audio) {
                previewState.audio.pause();
                previewState.pausedAudioTime = previewState.audio.currentTime;
                previewState.remainingMs = Math.max(0, ((currentScene()?.duration_seconds || 3) * 1000) - (previewState.pausedAudioTime * 1000));
            }
        }

        function stopPlayback() {
            previewState.playing = false;
            clearPlayback();
            window.togglePreviewButtons(previewPlay, previewStop, false);
        }

        function renderSequenceList() {
            if (!previewState.sequence.length) {
                previewSceneList.innerHTML = '<div class="empty-state">Content này chưa có chuỗi preview.</div>';
                return;
            }

            previewSceneList.innerHTML = previewState.sequence.map((scene, index) => `
                <button type="button" class="scene-item" data-index="${index}" style="${index === previewState.index ? 'border-color: rgba(245, 158, 11, 0.55);' : ''}">
                    <div class="scene-main">
                        <div class="scene-number">${scene.position_label || scene.position}</div>
                        <div>
                            <div class="scene-name">${scene.name}</div>
                            <div class="scene-details">${scene.scene_type === 'transition' ? 'Chuyển tiếp' : 'Phân cảnh chính'} | ⏱️ ${scene.duration_seconds}s${index === previewState.index && !previewState.playing ? ` | ⏸ ${currentSceneRemainingSeconds(scene)}s còn lại` : ''}</div>
                        </div>
                    </div>
                </button>
            `).join('');

            previewSceneList.querySelectorAll('[data-index]').forEach((button) => {
                button.addEventListener('click', () => {
                    previewState.index = Number(button.dataset.index);
                    stopPlayback();
                    resetCurrentSceneProgress();
                    renderCurrentScene(false);
                });
            });
        }

        function renderCurrentScene(autoplay = false) {
            const scene = previewState.sequence[previewState.index];

            if (!scene) {
                previewScreen.innerHTML = '<div class="muted">Không có phân cảnh để xem.</div>';
                return;
            }

            clearPlayback();
            previewScreen.innerHTML = scene.gif_url
                ? `<img src="${scene.gif_url}" alt="${scene.name}">`
                : `<div class="muted" style="text-align:center;">${scene.name}<br>Chưa có GIF</div>`;

            renderSequenceList();

            if (!autoplay) {
                return;
            }

            if (scene.audio_url) {
                if (!previewState.audio) {
                    previewState.audio = new Audio(scene.audio_url);
                    previewState.audio.loop = false;
                    previewState.audio.onended = () => {
                        previewState.pausedAudioTime = 0;
                        advanceScene();
                    };
                }

                previewState.audio.currentTime = previewState.pausedAudioTime || 0;
                previewState.audio.play().catch(() => advanceScene());
                return;
            }

            previewState.startedAt = Date.now();
            previewState.timer = setTimeout(() => advanceScene(), previewState.remainingMs || (scene.duration_seconds || 3) * 1000);
        }

        function advanceScene() {
            clearPlayback();
            resetCurrentSceneProgress();

            if (!previewState.playing) {
                return;
            }

            if (previewState.index >= previewState.sequence.length - 1) {
                stopPlayback();
                resetCurrentSceneProgress();
                renderCurrentScene(false);
                return;
            }

            previewState.index += 1;
            resetCurrentSceneProgress();
            renderCurrentScene(true);
        }

        function setPreviewContent(contentId) {
            previewState.content = contents.find((item) => Number(item.id) === Number(contentId)) || null;
            previewState.sequence = previewState.content ? [...previewState.content.scenes].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0)) : [];
            previewState.index = 0;
            stopPlayback();
            resetCurrentSceneProgress();
            renderSequenceList();
            renderCurrentScene(false);
        }

        previewContentSelect?.addEventListener('change', (event) => {
            setPreviewContent(event.target.value);
        });

        document.getElementById('preview-prev')?.addEventListener('click', () => {
            if (!previewState.sequence.length) return;
            previewState.index = Math.max(0, previewState.index - 1);
            stopPlayback();
            resetCurrentSceneProgress();
            renderCurrentScene(false);
        });

        document.getElementById('preview-next')?.addEventListener('click', () => {
            if (!previewState.sequence.length) return;
            previewState.index = Math.min(previewState.sequence.length - 1, previewState.index + 1);
            stopPlayback();
            resetCurrentSceneProgress();
            renderCurrentScene(false);
        });

        previewPlay?.addEventListener('click', () => {
            if (!previewState.sequence.length) return;
            previewState.playing = true;
            window.togglePreviewButtons(previewPlay, previewStop, true);
            renderCurrentScene(true);
        });

        previewStop?.addEventListener('click', () => {
            stopPlayback();
            renderCurrentScene(false);
        });
    </script>
@endsection
