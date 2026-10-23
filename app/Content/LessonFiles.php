<?php

namespace App\Content;

use App\Models\Content\Lesson;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Lesson bodies are plain text files in storage/app/lessons, named by lesson
 * ID so they survive slug changes. They do not count towards the database
 * size limit, and deploys never touch them.
 */
class LessonFiles
{
    public function path(Lesson|int $lesson): string
    {
        $id = $lesson instanceof Lesson ? $lesson->getKey() : $lesson;

        return rtrim(config('openrevise.paths.lessons'), '/').'/'.$id.'.txt';
    }

    public function read(Lesson|int $lesson): string
    {
        $path = $this->path($lesson);

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    public function write(Lesson|int $lesson, string $text): void
    {
        $path = $this->path($lesson);
        $dir = dirname($path);

        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException("Cannot create the lessons folder at $dir.");
        }

        // Write to a temporary file first so a reader never sees half a lesson.
        $tmp = $path.'.'.bin2hex(random_bytes(4)).'.tmp';
        if (file_put_contents($tmp, $text) === false || ! rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException("Cannot write the lesson file $path.");
        }

        Cache::forget($this->cacheKey($lesson));
    }

    public function delete(Lesson|int $lesson): void
    {
        $path = $this->path($lesson);

        if (is_file($path)) {
            @unlink($path);
        }

        Cache::forget($this->cacheKey($lesson));
    }

    /** The lesson as a page, cached until the file or the images change. */
    public function render(Lesson $lesson): ParsedDocument
    {
        $path = $this->path($lesson);
        $version = is_file($path) ? filemtime($path) : 0;
        $key = $this->cacheKey($lesson);
        $cached = Cache::get($key);

        if (is_array($cached) && $cached['version'] === $version.'-'.ImageVersion::get().'-'.LessonParser::VERSION && is_array($cached['doc'])) {
            return ParsedDocument::fromArray($cached['doc']);
        }

        $doc = app(LessonParser::class)->parse($this->read($lesson));
        Cache::put($key, ['version' => $version.'-'.ImageVersion::get().'-'.LessonParser::VERSION, 'doc' => $doc->toArray()], now()->addDay());

        return $doc;
    }

    protected function cacheKey(Lesson|int $lesson): string
    {
        return 'lesson-html:'.($lesson instanceof Lesson ? $lesson->getKey() : $lesson);
    }
}
