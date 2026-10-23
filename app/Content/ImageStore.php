<?php

namespace App\Content;

use App\Models\Content\Image;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Saves diagrams straight into openrevise.com's media folder (no storage
 * link needed on LWS). Large photos are resized and turned into WebP.
 */
class ImageStore
{
    public const MAX_WIDTH = 1600;

    public const TYPES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];

    public function __construct(protected SvgSanitizer $svg) {}

    /**
     * @param  string  $bytes  The raw file.
     * @param  string  $name  The name used in [IMAGE] tags; made into a slug.
     */
    public function store(string $bytes, string $name, ?string $alt = null, bool $replace = false): Image
    {
        $name = Str::slug(pathinfo($name, PATHINFO_FILENAME) ?: $name);

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'The image needs a name made of letters, numbers or hyphens.']);
        }

        $existing = Image::where('name', $name)->first();

        if ($existing && ! $replace) {
            throw ValidationException::withMessages([
                'name' => "An image called \"$name\" already exists. Pick another name, or send replace=true to overwrite it.",
            ]);
        }

        $mime = $this->detectMime($bytes);
        $width = $height = null;

        if ($mime === 'image/svg+xml') {
            $bytes = $this->svg->sanitize($bytes) ?? throw ValidationException::withMessages(['file' => 'This SVG could not be read.']);
            [$width, $height] = $this->svgSize($bytes);
        } else {
            [$bytes, $mime, $width, $height] = $this->optimise($bytes, $mime);
        }

        $dir = rtrim(config('openrevise.paths.media'), '/');
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException("Cannot create the media folder at $dir.");
        }

        // A new file name on every upload, so browsers never show a stale copy.
        $filename = $name.'-'.Str::lower(Str::random(6)).'.'.self::TYPES[$mime];

        if (file_put_contents($dir.'/'.$filename, $bytes) === false) {
            throw new RuntimeException("Cannot write $filename to the media folder.");
        }

        $oldFile = $existing?->filePath();

        $image = $existing ?? new Image(['name' => $name]);
        $image->fill([
            'filename' => $filename,
            'alt' => $alt ?? $image->alt,
            'mime' => $mime,
            'width' => $width,
            'height' => $height,
            'size_bytes' => strlen($bytes),
        ])->save();

        if ($oldFile && is_file($oldFile) && $oldFile !== $image->filePath()) {
            @unlink($oldFile);
        }

        return $image;
    }

    protected function detectMime(string $bytes): string
    {
        $head = ltrim(substr($bytes, 0, 1024));

        if (str_starts_with($head, '<svg') || (str_starts_with($head, '<?xml') && str_contains($head, '<svg'))) {
            return 'image/svg+xml';
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: '';

        if (! isset(self::TYPES[$mime]) || $mime === 'image/svg+xml') {
            throw ValidationException::withMessages(['file' => 'Images must be PNG, JPEG, WebP, GIF or SVG.']);
        }

        return $mime;
    }

    /** @return array{0: string, 1: string, 2: ?int, 3: ?int} */
    protected function optimise(string $bytes, string $mime): array
    {
        $size = @getimagesizefromstring($bytes);
        if (! $size) {
            throw ValidationException::withMessages(['file' => 'This image could not be read.']);
        }

        [$width, $height] = $size;

        if (! function_exists('imagecreatefromstring') || $mime === 'image/gif') {
            return [$bytes, $mime, $width, $height];
        }

        $needsResize = $width > self::MAX_WIDTH;
        $isPhoto = $mime === 'image/jpeg';

        if (! $needsResize && ! $isPhoto) {
            return [$bytes, $mime, $width, $height];
        }

        $source = @imagecreatefromstring($bytes);
        if (! $source) {
            return [$bytes, $mime, $width, $height];
        }

        if ($needsResize) {
            $newHeight = (int) round($height * self::MAX_WIDTH / $width);
            $resized = imagecreatetruecolor(self::MAX_WIDTH, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, self::MAX_WIDTH, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
            [$width, $height] = [self::MAX_WIDTH, $newHeight];
        }

        if (function_exists('imagewebp')) {
            ob_start();
            imagewebp($source, null, 82);
            $out = (string) ob_get_clean();
            $outMime = 'image/webp';
        } else {
            ob_start();
            $mime === 'image/png' ? imagepng($source, null, 9) : imagejpeg($source, null, 82);
            $out = (string) ob_get_clean();
            $outMime = $mime;
        }

        imagedestroy($source);

        // Keep whichever is smaller.
        return strlen($out) > 0 && (strlen($out) < strlen($bytes) || $needsResize)
            ? [$out, $outMime, $width, $height]
            : [$bytes, $mime, $width, $height];
    }

    /** @return array{0: ?int, 1: ?int} */
    protected function svgSize(string $svg): array
    {
        $xml = @simplexml_load_string($svg);
        if (! $xml) {
            return [null, null];
        }

        $w = (float) ($xml['width'] ?? 0);
        $h = (float) ($xml['height'] ?? 0);

        if ((! $w || ! $h) && isset($xml['viewBox'])) {
            $box = preg_split('/[\s,]+/', trim((string) $xml['viewBox']));
            if (count($box) === 4) {
                [$w, $h] = [(float) $box[2], (float) $box[3]];
            }
        }

        return $w && $h ? [(int) round($w), (int) round($h)] : [null, null];
    }
}
