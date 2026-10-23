<?php

namespace Tests;

use App\Models\Admin\ApiKey;
use App\Models\Content\Board;
use App\Support\SubjectOutline;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected string $tmp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Each test gets three empty in-memory databases and its own folders.
        $this->tmp = sys_get_temp_dir().'/openrevise-tests/'.uniqid();
        config([
            'openrevise.paths.lessons' => $this->tmp.'/lessons',
            'openrevise.paths.media' => $this->tmp.'/media',
        ]);

        $this->artisan('migrate');

        Board::flushSlugCache();
        SubjectOutline::flush();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);

        parent::tearDown();
    }

    /**
     * GET a URL exactly as given. Laravel's test client trims the trailing
     * slash, and every page address on OpenRevise ends with one.
     */
    protected function visit(string $url): TestResponse
    {
        $kernel = $this->app->make(HttpKernel::class);
        $request = Request::create($url, 'GET', [], [], [], $this->transformHeadersToServerVars($this->defaultHeaders));
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $this->createTestResponse($response, $request);
    }

    protected function apiUrl(string $path = '/'): string
    {
        return 'http://api.openrevise.test'.$path;
    }

    protected function mainUrl(string $path = '/'): string
    {
        return 'http://openrevise.test'.$path;
    }

    /** @param list<string> $abilities */
    protected function apiKey(array $abilities = ['read', 'write', 'publish']): string
    {
        return ApiKey::generate('Test key', $abilities)[1];
    }
}
