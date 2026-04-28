@extends('layouts.app')

@section('title', $content->name)

@section('content')
    <div class="header">
        <a class="btn btn-secondary" href="{{ route('contents.index') }}">← Quay lại</a>
        <a class="btn btn-primary" href="{{ route('exports.contents', $content) }}">📦 Xuất content</a>
    </div>
    <div class="card">
        <div class="detail-title">{{ $content->name }}</div>
        <p class="detail-desc" style="margin-top: 8px;">{{ $content->description ?: 'Không có mô tả' }}</p>
        <div class="detail-stats">
            <div class="stat-item">
                <span class="stat-value">{{ $content->category?->name }}</span>
                <span class="muted">danh mục</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">{{ $content->mainScenes->count() }}</span>
                <span class="muted">phân cảnh chính</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">{{ $content->scenes->count() }}</span>
                <span class="muted">tổng thư mục xuất</span>
            </div>
        </div>
    </div>
    <div class="tabs" style="margin-top: 20px;">
        <a class="tab tab-link active" href="{{ route('contents.index') }}">Danh sách content</a>
        <a class="tab tab-link" href="{{ route('transition-templates.index') }}">Mẫu chuyển tiếp</a>
    </div>
    <div class="grid grid-2" style="margin-top: 20px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cập nhật content</h3>
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
                    <label class="form-label">Tên content</label>
                    <input class="form-input" type="text" name="name" value="{{ old('name', $content->name) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-input" name="description">{{ old('description', $content->description) }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Lưu content</button>
            </form>
            <form method="POST" action="{{ route('contents.destroy', $content) }}" style="margin-top: 12px;">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit" onclick="return confirm('Xóa content này?')">Xóa content</button>
            </form>
        </div>
        <div style="margin-top: 0px " class="card">
            <div class="card-header">
                <h3 class="card-title">Tạo phân cảnh chính mới</h3>
            </div>
            <form method="POST" action="{{ route('scenes.store', $content) }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Tên phân cảnh</label>
                    <input class="form-input" type="text" name="name">
                </div>
                <div class="form-group">
                    <label class="form-label">GIF</label>
                    <input class="form-input" type="file" name="gif" accept=".gif,.jpg,.jpeg,.png,.webp" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Audio</label>
                    <input class="form-input" id="scene-audio-input" type="file" name="audio" accept="audio/*">
                    <div class="muted" id="scene-audio-help" style="margin-top: 8px;">Nếu có audio, Duration sẽ tự lấy bằng thời lượng audio.</div>
                </div>
                <div class="form-group">
                    <label class="form-label" id="scene-duration-label">Duration fallback (Trong trường hợp không có audio)</label>
                    <input class="form-input" id="scene-duration-input" type="number" min="1" max="3600" name="duration_seconds" value="3">
                </div>
                <div class="form-group">
                    <label class="form-label">Phân cảnh chuyển tiếp sau cảnh này</label>
                    <select class="form-input" name="next_transition_template_id">
                        <option value="">Không dùng chuyển tiếp</option>
                        @foreach ($transitionTemplates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->duration_seconds }}s)</option>
                        @endforeach
                    </select>
                    <div class="muted" style="margin-top: 8px;">Có thể chọn từ thư viện mẫu, hoặc tạo mới ngay bên dưới cho riêng cảnh này.</div>
                </div>
                <hr style="margin: 30px 0;">
                <details class="card details-card" style="margin-bottom: 16px;">
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
                            <label class="form-label" id="transition-duration-label">Duration khi không có audio</label>
                            <input class="form-input" id="transition-duration-input" type="number" min="1" max="3600" name="transition_duration_seconds" value="{{ old('transition_duration_seconds', 3) }}">
                        </div>
                    </div>
                </details>
                <button class="btn btn-primary" type="submit">+ Tạo phân cảnh</button>
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
                            <div class="scene-details">
                                <span>⏱️ {{ $scene->duration_seconds }} giây</span>
                                <span>🖼️ {{ $scene->gif_original_name ?: 'Chưa có GIF' }}</span>
                                <span>🎵 {{ $scene->audio_original_name ?: 'Không có audio' }}</span>
                                <span>🔀 {{ $scene->nextTransitionTemplate?->name ?: 'Không có chuyển tiếp sau cảnh này' }}</span>
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
                <div class="empty-state">Content này chưa có phân cảnh chính nào.</div>
            @endforelse
        </div>
    </div>
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">Chuỗi preview / export</h3>
        </div>
        <div class="stack">
            @forelse ($content->scenes->sortBy([['sort_order', 'asc'], ['position', 'asc']]) as $scene)
                <div class="scene-item">
                    <div class="scene-main">
                        <div class="scene-number">{{ $scene->position_label ?: $scene->position }}</div>
                        <div>
                            <div class="scene-name">{{ $scene->name }}</div>
                            <div class="scene-details">
                                <span>{{ $scene->scene_type === 'transition' ? 'Chuyển tiếp' : 'Phân cảnh chính' }}</span>
                                <span>⏱️ {{ $scene->duration_seconds }} giây</span>
                                <span>📁 folder: {{ $scene->position_label ?: $scene->position }}</span>
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
            audioInputId: 'scene-audio-input',
            durationInputId: 'scene-duration-input',
            durationLabelId: 'scene-duration-label',
            helpTextId: 'scene-audio-help',
            emptyLabel: 'Duration fallback (Trong trường hợp không có audio)',
            activeLabel: 'Duration',
            emptyHelp: 'Nếu có audio, Duration sẽ tự lấy bằng thời lượng audio.',
            activeHelpPrefix: 'Đã lấy Duration theo audio:',
        });

        bindAudioDurationSync({
            audioInputId: 'transition-audio-input',
            durationInputId: 'transition-duration-input',
            durationLabelId: 'transition-duration-label',
            helpTextId: 'transition-audio-help',
            emptyLabel: 'Duration khi không có audio',
            activeLabel: 'Duration',
            emptyHelp: 'Nếu có audio, thời lượng chuyển tiếp sẽ tự lấy đúng theo thời lượng audio.',
            activeHelpPrefix: 'Đã lấy Duration chuyển tiếp theo audio:',
        });
    </script>
@endsection
