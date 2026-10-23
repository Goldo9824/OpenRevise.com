<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/** The API reference, as a web page and as Markdown that Claude can read. */
class DocsController extends Controller
{
    public function markdown(): Response
    {
        return response($this->source(), 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    public function html(): Response
    {
        $html = Str::markdown($this->source(), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return response()->view('api.docs', ['content' => $html]);
    }

    protected function source(): string
    {
        return str_replace(
            ['{{api}}', '{{site}}'],
            [rtrim(api_url('/'), '/'), rtrim(main_url('/'), '/')],
            (string) file_get_contents(resource_path('docs/api.md')),
        );
    }
}
