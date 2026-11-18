<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stocke une image envoyée par un utilisateur en la ré-encodant :
 * redimensionnement à une largeur maximale, suppression des métadonnées EXIF
 * (localisation GPS du téléphone, etc.) et normalisation du format.
 */
class ImageStorage
{
    /**
     * @return array{path: string, width: int, height: int}
     */
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): array
    {
        $image = $this->load($file);

        if ($image === null) {
            // Format non géré par GD : stockage tel quel (le fichier a déjà été validé comme image).
            $size = getimagesize($file->getRealPath()) ?: [0, 0];

            return ['path' => $file->store($directory, $disk), 'width' => (int) $size[0], 'height' => (int) $size[1]];
        }

        $image = $this->applyExifOrientation($image, $file);
        $image = $this->resize($image, (int) config('campacity.image_max_width', 1600));

        ob_start();
        imagewebp($image, null, 82);
        $contents = (string) ob_get_clean();

        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        Storage::disk($disk)->put($path, $contents);

        return ['path' => $path, 'width' => $width, 'height' => $height];
    }

    private function load(UploadedFile $file): ?\GdImage
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $this->ensureEnoughMemory($file);

        $image = match ($file->getMimeType()) {
            'image/jpeg' => @imagecreatefromjpeg($file->getRealPath()),
            'image/png' => @imagecreatefrompng($file->getRealPath()),
            'image/webp' => @imagecreatefromwebp($file->getRealPath()),
            default => false,
        };

        if ($image === false) {
            return null;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    private function applyExifOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated ?: $image;
    }

    private function resize(\GdImage $image, int $maxWidth): \GdImage
    {
        $width = imagesx($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        $resized = imagescale($image, $maxWidth);

        if ($resized === false) {
            return $image;
        }

        imagedestroy($image);

        return $resized;
    }

    /**
     * GD décompresse l'image en mémoire (~4 octets par pixel, plus le
     * redimensionnement) : on relève la limite pour ce traitement si besoin.
     */
    private function ensureEnoughMemory(UploadedFile $file): void
    {
        [$width, $height] = getimagesize($file->getRealPath()) ?: [0, 0];
        $needed = (int) ($width * $height * 4 * 1.8) + memory_get_usage() + 16 * 1024 * 1024;
        $limit = $this->bytes((string) ini_get('memory_limit'));

        if ($limit !== -1 && $needed > $limit) {
            ini_set('memory_limit', (string) min($needed, 768 * 1024 * 1024));
        }
    }

    private function bytes(string $value): int
    {
        $value = trim($value);

        if ($value === '-1') {
            return -1;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
