<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Forms\GuideImageBlock;
use App\Filament\Resources\GuideResource\Pages\EditGuide;
use App\Models\Guide;
use App\Models\User;
use App\Services\Guides\GuideContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

final class GuideImagesTest extends TestCase
{
    use RefreshDatabase;

    private function image(array $overrides = []): array
    {
        return ['type' => 'customBlock', 'attrs' => ['id' => 'guide-image', 'config' => array_replace([
            'src' => '/images/guides/test-guide/states.jpg', 'alt' => 'Карточка заказа',
            'caption' => 'Макет из примера', 'width' => 1080, 'height' => 528,
            'mobile_src' => '/images/guides/test-guide/states-mobile.jpg',
            'mobile_width' => 390, 'mobile_height' => 1100,
        ], $overrides)]];
    }

    public function test_image_renders_responsive_sources_dimensions_and_caption_without_links(): void
    {
        $guide = Guide::query()->firstOrFail();
        $guide->update(['preamble' => ['type' => 'doc', 'content' => [$this->image()]]]);
        $html = $this->get($guide->path())->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $image = $xpath->query('//figure//img')->item(0);
        $this->assertSame('Карточка заказа', $image->getAttribute('alt'));
        $this->assertSame('1080', $image->getAttribute('width'));
        $this->assertSame('528', $image->getAttribute('height'));
        $this->assertSame('lazy', $image->getAttribute('loading'));
        $mobile = $xpath->query('//figure/div[contains(@class,"guide-illustration-mobile")]/img')->item(0);
        $this->assertSame('/images/guides/test-guide/states-mobile.jpg', $mobile->getAttribute('src'));
        $this->assertSame('390', $mobile->getAttribute('width'));
        $this->assertSame('1100', $mobile->getAttribute('height'));
        $this->assertSame('Макет из примера', $xpath->evaluate('string(//figure/figcaption)'));
        $this->assertSame(0, $xpath->query('//figure//a')->length);
    }

    public function test_invalid_paths_and_incomplete_mobile_variant_are_rejected(): void
    {
        $guide = Guide::query()->firstOrFail();
        $cases = [
            ['src' => 'https://outside.example/image.jpg'], ['src' => '//outside.example/image.jpg'],
            ['src' => 'javascript:alert(1)'], ['src' => '/images/guides/a/../x.jpg'],
            ['src' => '/images/guides/a/x.svg'], ['src' => '/images/guides/a/x.jpg?x=1'],
            ['src' => '/images/guides/a/x.jpg" onerror="alert(1)'],
            ['alt' => ''], ['width' => 0], ['height' => 99999],
            ['mobile_height' => null], ['mobile_src' => null],
        ];
        foreach ($cases as $override) {
            $data = $guide->export();
            $data['preamble'] = ['type' => 'doc', 'content' => [$this->image($override)]];
            try {
                app(GuideContent::class)->validate($data, $guide->id);
                $this->fail('Invalid image accepted: '.json_encode($override));
            } catch (ValidationException) {
                $this->assertSame('', GuideImageBlock::toHtml($this->image($override)['attrs']['config'], []));
            }
        }
    }

    public function test_text_is_escaped_and_a_mobile_variant_is_optional(): void
    {
        $config = $this->image([
            'alt' => '"><script>alert(1)</script>', 'caption' => '<img src=x onerror=alert(1)>',
            'mobile_src' => null, 'mobile_width' => null, 'mobile_height' => null,
        ])['attrs']['config'];
        $html = GuideImageBlock::toPreviewHtml($config);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<source', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img src=x', $html);
    }

    public function test_admin_save_export_and_repeat_import_preserve_image_config(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $guide = Guide::query()->firstOrFail();
        $document = ['type' => 'doc', 'content' => [$this->image()]];
        Livewire::test(EditGuide::class, ['record' => $guide->id])
            ->fillForm(['preamble' => $document])->call('save')->assertHasNoFormErrors();
        $this->assertSame($document, $guide->fresh()->preamble);
        $path = tempnam(sys_get_temp_dir(), 'guide-image-');
        try {
            $this->artisan('guides:export', ['file' => $path, '--slug' => $guide->slug])->assertSuccessful();
            $export = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame($document, $export[0]['preamble']);
            $this->artisan('guides:import', ['file' => $path])->expectsOutput('Imported: 1; changed: 0.')->assertSuccessful();
        } finally {
            unlink($path);
        }
    }
}
