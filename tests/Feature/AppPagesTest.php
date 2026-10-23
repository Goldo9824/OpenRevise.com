<?php

namespace Tests\Feature;

use App\Http\Controllers\App\Preview;
use App\Models\Admin\Report;
use App\Models\Content\Status;
use Tests\Concerns\BuildsContent;
use Tests\TestCase;

class AppPagesTest extends TestCase
{
    use BuildsContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildTree();
    }

    public function test_the_whole_path_down_to_a_lesson_works(): void
    {
        $lesson = $this->makeLesson();
        $lesson->questions()->create(['type' => 'short-answer', 'question' => 'Name it.', 'marks' => 2, 'mark_scheme' => 'nucleus', 'model_answer' => 'The nucleus.']);

        $this->visit($this->appUrl('/'))->assertOk()->assertSee('What are you revising?')->assertSee('Biology');
        $this->visit($this->appUrl('/gcse/'))->assertOk()->assertSee('GCSE subjects');
        $this->visit($this->appUrl('/gcse/biology/'))->assertOk()->assertSee('1. Cell biology')->assertSee('1.1');
        $this->visit($this->appUrl('/gcse/biology/aqa/'))->assertOk()->assertSee('AQA GCSE Biology')->assertSee('Specification 8461');
        $this->visit($this->appUrl('/gcse/biology/cell-biology/'))->assertOk()->assertSee('Cell structure');
        $this->visit($this->appUrl('/gcse/biology/cell-biology/cell-structure/'))->assertOk()->assertSee($lesson->title);

        $this->visit($this->appUrl($lesson->path()))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="http://app.openrevise.test/gcse/biology/cell-biology/cell-structure/eukaryotic-and-prokaryotic-cells/">', false)
            ->assertSee('"@type":"LearningResource"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('Key terms')
            ->assertSee('Exam questions');

        $this->visit($this->appUrl($lesson->questionsPath()))
            ->assertOk()
            ->assertSee('Name it.')
            ->assertSee('[2 marks]')
            ->assertSee('"@type":"Quiz"', false);
    }

    public function test_addresses_get_a_trailing_slash(): void
    {
        $this->visit($this->appUrl('/gcse/biology'))->assertStatus(301)->assertRedirect($this->appUrl('/gcse/biology/'));
    }

    public function test_drafts_are_hidden_but_can_be_previewed(): void
    {
        $lesson = $this->makeLesson(['status' => Status::Draft->value]);

        $this->visit($this->appUrl($lesson->path()))->assertNotFound();
        $this->visit(Preview::url($lesson))->assertOk()->assertSee('Preview: this lesson is a draft')->assertSee('noindex', false);
        $this->visit($this->appUrl($lesson->path()).'?preview=nope')->assertNotFound();
    }

    public function test_a_moved_topic_redirects_every_page_under_it(): void
    {
        $lesson = $this->makeLesson();
        $old = $lesson->path();

        $this->topic->update(['slug' => 'cells']);

        $this->visit($this->appUrl($old))
            ->assertStatus(301)
            ->assertRedirect($this->appUrl('/gcse/biology/cells/cell-structure/eukaryotic-and-prokaryotic-cells/'));
    }

    public function test_topic_slugs_cannot_take_a_board_name(): void
    {
        $topic = $this->subject->topics()->create(['title' => 'AQA']);

        $this->assertSame('aqa-2', $topic->slug);
    }

    public function test_the_sitemap_lists_published_lessons_only(): void
    {
        $published = $this->makeLesson();
        $draft = $this->makeLesson(['title' => 'Secret draft', 'status' => 'draft']);

        $this->visit($this->appUrl('/sitemap.xml'))
            ->assertOk()
            ->assertSee($this->appUrl($published->path()))
            ->assertDontSee($draft->slug);

        $this->visit($this->appUrl('/robots.txt'))->assertOk()->assertSee('Sitemap: http://app.openrevise.test/sitemap.xml');
    }

    public function test_search_finds_lessons(): void
    {
        $this->makeLesson();

        $this->getJson($this->appUrl('/_search?q=prokaryotic'))
            ->assertOk()
            ->assertJsonPath('results.0.type', 'Lesson')
            ->assertJsonPath('results.0.title', 'Eukaryotic and prokaryotic cells');

        $this->visit($this->appUrl('/search/?q=cell'))->assertOk()->assertSee('Cell biology');
    }

    public function test_students_can_report_a_mistake(): void
    {
        $lesson = $this->makeLesson();

        $this->postJson($this->appUrl('/_report'), ['message' => 'The units are wrong.', 'lesson_id' => $lesson->id, 'page' => $this->appUrl($lesson->path())], ['Origin' => 'http://app.openrevise.test'])
            ->assertCreated();
        $this->postJson($this->appUrl('/_report'), ['message' => 'Spam from elsewhere', 'page' => 'x'], ['Origin' => 'http://evil.test'])
            ->assertForbidden();

        $this->assertSame(1, Report::count());
    }

    public function test_public_pages_set_no_cookies(): void
    {
        $this->makeLesson();

        $response = $this->visit($this->appUrl('/gcse/biology/'));

        $this->assertSame([], $response->headers->getCookies());
    }
}
