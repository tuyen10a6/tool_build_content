<?php

namespace App\Http\Controllers;

use App\Models\TransitionTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransitionTemplateController extends Controller
{
    public function index(): View
    {
        return view('transition-templates.index', [
            'transitionTemplates' => TransitionTemplate::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'gif' => ['nullable', 'file', 'mimes:gif,jpg,jpeg,png,webp'],
            'audio' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/ogg,audio/aac'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:3600'],
        ]);

        $template = new TransitionTemplate([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'duration_seconds' => $validated['duration_seconds'] ?? 3,
        ]);

        if ($request->hasFile('gif')) {
            $template->gif_path = $request->file('gif')->storeAs(
                'transition-templates/gifs',
                Str::uuid().'.'.$request->file('gif')->getClientOriginalExtension(),
                'public'
            );
            $template->gif_original_name = $request->file('gif')->getClientOriginalName();
        }

        if ($request->hasFile('audio')) {
            $template->audio_path = $request->file('audio')->storeAs(
                'transition-templates/audios',
                Str::uuid().'.'.$request->file('audio')->getClientOriginalExtension(),
                'public'
            );
            $template->audio_original_name = $request->file('audio')->getClientOriginalName();
            $template->duration_seconds = $this->extractAudioDuration(
                $request->file('audio')->getRealPath()
            ) ?? $template->duration_seconds;
        }

        $template->save();

        return redirect()->route('transition-templates.index')->with('status', 'Đã tạo mẫu phân cảnh chuyển tiếp.');
    }

    public function destroy(TransitionTemplate $transitionTemplate): RedirectResponse
    {
        if ($transitionTemplate->gif_path) {
            Storage::disk('public')->delete($transitionTemplate->gif_path);
        }

        if ($transitionTemplate->audio_path) {
            Storage::disk('public')->delete($transitionTemplate->audio_path);
        }

        $transitionTemplate->delete();

        return redirect()->route('transition-templates.index')->with('status', 'Đã xóa mẫu phân cảnh chuyển tiếp.');
    }

    public function gif(TransitionTemplate $transitionTemplate): StreamedResponse
    {
        abort_unless(
            $transitionTemplate->gif_path && Storage::disk('public')->exists($transitionTemplate->gif_path),
            Response::HTTP_NOT_FOUND
        );

        return Storage::disk('public')->response($transitionTemplate->gif_path);
    }

    public function audio(TransitionTemplate $transitionTemplate): StreamedResponse
    {
        abort_unless(
            $transitionTemplate->audio_path && Storage::disk('public')->exists($transitionTemplate->audio_path),
            Response::HTTP_NOT_FOUND
        );

        return Storage::disk('public')->response($transitionTemplate->audio_path);
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
}
