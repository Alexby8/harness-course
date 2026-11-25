<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class DocumentationMetadataTest extends TestCase
{
    public function test_documentation_metadata_uses_the_configured_canonical_origin(): void
    {
        config(['app.url' => 'https://bot-gate.ru/']);

        $this->get('https://www.bot-gate.ru/docs?utm_source=test')
            ->assertOk()
            ->assertSee('<title>Документация Telegram Bot API: подключение, SDK и webhook — BotGate</title>', false)
            ->assertSee('<meta name="description" content="Как подключить Telegram-бота к BotGate:', false)
            ->assertSee('<link rel="canonical" href="https://bot-gate.ru/docs">', false)
            ->assertSee('<meta property="og:url" content="https://bot-gate.ru/docs">', false)
            ->assertSee('<meta property="og:image" content="https://bot-gate.ru/images/og-image.png">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta name="robots" content="index, follow">', false);
    }
}
