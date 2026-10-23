<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics DB: raw page views (kept for 90 days), the daily totals they
 * are rolled up into, and the daily salts used to hash visitors.
 */
return new class extends Migration
{
    protected $connection = 'analytics';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('page_views', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->useCurrent();
            $table->char('view_key', 16);
            $table->char('visitor', 16);
            $table->string('site', 8);
            $table->string('path', 500);
            $table->string('page_type', 30)->nullable();
            $table->string('subject', 120)->nullable();
            $table->string('board', 60)->nullable();
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->string('source', 80);
            $table->string('referrer_host', 190)->nullable();
            $table->string('device', 10);
            $table->string('browser', 40);
            $table->string('os', 40);
            $table->char('country', 2)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->index('created_at');
            $table->index(['visitor', 'created_at']);
            $table->index('view_key');
        });

        $schema->create('daily_totals', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('bounces')->default(0);
            $table->unsignedBigInteger('duration_ms')->default(0);
            $table->unsignedInteger('duration_samples')->default(0);
            $table->unsignedInteger('funnel_home')->default(0);
            $table->unsignedInteger('funnel_lesson')->default(0);
            $table->unsignedInteger('funnel_questions')->default(0);
        });

        $schema->create('daily_pages', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('site', 8);
            $table->string('path', 500);
            $table->string('page_type', 30)->nullable();
            $table->string('subject', 120)->nullable();
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedBigInteger('duration_ms')->default(0);
            $table->unsignedInteger('duration_samples')->default(0);
            $table->index('date');
            $table->index(['lesson_id', 'date']);
        });

        $schema->create('daily_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('dimension', 20);
            $table->string('value', 190);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('visitors')->default(0);
            $table->unique(['date', 'dimension', 'value']);
        });

        $schema->create('salts', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->char('salt', 64);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        foreach (['salts', 'daily_breakdowns', 'daily_pages', 'daily_totals', 'page_views'] as $table) {
            $schema->dropIfExists($table);
        }
    }
};
