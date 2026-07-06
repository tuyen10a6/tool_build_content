<?php

namespace App\Jobs;

use App\Models\Scene;
use App\Services\TextToAudioService;
use App\Services\VideoToGifService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessSceneMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $sceneId,
    ) {
    }

    public function handle(TextToAudioService $textToAudioService, VideoToGifService $videoToGifService): void
    {
        $scene = Scene::query()->find($this->sceneId);

        if (! $scene || ! $scene->isMain()) {
            return;
        }

        $scene->forceFill([
            'media_status' => 'processing',
            'media_error' => null,
            'media_started_at' => now(),
            'media_completed_at' => null,
            'media_attempts' => (int) $scene->media_attempts + 1,
        ])->save();

        $oldAudioPath = $scene->audio_path;
        $oldGifPath = $scene->gif_path;

        try {
            $generatedAudio = $textToAudioService->convertTextToStoredAudio((string) $scene->scene_text);
            $generatedGif = null;

            if ($scene->source_video_path && Storage::disk('local')->exists($scene->source_video_path)) {
                $generatedGif = $videoToGifService->convertStoredVideoToGif(
                    $scene->source_video_path,
                    $scene->source_video_original_name
                );
            }

            $scene->forceFill([
                'audio_path' => $generatedAudio['audio_path'],
                'audio_original_name' => $generatedAudio['audio_original_name'],
                'duration_seconds' => $generatedAudio['duration_seconds'] ?? $scene->duration_seconds ?? 3,
                'gif_path' => $generatedGif['gif_path'] ?? $scene->gif_path,
                'gif_original_name' => $generatedGif['gif_original_name'] ?? $scene->gif_original_name,
                'media_status' => 'completed',
                'media_error' => null,
                'media_completed_at' => now(),
            ])->save();

            if ($oldAudioPath && $oldAudioPath !== $scene->audio_path) {
                Storage::disk('public')->delete($oldAudioPath);
            }

            if ($generatedGif && $oldGifPath && $oldGifPath !== $scene->gif_path) {
                Storage::disk('public')->delete($oldGifPath);
            }
        } catch (Throwable $exception) {
            $scene->forceFill([
                'media_status' => 'failed',
                'media_error' => $exception->getMessage(),
                'media_completed_at' => now(),
            ])->save();

            throw $exception;
        } finally {
            $this->cleanupSourceVideo($scene);
        }
    }

    private function cleanupSourceVideo(Scene $scene): void
    {
        if ($scene->source_video_path) {
            Storage::disk('local')->delete($scene->source_video_path);
        }

        $scene->forceFill([
            'source_video_path' => null,
            'source_video_original_name' => null,
        ])->save();
    }
}
