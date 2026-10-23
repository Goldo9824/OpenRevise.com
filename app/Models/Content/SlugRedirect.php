<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;

/**
 * An address a page used to have. Changing a slug, or moving a lesson, keeps
 * the old address working as a permanent redirect.
 */
class SlugRedirect extends Model
{
    protected $connection = 'content';

    protected $guarded = ['id'];

    /** @var array<string, class-string<Model>> */
    public const TARGETS = [
        'subject' => Subject::class,
        'topic' => Topic::class,
        'chapter' => Chapter::class,
        'lesson' => Lesson::class,
        'mock-exam' => MockExam::class,
    ];

    /**
     * The current address for an old one, or null. A redirect recorded for a
     * topic also covers every chapter and lesson that used to sit under it.
     */
    public static function resolve(string $path): ?string
    {
        $path = '/'.trim($path, '/').'/';
        $segments = array_values(array_filter(explode('/', $path)));

        $candidates = [];
        for ($i = count($segments); $i >= 2; $i--) {
            $candidates[] = '/'.implode('/', array_slice($segments, 0, $i)).'/';
        }

        if (! $candidates) {
            return null;
        }

        $redirects = static::query()->whereIn('old_path', $candidates)->latest('id')->get();

        foreach ($candidates as $candidate) {
            foreach ($redirects->where('old_path', $candidate) as $redirect) {
                $class = self::TARGETS[$redirect->target_type] ?? null;
                $target = $class ? $class::find($redirect->target_id) : null;

                if (! $target) {
                    continue;
                }

                $new = $target->path().substr($path, strlen($candidate));

                if ($new !== $path) {
                    return $new;
                }
            }
        }

        return null;
    }
}
