<?php

namespace App\Services;

use App\Exceptions\TextToAudioException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TextToAudioService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function convertTextToStoredAudio(string $text): array
    {
        $text = trim($text);
        $apiUrl = $this->resolveConfiguredUrl();
        $apiKey = (string) config('services.text_to_audio.key');
        $timeout = (int) config('services.text_to_audio.timeout', 30);
        $voice = (string) config('services.text_to_audio.voice', 'vi-VN-HoaiMyNeural');

        if ($text === '') {
            throw new TextToAudioException('Vui lòng nhập nội dung phân cảnh để tạo audio.');
        }

        if ($apiUrl === '' || $apiKey === '') {
            throw new TextToAudioException('Chưa cấu hình API chuyển đổi Text sang Audio.');
        }

        try {
            $response = $this->sendConvertRequest(
                $this->candidateApiUrls($apiUrl),
                $text,
                $voice,
                $apiKey,
                $timeout,
            );

            $payload = $response->json();
            $audioUrl = is_array($payload) ? ($payload['audio_url'] ?? $payload['file_url'] ?? null) : null;
            $status = is_array($payload) ? ($payload['status'] ?? null) : null;
            $durationSeconds = is_array($payload) ? ($payload['duration_seconds'] ?? null) : null;

            if ($status !== 'success' || ! is_string($audioUrl) || $audioUrl === '') {
                throw new TextToAudioException('Không thể tạo audio từ nội dung phân cảnh. Vui lòng thử lại.');
            }

            $audioResponse = $this->http
                ->timeout($timeout)
                ->connectTimeout(min($timeout, 10))
                ->get($this->resolveAssetUrl($audioUrl, $apiUrl));

            $audioResponse->throw();

            $extension = pathinfo(parse_url($audioUrl, PHP_URL_PATH) ?: 'generated.mp3', PATHINFO_EXTENSION) ?: 'mp3';
            $storedPath = 'scenes/audios/'.Str::uuid().'.'.$extension;

            Storage::disk('public')->put($storedPath, $audioResponse->body());

            return [
                'audio_path' => $storedPath,
                'audio_original_name' => basename(parse_url($audioUrl, PHP_URL_PATH) ?: 'generated.mp3'),
                'duration_seconds' => is_numeric($durationSeconds) ? max(1, (int) ceil((float) $durationSeconds)) : null,
            ];
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            throw new TextToAudioException('Không thể tạo audio từ nội dung phân cảnh. Kiểm tra lại API text-to-audio hoặc thử lại.', previous: $exception);
        }
    }

    private function sendConvertRequest(array $candidateUrls, string $text, string $voice, string $apiKey, int $timeout)
    {
        $lastException = null;

        foreach ($candidateUrls as $url) {
            try {
                $response = $this->http
                    ->asForm()
                    ->timeout($timeout)
                    ->connectTimeout(min($timeout, 10))
                    ->withHeaders([
                        'X-API-Key' => $apiKey,
                    ])
                    ->post($url, [
                        'text' => $text,
                        'voice' => $voice,
                    ]);

                $response->throw();

                return $response;
            } catch (RequestException $exception) {
                $lastException = $exception;

                if ($exception->response?->status() !== 405) {
                    throw $exception;
                }
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        throw new TextToAudioException('Không thể kết nối API chuyển đổi Text sang Audio.');
    }

    private function candidateApiUrls(string $apiUrl): array
    {
        $normalized = rtrim($apiUrl, '/');
        $candidates = [$normalized];
        $parts = parse_url($normalized);
        $path = $parts['path'] ?? '';

        if ($path === '' || $path === '/') {
            $candidates[] = $normalized.'/api/text-to-audio';
        }

        return array_values(array_unique($candidates));
    }

    private function resolveConfiguredUrl(): string
    {
        $configured = (string) config('services.text_to_audio.url');

        if ($configured !== '') {
            return $configured;
        }

        $videoUrl = rtrim((string) config('services.video_to_gif.url'), '/');

        if ($videoUrl === '') {
            return '';
        }

        return Str::endsWith($videoUrl, '/api/video-to-gif')
            ? Str::replaceEnd('/api/video-to-gif', '/api/text-to-audio', $videoUrl)
            : $videoUrl.'/api/text-to-audio';
    }

    private function resolveAssetUrl(string $assetUrl, string $apiUrl): string
    {
        if (Str::startsWith($assetUrl, ['http://', 'https://'])) {
            return $assetUrl;
        }

        $parts = parse_url($apiUrl);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            throw new TextToAudioException('URL API chuyển đổi Text sang Audio không hợp lệ.');
        }

        $base = $parts['scheme'].'://'.$parts['host'];

        if (isset($parts['port'])) {
            $base .= ':'.$parts['port'];
        }

        return $base.'/'.ltrim($assetUrl, '/');
    }
}
