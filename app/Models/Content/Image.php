<?php

namespace App\Models\Content;

use App\Content\ImageVersion;

/**
 * A diagram. The file sits in the app subdomain's media folder; this row
 * holds the name used in [IMAGE] tags, its size and default alt text.
 */
class Image extends ContentModel
{
    protected static function booted(): void
    {
        static::saved(fn () => ImageVersion::bump());

        static::deleted(function (Image $image) {
            ImageVersion::bump();
            $file = $image->filePath();
            if (is_file($file)) {
                @unlink($file);
            }
        });
    }

    public function url(): string
    {
        return app_url('/media/'.$this->filename);
    }

    public function filePath(): string
    {
        return rtrim(config('openrevise.paths.media'), '/').'/'.$this->filename;
    }

    public function tag(): string
    {
        return '[IMAGE] '.$this->name.' | '.($this->alt ?: 'Caption');
    }

    public function changeLabel(): string
    {
        return 'Image '.$this->name;
    }
}
