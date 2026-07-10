<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\ExportLog;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ExportController extends Controller
{
    public function index(Request $request)
    {
        $selectedUserIds = $this->user()->isAdmin()
            ? collect($request->input('user_ids', []))->filter()->map(fn ($value) => (int) $value)->values()->all()
            : [$this->user()->id];
        $fromDate = $request->string('from_date')->toString();
        $toDate = $request->string('to_date')->toString();
        $exportType = $request->string('export_type')->toString();

        $contents = ContentItem::query()
            ->visibleTo($this->user())
            ->with(['scenes' => fn ($query) => $query->orderBy('sort_order')->orderBy('position')])
            ->when($selectedUserIds !== [], fn ($query) => $query->whereIn('created_by', $selectedUserIds))
            ->get();

        $logs = ExportLog::query()
            ->visibleTo($this->user())
            ->when($selectedUserIds !== [], fn ($query) => $query->whereIn('user_id', $selectedUserIds))
            ->when($fromDate, fn ($query) => $query->whereDate('exported_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('exported_at', '<=', $toDate))
            ->when($exportType, fn ($query) => $query->where('export_type', $exportType))
            ->orderByDesc('exported_at')
            ->get();

        return view('exports.index', [
            'contents' => $contents,
            'logs' => $logs,
            'users' => $this->user()->isAdmin() ? User::query()->orderBy('full_name')->get() : collect([$this->user()]),
            'selectedUserIds' => $selectedUserIds,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'exportType' => $exportType,
        ]);
    }

    public function scene(Scene $scene)
    {
        $this->authorizeOwnership($scene);

        $scene->load('content.category');

        $zipPath = $this->makeZipPath('scene-'.$scene->id);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $folder = $this->safeName($scene->name);
        $zip->addFromString($folder.'/scene.md', $this->sceneMarkdown($scene));
        $this->addMedia($zip, $folder, $scene);
        $zip->close();
        $this->logExport('SCENE_ZIP', $folder.'.zip', 'scene:'.$scene->id.'|content:'.$scene->content_item_id);

        return response()->download($zipPath, $folder.'.zip')->deleteFileAfterSend(true);
    }

    public function content(ContentItem $content)
    {
        $this->authorizeOwnership($content);

        $content->load(['category', 'scenes']);

        $zipPath = $this->makeZipPath('content-'.$content->id);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $folder = $this->safeFolderName($content->name);
        $mainScenes = $content->scenes
            ->where('scene_type', 'main')
            ->sortBy('position')
            ->values();

        $zip->addFromString($folder.'/story.md', $this->storyMarkdown($content, $mainScenes, $folder));

        foreach ($mainScenes as $scene) {
            $gifFileName = $this->exportGifFileName($content, $scene);
            $this->addGifMedia($zip, $folder, $scene, $gifFileName);
        }

        $zip->close();
        $this->logExport('CONTENT_ZIP', $folder.'.zip', 'content:'.$content->id);

        return response()->download($zipPath, $folder.'.zip')->deleteFileAfterSend(true);
    }

    private function logExport(string $type, string $fileName, string $scope): void
    {
        ExportLog::create([
            'user_id' => $this->user()->id,
            'username' => $this->user()->username,
            'export_type' => $type,
            'file_name' => $fileName,
            'data_scope' => $scope,
            'exported_at' => now(),
        ]);
    }

    private function addMedia(ZipArchive $zip, string $folder, Scene $scene): void
    {
        if ($scene->gif_path && Storage::disk('public')->exists($scene->gif_path)) {
            $zip->addFromString($folder.'/'.($scene->gif_original_name ?: basename($scene->gif_path)), Storage::disk('public')->get($scene->gif_path));
        }

        if ($scene->audio_path && Storage::disk('public')->exists($scene->audio_path)) {
            $zip->addFromString($folder.'/'.($scene->audio_original_name ?: basename($scene->audio_path)), Storage::disk('public')->get($scene->audio_path));
        }
    }

    private function addGifMedia(ZipArchive $zip, string $folder, Scene $scene, string $fileName): void
    {
        if (! $scene->gif_path || ! Storage::disk('public')->exists($scene->gif_path)) {
            return;
        }

        $zip->addFromString($folder.'/'.$fileName, Storage::disk('public')->get($scene->gif_path));
    }

    private function sceneMarkdown(Scene $scene): string
    {
        return implode("\n", [
            '# '.$scene->name,
            '',
            '- Content: '.$scene->content->name,
            '- Danh mục: '.$scene->content->category->name,
            '- Kiểu: '.($scene->scene_type === 'transition' ? 'Chuyển tiếp' : 'Phân cảnh chính'),
            '- Thứ tự: '.($scene->position_label ?: $scene->position),
            '- Duration: '.$scene->duration_seconds.' giây',
            '- GIF: '.($scene->gif_original_name ?: 'Không có'),
            '- Audio: '.($scene->audio_original_name ?: 'Không có'),
            '- Nội dung: '.($scene->scene_text ?: 'Không có'),
        ]);
    }

    private function storyMarkdown(ContentItem $content, $scenes, string $folder): string
    {
        $lines = [];

        foreach ($scenes as $scene) {
            $text = trim((string) $scene->scene_text);

            if ($text !== '') {
                $lines[] = $text.' /';
            }

            if ($scene->gif_path && Storage::disk('public')->exists($scene->gif_path)) {
                $gifFileName = $this->exportGifFileName($content, $scene);
                $lines[] = sprintf('[%s/%s](./%s).', $folder, $gifFileName, $gifFileName);
            }

            $lines[] = '';
        }

        return rtrim(implode("\n", $lines));
    }

    private function safeName(string $value): string
    {
        return Str::slug($value) ?: 'export';
    }

    private function safeFolderName(string $value): string
    {
        $ascii = Str::ascii($value);
        $folder = preg_replace('/[^A-Za-z0-9]+/', '', strtolower($ascii)) ?: 'export';

        return $folder;
    }

    private function exportGifFileName(ContentItem $content, Scene $scene): string
    {
        $prefix = $this->contentInitials($content->name);
        $position = preg_replace('/[^0-9A-Za-z]+/', '', (string) ($scene->position_label ?: $scene->position)) ?: (string) $scene->position;

        return $prefix.$position.'.GIF';
    }

    private function contentInitials(string $value): string
    {
        $ascii = trim(Str::ascii($value));
        $words = preg_split('/\s+/', $ascii, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return 'SCN';
        }

        $initials = collect($words)
            ->map(fn (string $word) => strtoupper(substr($word, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'SCN';
    }

    private function makeZipPath(string $prefix): string
    {
        $directory = storage_path('app/tmp');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $directory.'/'.$prefix.'-'.Str::uuid().'.zip';
    }
}
