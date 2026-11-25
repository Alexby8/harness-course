<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class PublicLegalInformationTest extends TestCase
{
    public function test_home_page_explains_where_data_is_stored_and_how_requests_are_processed(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Постоянные данные, настройки и зашифрованные токены хранятся в России.')
            ->assertSee('Зарубежные узлы используются только для передачи запросов в Telegram');
    }

    public function test_registration_links_all_legal_documents(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('/documents/BotGate_User_Agreement.pdf', false)
            ->assertSee('/documents/BotGate_Privacy_Policy.pdf', false)
            ->assertSee('/documents/BotGate_Consent_Personal_Data.pdf', false);
    }
}
