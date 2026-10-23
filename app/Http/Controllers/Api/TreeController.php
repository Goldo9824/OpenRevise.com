<?php

namespace App\Http\Controllers\Api;

use App\Models\Content\Board;
use App\Models\Content\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TreeController extends ApiController
{
    /** GET /v1/tree: every subject, topic, chapter and lesson, drafts included. */
    public function tree(Request $request): JsonResponse
    {
        $subjects = Subject::query()
            ->ordered()
            ->when($request->query('subject'), fn ($q, $s) => $q->where('slug', $s)->orWhere('id', (int) $s))
            ->with([
                'boards',
                'topics.chapters.lessons' => fn ($q) => $q->with('boards')->withCount('questions'),
                'mockExams' => fn ($q) => $q->with(['board', 'paperTemplate', 'subject', 'questions']),
            ])
            ->get();

        $this->summary($request, 'Read the content tree');

        return $this->respond([
            'subjects' => $subjects->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'slug' => $subject->slug,
                'boards' => $subject->boards->pluck('slug')->all(),
                'status' => $subject->status->value,
                'url' => app_url($subject->path()),
                'topics' => $subject->topics->map(fn ($topic) => Presenter::topic($topic) + [
                    'chapters' => $topic->chapters->map(fn ($chapter) => Presenter::chapter($chapter) + [
                        'lessons' => $chapter->lessons->map(fn ($lesson) => Presenter::lesson($lesson))->all(),
                    ])->all(),
                ])->all(),
                'mock_exams' => $subject->mockExams->map(fn ($exam) => Presenter::mockExam($exam))->all(),
            ])->all(),
        ]);
    }

    /** GET /v1/boards: boards, the subjects they examine and their paper templates. */
    public function boards(Request $request): JsonResponse
    {
        $boards = Board::query()->ordered()->with(['subjects', 'paperTemplates.subject'])->get();

        $this->summary($request, 'Read the boards');

        return $this->respond([
            'boards' => $boards->map(fn (Board $board) => [
                'id' => $board->id,
                'name' => $board->name,
                'slug' => $board->slug,
                'subjects' => $board->subjects->map(fn ($s) => [
                    'slug' => $s->slug,
                    'name' => $s->name,
                    'spec_code' => $s->pivot->spec_code,
                ])->all(),
                'paper_templates' => $board->paperTemplates->map(fn ($t) => Presenter::template($t))->all(),
            ])->all(),
        ]);
    }
}
