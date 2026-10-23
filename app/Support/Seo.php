<?php

namespace App\Support;

/** Structured data (schema.org) so search engines understand each page. */
class Seo
{
    /**
     * @param  list<array{0: string, 1: string}>  $crumbs  [name, absolute url]
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn ($crumb, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ], $crumbs, array_keys($crumbs)),
        ];
    }

    /** @return array<string, mixed> */
    public static function organisation(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'OpenRevise',
            'url' => main_url('/'),
            'logo' => main_url('/favicon.svg'),
        ];
    }

    /** @return array<string, mixed> */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'OpenRevise',
            'url' => main_url('/'),
        ];
    }

    public static function title(string ...$parts): string
    {
        return implode(' | ', array_filter([...$parts, 'OpenRevise']));
    }
}
