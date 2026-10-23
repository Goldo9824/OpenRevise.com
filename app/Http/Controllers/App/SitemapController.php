<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Content\MockExam;
use App\Models\Content\Subject;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * sitemap.xml for openrevise.com: the presentation pages, then the app's
 * pages built from the content. Cached for an hour.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap', 3600, function () {
            $urls = collect(['/', '/about/', '/contact/', '/privacy/', '/terms/', '/revise/'])
                ->map(fn ($path) => [main_url($path), null])
                ->all();

            $subjects = Subject::visible()->ordered()->with([
                'boards',
                'topics' => fn ($q) => $q->visible()->with([
                    'chapters' => fn ($q) => $q->visible()->with([
                        'lessons' => fn ($q) => $q->visible()->withCount('questions'),
                    ]),
                ]),
            ])->get();

            foreach ($subjects as $subject) {
                $lessons = $subject->topics->flatMap->chapters->flatMap->lessons;
                if ($lessons->isEmpty()) {
                    continue;
                }

                $urls[] = [main_url($subject->path()), $lessons->max('updated_at')];

                foreach ($subject->boards as $board) {
                    $urls[] = [main_url($subject->path().$board->slug.'/'), null];
                }

                foreach ($subject->topics as $topic) {
                    if ($topic->chapters->flatMap->lessons->isEmpty()) {
                        continue;
                    }
                    $urls[] = [main_url($topic->path()), null];

                    foreach ($topic->chapters as $chapter) {
                        if ($chapter->lessons->isEmpty()) {
                            continue;
                        }
                        $urls[] = [main_url($chapter->path()), null];

                        foreach ($chapter->lessons as $lesson) {
                            $lesson->setRelation('chapter', $chapter->setRelation('topic', $topic->setRelation('subject', $subject)));
                            $urls[] = [main_url($lesson->path()), $lesson->updated_at];
                            if ($lesson->questions_count) {
                                $urls[] = [main_url($lesson->questionsPath()), $lesson->updated_at];
                            }
                        }
                    }
                }

                $mocks = MockExam::visible()->where('subject_id', $subject->id)->get();
                if ($mocks->isNotEmpty()) {
                    $urls[] = [main_url($subject->path().'mock-exams/'), null];
                    foreach ($mocks as $mock) {
                        $mock->setRelation('subject', $subject);
                        $urls[] = [main_url($mock->path()), $mock->updated_at];
                    }
                }
            }

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
