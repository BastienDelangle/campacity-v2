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
}
