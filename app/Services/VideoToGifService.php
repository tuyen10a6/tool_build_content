<?php

namespace App\Services;

use App\Exceptions\VideoToGifException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoToGifService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function convertToStoredGif(UploadedFile $video): array
    {
        $tempPath = $video->storeAs('tmp/videos', Str::uuid().'.'.$video->getClientOriginalExtension());

        try {
            return $this->convertFromStoredLocalPath($tempPath, $video->getClientOriginalName());
        } finally {
            Storage::disk('local')->delete($tempPath);
        }
    }

    public function convertStoredVideoToGif(string $storedPath, ?string $originalName = null): array
    {
        if (! Storage::disk('local')->exists($storedPath)) {
            throw new VideoToGifException('Không tìm thấy video nguồn để chuyển đổi sang GIF.');
        }

        return $this->convertFromStoredLocalPath(
            $storedPath,
            $originalName ?: basename($storedPath)
        );
    }

    private function convertFromStoredLocalPath(string $storedPath, string $originalName): array
    {
        $apiUrl = (string) config('services.video_to_gif.url');
        $apiKey = (string) config('services.video_to_gif.key');
        $timeout = (int) config('services.video_to_gif.timeout', 30);

        if ($apiUrl === '' || $apiKey === '') {
            throw new VideoToGifException('Chưa cấu hình API chuyển đổi Video sang GIF.');
        }

        $absoluteTempPath = Storage::disk('local')->path($storedPath);

        try {
            $response = $this->sendConvertRequest(
                $this->candidateApiUrls($apiUrl),
                $absoluteTempPath,
                $originalName,
                $apiKey,
                $timeout,
            );

            $payload = $response->json();
            $gifUrl = is_array($payload) ? ($payload['gif_url'] ?? null) : null;
            $status = is_array($payload) ? ($payload['status'] ?? null) : null;

            if ($status !== 'success' || ! is_string($gifUrl) || $gifUrl === '') {
                throw new VideoToGifException('Không thể chuyển đổi Video sang GIF. Vui lòng thử lại.');
            }

            $gifResponse = $this->http
                ->timeout($timeout)
                ->connectTimeout(min($timeout, 10))
                ->get($this->resolveGifUrl($gifUrl, $apiUrl));

            $gifResponse->throw();

            $extension = pathinfo(parse_url($gifUrl, PHP_URL_PATH) ?: 'converted.gif', PATHINFO_EXTENSION) ?: 'gif';
            $storedGifPath = 'scenes/gifs/'.Str::uuid().'.'.$extension;

            Storage::disk('public')->put($storedGifPath, $gifResponse->body());

            return [
                'gif_path' => $storedGifPath,
                'gif_original_name' => basename(parse_url($gifUrl, PHP_URL_PATH) ?: 'converted.gif'),
            ];
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            throw new VideoToGifException('Không thể chuyển đổi Video sang GIF. Kiểm tra lại VIDEO_TO_GIF_API_URL hoặc thử lại.', previous: $exception);
        }
    }

    private function sendConvertRequest(
        array $candidateUrls,
        string $absoluteTempPath,
        string $originalName,
        string $apiKey,
        int $timeout,
    ) {
        $lastException = null;

        foreach ($candidateUrls as $url) {
            try {
                $response = $this->http
                    ->timeout($timeout)
                    ->connectTimeout(min($timeout, 10))
                    ->withHeaders([
                        'X-API-Key' => $apiKey,
                    ])
                    ->attach(
                        'file',
                        fopen($absoluteTempPath, 'r'),
                        $originalName
                    )
                    ->post($url);

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

        throw new VideoToGifException('Không thể kết nối API chuyển đổi Video sang GIF.');
    }

    private function candidateApiUrls(string $apiUrl): array
    {
        $normalized = rtrim($apiUrl, '/');
        $candidates = [$normalized];
        $parts = parse_url($normalized);
        $path = $parts['path'] ?? '';

        if ($path === '' || $path === '/') {
            $candidates[] = $normalized.'/api/video-to-gif';
        }

        return array_values(array_unique($candidates));
    }

    private function resolveGifUrl(string $gifUrl, string $apiUrl): string
    {
        if (Str::startsWith($gifUrl, ['http://', 'https://'])) {
            return $gifUrl;
        }

        $parts = parse_url($apiUrl);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            throw new VideoToGifException('URL API chuyển đổi Video sang GIF không hợp lệ.');
        }

        $base = $parts['scheme'].'://'.$parts['host'];

        if (isset($parts['port'])) {
            $base .= ':'.$parts['port'];
        }

        return $base.'/'.ltrim($gifUrl, '/');
    }
}
