<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AnalyticsGoal;
use App\Enums\ProxyNodeStatus;
use App\Models\Bot;
use App\Models\ProxyNode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AnalyticsGoalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_is_emitted_only_after_success_and_consumed_by_the_success_page(): void
    {
        Notification::fake();
        $response = $this->post('/register', [
            'email' => 'goal@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'terms' => '1',
        ])->assertRedirect('/email/verify')->assertSessionHas('metrika_goal.name', 'register');
        $this->assertTrue(Str::isUuid($response->getSession()->get('metrika_goal.id')));
        $this->app['env'] = 'production';
        $this->get('/email/verify')->assertOk()->assertSee('data-goal="register"', false)
            ->assertSee('data-metrika-id="109789253"', false)
            ->assertSee('data-path="/email/verify"', false);
        $this->get('/email/verify')->assertOk()->assertDontSee('data-metrika-goal', false);
    }

    public function test_failed_registration_does_not_emit_a_goal(): void
    {
        $this->post('/register', ['email' => 'invalid'])->assertSessionHasErrors()->assertSessionMissing('metrika_goal');
        $this->get('/register')->assertOk()->assertDontSee('data-metrika-goal', false);
    }

    public function test_verification_goal_is_emitted_once_after_a_valid_confirmation(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($url)->assertRedirect('/dashboard')->assertSessionHas('metrika_goal.name', 'register_final');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get('/dashboard')->assertOk()->assertSee('data-goal="register_final"', false)
            ->assertSee('data-path="/dashboard"', false);
        $this->get($url)->assertRedirect('/dashboard')->assertSessionMissing('metrika_goal');
        $this->get('/dashboard')->assertOk()->assertDontSee('data-metrika-goal', false);
    }

    public function test_invalid_confirmation_and_resending_email_do_not_emit_goals(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get('/email/verify/'.$user->id.'/wrong?signature=wrong')
            ->assertForbidden()->assertSessionMissing('metrika_goal');
        $this->post('/email/verification-notification')->assertRedirect()->assertSessionMissing('metrika_goal');
    }

    public function test_successful_bot_creation_emits_a_goal_without_exposing_bot_identifiers(): void
    {
        $user = User::factory()->create();
        ProxyNode::factory()->create(['status' => ProxyNodeStatus::Online, 'enabled' => true]);
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['ok' => true, 'result' => ['id' => 123, 'username' => 'testbot', 'url' => '']])]);
        $response = $this->actingAs($user)->post('/bots', ['name' => 'Private Bot', 'token' => 'fake-test-token'])
            ->assertRedirect()->assertSessionHas('metrika_goal.name', 'bot_create');
        $bot = Bot::query()->sole();
        $page = $this->get($response->headers->get('Location'))->assertOk()->assertSee('data-goal="bot_create"', false)
            ->assertSee('data-path="/bots/created"', false);
        preg_match('/<div hidden data-metrika-goal[\s\S]*?<\/div>/', $page->getContent(), $marker);
        $this->assertNotEmpty($marker);
        $this->assertStringNotContainsString($bot->public_id, $marker[0]);
        $this->assertStringNotContainsString('fake-test-token', $marker[0]);
        $this->assertStringNotContainsString('Private Bot', $marker[0]);
        $this->get($response->headers->get('Location'))->assertOk()->assertDontSee('data-metrika-goal', false);
    }

    public function test_rejected_bot_creation_and_edits_do_not_emit_goals(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/bots', ['name' => 'Test Bot', 'token' => 'invalid'])
            ->assertRedirect()->assertSessionHasErrors('token')->assertSessionMissing('metrika_goal');
        $bot = $user->bots()->create([
            'public_id' => 'bot_edit_goal', 'name' => 'Existing Bot', 'telegram_token_encrypted' => 'fake-token',
            'telegram_token_hash' => hash('sha256', 'fake-token'),
        ]);
        $this->put('/bots/'.$bot->public_id, ['name' => 'Renamed'])->assertRedirect()->assertSessionMissing('metrika_goal');
    }

    public function test_counter_is_disabled_on_dev_and_ordinary_private_pages(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['metrika_goal' => AnalyticsGoal::RegisterFinal->event()])
            ->get('/dashboard')->assertOk()->assertSee('data-metrika-id=""', false);
        $this->app['env'] = 'production';
        $this->get('/dashboard')->assertOk()->assertDontSee('data-metrika-goal', false);
        $this->get('/bots/create')->assertOk()->assertDontSee('data-metrika-goal', false);
    }

    public function test_saved_refusal_discards_the_goal_without_rendering_a_counter(): void
    {
        $user = User::factory()->create();
        $this->app['env'] = 'production';
        $this->actingAs($user)->withCookie(config('cookie-consent.cookie'), json_encode([
            'id' => (string) Str::uuid(), 'choice' => 'declined', 'version' => 'old',
            'expiresAt' => now()->addDay()->getTimestampMs(),
        ], JSON_THROW_ON_ERROR))->withSession(['metrika_goal' => AnalyticsGoal::RegisterFinal->event()])
            ->get('/dashboard')->assertOk()->assertDontSee('data-metrika-goal', false);
        $this->assertNull(session('metrika_goal'));
    }

    public function test_query_parameters_cannot_trigger_a_goal_and_unknown_flash_events_are_ignored(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard?goal=register_final')->assertOk()->assertDontSee('data-metrika-goal', false);
        $this->withSession(['metrika_goal' => ['id' => (string) Str::uuid(), 'name' => 'unknown']])
            ->get('/dashboard')->assertOk()->assertDontSee('data-metrika-goal', false);
        $this->withSession(['metrika_goal' => AnalyticsGoal::BotCreate->event()])
            ->get('/dashboard')->assertOk()->assertDontSee('data-metrika-goal', false);
    }
}
