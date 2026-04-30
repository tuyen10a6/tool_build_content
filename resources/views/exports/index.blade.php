@extends('layouts.app')

@section('title', 'Xuất file')

@section('content')
    <section class="card">
        <div class="header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Xuất file</h1>
                <p class="muted">Trang riêng chỉ để xuất phân cảnh hoặc nội dung.</p>
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
