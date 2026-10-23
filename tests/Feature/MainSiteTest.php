<?php

namespace Tests\Feature;

use Tests\TestCase;

class MainSiteTest extends TestCase
{
    public function test_the_presentation_pages_load(): void
    {
        $this->visit($this->mainUrl('/'))->assertOk()->assertSee('Revise smarter.');

        foreach (['/about/', '/contact/', '/privacy/', '/terms/'] as $path) {
            $this->visit($this->mainUrl($path))->assertOk()->assertSee('rel="canonical"', false);
        }
    }

    public function test_unknown_pages_get_the_friendly_404(): void
    {
        $this->visit($this->mainUrl('/nope/'))->assertNotFound()->assertSee('We can');
    }

    public function test_www_goes_to_the_bare_domain(): void
    {
        $this->visit('http://www.openrevise.test/about/')->assertRedirect('http://openrevise.test/about/');
    }

    public function test_robots_points_at_both_sitemaps_and_hides_admin_and_api(): void
    {
        $this->visit($this->mainUrl('/robots.txt'))->assertSee('app.openrevise.test/sitemap.xml');
        $this->get($this->apiUrl('/robots.txt'))->assertSee('Disallow: /');
        $this->get('http://admin.openrevise.test/robots.txt')->assertSee('Disallow: /');
    }
}
