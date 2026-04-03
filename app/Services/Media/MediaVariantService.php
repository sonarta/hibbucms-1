<?php

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class MediaVariantService
{
    /**
     * Generate scaled JPEG/PNG/WebP variants and persist paths on the media row.
     */
    public function generate(Media $media): void
    {
        if (! config('media.variants.enabled', true)) {
            return;
        }

        if (! $this->shouldGenerateVariants($media)) {
            return;
        }

        $disk = $media->disk;
        $path = $media->path;

        if (! Storage::disk($disk)->exists($path)) {
            return;
        }

        try {
            $binary = Storage::disk($disk)->get($path);
        } catch (\Throwable $e) {
            Log::warning('Media variants: could not read original file', [
                'media_id' => $media->id,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $manager = new ImageManager(new Driver);
        $presets = config('media.variants.presets', []);
        $quality = max(1, min(100, (int) config('media.variants.jpeg_quality', 82)));

        $directory = dirname($path);
        $basename = pathinfo($path, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');
        $prefix = ($directory === '.' || $directory === '') ? '' : $directory.'/';

        $variants = [];

        foreach ($presets as $name => $opts) {
            $maxW = (int) ($opts['width'] ?? 0);
            if ($maxW < 1) {
                continue;
            }

            $image = $manager->read($binary);

            if ($image->width() > $maxW) {
                $image->scale(width: $maxW);
            }

            $variantPath = $prefix.$basename.'_'.$name.'.'.$extension;

            try {
                $encoded = $this->encodeRaster($image, $extension, $quality);
                Storage::disk($disk)->put($variantPath, $encoded);
            } catch (\Throwable $e) {
                Log::warning('Media variants: failed to write variant', [
                    'media_id' => $media->id,
                    'variant' => $name,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }

            $variants[$name] = [
                'path' => $variantPath,
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        }

        if ($variants !== []) {
            $media->forceFill(['variants' => $variants])->save();
        }
    }

    /**
     * Remove variant files from storage (original path is handled by caller).
     *
     * @param  array<string, array{path?: string}>|null  $variants
     */
    public function deleteVariantFiles(?array $variants, string $disk): void
    {
        if (! is_array($variants)) {
            return;
        }

        foreach ($variants as $meta) {
            if (! empty($meta['path'])) {
                Storage::disk($disk)->delete($meta['path']);
            }
        }
    }

    private function shouldGenerateVariants(Media $media): bool
    {
        if (! Str::startsWith($media->mime_type ?? '', 'image/')) {
            return false;
        }

        // Skip SVG (vector) and GIF (animation) for predictable output.
        return ! in_array($media->mime_type, ['image/svg+xml', 'image/gif'], true);
    }

    private function encodeRaster(ImageInterface $image, string $extension, int $quality): string
    {
        return match ($extension) {
            'png' => $image->toPng()->toString(),
            'webp' => $image->toWebp($quality)->toString(),
            'gif' => $image->toGif()->toString(),
            default => $image->toJpeg($quality)->toString(),
        };
    }
}
