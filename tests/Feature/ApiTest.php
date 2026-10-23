<?php

namespace Tests\Feature;

use App\Models\Admin\ApiRequest;
use App\Models\Content\Image;
use App\Models\Content\Lesson;
use App\Models\Content\MockExam;
use App\Models\Content\PaperTemplate;
use Tests\Concerns\BuildsContent;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use BuildsContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildTree();
    }

    protected function lessonPayload(array $overrides = []): array
    {
        return $overrides + [
            'chapter_id' => $this->chapter->id,
            'title' => 'Animal and plant cells',
            'summary' => 'What is inside.',
            'boards' => ['aqa', 'edexcel'],
            'body' => "## Parts\n\n[KEYTERM] Nucleus: holds DNA.",
        ];
    }

    public function test_a_key_is_needed(): void
    {
        $this->getJson($this->apiUrl('/v1/tree'))->assertStatus(401);
        $this->getJson($this->apiUrl('/v1/tree'), ['Authorization' => 'Bearer or_nope'])->assertStatus(401);
    }

    public function test_a_read_only_key_cannot_write(): void
    {
        $key = $this->apiKey(['read']);

        $this->getJson($this->apiUrl('/v1/tree'), ['Authorization' => "Bearer $key"])->assertOk();
        $this->postJson($this->apiUrl('/v1/lessons'), $this->lessonPayload(), ['Authorization' => "Bearer $key"])->assertStatus(403);
    }

    public function test_the_tree_lists_drafts_too(): void
    {
        $this->makeLesson(['status' => 'draft']);

        $this->getJson($this->apiUrl('/v1/tree'), ['Authorization' => 'Bearer '.$this->apiKey(['read'])])
            ->assertOk()
            ->assertJsonPath('data.subjects.0.topics.0.chapters.0.lessons.0.status', 'draft');
    }

    public function test_it_creates_topics_chapters_and_lessons(): void
    {
        $headers = ['Authorization' => 'Bearer '.$this->apiKey()];

        $topic = $this->postJson($this->apiUrl('/v1/topics'), ['subject' => 'biology', 'title' => 'Organisation'], $headers)
            ->assertCreated()->assertJsonPath('data.slug', 'organisation')->json('data');

        $chapter = $this->postJson($this->apiUrl('/v1/chapters'), ['topic_id' => $topic['id'], 'title' => 'The heart'], $headers)
            ->assertCreated()->json('data');

        $this->postJson($this->apiUrl('/v1/lessons'), $this->lessonPayload(['chapter_id' => $chapter['id'], 'status' => 'published']), $headers)
            ->assertCreated()
            ->assertJsonPath('data.slug', 'animal-and-plant-cells')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.url', 'http://app.openrevise.test/gcse/biology/organisation/the-heart/animal-and-plant-cells/');

        $lesson = Lesson::where('title', 'Animal and plant cells')->first();
        $this->assertStringContainsString('[KEYTERM] Nucleus', $lesson->body());
        $this->assertSame(1, ApiRequest::where('path', '/v1/lessons')->where('status', 201)->count());
    }

    public function test_without_the_publish_right_lessons_are_drafts(): void
    {
        $this->postJson($this->apiUrl('/v1/lessons'), $this->lessonPayload(['status' => 'published']), ['Authorization' => 'Bearer '.$this->apiKey(['read', 'write'])])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(1, 'warnings');
    }

    public function test_a_lesson_for_one_board_gets_its_name_in_the_slug(): void
    {
        $this->postJson($this->apiUrl('/v1/lessons'), $this->lessonPayload(['title' => 'Required practical: microscopy', 'boards' => ['aqa']]), ['Authorization' => 'Bearer '.$this->apiKey()])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'required-practical-microscopy-aqa');
    }

    public function test_broken_lesson_text_is_refused_with_the_line(): void
    {
        $this->postJson($this->apiUrl('/v1/lessons'), $this->lessonPayload(['body' => "Hello\n[TABLE]\na | b"]), ['Authorization' => 'Bearer '.$this->apiKey()])
            ->assertStatus(422)
            ->assertJsonPath('errors.body.0', 'Line 2: [TABLE] is never closed with [/TABLE].');

        $this->assertSame(0, Lesson::count());
    }

    public function test_bulk_is_all_or_nothing(): void
    {
        $headers = ['Authorization' => 'Bearer '.$this->apiKey()];

        $this->postJson($this->apiUrl('/v1/lessons/bulk'), ['lessons' => [
            $this->lessonPayload(['title' => 'One']),
            $this->lessonPayload(['title' => 'Two', 'boards' => ['wjec']]),
        ]], $headers)->assertStatus(422)->assertJsonValidationErrors('lessons.1.boards');

        $this->assertSame(0, Lesson::count());

        $this->postJson($this->apiUrl('/v1/lessons/bulk'), ['lessons' => [
            $this->lessonPayload(['title' => 'One']),
            $this->lessonPayload(['title' => 'Two']),
        ]], $headers)->assertCreated()->assertJsonCount(2, 'data.lessons');
    }

    public function test_updating_a_slug_keeps_the_old_address(): void
    {
        $lesson = $this->makeLesson();
        $old = $lesson->path();

        $this->putJson($this->apiUrl("/v1/lessons/{$lesson->id}"), ['slug' => 'cell-types'], ['Authorization' => 'Bearer '.$this->apiKey()])
            ->assertOk()->assertJsonPath('data.slug', 'cell-types');

        $this->visit($this->appUrl($old))->assertRedirect($this->appUrl('/gcse/biology/cell-biology/cell-structure/cell-types/'));
    }

    public function test_questions_are_added_to_a_lesson(): void
    {
        $lesson = $this->makeLesson();

        $this->postJson($this->apiUrl('/v1/questions'), [
            'lesson_id' => $lesson->id,
            'questions' => [
                ['type' => 'multiple-choice', 'question' => 'Which is in bacteria?', 'options' => ['Nucleus', 'Plasmid'], 'answer' => 'B', 'marks' => 1, 'mark_scheme' => 'B', 'model_answer' => 'Plasmid.'],
                ['question' => 'Name the jelly.', 'marks' => 1, 'mark_scheme' => 'cytoplasm', 'model_answer' => 'Cytoplasm.', 'board' => 'aqa'],
            ],
        ], ['Authorization' => 'Bearer '.$this->apiKey()])
            ->assertCreated()
            ->assertJsonPath('data.questions.0.answer', 'Plasmid')
            ->assertJsonPath('data.questions.1.type', 'short-answer')
            ->assertJsonPath('data.questions.1.board', 'aqa');

        $this->visit($this->appUrl($lesson->questionsPath()))->assertOk()->assertSee('Name the jelly.');
    }

    public function test_multiple_choice_needs_a_valid_answer(): void
    {
        $lesson = $this->makeLesson();

        $this->postJson($this->apiUrl('/v1/questions'), [
            'lesson_id' => $lesson->id,
            'type' => 'multiple-choice', 'question' => 'Q?', 'options' => ['A thing', 'B thing'], 'answer' => 'Z', 'marks' => 1, 'mark_scheme' => 'x', 'model_answer' => 'x',
        ], ['Authorization' => 'Bearer '.$this->apiKey()])->assertStatus(422)->assertJsonValidationErrors('questions.0.answer');
    }

    public function test_mock_exams_take_time_and_marks_from_the_template(): void
    {
        PaperTemplate::create(['board_id' => 1, 'subject_id' => $this->subject->id, 'name' => 'Paper 1', 'duration_minutes' => 105, 'total_marks' => 100]);

        $this->postJson($this->apiUrl('/v1/mock-exams'), [
            'subject' => 'biology', 'board' => 'aqa', 'paper_template' => 'paper-1', 'status' => 'published',
            'questions' => [['question' => 'Explain osmosis.', 'marks' => 4, 'mark_scheme' => 'x', 'model_answer' => 'y']],
        ], ['Authorization' => 'Bearer '.$this->apiKey()])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'aqa-paper-1')
            ->assertJsonPath('data.duration_minutes', 105)
            ->assertJsonPath('data.question_marks', 4)
            ->assertJsonCount(1, 'warnings');

        $this->visit($this->appUrl(MockExam::first()->path()))->assertOk()->assertSee('1 hour 45 minutes');
    }

    public function test_svg_diagrams_are_cleaned_before_saving(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 50" onload="alert(1)"><script>alert(2)</script><rect width="10" height="10"/><a href="javascript:alert(3)"><text>x</text></a></svg>';

        $this->postJson($this->apiUrl('/v1/images'), ['name' => 'Animal Cell', 'svg' => $svg, 'alt' => 'A cell'], ['Authorization' => 'Bearer '.$this->apiKey()])
            ->assertCreated()
            ->assertJsonPath('data.name', 'animal-cell')
            ->assertJsonPath('data.tag', '[IMAGE] animal-cell | A cell')
            ->assertJsonPath('data.width', 100);

        $stored = file_get_contents(Image::first()->filePath());
        $this->assertStringNotContainsString('script', $stored);
        $this->assertStringNotContainsString('onload', $stored);
        $this->assertStringNotContainsString('javascript:', $stored);
        $this->assertStringContainsString('<rect', $stored);

        $this->postJson($this->apiUrl('/v1/images'), ['name' => 'animal-cell', 'svg' => $svg], ['Authorization' => 'Bearer '.$this->apiKey()])
            ->assertStatus(422);
    }

    public function test_the_reference_is_public(): void
    {
        $this->get($this->apiUrl('/'))->assertOk()->assertSee('OpenRevise API');
        $this->get($this->apiUrl('/v1/docs'))->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    }
}
