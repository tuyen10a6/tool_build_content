@extends('layouts.app')

@section('title', 'Xem trước')

@section('content')
    <style>
        .preview-stage {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .preview-stage-image,
        .preview-stage-placeholder {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        .preview-stage-image {
            object-fit: contain;
            opacity: 0;
            transition: opacity 0.18s ease;
        }

        .preview-stage-image.is-visible {
            opacity: 1;
        }

        .preview-stage-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--text-muted);
            padding: 16px;
        }
    </style>
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Xem trước</h1>
                <p class="muted">Xem trước theo đúng chuỗi xuất: 1, 1-2, 2, 2-3...</p>
            </div>
        </div>
        <div class="grid grid-2" style="margin-top: 20px;">
            <div class="card">
                <div class="form-group">
                    <label class="form-label">Chọn nội dung</label>
                    <select class="form-input" id="preview-content-select">
                        <option value="">Chọn nội dung</option>
                        @foreach ($contents as $content)
                            <option value="{{ $content->id }}">{{ $content->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="preview-scenes-list" class="stack"></div>
            </div>
            <div class="card">
                <div class="preview-screen" id="preview-screen">
                    <div class="preview-stage">
                        <img class="preview-stage-image" id="preview-stage-image" alt="">
                    <div class="preview-stage-placeholder" id="preview-stage-placeholder">Chọn nội dung để xem trước</div>
                    </div>
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
            imageCache: new Map(),
            audioCache: new Map(),
        };
        const previewContentSelect = document.getElementById('preview-content-select');
        const previewSceneList = document.getElementById('preview-scenes-list');
        const previewScreen = document.getElementById('preview-screen');
        const previewStageImage = document.getElementById('preview-stage-image');
        const previewStagePlaceholder = document.getElementById('preview-stage-placeholder');
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

        function primeSceneMedia(scene) {
            if (!scene) {
                return;
            }

            if (scene.gif_url && !previewState.imageCache.has(scene.gif_url)) {
                const image = new Image();
                const ready = new Promise((resolve) => {
                    image.onload = () => resolve(image);
                    image.onerror = () => resolve(null);
                });

                image.decoding = 'async';
                image.src = scene.gif_url;
                previewState.imageCache.set(scene.gif_url, ready);
            }

            if (scene.audio_url && !previewState.audioCache.has(scene.audio_url)) {
                const audio = new Audio();
                audio.preload = 'auto';
                audio.src = scene.audio_url;
                previewState.audioCache.set(scene.audio_url, audio);
            }
        }

        function primeNearbyScenes() {
            primeSceneMedia(currentScene());
            primeSceneMedia(previewState.sequence[previewState.index + 1] || null);
            primeSceneMedia(previewState.sequence[previewState.index - 1] || null);
        }

        async function showSceneImage(scene) {
            if (!scene?.gif_url) {
                previewStageImage.classList.remove('is-visible');
                previewStageImage.removeAttribute('src');
                previewStageImage.alt = '';
                previewStagePlaceholder.innerHTML = `${scene?.name || 'Không có phân cảnh để xem.'}<br>${scene ? 'Chưa có GIF' : ''}`.trim();
                previewStagePlaceholder.style.display = 'flex';
                return;
            }

            primeSceneMedia(scene);
            const loadedImage = await previewState.imageCache.get(scene.gif_url);

            if (currentScene()?.gif_url !== scene.gif_url) {
                return;
            }

            if (!loadedImage) {
                previewStageImage.classList.remove('is-visible');
                previewStageImage.removeAttribute('src');
                previewStagePlaceholder.innerHTML = `${scene.name}<br>Không tải được GIF`;
                previewStagePlaceholder.style.display = 'flex';
                return;
            }

            previewStagePlaceholder.style.display = 'none';
            previewStageImage.classList.remove('is-visible');

            requestAnimationFrame(() => {
                previewStageImage.src = scene.gif_url;
                previewStageImage.alt = scene.name;
                requestAnimationFrame(() => {
                    previewStageImage.classList.add('is-visible');
                });
            });
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
                previewSceneList.innerHTML = '<div class="empty-state">Nội dung này chưa có chuỗi xem trước.</div>';
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
                    renderCurrentScene(true);
                });
            });
        }

        function renderCurrentScene(autoplay = false) {
            const scene = previewState.sequence[previewState.index];

            if (!scene) {
                previewStageImage.classList.remove('is-visible');
                previewStageImage.removeAttribute('src');
                previewStagePlaceholder.textContent = 'Không có phân cảnh để xem.';
                previewStagePlaceholder.style.display = 'flex';
                return;
            }

            clearPlayback();
            primeNearbyScenes();
            showSceneImage(scene);
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
            previewState.imageCache = new Map();
            previewState.audioCache = new Map();
            stopPlayback();
            resetCurrentSceneProgress();
            renderSequenceList();
            renderCurrentScene(false);
        }

        function playFromCurrentScene({ resetProgress = false } = {}) {
            if (!previewState.sequence.length) return;
            previewState.playing = true;
            if (resetProgress) {
                resetCurrentSceneProgress();
            }
            window.togglePreviewButtons(previewPlay, previewStop, true);
            renderCurrentScene(true);
        }

        previewContentSelect?.addEventListener('change', (event) => {
            setPreviewContent(event.target.value);
        });

        document.getElementById('preview-prev')?.addEventListener('click', () => {
            if (!previewState.sequence.length) return;
            previewState.index = Math.max(0, previewState.index - 1);
            playFromCurrentScene({ resetProgress: true });
        });

        document.getElementById('preview-next')?.addEventListener('click', () => {
            if (!previewState.sequence.length) return;
            previewState.index = Math.min(previewState.sequence.length - 1, previewState.index + 1);
            playFromCurrentScene({ resetProgress: true });
        });

        previewPlay?.addEventListener('click', () => {
            playFromCurrentScene();
        });

        previewStop?.addEventListener('click', () => {
            stopPlayback();
            renderCurrentScene(false);
        });
    </script>
@endsection
