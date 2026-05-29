<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\Scene;
use App\Models\TransitionTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SceneController extends Controller
{
    public function gif(Scene $scene): StreamedResponse
    {
        $this->authorizeOwnership($scene);

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
        $this->authorizeOwnership($scene);

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
        $this->authorizeOwnership($scene);

        $scene->load(['content.category', 'nextTransitionTemplate', 'transitionTemplate', 'fromScene', 'toScene']);

        $transitionTemplates = TransitionTemplate::query()
            ->where(function ($query) use ($scene) {
                $query->where('is_active', true);

                if ($scene->next_transition_template_id) {
                    $query->orWhere('id', $scene->next_transition_template_id);
                }
            })
            ->orderBy('name')
            ->get();

        return view('scenes.show', [
            'scene' => $scene,
            'transitionTemplates' => $transitionTemplates,
        ]);
    }

    public function store(Request $request, ContentItem $content): RedirectResponse
    {
        $this->authorizeOwnership($content);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gif' => ['required', 'file', 'mimes:gif,jpg,jpeg,png,webp'],
            'audio' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/ogg,audio/aac'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'next_transition_template_id' => ['nullable', 'exists:transition_templates,id'],
            'transition_name' => ['nullable', 'string', 'max:255'],
            'transition_description' => ['nullable', 'string'],
            'transition_gif' => ['nullable', 'file', 'mimes:gif,jpg,jpeg,png,webp'],
            'transition_audio' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/ogg,audio/aac'],
            'transition_duration_seconds' => ['nullable', 'integer', 'min:1', 'max:3600'],
        ]);

        $transitionTemplateId = $validated['next_transition_template_id'] ?? null;

        if ($this->hasInlineTransitionTemplateData($request, $validated)) {
            $request->validate([
                'transition_name' => ['required', 'string', 'max:255'],
            ]);

            $transitionTemplateId = $this->createInlineTransitionTemplate($request, $validated)->id;
        }

        $scene = new Scene([
            'scene_type' => 'main',
            'name' => $validated['name'],
            'position' => ((int) $content->mainScenes()->max('position')) + 1,
            'sort_order' => ((int) $content->scenes()->max('sort_order')) + 1,
            'next_transition_template_id' => $transitionTemplateId,
            'created_by' => $this->user()->id,
            'created_by_name' => $this->user()->display_name,
        ]);

        $this->persistMainSceneMedia($request, $scene, $validated);
        $content->scenes()->save($scene);
        $this->rebuildTransitions($content->fresh());

        return redirect()->route('contents.show', $content)->with('status', 'Tạo phân cảnh thành công.');
    }

    public function update(Request $request, Scene $scene): RedirectResponse
    {
        $this->authorizeOwnership($scene);

        if ($scene->isTransition()) {
            return back()->with('status', 'Phân cảnh chuyển tiếp được quản lý từ mẫu chuyển tiếp và thứ tự phân cảnh chính.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gif' => ['nullable', 'file', 'mimes:gif,jpg,jpeg,png,webp'],
            'audio' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/ogg,audio/aac'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'position' => ['required', 'integer', 'min:1'],
            'remove_audio' => ['nullable', 'boolean'],
            'next_transition_template_id' => ['nullable', 'exists:transition_templates,id'],
        ]);

        $content = $scene->content;
        $oldPosition = $scene->position;
        $newPosition = min($validated['position'], max($content->mainScenes()->count(), 1));

        $scene->fill([
            'name' => $validated['name'],
            'position' => $newPosition,
            'next_transition_template_id' => $validated['next_transition_template_id'] ?? null,
        ]);

        $this->persistMainSceneMedia($request, $scene, $validated, true);

        if ($request->boolean('remove_audio') && ! $request->hasFile('audio') && $scene->audio_path) {
            Storage::disk('public')->delete($scene->audio_path);
            $scene->audio_path = null;
            $scene->audio_original_name = null;
            $scene->duration_seconds = $validated['duration_seconds'] ?? $scene->duration_seconds;
        }

        $scene->save();

        if ($oldPosition !== $newPosition) {
            $this->normalizeMainPositions($content, $scene->id, $newPosition);
        }

        $this->rebuildTransitions($content->fresh());

        return redirect()->route('scenes.show', $scene)->with('status', 'Cập nhật phân cảnh thành công.');
    }

    public function destroy(Scene $scene): RedirectResponse
    {
        $this->authorizeOwnership($scene);

        $content = $scene->content;

        if ($scene->isMain()) {
            $this->deleteMedia($scene);
            $scene->delete();
            $this->normalizeAfterDelete($content);
            $this->rebuildTransitions($content->fresh());

            return redirect()->route('contents.show', $content)->with('status', 'Đã xóa phân cảnh.');
        }

        if ($scene->fromScene) {
            $scene->fromScene->update(['next_transition_template_id' => null]);
        }

        $scene->delete();
        $this->rebuildTransitions($content->fresh());

        return redirect()->route('contents.show', $content)->with('status', 'Đã bỏ phân cảnh chuyển tiếp.');
    }

    public function duplicate(Scene $scene): RedirectResponse
    {
        $this->authorizeOwnership($scene);

        if ($scene->isTransition()) {
            return back()->with('status', 'Không nhân bản trực tiếp phân cảnh chuyển tiếp. Hãy nhân bản phân cảnh chính hoặc dùng mẫu chuyển tiếp.');
        }

        $copy = $scene->replicate([
            'sort_order',
            'position_label',
            'from_scene_id',
            'to_scene_id',
            'transition_template_id',
        ]);
        $copy->name = $scene->name.' (Copy)';
        $copy->position = ((int) $scene->content->mainScenes()->max('position')) + 1;
        $copy->sort_order = ((int) $scene->content->scenes()->max('sort_order')) + 1;
        $copy->created_by = $this->user()->id;
        $copy->created_by_name = $this->user()->display_name;

        if ($scene->gif_path) {
            $copy->gif_path = $this->duplicateFile($scene->gif_path, 'scenes/gifs');
        }

        if ($scene->audio_path) {
            $copy->audio_path = $this->duplicateFile($scene->audio_path, 'scenes/audios');
        }

        $copy->save();
        $this->rebuildTransitions($scene->content->fresh());

        return redirect()->route('contents.show', $scene->content)->with('status', 'Đã nhân bản phân cảnh.');
    }

    private function persistMainSceneMedia(Request $request, Scene $scene, array $validated, bool $replace = false): void
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
            $scene->duration_seconds = $this->extractAudioDuration(
                $request->file('audio')->getRealPath()
            ) ?? ($validated['duration_seconds'] ?? $scene->duration_seconds ?? 3);

            return;
        }

        $scene->duration_seconds = $validated['duration_seconds'] ?? $scene->duration_seconds ?? 3;
    }

    private function hasInlineTransitionTemplateData(Request $request, array $validated): bool
    {
        return $request->hasFile('transition_gif')
            || $request->hasFile('transition_audio')
            || filled($validated['transition_name'] ?? null)
            || filled($validated['transition_description'] ?? null);
    }

    private function createInlineTransitionTemplate(Request $request, array $validated): TransitionTemplate
    {
        $template = new TransitionTemplate([
            'name' => $validated['transition_name'],
            'description' => $validated['transition_description'] ?? null,
            'duration_seconds' => $validated['transition_duration_seconds'] ?? 3,
            'is_active' => false,
        ]);

        if ($request->hasFile('transition_gif')) {
            $template->gif_path = $request->file('transition_gif')->storeAs(
                'transition-templates/gifs',
                Str::uuid().'.'.$request->file('transition_gif')->getClientOriginalExtension(),
                'public'
            );
            $template->gif_original_name = $request->file('transition_gif')->getClientOriginalName();
        }

        if ($request->hasFile('transition_audio')) {
            $template->audio_path = $request->file('transition_audio')->storeAs(
                'transition-templates/audios',
                Str::uuid().'.'.$request->file('transition_audio')->getClientOriginalExtension(),
                'public'
            );
            $template->audio_original_name = $request->file('transition_audio')->getClientOriginalName();
            $template->duration_seconds = $this->extractAudioDuration(
                $request->file('transition_audio')->getRealPath()
            ) ?? $template->duration_seconds;
        }

        $template->save();

        return $template;
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

    private function normalizeMainPositions(ContentItem $content, int $sceneId, int $newPosition): void
    {
        $orderedScenes = $content->mainScenes()->whereKeyNot($sceneId)->orderBy('position')->get();
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
        foreach ($content->mainScenes()->orderBy('position')->get() as $index => $item) {
            $item->update(['position' => $index + 1]);
        }
    }

    private function rebuildTransitions(ContentItem $content): void
    {
        $mainScenes = $content->mainScenes()->with('nextTransitionTemplate')->get()->values();

        $content->scenes()->where('scene_type', 'transition')->delete();

        $sortOrder = 1;

        foreach ($mainScenes as $index => $scene) {
            $scene->update([
                'sort_order' => $sortOrder++,
                'position_label' => (string) $scene->position,
            ]);

            $nextScene = $mainScenes->get($index + 1);
            $template = $scene->nextTransitionTemplate;

            if (! $nextScene || ! $template) {
                continue;
            }

            $content->scenes()->create([
                'scene_type' => 'transition',
                'name' => $template->name,
                'gif_path' => $template->gif_path,
                'gif_original_name' => $template->gif_original_name,
                'audio_path' => $template->audio_path,
                'audio_original_name' => $template->audio_original_name,
                'duration_seconds' => $template->duration_seconds,
                'position' => $scene->position,
                'sort_order' => $sortOrder++,
                'position_label' => $scene->position.'-'.$nextScene->position,
                'from_scene_id' => $scene->id,
                'to_scene_id' => $nextScene->id,
                'transition_template_id' => $template->id,
                'created_by' => $scene->created_by,
                'created_by_name' => $scene->created_by_name,
            ]);
        }
    }

    private function extractAudioDuration(string $path): ?int
    {
        $command = sprintf(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
            escapeshellarg($path)
        );

        $output = shell_exec($command);
        $duration = is_string($output) ? (float) trim($output) : 0;

        return $duration > 0 ? (int) ceil($duration) : null;
    }

    private function safeFilename(string $filename): string
    {
        return str_replace(['"', "\r", "\n"], '', $filename);
    }
}
