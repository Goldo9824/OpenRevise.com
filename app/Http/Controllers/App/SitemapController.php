<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Content\MockExam;
use App\Models\Content\Subject;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/** sitemap.xml, built from the content and cached for an hour. */
class SitemapController extends Controller
{
    public function app(): Response
    {
        $xml = Cache::remember('sitemap.app', 3600, function () {
            $urls = [[app_url('/'), null]];

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

                $urls[] = [app_url($subject->path()), $lessons->max('updated_at')];

                foreach ($subject->boards as $board) {
                    $urls[] = [app_url($subject->path().$board->slug.'/'), null];
                }

                foreach ($subject->topics as $topic) {
                    if ($topic->chapters->flatMap->lessons->isEmpty()) {
                        continue;
                    }
                    $urls[] = [app_url($topic->path()), null];

                    foreach ($topic->chapters as $chapter) {
                        if ($chapter->lessons->isEmpty()) {
                            continue;
                        }
                        $urls[] = [app_url($chapter->path()), null];

                        foreach ($chapter->lessons as $lesson) {
                            $lesson->setRelation('chapter', $chapter->setRelation('topic', $topic->setRelation('subject', $subject)));
                            $urls[] = [app_url($lesson->path()), $lesson->updated_at];
                            if ($lesson->questions_count) {
                                $urls[] = [app_url($lesson->questionsPath()), $lesson->updated_at];
                            }
                        }
                    }
                }

                $mocks = MockExam::visible()->where('subject_id', $subject->id)->get();
                if ($mocks->isNotEmpty()) {
                    $urls[] = [app_url($subject->path().'mock-exams/'), null];
                    foreach ($mocks as $mock) {
                        $mock->setRelation('subject', $subject);
                        $urls[] = [app_url($mock->path()), $mock->updated_at];
                    }
                }
            }

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        return response("User-agent: *\nAllow: /\n\nSitemap: ".app_url('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
