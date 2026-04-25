@extends('layouts.app')

@section('title', 'Xem trước')

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Xem trước</h1>
                <p class="muted">Chỉ hiển thị chức năng preview content hoặc phân cảnh.</p>
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
                <div class="form-group">
                    <label class="form-label">Hoặc chọn phân cảnh</label>
                    <select class="form-input" id="preview-scene-select">
                        <option value="">Chọn phân cảnh</option>
                    </select>
                </div>
                <div id="preview-scenes-list" class="stack"></div>
            </div>
            <div class="card">
                <div class="preview-screen" id="preview-screen">
                    <div class="muted" style="text-align: center;">Chọn content hoặc phân cảnh để xem trước</div>
                </div>
                <div class="preview-controls">
                    <button class="btn btn-secondary" type="button" id="preview-prev">◀ Trước</button>
                    <button class="btn btn-primary" type="button" id="preview-play">▶ Chạy</button>
                    <button class="btn btn-secondary" type="button" id="preview-next">Sau ▶</button>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        const contents = @json($contents);
        const previewState = { content: null, index: 0, timer: null, audio: null, playing: false };
        const previewContentSelect = document.getElementById('preview-content-select');
        const previewSceneSelect = document.getElementById('preview-scene-select');
        const previewSceneList = document.getElementById('preview-scenes-list');
        const previewScreen = document.getElementById('preview-screen');
        const previewPlay = document.getElementById('preview-play');

        function stopPlayback() {
            previewState.playing = false;
            previewPlay.textContent = '▶ Chạy';
            if (previewState.timer) clearTimeout(previewState.timer);
            if (previewState.audio) {
                previewState.audio.pause();
                previewState.audio = null;
            }
        }

        function renderOptions(contentId = '') {
            previewSceneSelect.innerHTML = '<option value="">Chọn phân cảnh</option>';
            contents.forEach(content => {
                if (!contentId || Number(content.id) === Number(contentId)) {
                    content.scenes.forEach(scene => {
                        previewSceneSelect.insertAdjacentHTML('beforeend', `<option value="${scene.id}">${content.name} - ${scene.name}</option>`);
                    });
                }
            });
        }

        function renderPreviewSceneList() {
            if (!previewState.content) {
                previewSceneList.innerHTML = '<div class="empty-state">Chưa có phân cảnh để xem trước.</div>';
                return;
            }
            previewSceneList.innerHTML = previewState.content.scenes.map((scene, index) => `
                <button type="button" class="scene-item" data-index="${index}" style="${index === previewState.index ? 'border-color: rgba(245, 158, 11, 0.55);' : ''}">
                    <div class="scene-main">
                        <div class="scene-number">${index + 1}</div>
                        <div>
                            <div class="scene-name">${scene.name}</div>
                            <div class="scene-details">⏱️ ${scene.duration_seconds}s ${scene.audio_url ? '| 🎵 Có audio' : '| 🎵 Không có audio'}</div>
                        </div>
                    </div>
                </button>
            `).join('');
            previewSceneList.querySelectorAll('[data-index]').forEach(button => {
                button.addEventListener('click', () => {
                    previewState.index = Number(button.dataset.index);
                    renderPreviewScene();
                });
            });
        }

        function renderPreviewScene() {
            if (!previewState.content || !previewState.content.scenes.length) {
                previewScreen.innerHTML = '<div class="muted">Content chưa có phân cảnh.</div>';
                return;
            }
            const scene = previewState.content.scenes[previewState.index];
            if (previewState.audio) {
                previewState.audio.pause();
                previewState.audio = null;
            }
            if (previewState.timer) clearTimeout(previewState.timer);
            previewScreen.innerHTML = scene.gif_url ? `<img src="${scene.gif_url}" alt="${scene.name}">` : `<div class="muted" style="text-align:center;">${scene.name}<br>Chưa có GIF</div>`;
            if (scene.audio_url) {
                previewState.audio = new Audio(scene.audio_url);
                previewState.audio.play().catch(() => {});
                if (previewState.playing) previewState.audio.onended = playNextScene;
            } else if (previewState.playing) {
                previewState.timer = setTimeout(playNextScene, (scene.duration_seconds || 3) * 1000);
            }
            renderPreviewSceneList();
        }

        function setPreviewContent(contentId) {
            previewState.content = contents.find(item => Number(item.id) === Number(contentId)) || null;
            previewState.index = 0;
            stopPlayback();
            renderPreviewSceneList();
            renderPreviewScene();
        }

        function playNextScene() {
            if (!previewState.playing || !previewState.content || !previewState.content.scenes.length) return;
            previewState.index = (previewState.index + 1) % previewState.content.scenes.length;
            renderPreviewScene();
        }

        previewContentSelect?.addEventListener('change', event => {
            renderOptions(event.target.value);
            if (event.target.value) setPreviewContent(event.target.value);
        });

        previewSceneSelect?.addEventListener('change', event => {
            if (!event.target.value) return;
            const sceneId = Number(event.target.value);
            const selectedContent = contents.find(content => content.scenes.some(scene => Number(scene.id) === sceneId));
            if (!selectedContent) return;
            previewState.content = selectedContent;
            previewState.index = selectedContent.scenes.findIndex(scene => Number(scene.id) === sceneId);
            stopPlayback();
            renderPreviewScene();
        });

        document.getElementById('preview-prev')?.addEventListener('click', () => {
            if (!previewState.content || !previewState.content.scenes.length) return;
            previewState.index = (previewState.index - 1 + previewState.content.scenes.length) % previewState.content.scenes.length;
            stopPlayback();
            renderPreviewScene();
        });

        document.getElementById('preview-next')?.addEventListener('click', () => {
            if (!previewState.content || !previewState.content.scenes.length) return;
            previewState.index = (previewState.index + 1) % previewState.content.scenes.length;
            stopPlayback();
            renderPreviewScene();
        });

        previewPlay?.addEventListener('click', () => {
            if (!previewState.content || !previewState.content.scenes.length) return;
            previewState.playing = !previewState.playing;
            previewPlay.textContent = previewState.playing ? '⏸ Dừng' : '▶ Chạy';
            renderPreviewScene();
        });

        renderOptions();
        renderPreviewSceneList();
    </script>
@endsection
