<?php

namespace App\Http\Controllers\Api;

use App\Content\LessonParser;
use App\Http\Controllers\Controller;
use App\Models\Admin\ApiKey;
use App\Models\Content\Board;
use App\Models\Content\Status;
use App\Models\Content\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class ApiController extends Controller
{
    /** @var list<string> */
    protected array $warnings = [];

    protected function key(Request $request): ApiKey
    {
        return $request->attributes->get('api_key');
    }

    protected function summary(Request $request, string $summary): void
    {
        $request->attributes->set('api_summary', $summary);
    }

    /**
     * Content goes live only if the key may publish; otherwise it is kept as
     * a draft for the admin to approve.
     */
    protected function allowedStatus(Request $request, ?string $status, string $what = 'This content'): string
    {
        $status ??= Status::Draft->value;

        if ($status === Status::Published->value && ! $this->key($request)->can('publish')) {
            $this->warnings[] = "$what was saved as a draft: this key cannot publish. An admin can publish it from the admin panel.";

            return Status::Draft->value;
        }

        return $status;
    }

    /**
     * Boards may be given by slug ("aqa") or ID.
     *
     * @param  list<string|int>  $values
     * @return list<int>
     */
    protected function boardIds(array $values, string $field = 'boards'): array
    {
        $boards = Board::all();
        $ids = [];

        foreach ($values as $value) {
            $board = $boards->first(fn (Board $b) => (string) $b->id === (string) $value || $b->slug === strtolower((string) $value));

            if (! $board) {
                throw ValidationException::withMessages([
                    $field => "Unknown board \"$value\". Known boards: ".$boards->pluck('slug')->implode(', ').'.',
                ]);
            }

            $ids[] = $board->id;
        }

        return array_values(array_unique($ids));
    }

    protected function subject(string|int $value, string $field = 'subject'): Subject
    {
        $subject = Subject::query()
            ->where('slug', strtolower((string) $value))
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->first();

        if (! $subject) {
            throw ValidationException::withMessages([
                $field => "Unknown subject \"$value\". Known subjects: ".Subject::orderBy('position')->pluck('slug')->implode(', ').'. Subjects are created in the admin panel.',
            ]);
        }

        return $subject;
    }

    /**
     * Runs text through the lesson parser and fails on any tag error.
     *
     * @param  array<string, ?string>  $fields  field name => text
     */
    protected function checkText(array $fields): void
    {
        $errors = [];

        foreach ($fields as $field => $text) {
            if ($text === null || $text === '') {
                continue;
            }

            $doc = app(LessonParser::class)->parse($text);

            if (! $doc->isValid()) {
                $errors[$field] = $doc->errorMessages();
            }

            foreach ($doc->warnings as $warning) {
                $this->warnings[] = "$field, line {$warning['line']}: {$warning['message']}";
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** @param array<string, mixed> $data */
    protected function respond(array $data, int $status = 200): \Illuminate\Http\JsonResponse
    {
        $payload = ['data' => $data];

        if ($this->warnings) {
            $payload['warnings'] = array_values(array_unique($this->warnings));
        }

        return response()->json($payload, $status, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string, mixed> $q */
    public function checkQuestionText(array $q, string $prefix): void
    {
        try {
            $this->checkText([
                'question' => $q['question'] ?? null,
                'mark_scheme' => $q['mark_scheme'] ?? null,
                'model_answer' => $q['model_answer'] ?? null,
            ]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(
                collect($e->errors())->mapWithKeys(fn ($m, $k) => [$prefix.$k => $m])->all()
            );
        }
    }

    public function resolveBoard(string|int $value, string $field): int
    {
        return $this->boardIds([$value], $field)[0];
    }
}
