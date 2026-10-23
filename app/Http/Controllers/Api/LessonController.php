<?php

namespace App\Http\Controllers\Api;

use App\Content\LessonParser;
use App\Content\LessonService;
use App\Models\Content\Course;
use App\Models\Content\Lesson;
use App\Models\Content\Status;
use App\Models\Content\Tier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LessonController extends ApiController
{
    public function __construct(protected LessonService $lessons) {}

    /** GET /v1/lessons/{id}: one lesson with its text and questions. */
    public function show(Request $request, int $id): JsonResponse
    {
        $lesson = Lesson::with(['boards', 'chapter.topic.subject', 'questions.board'])->findOrFail($id);

        $this->summary($request, "Read lesson #{$lesson->id}");

        return $this->respond(Presenter::lesson($lesson, withBody: true) + [
            'exam_questions' => $lesson->questions->map(fn ($q) => Presenter::question($q))->all(),
        ]);
    }

    /** POST /v1/lessons */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request->all());

        $lesson = $this->lessons->create($this->prepare($request, $data));

        $this->summary($request, "Created lesson #{$lesson->id} \"{$lesson->title}\"");

        return $this->respond(Presenter::lesson($lesson), 201);
    }

    /** POST /v1/lessons/bulk: several lessons, all or nothing. */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate(['lessons' => ['required', 'array', 'min:1', 'max:50']]);

        $all = [];
        $errors = [];

        foreach ($request->input('lessons') as $i => $item) {
            try {
                $all[] = $this->validated(is_array($item) ? $item : [], "lessons.$i.");
            } catch (ValidationException $e) {
                $errors += $e->errors();
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $created = DB::connection('content')->transaction(
            fn () => array_map(fn ($data) => $this->lessons->create($this->prepare($request, $data)), $all)
        );

        $this->summary($request, 'Created '.count($created).' lessons (#'.implode(', #', array_map(fn ($l) => $l->id, $created)).')');

        return $this->respond(['lessons' => array_map(fn ($l) => Presenter::lesson($l), $created)], 201);
    }

    /** PUT /v1/lessons/{id}: change only the fields sent. */
    public function update(Request $request, int $id): JsonResponse
    {
        $lesson = Lesson::with('chapter')->findOrFail($id);

        $data = $this->validated($request->all(), partial: true);

        if (array_key_exists('boards', $data)) {
            $data['boards'] = $this->boardIds($data['boards']);
        }

        if (array_key_exists('status', $data)) {
            $data['status'] = $this->allowedStatus($request, $data['status'], 'The lesson');
        }

        $lesson = $this->lessons->update($lesson, $data);

        $this->summary($request, "Updated lesson #{$lesson->id} \"{$lesson->title}\"");

        return $this->respond(Presenter::lesson($lesson));
    }

    /** POST /v1/lessons/check: parse lesson text without saving it. */
    public function check(Request $request): JsonResponse
    {
        $request->validate(['body' => ['required', 'string', 'max:200000']]);

        $doc = app(LessonParser::class)->parse($request->input('body'));

        $this->summary($request, 'Checked lesson text');

        return $this->respond([
            'valid' => $doc->isValid(),
            'errors' => $doc->errorMessages(),
            'warnings' => array_map(fn ($w) => 'Line '.$w['line'].': '.$w['message'], $doc->warnings),
            'title' => $doc->title,
            'headings' => $doc->headings,
            'key_terms' => array_map(fn ($t) => ['term' => $t['term'], 'definition' => $t['definition']], $doc->keyTerms),
            'images' => $doc->images,
            'word_count' => $doc->wordCount,
            'html' => $doc->html,
        ]);
    }

    /** @return array<string, mixed> */
    protected function validated(array $input, string $prefix = '', bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        $rules = [
            'chapter_id' => [$required, 'integer', Rule::exists('content.chapters', 'id')],
            'title' => [$required, 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
            'summary' => ['nullable', 'string', 'max:300'],
            'body' => [$required, 'string', 'max:200000'],
            'boards' => [$required, 'array', 'min:1'],
            'boards.*' => ['required'],
            'tier' => ['nullable', Rule::enum(Tier::class)],
            'course' => ['nullable', Rule::enum(Course::class)],
            'status' => ['nullable', Rule::enum(Status::class)],
            'position' => ['nullable', 'integer', 'min:0'],
        ];

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages(
                collect($validator->errors()->toArray())->mapWithKeys(fn ($m, $k) => [$prefix.$k => $m])->all()
            );
        }

        $data = $validator->validated();

        if (isset($data['body'])) {
            try {
                $this->checkText(['body' => $data['body']]);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages([$prefix.'body' => $e->errors()['body']]);
            }
        }

        if (isset($data['boards'])) {
            try {
                $this->boardIds($data['boards']);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages([$prefix.'boards' => $e->errors()['boards']]);
            }
        }

        return $data;
    }

    /** @return array<string, mixed> */
    protected function prepare(Request $request, array $data): array
    {
        $data['boards'] = $this->boardIds($data['boards']);
        $data['status'] = $this->allowedStatus($request, $data['status'] ?? null, 'The lesson "'.$data['title'].'"');
        $data['tier'] ??= Tier::All->value;
        $data['course'] ??= Course::All->value;

        return $data;
    }
}
