<?php

namespace App\Http\Controllers\Api;

use App\Models\Content\MockExam;
use App\Models\Content\PaperTemplate;
use App\Models\Content\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MockExamController extends ApiController
{
    /**
     * POST /v1/mock-exams: a full paper for one board and subject. The time
     * and total marks come from the paper template, never from the request.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required'],
            'board' => ['required'],
            'paper_template' => ['required'],
            'title' => ['nullable', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'topic_id' => ['nullable', 'integer', Rule::exists('content.topics', 'id')],
            'status' => ['nullable', Rule::enum(Status::class)],
            'questions' => ['required', 'array', 'min:1', 'max:80'],
        ]);

        $subject = $this->subject($data['subject']);
        $boardId = $this->resolveBoard($data['board'], 'board');

        $template = PaperTemplate::query()
            ->where('board_id', $boardId)
            ->where('subject_id', $subject->id)
            ->where(fn ($q) => $q->where('slug', $data['paper_template'])->orWhere('id', is_numeric($data['paper_template']) ? (int) $data['paper_template'] : 0))
            ->first();

        if (! $template) {
            $known = PaperTemplate::where('board_id', $boardId)->where('subject_id', $subject->id)->pluck('slug');
            throw ValidationException::withMessages([
                'paper_template' => 'No paper template "'.$data['paper_template'].'" for this board and subject. '
                    .($known->isEmpty() ? 'None exist yet: an admin adds them under Boards.' : 'Known templates: '.$known->implode(', ').'.'),
            ]);
        }

        $questions = QuestionController::validateQuestions($data['questions'], $this, mock: true);
        $sum = array_sum(array_column($questions, 'marks'));

        if ($sum !== $template->total_marks) {
            $this->warnings[] = "The questions add up to $sum marks; the {$template->name} template says {$template->total_marks}.";
        }

        $exam = DB::connection('content')->transaction(function () use ($request, $data, $subject, $boardId, $template, $questions) {
            $exam = MockExam::create([
                'subject_id' => $subject->id,
                'board_id' => $boardId,
                'paper_template_id' => $template->id,
                'topic_id' => $data['topic_id'] ?? null,
                'title' => $data['title'] ?? trim($template->board->name.' '.$subject->name.' '.$template->name),
                'slug' => $data['slug'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => $this->allowedStatus($request, $data['status'] ?? null, 'The mock exam'),
                'position' => (int) MockExam::where('subject_id', $subject->id)->max('position') + 1,
            ]);

            foreach ($questions as $i => $q) {
                $exam->questions()->create($q + ['position' => $i + 1]);
            }

            return $exam;
        });

        $exam->load(['subject', 'board', 'paperTemplate', 'questions']);

        $this->summary($request, "Created mock exam #{$exam->id} \"{$exam->title}\"");

        return $this->respond(Presenter::mockExam($exam), 201);
    }
}
