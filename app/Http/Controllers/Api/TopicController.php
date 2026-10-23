<?php

namespace App\Http\Controllers\Api;

use App\Models\Content\Chapter;
use App\Models\Content\Status;
use App\Models\Content\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TopicController extends ApiController
{
    /** POST /v1/topics */
    public function storeTopic(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required_without:subject_id'],
            'subject_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::enum(Status::class)],
        ]);

        $subject = $this->subject($data['subject'] ?? $data['subject_id'], isset($data['subject']) ? 'subject' : 'subject_id');

        $topic = Topic::create([
            'subject_id' => $subject->id,
            'title' => $data['title'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? Status::Published->value,
            'position' => $data['position'] ?? ((int) Topic::where('subject_id', $subject->id)->max('position') + 1),
        ]);

        $this->summary($request, "Created topic #{$topic->id} \"{$topic->title}\"");

        return $this->respond(Presenter::topic($topic->load('subject')), 201);
    }

    /** POST /v1/chapters */
    public function storeChapter(Request $request): JsonResponse
    {
        $data = $request->validate([
            'topic_id' => ['required', 'integer', Rule::exists('content.topics', 'id')],
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::enum(Status::class)],
        ]);

        $chapter = Chapter::create([
            'topic_id' => $data['topic_id'],
            'title' => $data['title'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? Status::Published->value,
            'position' => $data['position'] ?? ((int) Chapter::where('topic_id', $data['topic_id'])->max('position') + 1),
        ]);

        $this->summary($request, "Created chapter #{$chapter->id} \"{$chapter->title}\"");

        return $this->respond(Presenter::chapter($chapter->load('topic.subject')), 201);
    }
}
