@extends('layouts.app')

@section('title', $content->name)

@php
    $shouldOpenSceneModal = $errors->hasAny([
        'video',
        'audio',
        'scene_text',
        'next_transition_template_id',
        'transition_name',
        'transition_description',
        'transition_gif',
        'transition_audio',
        'transition_duration_seconds',
    ]);
    $shouldOpenContentModal = $errors->hasAny([
        'category_id',
        'description',
    ]);
@endphp

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

        .scene-line-block {
            min-width: 0;
            flex: 1;
        }

        .scene-line {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            flex-wrap: nowrap;
            width: 650px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .scene-line-name {
            font-weight: 700;
            color: var(--text);
            flex-shrink: 0;
            display: inline-block; /* hoặc block */
            max-width: 300px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .scene-line-meta {
            color: var(--text-muted);
            font-size: 13px;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.62);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 1200;
        }

        .modal-overlay.is-open {
            display: flex;
        }

        .modal-dialog {
            width: min(820px, 100%);
            max-height: calc(100vh - 40px);
            overflow: auto;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
            padding: 24px;
        }

        .modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .modal-close {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--input-bg);
            color: var(--text);
            cursor: pointer;
            font-size: 20px;
            line-height: 1;
            flex-shrink: 0;
        }

        body.modal-open {
            overflow: hidden;
        }

        @media (max-width: 640px) {
            .scene-line {
                display: block;
            }

            .scene-line-meta {
                display: block;
                margin-top: 4px;
            }

            .modal-dialog {
                padding: 18px;
                max-height: calc(100vh - 24px);
            }
        }
    </style>
    <div class="header">
        <a class="btn btn-secondary" href="{{ route('contents.index') }}">← Quay lại</a>
        <a class="btn btn-primary" href="{{ route('exports.contents', $content) }}">📦 Xuất nội dung</a>
    </div>
    <div style="display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap;" class="card">
        <div>
             <div class="detail-title">{{ $content->name }}</div>
            <p class="detail-desc" style="margin-top: 8px;">{{ $content->description ?: 'Không có mô tả' }}</p>
            <div class="detail-stats">
            <div class="stat-item">
                <span class="muted">Danh mục:</span>
                <span class="stat-value">{{ $content->category?->name }}</span>
        
            </div>
            <div class="stat-item">
                <span class="muted">Phân cảnh chính:</span>
                <span class="stat-value">{{ $content->mainScenes->count() }}</span>
            </div>
            <div class="stat-item">
                <span class="muted">Tổng thư mục xuất:</span>
                <span class="stat-value">{{ $content->scenes->count() }}</span>
            </div>
          </div>
        </div>
        <div class="actions" style="justify-content: flex-end;">
            <button class="btn btn-secondary" type="button" id="open-update-content-modal">✏️ Cập nhật nội dung</button>
            <button class="btn btn-primary" type="button" id="open-create-scene-modal">+ Tạo phân cảnh</button>
        </div>
    
    </div>

    <div class="tabs" style="margin-top: 20px;">
            <a class="tab tab-link active" href="{{ route('contents.index') }}">Danh sách nội dung</a>
        <a class="tab tab-link" href="{{ route('transition-templates.index') }}">Mẫu chuyển tiếp</a>
    </div>
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            
        </div>
        <div class="grid grid-2" style="margin-top: 20px;">
            <div id="content-preview-scenes-list" class="stack"></div>
            <div>
                <div class="preview-screen" id="content-preview-screen">
                    <div class="preview-stage">
                        <img class="preview-stage-image" id="content-preview-stage-image" alt="">
                        <div class="preview-stage-placeholder" id="content-preview-stage-placeholder">Nội dung này chưa có chuỗi xem trước.</div>
                    </div>
                </div>
                <div class="preview-controls">
                    <button class="btn btn-secondary" type="button" id="content-preview-prev">◀ Trước</button>
                    <button class="btn btn-primary" type="button" id="content-preview-play">▶ Xem trước</button>
                    <button class="btn btn-secondary" type="button" id="content-preview-stop">⏹ Dừng</button>
                    <button class="btn btn-secondary" type="button" id="content-preview-next">Sau ▶</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="update-content-modal" aria-hidden="true">
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="update-content-modal-title">
            <div class="modal-header">
                <div>
                    <h3 class="card-title" id="update-content-modal-title">Cập nhật nội dung</h3>
                    <div class="muted" style="margin-top: 6px;">Chỉnh sửa danh mục, tên và mô tả của nội dung này.</div>
                </div>
                <button class="modal-close" type="button" id="close-update-content-modal" aria-label="Đóng modal">×</button>
            </div>
            <form method="POST" action="{{ route('contents.update', $content) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Danh mục</label>
                    <select class="form-input" name="category_id">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $content->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tên nội dung</label>
                    <input class="form-input" type="text" name="name" value="{{ $content->name }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-input" name="description">{{ old('description', $content->description) }}</textarea>
                </div>
                <div class="actions" style="justify-content: flex-end; margin-top: 20px;">
                    <button class="btn btn-secondary" type="button" id="cancel-update-content-modal">Đóng</button>
                    <button class="btn btn-primary" type="submit">Lưu nội dung</button>
                </div>
            </form>
            <form method="POST" action="{{ route('contents.destroy', $content) }}" style="margin-top: 12px;">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit" onclick="return confirm('Xóa nội dung này?')">Xóa nội dung</button>
            </form>
        </div>
    </div>
    <div class="modal-overlay" id="create-scene-modal" aria-hidden="true">
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="create-scene-modal-title">
            <div class="modal-header">
                <div>
                    <h3 class="card-title" id="create-scene-modal-title">Tạo phân cảnh chính mới</h3>
                </div>
                <button class="modal-close" type="button" id="close-create-scene-modal" aria-label="Đóng modal">×</button>
            </div>
            <form method="POST" action="{{ route('scenes.store', $content) }}" enctype="multipart/form-data" id="create-scene-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">Tên phân cảnh</label>
                    <input class="form-input" type="text" name="name" value="{{ old('name') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Nội dung phân cảnh</label>
                    <textarea class="form-input" name="scene_text" rows="5" placeholder="Nhập nội dung text của phân cảnh...">{{ old('scene_text') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Video MP4</label>
                    <input class="form-input" type="file" name="video" accept=".mp4,video/mp4" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ảnh minh hoạ (không bắt buộc)</label>
                    <input class="form-input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <div class="muted" style="margin-top: 8px;">Nếu cần, bạn có thể đính kèm thêm một ảnh cho phân cảnh này.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Audio</label>
                    <input class="form-input" id="scene-audio-input" type="file" name="audio" accept="audio/*">
                    <div class="muted" style="margin-top: 8px;">Trường này hiện tại chỉ cần nhập nếu muốn xem trước nội dung video, không bắt buộc.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Phân cảnh chuyển tiếp sau cảnh này</label>
                    <select class="form-input" name="next_transition_template_id">
                        <option value="">Không dùng chuyển tiếp</option>
                        @foreach ($transitionTemplates as $template)
                            <option value="{{ $template->id }}" @selected(old('next_transition_template_id') == $template->id)>{{ $template->name }} ({{ $template->duration_seconds }} giây)</option>
                        @endforeach
                    </select>
                    <div class="muted" style="margin-top: 8px;">Có thể chọn từ thư viện mẫu, hoặc tạo mới ngay bên dưới cho riêng cảnh này.</div>
                </div>
                <hr style="margin: 30px 0;">
                <details class="card details-card" style="margin-bottom: 16px;" @if(old('transition_name') || old('transition_description') || old('transition_duration_seconds')) open @endif>
                    <summary>Tạo nhanh phân cảnh chuyển tiếp mới cho cảnh này</summary>
                    <div class="details-card-body">
                        <div class="form-group" style="margin-top: 16px;">
                            <label class="form-label">Tên chuyển tiếp mới</label>
                            <input class="form-input" type="text" name="transition_name" value="{{ old('transition_name') }}" placeholder="Ví dụ: Màn đen 3s riêng cho cảnh này">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mô tả chuyển tiếp</label>
                            <textarea class="form-input" name="transition_description" placeholder="Mô tả ngắn nếu cần">{{ old('transition_description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">GIF chuyển tiếp</label>
                            <input class="form-input" type="file" name="transition_gif" accept=".gif,.jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Audio chuyển tiếp</label>
                            <input class="form-input" id="transition-audio-input" type="file" name="transition_audio" accept="audio/*">
                            <div class="muted" id="transition-audio-help" style="margin-top: 8px;">Nếu có audio, thời lượng chuyển tiếp sẽ tự lấy đúng theo thời lượng audio.</div>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" id="transition-duration-label">Thời lượng khi không có audio</label>
                            <input class="form-input" id="transition-duration-input" type="number" min="1" max="3600" name="transition_duration_seconds" value="{{ old('transition_duration_seconds', 3) }}">
                        </div>
                    </div>
                </details>
                <div class="actions" style="justify-content: flex-end; margin-top: 20px;">
                    <button class="btn btn-secondary" type="button" id="cancel-create-scene-modal">Đóng</button>
                    <button class="btn btn-primary" type="submit" id="create-scene-submit">
                        <span id="create-scene-submit-text">+ Tạo phân cảnh</span>
                        <span class="btn-loading-spinner" id="create-scene-submit-spinner" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">Phân cảnh chính</h3>
        </div>
        <div class="stack">
            @forelse ($content->mainScenes as $scene)
                <div class="scene-item">
                    <div class="scene-main">
                        <div class="scene-number">{{ $scene->position }}</div>
                        <div>
                            <div class="scene-name">{{ $scene->name }}</div>
                            @if ($scene->scene_text)
                                <div class="list-item-desc">{{ $scene->scene_text }}</div>
                            @endif
                            <div class="scene-details">
                                <span>⏱️ {{ $scene->duration_seconds }} giây</span>
                                <span>🖼️ {{ $scene->gif_original_name ?: 'Chưa có GIF' }}</span>
                                <span>🖼️ Ảnh: {{ $scene->image_original_name ?: 'Không có ảnh' }}</span>
                                <span>🎵 {{ $scene->audio_original_name ?: 'Không có audio' }}</span>
                                <span>🔀 {{ $scene->nextTransitionTemplate?->name ?: 'Không có chuyển tiếp sau cảnh này' }}</span>
                                <span>👤 {{ $scene->created_by_name }}</span>
                                <span>🗓️ {{ $scene->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn btn-secondary" href="{{ route('scenes.show', $scene) }}">Chi tiết</a>
                        <form method="POST" action="{{ route('scenes.duplicate', $scene) }}">
                            @csrf
                            <button class="btn btn-secondary" type="submit">Nhân bản</button>
                        </form>
                        <a class="btn btn-secondary" href="{{ route('exports.scenes', $scene) }}">Xuất</a>
                    </div>
                </div>
            @empty
                <div class="empty-state">Nội dung này chưa có phân cảnh chính nào.</div>
            @endforelse
        </div>
    </div>
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">Chuỗi preview / export</h3>
        </div>
        <div class="stack">
            @forelse ($previewSequence as $scene)
                <div class="scene-item">
                    <div class="scene-main">
                        <div class="scene-number">{{ $scene->position_label ?: $scene->position }}</div>
                        <div class="scene-line-block">
                            <div class="scene-line">
                                <span class="scene-line-name">{{ $scene->name }}</span>
                                <span class="scene-line-meta">{{ $scene->duration_seconds }} giây ; {{ $scene->gif_original_name ?: 'Chưa có GIF' }} ; {{ $scene->audio_original_name ?: 'Không có audio' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn btn-secondary" href="{{ route('scenes.show', $scene) }}">Xem</a>
                    </div>
                </div>
            @empty
                <div class="empty-state">Chưa có chuỗi export nào.</div>
            @endforelse
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const contentPreviewSequence = @json($previewSequence);

        function initPreviewPlayer({
            sequence,
            listElementId,
            imageElementId,
            placeholderElementId,
            playButtonId,
            stopButtonId,
            prevButtonId,
            nextButtonId,
            emptyMessage,
        }) {
            const state = {
                sequence: Array.isArray(sequence) ? sequence : [],
                index: 0,
                timer: null,
                audio: null,
                hasInteracted: false,
                playing: false,
                remainingMs: 0,
                startedAt: null,
                pausedAudioTime: 0,
                imageCache: new Map(),
                audioCache: new Map(),
            };

            const listElement = document.getElementById(listElementId);
            const imageElement = document.getElementById(imageElementId);
            const placeholderElement = document.getElementById(placeholderElementId);
            const playButton = document.getElementById(playButtonId);
            const stopButton = document.getElementById(stopButtonId);
            const prevButton = document.getElementById(prevButtonId);
            const nextButton = document.getElementById(nextButtonId);

            window.togglePreviewButtons(playButton, stopButton, false);

            const currentScene = () => state.sequence[state.index] || null;

            const isCurrentScenePaused = (scene = currentScene()) => {
                if (!scene) {
                    return false;
                }

                if (scene.audio_url) {
                    return (state.pausedAudioTime || 0) > 0;
                }

                const totalMs = (scene.duration_seconds || 3) * 1000;
                return state.remainingMs > 0 && state.remainingMs < totalMs;
            };

            const currentSceneRemainingSeconds = (scene = currentScene()) => {
                if (!scene) {
                    return 0;
                }

                if (scene.audio_url) {
                    return Math.max(0, Math.ceil((scene.duration_seconds || 3) - (state.pausedAudioTime || 0)));
                }

                return Math.max(0, Math.ceil((state.remainingMs || (scene.duration_seconds || 3) * 1000) / 1000));
            };

            const primeSceneMedia = (scene) => {
                if (!scene) {
                    return;
                }

                if (scene.gif_url && !state.imageCache.has(scene.gif_url)) {
                    const image = new Image();
                    const ready = new Promise((resolve) => {
                        image.onload = () => resolve(image);
                        image.onerror = () => resolve(null);
                    });

                    image.decoding = 'async';
                    image.src = scene.gif_url;
                    state.imageCache.set(scene.gif_url, ready);
                }

                if (scene.audio_url && !state.audioCache.has(scene.audio_url)) {
                    const audio = new Audio();
                    audio.preload = 'auto';
                    audio.src = scene.audio_url;
                    state.audioCache.set(scene.audio_url, audio);
                }
            };

            const primeNearbyScenes = () => {
                primeSceneMedia(currentScene());
                primeSceneMedia(state.sequence[state.index + 1] || null);
                primeSceneMedia(state.sequence[state.index - 1] || null);
            };

            const showSceneImage = async (scene) => {
                if (!scene?.gif_url) {
                    imageElement.classList.remove('is-visible');
                    imageElement.removeAttribute('src');
                    imageElement.alt = '';
                    placeholderElement.innerHTML = `${scene?.name || emptyMessage}<br>${scene ? 'Chưa có GIF' : ''}`.trim();
                    placeholderElement.style.display = 'flex';
                    return;
                }

                primeSceneMedia(scene);
                const loadedImage = await state.imageCache.get(scene.gif_url);

                if (currentScene()?.gif_url !== scene.gif_url) {
                    return;
                }

                if (!loadedImage) {
                    imageElement.classList.remove('is-visible');
                    imageElement.removeAttribute('src');
                    placeholderElement.innerHTML = `${scene.name}<br>Không tải được GIF`;
                    placeholderElement.style.display = 'flex';
                    return;
                }

                placeholderElement.style.display = 'none';
                imageElement.classList.remove('is-visible');

                requestAnimationFrame(() => {
                    imageElement.src = scene.gif_url;
                    imageElement.alt = scene.name;
                    requestAnimationFrame(() => {
                        imageElement.classList.add('is-visible');
                    });
                });
            };

            const clearPlayback = () => {
                if (state.timer) {
                    clearTimeout(state.timer);
                    state.timer = null;
                }

                if (state.startedAt && state.playing && !state.audio) {
                    state.remainingMs = Math.max(0, state.remainingMs - (Date.now() - state.startedAt));
                }

                state.startedAt = null;

                if (state.audio) {
                    state.audio.pause();
                    state.pausedAudioTime = state.audio.currentTime;
                    state.remainingMs = Math.max(0, ((currentScene()?.duration_seconds || 3) * 1000) - (state.pausedAudioTime * 1000));
                }
            };

            const resetCurrentSceneProgress = () => {
                const scene = currentScene();
                state.remainingMs = Math.max(1, (scene?.duration_seconds || 3) * 1000);
                state.startedAt = null;
                state.pausedAudioTime = 0;

                if (state.audio) {
                    state.audio.pause();
                    state.audio.currentTime = 0;
                    state.audio = null;
                }
            };

            const stopPlayback = () => {
                state.playing = false;
                clearPlayback();
                window.togglePreviewButtons(playButton, stopButton, false);
            };

            const renderSequenceList = () => {
                if (!listElement) {
                    return;
                }

                if (!state.sequence.length) {
                    listElement.innerHTML = `<div class="empty-state">${emptyMessage}</div>`;
                    return;
                }

                listElement.innerHTML = state.sequence.map((scene, index) => `
                    <button type="button" class="scene-item" data-index="${index}" style="${index === state.index ? 'border-color: rgba(245, 158, 11, 0.55);' : ''}">
                        <div class="scene-main">
                            <div class="scene-number">${scene.position_label || scene.position}</div>
                            <div class="scene-line-block">
                                <div class="scene-line">
                                    <span class="scene-line-name">${scene.name}</span>
                                    <span class="scene-line-meta">${scene.duration_seconds} giây ; ${scene.gif_original_name || 'Chưa có GIF'} ; ${scene.audio_original_name || 'Không có audio'}${index === state.index && !state.playing && isCurrentScenePaused(scene) ? ` ; còn ${currentSceneRemainingSeconds(scene)} giây` : ''}</span>
                                </div>
                            </div>
                        </div>
                    </button>
                `).join('');

                listElement.querySelectorAll('[data-index]').forEach((button) => {
                    button.addEventListener('click', () => {
                        state.index = Number(button.dataset.index);
                        playFromCurrentScene({ resetProgress: true });
                    });
                });
            };

            const advanceScene = () => {
                clearPlayback();
                resetCurrentSceneProgress();

                if (!state.playing) {
                    return;
                }

                if (state.index >= state.sequence.length - 1) {
                    stopPlayback();
                    resetCurrentSceneProgress();
                    renderCurrentScene(false);
                    return;
                }

                state.index += 1;
                resetCurrentSceneProgress();
                renderCurrentScene(true);
            };

            const renderCurrentScene = (autoplay = false) => {
                const scene = currentScene();

                if (!scene) {
                    imageElement.classList.remove('is-visible');
                    imageElement.removeAttribute('src');
                    placeholderElement.textContent = emptyMessage;
                    placeholderElement.style.display = 'flex';
                    renderSequenceList();
                    return;
                }

                if (!state.hasInteracted) {
                    imageElement.classList.remove('is-visible');
                    imageElement.removeAttribute('src');
                    placeholderElement.textContent = 'Bấm Xem trước để bắt đầu.';
                    placeholderElement.style.display = 'flex';
                    renderSequenceList();
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
                    if (!state.audio) {
                        state.audio = new Audio(scene.audio_url);
                        state.audio.loop = false;
                        state.audio.onended = () => {
                            state.pausedAudioTime = 0;
                            advanceScene();
                        };
                    }

                    state.audio.currentTime = state.pausedAudioTime || 0;
                    state.audio.play().catch(() => advanceScene());
                    return;
                }

                state.startedAt = Date.now();
                state.timer = setTimeout(() => advanceScene(), state.remainingMs || (scene.duration_seconds || 3) * 1000);
            };

            const playFromCurrentScene = ({ resetProgress = false } = {}) => {
                if (!state.sequence.length) {
                    return;
                }

                state.hasInteracted = true;
                state.playing = true;
                if (resetProgress) {
                    resetCurrentSceneProgress();
                }
                window.togglePreviewButtons(playButton, stopButton, true);
                renderCurrentScene(true);
            };

            prevButton?.addEventListener('click', () => {
                if (!state.sequence.length) {
                    return;
                }

                state.index = Math.max(0, state.index - 1);
                playFromCurrentScene({ resetProgress: true });
            });

            nextButton?.addEventListener('click', () => {
                if (!state.sequence.length) {
                    return;
                }

                state.index = Math.min(state.sequence.length - 1, state.index + 1);
                playFromCurrentScene({ resetProgress: true });
            });

            playButton?.addEventListener('click', () => {
                playFromCurrentScene();
            });

            stopButton?.addEventListener('click', () => {
                stopPlayback();
                renderCurrentScene(false);
            });

            resetCurrentSceneProgress();
            renderCurrentScene(false);
        }

        initPreviewPlayer({
            sequence: contentPreviewSequence,
            listElementId: 'content-preview-scenes-list',
            imageElementId: 'content-preview-stage-image',
            placeholderElementId: 'content-preview-stage-placeholder',
            playButtonId: 'content-preview-play',
            stopButtonId: 'content-preview-stop',
            prevButtonId: 'content-preview-prev',
            nextButtonId: 'content-preview-next',
            emptyMessage: 'Nội dung này chưa có chuỗi xem trước.',
        });

        const updateContentModal = document.getElementById('update-content-modal');
        const openUpdateContentModal = document.getElementById('open-update-content-modal');
        const closeUpdateContentModal = document.getElementById('close-update-content-modal');
        const cancelUpdateContentModal = document.getElementById('cancel-update-content-modal');
        const shouldOpenContentModal = @json($shouldOpenContentModal);
        const createSceneModal = document.getElementById('create-scene-modal');
        const openCreateSceneModal = document.getElementById('open-create-scene-modal');
        const closeCreateSceneModal = document.getElementById('close-create-scene-modal');
        const cancelCreateSceneModal = document.getElementById('cancel-create-scene-modal');
        const shouldOpenSceneModal = @json($shouldOpenSceneModal);

        function syncModalBodyState() {
            const hasOpenModal = document.querySelector('.modal-overlay.is-open');
            document.body.classList.toggle('modal-open', Boolean(hasOpenModal));
        }

        function setModalState(modalElement, isOpen) {
            if (!modalElement) {
                return;
            }

            modalElement.classList.toggle('is-open', isOpen);
            modalElement.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            syncModalBodyState();
        }

        openUpdateContentModal?.addEventListener('click', () => setModalState(updateContentModal, true));
        closeUpdateContentModal?.addEventListener('click', () => setModalState(updateContentModal, false));
        cancelUpdateContentModal?.addEventListener('click', () => setModalState(updateContentModal, false));
        openCreateSceneModal?.addEventListener('click', () => setModalState(createSceneModal, true));
        closeCreateSceneModal?.addEventListener('click', () => setModalState(createSceneModal, false));
        cancelCreateSceneModal?.addEventListener('click', () => setModalState(createSceneModal, false));

        updateContentModal?.addEventListener('click', (event) => {
            if (event.target === updateContentModal) {
                setModalState(updateContentModal, false);
            }
        });

        createSceneModal?.addEventListener('click', (event) => {
            if (event.target === createSceneModal) {
                setModalState(createSceneModal, false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            if (createSceneModal?.classList.contains('is-open')) {
                setModalState(createSceneModal, false);
            }

            if (updateContentModal?.classList.contains('is-open')) {
                setModalState(updateContentModal, false);
            }
        });

        if (shouldOpenContentModal) {
            setModalState(updateContentModal, true);
        }

        if (shouldOpenSceneModal) {
            setModalState(createSceneModal, true);
        }

        function bindAudioDurationSync({
            audioInputId,
            durationInputId,
            durationLabelId,
            helpTextId,
            emptyLabel,
            activeLabel,
            emptyHelp,
            activeHelpPrefix,
        }) {
            const audioInput = document.getElementById(audioInputId);
            const durationInput = document.getElementById(durationInputId);
            const durationLabel = document.getElementById(durationLabelId);
            const helpText = document.getElementById(helpTextId);
            let fallbackValue = durationInput?.value || '3';

            if (!audioInput || !durationInput || !durationLabel || !helpText) {
                return;
            }

            const resetState = () => {
                durationLabel.textContent = emptyLabel;
                helpText.textContent = emptyHelp;
                durationInput.readOnly = false;
                durationInput.value = fallbackValue;
            };

            durationInput.addEventListener('input', () => {
                if (!durationInput.readOnly && durationInput.value) {
                    fallbackValue = durationInput.value;
                }
            });

            audioInput.addEventListener('change', () => {
                const [file] = audioInput.files || [];

                if (!file) {
                    resetState();
                    return;
                }

                const previewAudio = document.createElement('audio');
                const objectUrl = URL.createObjectURL(file);

                previewAudio.preload = 'metadata';
                previewAudio.src = objectUrl;
                previewAudio.onloadedmetadata = () => {
                    const duration = Math.max(1, Math.ceil(previewAudio.duration || 0));
                    durationLabel.textContent = activeLabel;
                    durationInput.value = duration;
                    durationInput.readOnly = true;
                    helpText.textContent = `${activeHelpPrefix} ${duration} giây.`;
                    URL.revokeObjectURL(objectUrl);
                };
                previewAudio.onerror = () => {
                    resetState();
                    helpText.textContent = `${emptyHelp} Không đọc được thời lượng audio này.`;
                    URL.revokeObjectURL(objectUrl);
                };
            });
        }

        bindAudioDurationSync({
            audioInputId: 'transition-audio-input',
            durationInputId: 'transition-duration-input',
            durationLabelId: 'transition-duration-label',
            helpTextId: 'transition-audio-help',
            emptyLabel: 'Thời lượng khi không có audio',
            activeLabel: 'Thời lượng',
            emptyHelp: 'Nếu có audio, thời lượng chuyển tiếp sẽ tự lấy đúng theo thời lượng audio.',
            activeHelpPrefix: 'Đã lấy thời lượng chuyển tiếp theo audio:',
        });

        const createSceneForm = document.getElementById('create-scene-form');
        const createSceneSubmit = document.getElementById('create-scene-submit');
        const createSceneSubmitText = document.getElementById('create-scene-submit-text');
        const createSceneSubmitSpinner = document.getElementById('create-scene-submit-spinner');

        createSceneForm?.addEventListener('submit', () => {
            if (!createSceneSubmit || !createSceneSubmitText || !createSceneSubmitSpinner) {
                return;
            }

            createSceneSubmit.disabled = true;
            createSceneSubmitText.textContent = 'Đang tạo phân cảnh...';
            createSceneSubmitSpinner.style.display = 'inline-block';
        });
    </script>
@endsection
