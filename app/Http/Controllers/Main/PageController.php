<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Support\Navigation;
use App\Support\Seo;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** The presentation pages on openrevise.com. */
class PageController extends Controller
{
    public function home(): View
    {
        return view('main.home', [
            'boards' => Navigation::boards(),
            'subjects' => Navigation::footerSubjects(),
            'seo' => [
                'title' => 'OpenRevise: free GCSE revision notes, exam questions and mock exams',
                'description' => 'Free GCSE revision for AQA, Edexcel and OCR. Notes on every topic, exam questions with mark schemes after every lesson, and mock exams. No account needed.',
                'canonical' => main_url('/'),
                'jsonLd' => [Seo::organisation(), Seo::website()],
            ],
        ]);
    }

    public function about(): View
    {
        return $this->page('main.about', 'About OpenRevise', 'Who is behind OpenRevise, why it is free and how the lessons are written.', '/about/');
    }

    public function privacy(): View
    {
        return $this->page('main.privacy', 'Privacy', 'What OpenRevise collects (very little), why, and what it never does.', '/privacy/');
    }

    public function terms(): View
    {
        return $this->page('main.terms', 'Terms of use', 'The rules for using OpenRevise and its content.', '/terms/');
    }

    public function contact(): View
    {
        return $this->page('main.contact', 'Contact and corrections', 'Spotted a mistake in a lesson? How to tell us, and how to get in touch.', '/contact/');
    }

    public function sitemap(): Response
    {
        $urls = collect(['/', '/about/', '/contact/', '/privacy/', '/terms/'])->map(fn ($p) => [main_url($p), null])->all();

        return response(view('sitemap', ['urls' => $urls])->render(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        return response("User-agent: *\nAllow: /\n\nSitemap: ".main_url('/sitemap.xml')."\nSitemap: ".app_url('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain']);
    }

    protected function page(string $view, string $title, string $description, string $path): View
    {
        return view($view, [
            'owner' => config('openrevise.owner'),
            'seo' => [
                'title' => Seo::title($title),
                'description' => $description,
                'canonical' => main_url($path),
            ],
        ]);
    }
}
