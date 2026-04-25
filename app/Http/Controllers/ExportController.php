<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\Scene;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ExportController extends Controller
{
    public function index()
    {
        return view('exports.index', [
            'contents' => ContentItem::with('scenes')->get(),
        ]);
    }

    public function scene(Scene $scene)
    {
        $scene->load('content.category');

        $zipPath = $this->makeZipPath('scene-'.$scene->id);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $folder = $this->safeName($scene->name);
        $zip->addFromString($folder.'/scene.md', $this->sceneMarkdown($scene));
        $this->addMedia($zip, $folder, $scene);
        $zip->close();

        return response()->download($zipPath, $folder.'.zip')->deleteFileAfterSend(true);
    }

    public function content(ContentItem $content)
    {
        $content->load(['category', 'scenes']);

        $zipPath = $this->makeZipPath('content-'.$content->id);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $folder = $this->safeName($content->name);
        $zip->addFromString($folder.'/content.md', $this->contentMarkdown($content));

        foreach ($content->scenes as $index => $scene) {
            $sceneFolder = $folder.'/'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'-'.$this->safeName($scene->name);
            $zip->addFromString($sceneFolder.'/scene.md', $this->sceneMarkdown($scene));
            $this->addMedia($zip, $sceneFolder, $scene);
        }

        $zip->close();

        return response()->download($zipPath, $folder.'.zip')->deleteFileAfterSend(true);
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

    private function sceneMarkdown(Scene $scene): string
    {
        return implode("\n", [
            '# '.$scene->name,
            '',
            '- Content: '.$scene->content->name,
            '- Danh mục: '.$scene->content->category->name,
            '- Thứ tự: '.$scene->position,
            '- Thời gian hiển thị GIF: '.$scene->duration_seconds.' giây',
            '- GIF: '.($scene->gif_original_name ?: 'Không có'),
            '- Audio: '.($scene->audio_original_name ?: 'Không có'),
        ]);
    }

    private function contentMarkdown(ContentItem $content): string
    {
        $lines = [
            '# '.$content->name,
            '',
            '- Danh mục: '.$content->category->name,
            '- Mô tả: '.($content->description ?: 'Không có'),
            '- Số phân cảnh: '.$content->scenes->count(),
            '',
            '## Danh sách phân cảnh',
            '',
        ];

        foreach ($content->scenes as $scene) {
            $lines[] = sprintf(
                '%d. %s | GIF: %s | Audio: %s | %d giây',
                $scene->position,
                $scene->name,
                $scene->gif_original_name ?: 'Không có',
                $scene->audio_original_name ?: 'Không có',
                $scene->duration_seconds
            );
        }

        return implode("\n", $lines);
    }

    private function safeName(string $value): string
    {
        return Str::slug($value) ?: 'export';
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
