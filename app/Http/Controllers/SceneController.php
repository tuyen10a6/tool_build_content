<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\Scene;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SceneController extends Controller
{
    public function gif(Scene $scene): StreamedResponse
    {
        abort_unless($scene->gif_path && Storage::disk('public')->exists($scene->gif_path), 404);

        return Storage::disk('public')->response(
            $scene->gif_path,
            $scene->gif_original_name ?: basename($scene->gif_path),
            [
                'Content-Disposition' => 'inline; filename="'.$this->safeFilename($scene->gif_original_name ?: basename($scene->gif_path)).'"',
                'Cache-Control' => 'public, max-age=31536000',
            ]
        );
    }

    public function audio(Scene $scene): StreamedResponse
    {
        abort_unless($scene->audio_path && Storage::disk('public')->exists($scene->audio_path), 404);

        return Storage::disk('public')->response(
            $scene->audio_path,
            $scene->audio_original_name ?: basename($scene->audio_path),
            [
                'Content-Disposition' => 'inline; filename="'.$this->safeFilename($scene->audio_original_name ?: basename($scene->audio_path)).'"',
                'Cache-Control' => 'public, max-age=31536000',
            ]
        );
    }

    public function show(Scene $scene)
    {
        $scene->load('content.category');

        return view('scenes.show', [
            'scene' => $scene,
        ]);
    }

    public function store(Request $request, ContentItem $content): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gif' => ['nullable', 'file', 'mimes:gif,jpg,jpeg,png,webp'],
            'audio' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/ogg,audio/aac'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:3600'],
        ]);

        $scene = new Scene([
            'name' => $validated['name'],
            'duration_seconds' => $validated['duration_seconds'],
            'position' => ((int) $content->scenes()->max('position')) + 1,
        ]);

        $this->persistMedia($request, $scene);
        $content->scenes()->save($scene);

        return redirect()->route('contents.show', $content)->with('status', 'Tạo phân cảnh thành công.');
    }

    public function update(Request $request, Scene $scene): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gif' => ['nullable', 'file', 'mimes:gif,jpg,jpeg,png,webp'],
            'audio' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/ogg,audio/aac'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:3600'],
            'position' => ['required', 'integer', 'min:1'],
            'remove_audio' => ['nullable', 'boolean'],
        ]);

        $content = $scene->content;
        $oldPosition = $scene->position;
        $newPosition = min($validated['position'], max($content->scenes()->count(), 1));

        $scene->fill([
            'name' => $validated['name'],
            'duration_seconds' => $validated['duration_seconds'],
            'position' => $newPosition,
        ]);

        $this->persistMedia($request, $scene, true);

        if ($request->boolean('remove_audio') && ! $request->hasFile('audio') && $scene->audio_path) {
            Storage::disk('public')->delete($scene->audio_path);
            $scene->audio_path = null;
            $scene->audio_original_name = null;
        }

        $scene->save();

        if ($oldPosition !== $newPosition) {
            $this->normalizePositions($content, $scene->id, $newPosition);
        }

        return redirect()->route('scenes.show', $scene)->with('status', 'Cập nhật phân cảnh thành công.');
    }

    public function destroy(Scene $scene): RedirectResponse
    {
        $content = $scene->content;

        $this->deleteMedia($scene);
        $scene->delete();
        $this->normalizeAfterDelete($content);

        return redirect()->route('contents.show', $content)->with('status', 'Đã xóa phân cảnh.');
    }

    public function duplicate(Scene $scene): RedirectResponse
    {
        $copy = $scene->replicate();
        $copy->name = $scene->name.' (Copy)';
        $copy->position = ((int) $scene->content->scenes()->max('position')) + 1;

        if ($scene->gif_path) {
            $copy->gif_path = $this->duplicateFile($scene->gif_path, 'scenes/gifs');
        }

        if ($scene->audio_path) {
            $copy->audio_path = $this->duplicateFile($scene->audio_path, 'scenes/audios');
        }

        $copy->save();

        return redirect()->route('contents.show', $scene->content)->with('status', 'Đã nhân bản phân cảnh.');
    }

    private function persistMedia(Request $request, Scene $scene, bool $replace = false): void
    {
        if ($request->hasFile('gif')) {
            if ($replace && $scene->gif_path) {
                Storage::disk('public')->delete($scene->gif_path);
            }

            $scene->gif_path = $request->file('gif')->storeAs(
                'scenes/gifs',
                Str::uuid().'.'.$request->file('gif')->getClientOriginalExtension(),
                'public'
            );
            $scene->gif_original_name = $request->file('gif')->getClientOriginalName();
        }

        if ($request->hasFile('audio')) {
            if ($replace && $scene->audio_path) {
                Storage::disk('public')->delete($scene->audio_path);
            }

            $scene->audio_path = $request->file('audio')->storeAs(
                'scenes/audios',
                Str::uuid().'.'.$request->file('audio')->getClientOriginalExtension(),
                'public'
            );
            $scene->audio_original_name = $request->file('audio')->getClientOriginalName();
        }
    }

    private function deleteMedia(Scene $scene): void
    {
        if ($scene->gif_path) {
            Storage::disk('public')->delete($scene->gif_path);
        }

        if ($scene->audio_path) {
            Storage::disk('public')->delete($scene->audio_path);
        }
    }

    private function duplicateFile(string $path, string $directory): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $copyPath = $directory.'/'.Str::uuid().($extension ? '.'.$extension : '');
        Storage::disk('public')->copy($path, $copyPath);

        return $copyPath;
    }

    private function normalizePositions(ContentItem $content, int $sceneId, int $newPosition): void
    {
        $orderedScenes = $content->scenes()->whereKeyNot($sceneId)->orderBy('position')->get();
        $position = 1;
        $inserted = false;

        foreach ($orderedScenes as $item) {
            if (! $inserted && $position === $newPosition) {
                $position++;
                $inserted = true;
            }

            $item->update(['position' => $position]);
            $position++;
        }
    }

    private function normalizeAfterDelete(ContentItem $content): void
    {
        foreach ($content->scenes()->orderBy('position')->get() as $index => $item) {
            $item->update(['position' => $index + 1]);
        }
    }

    private function safeFilename(string $filename): string
    {
        return str_replace(['"', "\r", "\n"], '', $filename);
    }
}
