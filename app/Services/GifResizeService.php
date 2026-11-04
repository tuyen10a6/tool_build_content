<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class GifResizeService
{
    /**
     * Đổi GIF về đúng kích thước $width x $height px.
     * Giữ tỉ lệ gốc, phần thừa được thêm viền đen (letterbox), giống cách khung xem trước hiển thị.
     *
     * @return string Nội dung binary của GIF sau khi đổi kích thước.
     */
    public function resizeToFit(string $absolutePath, int $width = 320, int $height = 240): string
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException('Không tìm thấy file GIF để đổi kích thước.');
        }

        $directory = storage_path('app/tmp');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $outputPath = $directory.'/gif-resize-'.Str::uuid().'.gif';

        try {
            $result = Process::timeout(120)->run([
                config('services.ffmpeg.binary', 'ffmpeg'),
                '-y',
                '-v', 'error',
                '-i', $absolutePath,
                '-vf', sprintf(
                    'scale=%1$d:%2$d:force_original_aspect_ratio=decrease,pad=%1$d:%2$d:(ow-iw)/2:(oh-ih)/2:color=black',
                    $width,
                    $height,
                ),
                '-loop', '0',
                $outputPath,
            ]);

            if (! $result->successful() || ! is_file($outputPath)) {
                throw new RuntimeException('Không thể đổi kích thước GIF: '.trim($result->errorOutput()));
            }

            return (string) file_get_contents($outputPath);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
        }
    }
}
