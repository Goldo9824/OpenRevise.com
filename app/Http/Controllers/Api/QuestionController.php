<?php

namespace App\Http\Controllers\Api;

use App\Models\Content\ExamQuestion;
use App\Models\Content\Lesson;
use App\Models\Content\QuestionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionController extends ApiController
{
    /**
     * POST /v1/questions: exam questions for one lesson. Send one question,
     * or several in "questions". "replace": true swaps out the whole batch.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'lesson_id' => ['required', 'integer', Rule::exists('content.lessons', 'id')],
            'questions' => ['sometimes', 'array', 'min:1', 'max:60'],
            'replace' => ['nullable', 'boolean'],
        ]);

        $lesson = Lesson::with('boards')->findOrFail($request->integer('lesson_id'));
        $items = $request->has('questions') ? $request->input('questions') : [$request->except(['lesson_id', 'replace'])];
        $questions = static::validateQuestions($items, $this, $lesson->boards->pluck('id')->all());

        $created = DB::connection('content')->transaction(function () use ($request, $lesson, $questions) {
            if ($request->boolean('replace')) {
                $lesson->questions()->get()->each->delete();
            }

            $position = (int) $lesson->questions()->max('position');

            return array_map(fn ($q) => $lesson->questions()->create($q + ['position' => ++$position]), $questions);
        });

        $this->summary($request, 'Added '.count($created)." exam questions to lesson #{$lesson->id}");

        return $this->respond([
            'lesson_id' => $lesson->id,
            'url' => app_url($lesson->load('chapter.topic.subject')->questionsPath()),
            'questions' => array_map(fn (ExamQuestion $q) => Presenter::question($q->load('board')), $created),
        ], 201);
    }

    /**
     * Shared with mock exams.
     *
     * @param  list<int>|null  $boardIds  Boards a question may be tagged with.
     * @return list<array<string, mixed>>
     */
    public static function validateQuestions(array $items, ApiController $controller, ?array $boardIds = null, bool $mock = false): array
    {
        $out = [];
        $errors = [];

        foreach (array_values($items) as $i => $item) {
            $prefix = "questions.$i.";
            $validator = Validator::make(is_array($item) ? $item : [], [
                'type' => ['nullable', 'string', 'max:30'],
                'question' => ['required', 'string', 'max:20000'],
                'marks' => ['required', 'integer', 'min:1', 'max:100'],
                'mark_scheme' => ['required', 'string', 'max:20000'],
                'model_answer' => ['required', 'string', 'max:20000'],
                'options' => ['nullable', 'array', 'min:2', 'max:8'],
                'options.*' => ['string', 'max:500'],
                'answer' => ['nullable', 'string', 'max:500'],
                'board' => ['nullable'],
                'section' => ['nullable', 'string', 'max:120'],
                'number' => ['nullable', 'string', 'max:12'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->toArray() as $k => $m) {
                    $errors[$prefix.$k] = $m;
                }

                continue;
            }

            $q = $validator->validated();
            $q['type'] = $q['type'] ?? (isset($q['options']) ? QuestionType::MultipleChoice->value : QuestionType::ShortAnswer->value);

            if (QuestionType::hasChoices($q['type'])) {
                if ($q['type'] === QuestionType::TrueFalse->value && empty($q['options'])) {
                    $q['options'] = ['True', 'False'];
                }
                if (empty($q['options'])) {
                    $errors[$prefix.'options'] = ['A multiple-choice question needs at least two options.'];
                } elseif (empty($q['answer']) || ! static::answerMatches($q['answer'], $q['options'])) {
                    $errors[$prefix.'answer'] = ['Give the correct option in "answer": its text, or its letter (A, B, C...).'];
                } elseif (! in_array($q['answer'], $q['options'], true)) {
                    // Store the option itself, not its letter.
                    $q['answer'] = $q['options'][ord(strtoupper(trim($q['answer']))) - 65];
                }
            }

            try {
                $controller->checkQuestionText($q, $prefix);
            } catch (ValidationException $e) {
                $errors += $e->errors();
            }

            if (! $mock) {
                $q['board_id'] = null;
                if (! empty($q['board'])) {
                    try {
                        $q['board_id'] = $controller->resolveBoard($q['board'], $prefix.'board');
                    } catch (ValidationException $e) {
                        $errors += $e->errors();
                    }
                    if ($q['board_id'] && $boardIds !== null && ! in_array($q['board_id'], $boardIds, true)) {
                        $errors[$prefix.'board'] = ['This lesson is not taught for that board.'];
                    }
                }
                unset($q['section'], $q['number']);
            }

            unset($q['board']);
            $out[] = $q;
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $out;
    }

    /** @param list<string> $options */
    public static function answerMatches(string $answer, array $options): bool
    {
        $answer = trim($answer);
        $letter = strtoupper($answer);

        return in_array($answer, $options, true)
            || (strlen($letter) === 1 && ord($letter) - 65 >= 0 && ord($letter) - 65 < count($options));
    }
}
