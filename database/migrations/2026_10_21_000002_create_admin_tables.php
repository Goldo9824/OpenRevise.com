<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin DB: the admin account and passkeys, API keys, every log the admin
 * panel shows, error reports from students and site settings.
 */
return new class extends Migration
{
    protected $connection = 'admin';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email')->unique();
            $table->string('password');
            $table->text('recovery_codes')->nullable();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        $schema->create('passkeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authenticatable_id')->constrained('admins')->cascadeOnDelete();
            $table->text('name');
            $table->text('credential_id');
            $table->json('data');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        $schema->create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('prefix', 16);
            $table->char('key_hash', 64)->unique();
            $table->json('abilities');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        $schema->create('api_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 8);
            $table->string('path', 300);
            $table->unsignedSmallInteger('status');
            $table->string('summary', 300)->nullable();
            $table->text('error')->nullable();
            $table->string('ip', 45)->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });

        $schema->create('sign_in_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('method', 20);
            $table->boolean('succeeded');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });

        $schema->create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20);
            $table->text('message');
            $table->string('exception')->nullable();
            $table->string('file', 300)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('url', 500)->nullable();
            $table->text('trace')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });

        $schema->create('content_changes', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 40);
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_label', 200)->nullable();
            $table->string('event', 20);
            $table->json('changes')->nullable();
            $table->string('causer', 160)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        $schema->create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->string('page_url', 500);
            $table->text('message');
            $table->string('status', 12)->default('new');
            $table->char('ip_hash', 16)->nullable();
            $table->timestamps();
        });

        $schema->create('settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        foreach ([
            'settings', 'reports', 'content_changes', 'error_logs', 'sign_in_attempts',
            'api_requests', 'api_keys', 'passkeys', 'admins',
        ] as $table) {
            $schema->dropIfExists($table);
        }
    }
};
