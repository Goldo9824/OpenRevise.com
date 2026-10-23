<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Content\Chapter;
use App\Models\Content\Lesson;
use App\Models\Content\Status;
use App\Models\Content\Subject;
use App\Models\Content\Topic;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Search across subjects, topics, chapters and lessons. */
class SearchController extends Controller
{
    /** The suggestions under the search box. */
    public function json(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->search((string) $request->query('q'), 8)->values()])
            ->header('Cache-Control', 'public, max-age=60');
    }

    public function page(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        return view('app.search', [
            'q' => $q,
            'results' => $q !== '' ? $this->search($q, 40) : collect(),
            'seo' => [
                'title' => Seo::title($q !== '' ? 'Search: '.$q : 'Search'),
                'description' => 'Search OpenRevise GCSE revision notes and exam questions.',
                'canonical' => main_url('/search/'),
                'robots' => 'noindex, follow',
            ],
        ]);
    }

    /** @return Collection<int, array{title: string, url: string, type: string, context: string}> */
    protected function search(string $q, int $limit): Collection
    {
        $q = trim(Str::limit($q, 80, ''));

        if (mb_strlen($q) < 2) {
            return collect();
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
        $published = fn (Builder $b) => $b->where('status', Status::Published);

        $subjects = Subject::query()->tap($published)->where('name', 'like', $like)->limit(5)->get()
            ->map(fn (Subject $s) => ['title' => $s->fullName(), 'url' => main_url($s->path()), 'type' => 'Subject', 'context' => $s->boards()->pluck('name')->join(', ')]);

        $topics = Topic::query()->tap($published)->where('title', 'like', $like)
            ->whereHas('subject', $published)->with('subject')->limit(8)->get()
            ->map(fn (Topic $t) => ['title' => $t->title, 'url' => main_url($t->path()), 'type' => 'Topic', 'context' => $t->subject->fullName()]);

        $chapters = Chapter::query()->tap($published)->where('title', 'like', $like)
            ->whereHas('topic', fn ($q) => $published($q)->whereHas('subject', $published))
            ->whereHas('lessons', $published)
            ->with('topic.subject')->limit(8)->get()
            ->map(fn (Chapter $c) => ['title' => $c->title, 'url' => main_url($c->path()), 'type' => 'Chapter', 'context' => $c->topic->subject->fullName().' › '.$c->topic->title]);

        $lessons = Lesson::query()->tap($published)
            ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('summary', 'like', $like))
            ->whereHas('chapter', fn ($c) => $published($c)->whereHas('topic', fn ($t) => $published($t)->whereHas('subject', $published)))
            ->with('chapter.topic.subject')->limit($limit)->get()
            ->map(fn (Lesson $l) => ['title' => $l->title, 'url' => main_url($l->path()), 'type' => 'Lesson', 'context' => $l->chapter->topic->subject->fullName().' › '.$l->chapter->topic->title, 'summary' => $l->summary]);

        $needle = Str::lower($q);

        // Titles that start with the words typed come first.
        return $subjects->concat($topics)->concat($chapters)->concat($lessons)
            ->sortBy(fn ($r) => [Str::startsWith(Str::lower($r['title']), $needle) ? 0 : 1, ['Subject' => 0, 'Topic' => 1, 'Chapter' => 2, 'Lesson' => 3][$r['type']]])
            ->take($limit)
            ->values();
    }
}
