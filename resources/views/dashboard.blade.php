@extends('layouts.app')

@section('title', 'Build Content Tool')

@section('content')
    <div class="stack">
        <section id="settings" class="card">
            <div class="header" style="margin-bottom: 0;">
                <div>
                    <h1 class="page-title">Setting</h1>
                    <p class="muted">Đổi nền toàn bộ hệ thống sang đen hoặc trắng. Cài đặt này được lưu trong database và áp dụng cho mọi màn hình.</p>
                </div>
            </div>
            <div class="grid grid-2" style="margin-top: 20px;">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Giao diện hệ thống</h2>
                    </div>
                    <form method="POST" action="{{ route('settings.theme') }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label class="form-label">Màu nền hệ thống</label>
                            <select class="form-input" name="app_theme">
                                <option value="dark" @selected(($appTheme ?? 'dark') === 'dark')>Nền đen</option>
                                <option value="light" @selected(($appTheme ?? 'dark') === 'light')>Nền trắng</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">Lưu setting</button>
                    </form>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Hiệu lực</h2>
                    </div>
                    <div class="stack">
                        <div class="list-item">
                            <div class="list-item-title">Toàn bộ hệ thống</div>
                            <div class="list-item-desc">Layout, card, text, preview screen và các màn hình chi tiết sẽ đổi theo theme đã chọn.</div>
                        </div>
                        <div class="list-item">
                            <div class="list-item-title">Lưu trong database</div>
                            <div class="list-item-desc">Cài đặt dùng chung cho project, không phụ thuộc trình duyệt hay máy đang mở.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="categories" class="card">
            <div class="header" style="margin-bottom: 0;">
                <div>
                    <h1 class="page-title">Danh sách danh mục</h1>
                    <p class="muted">CRUD danh mục và content đã dùng database thật.</p>
                </div>
            </div>
            <div class="grid grid-2" style="margin-top: 20px;">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Tạo danh mục mới</h2>
                    </div>
                    <form method="POST" action="{{ route('categories.store') }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Tên danh mục</label>
                            <input class="form-input" type="text" name="name" value="{{ old('name') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-input" name="description">{{ old('description') }}</textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">+ Tạo danh mục</button>
                    </form>
                </div>
                <div class="grid grid-2">
                    @forelse ($categories as $category)
                        <a class="list-item" href="{{ route('categories.show', $category) }}">
                            <div class="list-item-title">{{ $category->name }}</div>
                            <div class="list-item-desc">{{ $category->description ?: 'Không có mô tả' }}</div>
                            <div class="list-item-meta">
                                <span class="tag">{{ $category->contents_count }} content</span>
                            </div>
                        </a>
                    @empty
                        <div class="empty-state">Chưa có danh mục nào.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="contents" class="card">
            <div class="header" style="margin-bottom: 0;">
                <div>
                    <h2 class="page-title">Danh sách content</h2>
                    <p class="muted">Mỗi content thuộc một danh mục và chứa nhiều phân cảnh.</p>
                </div>
            </div>
            <div class="grid grid-2" style="margin-top: 20px;">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tạo content mới</h3>
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
                            <label class="form-label">Tên content</label>
                            <input class="form-input" type="text" name="name" value="{{ old('name') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-input" name="description">{{ old('description') }}</textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">+ Tạo content</button>
                    </form>
                </div>
                <div>
                    <div class="tabs">
                        <button class="tab active" type="button" data-filter="all">Tất cả</button>
                        @foreach ($categories as $category)
                            <button class="tab" type="button" data-filter="{{ $category->id }}">{{ $category->name }}</button>
                        @endforeach
                    </div>
                    <div class="grid grid-2">
                        @forelse ($contents as $content)
                            <a class="list-item content-card" data-category="{{ $content->category_id }}" href="{{ route('contents.show', $content) }}">
                                <div class="list-item-title">{{ $content->name }}</div>
                                <div class="list-item-desc">{{ $content->description ?: 'Không có mô tả' }}</div>
                                <div class="list-item-meta">
                                    <span class="tag">{{ $content->category?->name }}</span>
                                    <span class="tag tag-primary">{{ $content->scenes_count }} phân cảnh</span>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state">Chưa có content nào.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section id="preview" class="card">
            <div class="header" style="margin-bottom: 0;">
                <div>
                    <h2 class="page-title">Xem trước</h2>
                    <p class="muted">Màn hình preview tỷ lệ 3:2 để test nhanh content hoặc một scene riêng lẻ.</p>
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

        <section id="export" class="card">
            <div class="header" style="margin-bottom: 0;">
                <div>
                    <h2 class="page-title">Xuất file</h2>
                    <p class="muted">Xuất scene hoặc content thành file zip có media và file markdown ghi chú.</p>
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
                        </select>
                    </div>
                    <a class="btn btn-primary" id="export-scene-link" href="#" style="pointer-events: none; opacity: .5;">📦 Xuất phân cảnh</a>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Xuất content</h3>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Chọn content</label>
                        <select class="form-input" id="export-content-select">
                            <option value="">Chọn content</option>
                            @foreach ($contents as $content)
                                <option value="{{ $content->id }}">{{ $content->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a class="btn btn-primary" id="export-content-link" href="#" style="pointer-events: none; opacity: .5;">📦 Xuất content</a>
                </div>
            </div>
        </section>
    </div>
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
        const exportSceneSelect = document.getElementById('export-scene-select');
        const exportSceneLink = document.getElementById('export-scene-link');
        const exportContentSelect = document.getElementById('export-content-select');
        const exportContentLink = document.getElementById('export-content-link');

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
            exportSceneSelect.innerHTML = '<option value="">Chọn phân cảnh</option>';
            contents.forEach(content => {
                if (!contentId || Number(content.id) === Number(contentId)) {
                    content.scenes.forEach(scene => {
                        previewSceneSelect.insertAdjacentHTML('beforeend', `<option value="${scene.id}">${content.name} - ${scene.name}</option>`);
                    });
                }
                content.scenes.forEach(scene => {
                    exportSceneSelect.insertAdjacentHTML('beforeend', `<option value="${scene.id}">${content.name} - ${scene.name}</option>`);
                });
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

        document.querySelectorAll('.tab[data-filter]').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab[data-filter]').forEach(item => item.classList.remove('active'));
                tab.classList.add('active');
                const filter = tab.dataset.filter;
                document.querySelectorAll('.content-card').forEach(card => {
                    card.style.display = filter === 'all' || card.dataset.category === filter ? 'block' : 'none';
                });
            });
        });

        renderOptions();
        renderPreviewSceneList();
    </script>
@endsection
