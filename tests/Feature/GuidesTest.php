<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GuidesTest extends TestCase
{
    use RefreshDatabase;

    private const array PATHS = [
        '/guides',
        '/guides/telegram-bot-api-timeout',
        '/guides/telegram-webhook-not-working',
        '/guides/telegram-bot-api-proxy',
        '/guides/laravel-telegram-notifications',
        '/guides/php-telegram-bot-sdk',
        '/guides/telegram-bot-api-error-429',
        '/guides/telegram-chat-not-found',
        '/guides/telegram-cant-parse-entities',
        '/guides/telegram-bot-duplicate-messages',
        '/guides/telegram-bot-files-not-sending',
        '/guides/bitrix-telegram-order-notifications',
        '/guides/telegram-bot-botfather',
        '/guides/telegram-bot-launch',
        '/guides/telegram-bot-business',
    ];

    public function test_guests_can_read_guides_and_follow_the_home_navigation(): void
    {
        foreach (self::PATHS as $path) {
            $this->get($path)->assertOk()
                ->assertSee('<meta name="robots" content="index, follow">', false)
                ->assertHeaderMissing('X-Robots-Tag')
                ->assertDontSee('Предпросмотр раздела')
                ->assertSee(route('login'))
                ->assertSee(route('register'));
        }

        $this->get('/')->assertOk()->assertSee(route('guides.index'));
        $this->get('/guides/telegram-bot-api-timeout')
            ->assertSee('Короткий ответ')
            ->assertSee('getUpdates не поддерживается')
            ->assertSee('Автоматический повтор способен создать дубликат.');
    }

    public function test_regular_users_and_admins_see_the_account_link_and_can_read_guides(): void
    {
        foreach ([false, true] as $isAdmin) {
            $this->actingAs(User::factory()->create(['is_admin' => $isAdmin]));

            foreach (['/', ...self::PATHS] as $path) {
                $this->get($path)->assertOk()
                    ->assertSee('Личный кабинет')
                    ->assertSee(route('guides.index'))
                    ->assertDontSee('>Войти</a>', false);
            }
        }
    }

    public function test_catalog_paginates_published_guides_in_manual_order_without_duplicates(): void
    {
        $template = Guide::query()->firstOrFail()->export();
        for ($index = 0; $index < 13; $index++) {
            Guide::query()->create(array_replace($template, [
                'slug' => 'pagination-guide-'.$index,
                'title' => 'Pagination guide '.$index,
                'sort_order' => 3,
                'is_published' => $index !== 0,
            ]));
        }
        $expected = Guide::query()->published()->orderBy('sort_order')->orderBy('id')->get()->map->url()->all();
        $actual = [];

        foreach ([1 => 12, 2 => 12, 3 => 2] as $page => $count) {
            $response = $this->get('/guides?page='.$page)->assertOk()
                ->assertSee('Страница '.$page.' из 3')
                ->assertDontSee('Рекомендуемое руководство')
                ->assertDontSee('/guides/pagination-guide-0', false);
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $links = (new \DOMXPath($document))->query('//section[@id="guide-catalog"]//a');
            self::assertSame($count, $links->length);
            foreach ($links as $link) {
                $actual[] = $link->getAttribute('href');
            }
        }

        self::assertSame($expected, $actual);
        self::assertCount(26, array_unique($actual));
        $this->get('/guides')->assertSee('href="/guides?page=2" rel="next"', false);
        $this->get('/guides?page=2')->assertSee('href="/guides" rel="prev"', false);
        $this->get('/sitemap.xml')->assertSee('/guides/pagination-guide-12', false)
            ->assertDontSee('/guides/pagination-guide-0', false);
    }

    public function test_catalog_handles_one_page_empty_catalog_and_invalid_page_numbers(): void
    {
        Guide::query()->where('sort_order', '>=', 12)->update(['is_published' => false]);
        $this->get('/guides')->assertOk()->assertDontSee('aria-label="Страницы руководств"', false);
        $this->get('/guides?page=2')->assertNotFound();

        Guide::query()->update(['is_published' => false]);
        $this->get('/guides')->assertOk()->assertSee('Руководства пока не опубликованы.')
            ->assertDontSee('aria-label="Страницы руководств"', false);
        foreach (['2', '0', '-1', 'abc', '1.5', '99999999999999999999999', '%5B%5D=2'] as $page) {
            $query = str_starts_with($page, '%5B') ? 'page'.$page : 'page='.$page;
            $this->get('/guides?'.$query)->assertNotFound();
        }
    }

    public function test_paginated_catalog_metadata_keeps_only_its_page_number(): void
    {
        config(['app.url' => 'https://bot-gate.ru/']);
        foreach ([1 => '/guides', 2 => '/guides?page=2'] as $page => $canonical) {
            $response = $this->get('https://www.bot-gate.ru/guides?page='.$page.'&utm_source=test')->assertOk()
                ->assertSee('<link rel="canonical" href="https://bot-gate.ru'.$canonical.'">', false)
                ->assertSee('<meta property="og:url" content="https://bot-gate.ru'.$canonical.'">', false);
            $html = $response->getContent();
            self::assertIsString($html);
            // The body contains the acquisition parameter allowlist, but metadata must stay clean.
            self::assertStringNotContainsString('utm_source', explode('</head>', $html, 2)[0]);
            $response->assertDontSee('utm_source=test', false);
        }
    }

    public function test_canonical_urls_use_the_configured_origin_without_query_parameters(): void
    {
        config(['app.url' => 'https://bot-gate.ru/']);

        foreach (self::PATHS as $path) {
            $this->get('https://www.bot-gate.ru'.$path.'?utm_source=test')->assertOk()
                ->assertSee('<link rel="canonical" href="https://bot-gate.ru'.$path.'">', false)
                ->assertSee('<meta property="og:url" content="https://bot-gate.ru'.$path.'">', false);
        }
    }

    public function test_articles_have_distinct_metadata_without_the_other_guides_block(): void
    {
        $titles = [];
        $descriptions = [];

        foreach (array_slice(self::PATHS, 1) as $path) {
            $response = $this->get($path)->assertOk()
                ->assertSee('<meta property="og:type" content="article">', false)
                ->assertDontSee('Другие руководства')
                ->assertSee('Все руководства');
            $html = $response->getContent();
            self::assertIsString($html);
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new \DOMXPath($document);
            $titles[] = $xpath->evaluate('string(//title)');
            $descriptions[] = $xpath->evaluate('string(//meta[@name="description"]/@content)');
            self::assertSame(1.0, $xpath->evaluate('count(//h1)'));

        }

        self::assertCount(14, array_unique($titles));
        self::assertCount(14, array_unique($descriptions));
        self::assertNotContains('', $descriptions);
    }

    public function test_sitemap_includes_guides_and_documentation_keeps_its_own_navigation(): void
    {
        config(['app.url' => 'https://bot-gate.ru']);

        $this->get('/docs')->assertOk()->assertDontSee(route('guides.index'));
        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('<loc>https://bot-gate.ru/guides</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/telegram-bot-api-timeout</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/telegram-webhook-not-working</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/telegram-bot-api-proxy</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/laravel-telegram-notifications</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/php-telegram-bot-sdk</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/telegram-bot-api-error-429</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/telegram-chat-not-found</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/telegram-cant-parse-entities</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/telegram-bot-duplicate-messages</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/telegram-bot-files-not-sending</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/bitrix-telegram-order-notifications</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/telegram-bot-botfather</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/telegram-bot-launch</loc>', false)
            ->assertSee('<loc>https://bot-gate.ru/guides/telegram-bot-business</loc>', false)
            ->assertDontSee('/dashboard');
        $this->get('/robots.txt')->assertOk()->assertDontSee('Disallow: /guides');
    }

    public function test_beginner_articles_have_contextual_links_and_valid_contents(): void
    {
        $paths = [
            '/guides/telegram-bot-botfather',
            '/guides/telegram-bot-launch',
            '/guides/telegram-bot-business',
        ];

        foreach ($paths as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            self::assertIsString($html);
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new \DOMXPath($document);

            foreach (array_diff($paths, [$path]) as $otherPath) {
                $href = $otherPath;
                self::assertGreaterThan(0.0, $xpath->evaluate("count(//article/section//a[@href='$href'])"));
            }

            $anchors = $xpath->query('//aside[@aria-label="Оглавление"]//a');
            self::assertNotFalse($anchors);
            self::assertGreaterThan(0, $anchors->length);

            foreach ($anchors as $anchor) {
                self::assertInstanceOf(\DOMElement::class, $anchor);
                $id = substr($anchor->getAttribute('href'), 1);
                self::assertSame(1.0, $xpath->evaluate("count(//article/section[@id='$id'])"));
            }
        }
    }
}
