<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Forms\GuideExampleBlock;
use App\Filament\Resources\GuideResource\Pages\EditGuide;
use App\Models\Guide;
use App\Models\User;
use App\Services\Guides\GuideContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

final class GuideExamplesTest extends TestCase
{
    use RefreshDatabase;

    private function example(array $overrides = []): array
    {
        return ['type' => 'customBlock', 'attrs' => ['id' => 'guide-example', 'config' => array_replace([
            'title' => 'Калькулятор стоимости услуг',
            'description' => 'Форма, серверный расчёт и сохранение заявки.',
            'language' => 'PHP',
            'url' => 'https://github.com/Alexby8/botgate-examples/tree/main/examples/telegram-mini-app-service-calculator',
        ], $overrides)]];
    }

    public function test_example_is_optional_and_renders_after_intro_before_contents(): void
    {
        $guide = Guide::query()->firstOrFail();
        $this->get($guide->path())->assertOk()->assertDontSee('Пример на GitHub');
        $intro = $guide->intro;
        $intro['content'][] = $this->example();
        $guide->update(['intro' => $intro]);
        $response = $this->get($guide->path())->assertOk();
        $response->assertSeeInOrder([$intro['content'][0]['content'][0]['text'], 'Пример на GitHub', 'Оглавление']);
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//header//a[@class="guide-example"]');
        $this->assertSame(1, $links->length);
        $link = $links->item(0);
        $this->assertSame($this->example()['attrs']['config']['url'], $link->getAttribute('href'));
        $this->assertSame('_blank', $link->getAttribute('target'));
        $this->assertStringContainsString('noopener', $link->getAttribute('rel'));
        $this->assertSame('/images/github-mark.svg', $xpath->evaluate('string(//a[@class="guide-example"]//img/@src)'));
    }

    public function test_invalid_links_and_missing_content_are_rejected_and_not_rendered(): void
    {
        $guide = Guide::query()->firstOrFail();
        foreach ([
            ['url' => 'javascript:alert(1)'],
            ['url' => 'http://github.com/Alexby8/botgate-examples/tree/main'],
            ['url' => 'https://github.com.evil.example/Alexby8/botgate-examples/'],
            ['url' => 'https://github.com@evil.example/Alexby8/botgate-examples/'],
            ['url' => 'https://github.com/Alexby8/botgate-examples-other/tree/main'],
            ['url' => 'https://github.com/Alexby8/botgate-examples/../../another-repo'],
            ['url' => 'https://github.com/Alexby8/botgate-examples/tree/main-other/examples/test/php'],
            ['url' => 'https://github.com/Alexby8/botgate-examples/tree/main/examples/../another-example'],
            ['title' => ''], ['description' => null], ['language' => ''],
        ] as $override) {
            $node = $this->example($override);
            $data = $guide->export();
            $data['intro'] = ['type' => 'doc', 'content' => [$node]];
            try {
                app(GuideContent::class)->validate($data, $guide->id);
                $this->fail('Invalid example accepted: '.json_encode($override));
            } catch (ValidationException) {
                $this->assertSame('', GuideExampleBlock::toHtml($node['attrs']['config'], []));
            }
        }
    }

    public function test_text_is_escaped_in_public_render_and_editor_preview(): void
    {
        $node = $this->example([
            'title' => '<script>alert(1)</script>',
            'description' => '<img src=x onerror=alert(1)>',
            'language' => '<b>PHP</b>',
        ]);
        foreach ([
            GuideExampleBlock::toPreviewHtml($node['attrs']['config']),
            app(GuideContent::class)->render(['type' => 'doc', 'content' => [$node]]),
        ] as $html) {
            $this->assertStringNotContainsString('<script>', $html);
            $this->assertStringNotContainsString('<img src=x', $html);
            $this->assertStringNotContainsString('<b>PHP</b>', $html);
            $this->assertStringContainsString('&lt;script&gt;', $html);
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new \DOMXPath($document);
            $this->assertSame('<img src=x onerror=alert(1)>', $xpath->evaluate('string(//span[@class="guide-example-description"])'));
        }
    }

    public function test_pinned_example_links_remain_supported(): void
    {
        $guide = Guide::query()->firstOrFail();
        $url = 'https://github.com/Alexby8/botgate-examples/tree/0a8ef3505a83641106dd1f6e382fb279b64b3fad/examples/telegram-mini-app-service-calculator/php';
        $guide->update(['intro' => ['type' => 'doc', 'content' => [$this->example(['url' => $url])]]]);
        $this->get($guide->path())->assertOk()->assertSee('href="'.$url.'"', false);
    }

    public function test_admin_save_export_and_repeat_import_preserve_example(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $guide = Guide::query()->firstOrFail();
        $document = $guide->intro;
        $document['content'][] = $this->example();
        Livewire::test(EditGuide::class, ['record' => $guide->id])
            ->fillForm(['intro' => $document])->call('save')->assertHasNoFormErrors();
        $this->assertSame($document, $guide->fresh()->intro);
        $path = tempnam(sys_get_temp_dir(), 'guide-example-');
        try {
            $this->artisan('guides:export', ['file' => $path, '--slug' => $guide->slug])->assertSuccessful();
            $export = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame($document, $export[0]['intro']);
            $this->artisan('guides:import', ['file' => $path])->expectsOutput('Imported: 1; changed: 0.')->assertSuccessful();
        } finally {
            unlink($path);
        }
    }
}
