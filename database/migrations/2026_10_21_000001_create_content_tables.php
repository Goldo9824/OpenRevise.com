<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Content DB: boards, subjects, the four-level lesson tree, exam questions,
 * mock exams and image details. Lesson bodies and image files live on disk.
 */
return new class extends Migration
{
    protected $connection = 'content';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 60)->unique();
            $table->string('full_name')->nullable();
            $table->string('website')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $schema->create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->string('colour', 7)->nullable();
            $table->string('icon', 30)->nullable();
            $table->string('status', 12)->default('published');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $schema->create('board_subject', function (Blueprint $table) {
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('spec_code', 40)->nullable();
            $table->string('spec_url')->nullable();
            $table->primary(['board_id', 'subject_id']);
        });

        $schema->create('paper_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 120);
            $table->string('tier', 12)->default('all');
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('total_marks');
            $table->text('instructions')->nullable();
            $table->text('information')->nullable();
            $table->json('sections')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['board_id', 'subject_id', 'slug']);
        });

        $schema->create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->string('status', 12)->default('published');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['subject_id', 'slug']);
        });

        $schema->create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->string('status', 12)->default('published');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['topic_id', 'slug']);
        });

        $schema->create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('slug', 200);
            $table->string('summary', 300)->nullable();
            $table->string('tier', 12)->default('all');
            $table->string('course', 12)->default('all');
            $table->string('status', 12)->default('draft');
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('word_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['chapter_id', 'slug']);
            $table->index('status');
        });

        $schema->create('board_lesson', function (Blueprint $table) {
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->primary(['board_id', 'lesson_id']);
        });

        $schema->create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->default('short-answer');
            $table->text('question');
            $table->json('options')->nullable();
            $table->string('answer')->nullable();
            $table->unsignedSmallInteger('marks');
            $table->text('mark_scheme');
            $table->text('model_answer');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $schema->create('mock_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paper_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 200);
            $table->text('description')->nullable();
            $table->string('status', 12)->default('draft');
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['subject_id', 'slug']);
        });

        $schema->create('mock_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mock_exam_id')->constrained()->cascadeOnDelete();
            $table->string('section', 120)->nullable();
            $table->string('number', 12)->nullable();
            $table->string('type', 30)->default('short-answer');
            $table->text('question');
            $table->json('options')->nullable();
            $table->string('answer')->nullable();
            $table->unsignedSmallInteger('marks');
            $table->text('mark_scheme');
            $table->text('model_answer');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $schema->create('images', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('filename', 160);
            $table->string('alt')->nullable();
            $table->string('mime', 40);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('size_bytes')->default(0);
            $table->timestamps();
        });

        $schema->create('slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_path', 500);
            $table->string('target_type', 40);
            $table->unsignedBigInteger('target_id');
            $table->timestamps();
            $table->index('old_path');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        foreach ([
            'slug_redirects', 'images', 'mock_exam_questions', 'mock_exams', 'exam_questions',
            'board_lesson', 'lessons', 'chapters', 'topics', 'paper_templates', 'board_subject',
            'subjects', 'boards',
        ] as $table) {
            $schema->dropIfExists($table);
        }
    }
};
