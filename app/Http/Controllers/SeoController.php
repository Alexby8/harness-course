<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Guide;
use Illuminate\Http\Response;

final class SeoController extends Controller
{
    /**
     * @var list<string>
     */
    private const DISALLOWED = [
        '/dashboard',
        '/bots',
        '/profile',
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/logout',
        '/admin',
        '/email',
        '/notifications',
        '/api/',
        '/livewire/',
        '/cookie-consent',
        '/up',
    ];

    public function robots(): Response
    {
        $lines = ['User-agent: *', 'Allow: /'];

        foreach (self::DISALLOWED as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.$this->baseUrl().'/sitemap.xml';

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $base = $this->baseUrl();

        $pages = [
            $base.'/',
            $base.'/docs',
            $base.'/guides',
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($pages as $url) {
            $xml .= '    <url>'."\n";
            $xml .= '        <loc>'.htmlspecialchars($url, ENT_XML1, 'UTF-8').'</loc>'."\n";
            $xml .= '    </url>'."\n";
        }

        foreach (Guide::query()->published()->orderBy('sort_order')->orderBy('id')->get(['slug', 'content_updated_at']) as $guide) {
            $xml .= '    <url>'."\n";
            $xml .= '        <loc>'.htmlspecialchars($base.$guide->path(), ENT_XML1, 'UTF-8').'</loc>'."\n";
            if ($guide->content_updated_at !== null) {
                $xml .= '        <lastmod>'.$guide->content_updated_at->toAtomString().'</lastmod>'."\n";
            }
            $xml .= '    </url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function baseUrl(): string
    {
        return rtrim(config()->string('app.url'), '/');
    }
}
