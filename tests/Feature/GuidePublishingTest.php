<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\GuideResource\Pages\CreateGuide;
use App\Filament\Resources\GuideResource\Pages\EditGuide;
use App\Models\Guide;
use App\Models\User;
use App\Services\Guides\GuideContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

final class GuidePublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_preserves_all_articles_text_code_links_and_anchors(): void
    {
        $baseline = json_decode(file_get_contents(base_path('tests/Fixtures/guides-baseline.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->assertDatabaseCount('guides', 14);
        foreach ($baseline as $slug => $expected) {
            $guide = Guide::query()->where('slug', $slug)->firstOrFail();
            app(GuideContent::class)->validate($guide->export(), $guide->id);
            $this->assertSame($expected['title'], $guide->title);
            $this->assertSame($expected['description'], $guide->description);
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$this->get($guide->path())->assertOk()->getContent());
            $xpath = new \DOMXPath($document);
            $text = $xpath->evaluate('string(//article)');
            $this->assertSame(preg_replace('/\s+/u', '', $expected['text']), preg_replace('/\s+/u', '', trim($text)), $slug.' text');
            $codes = [];
            foreach ($xpath->query('//article//pre') as $pre) {
                $codes[] = $pre->textContent;
            }
            $this->assertSame($expected['code'], $codes, $slug.' code');
            $links = [];
            foreach ($xpath->query('//article//a') as $link) {
                $links[] = [$link->getAttribute('href'), trim($link->textContent)];
            }
            $this->assertSame($expected['links'], $links, $slug.' links');
            $this->assertSame($expected['toc'], array_column($guide->sections, 'label', 'id'));
            foreach ($expected['toc'] as $id => $label) {
                $this->assertSame(1.0, $xpath->evaluate("count(//article/section[@id='$id'])"));
            }
        }
    }

    public function test_visibility_updates_page_catalog_and_sitemap_immediately(): void
    {
        $guide = Guide::query()->where('slug', 'telegram-bot-api-timeout')->firstOrFail();
        $guide->update(['is_published' => false]);
        $this->get($guide->path())->assertNotFound();
        $this->get('/guides')->assertOk()->assertDontSee($guide->url(), false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee($guide->path(), false);
        $guide->update(['is_published' => true]);
        $this->get($guide->path())->assertOk();
        Guide::query()->update(['is_published' => false]);
        $this->get('/guides')->assertOk();
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/guides/');
        $this->get('/guides/does-not-exist')->assertNotFound();
    }

    public function test_content_date_only_changes_with_content_and_metadata_is_escaped(): void
    {
        config(['app.url' => 'https://bot-gate.ru']);
        $guide = Guide::query()->firstOrFail();
        $this->assertNull($guide->content_updated_at);
        $guide->update(['sort_order' => 42]);
        $this->assertNull($guide->fresh()->content_updated_at);
        $guide->update(['title' => 'Title " <script>alert(1)</script>', 'description' => '" onload="bad']);
        $date = $guide->fresh()->content_updated_at;
        $this->get($guide->path())->assertOk()->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('<lastmod>'.$date->toAtomString().'</lastmod>', false);
        $this->travel(1)->hours();
        $guide->save();
        $this->assertEquals($date, $guide->fresh()->content_updated_at);
    }

    public function test_import_is_repeatable_atomic_and_export_round_trips(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guides-');
        try {
            $this->artisan('guides:export', ['file' => $path])->assertSuccessful();
            $this->artisan('guides:import', ['file' => $path, '--dry-run' => true])->expectsOutput('Validated: 14; changed: 0.')->assertSuccessful();
            $this->artisan('guides:import', ['file' => $path])->expectsOutput('Imported: 14; changed: 0.')->assertSuccessful();
            $items = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            foreach ($items as $item) {
                foreach (['is_featured', 'related_order', 'related_title'] as $field) {
                    $this->assertArrayNotHasKey($field, $item);
                }
            }
            $legacy = json_decode(file_get_contents(database_path('data/guides.json')), true, 512, JSON_THROW_ON_ERROR);
            file_put_contents($path, json_encode($legacy, JSON_THROW_ON_ERROR));
            $this->artisan('guides:import', ['file' => $path])->expectsOutput('Imported: 14; changed: 0.')->assertSuccessful();
            $items[0]['title'] = 'Imported title';
            $items[1]['sections'][0]['id'] = 'bad anchor';
            file_put_contents($path, json_encode($items, JSON_THROW_ON_ERROR));
            $this->artisan('guides:import', ['file' => $path])->assertFailed();
            $this->assertDatabaseMissing('guides', ['title' => 'Imported title']);
            $new = $items[0];
            $new['slug'] = 'new-guide';
            file_put_contents($path, json_encode([$new], JSON_THROW_ON_ERROR));
            $this->artisan('guides:import', ['file' => $path, '--dry-run' => true])->assertSuccessful();
            $this->assertDatabaseMissing('guides', ['slug' => 'new-guide']);
            $this->artisan('guides:import', ['file' => $path])->assertSuccessful();
            $this->get('/guides/new-guide')->assertOk()->assertSee('Imported title');
            $this->assertDatabaseCount('guides', 15);
            $this->get('/sitemap.xml')->assertSee('/guides/new-guide');
        } finally {
            unlink($path);
        }
    }

    public function test_admin_can_create_and_edit_without_a_publication_workflow(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $original = Guide::query()->where('slug', 'telegram-bot-api-timeout')->firstOrFail();
        $data = $original->export();
        $data['slug'] = 'created-in-admin';
        $data['title'] = 'Created in admin';
        Livewire::test(CreateGuide::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $guide = Guide::query()->where('slug', $data['slug'])->firstOrFail();
        $this->get($guide->path())->assertOk()->assertSee('Created in admin');
        Livewire::test(EditGuide::class, ['record' => $guide->id])
            ->assertFormFieldDoesNotExist('is_featured')
            ->assertFormFieldDoesNotExist('related_order')
            ->assertFormFieldDoesNotExist('related_title')
            ->fillForm(['title' => 'Changed in admin'])->call('save')->assertHasNoFormErrors();
        $this->get($guide->path())->assertOk()->assertSee('Changed in admin');
        $this->assertSame($original->sections, $guide->fresh()->sections);
    }

    public function test_recommendation_fields_migration_preserves_existing_articles(): void
    {
        $before = Guide::query()->get()->map->export()->all();
        $migration = require database_path('migrations/2026_10_07_130000_remove_guide_recommendation_fields.php');
        foreach (['is_featured', 'related_order', 'related_title'] as $field) {
            $this->assertFalse(Schema::hasColumn('guides', $field));
        }
        $migration->down();
        foreach (['is_featured', 'related_order', 'related_title'] as $field) {
            $this->assertTrue(Schema::hasColumn('guides', $field));
        }
        DB::table('guides')->update([
            'is_featured' => true, 'related_order' => 42, 'related_title' => 'Legacy title',
        ]);
        $migration->up();
        $this->assertSame($before, Guide::query()->get()->map->export()->all());
        foreach (['is_featured', 'related_order', 'related_title'] as $field) {
            $this->assertFalse(Schema::hasColumn('guides', $field));
        }
    }

    public function test_regular_user_cannot_manage_articles(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $guide = Guide::query()->firstOrFail();
        $this->actingAs($user)->get('/admin/guides')->assertForbidden();
        $this->assertFalse(Gate::forUser($user)->allows('update', $guide));
        Livewire::test(EditGuide::class, ['record' => $guide->id])->assertForbidden();
    }

    public function test_invalid_structure_and_slug_changes_are_rejected(): void
    {
        $guide = Guide::query()->firstOrFail();
        $this->expectException(ValidationException::class);
        $guide->update(['slug' => 'changed-url']);
    }

    public function test_content_cannot_execute_template_code_or_javascript(): void
    {
        $document = ['type' => 'doc', 'content' => [
            ['type' => 'customBlock', 'attrs' => ['id' => 'guide-code', 'config' => ['label' => '<img src=x onerror=alert(1)>', 'code' => '{{ config("app.key") }} <script>alert(1)</script>']]],
            ['type' => 'customBlock', 'attrs' => ['id' => 'guide-note', 'config' => ['highlighted' => false, 'content' => '<script>alert(1)</script><a href="javascript:alert(1)">link</a>']]],
        ]];
        $html = app(GuideContent::class)->render($document);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('{{ config(', $html);
    }
}
