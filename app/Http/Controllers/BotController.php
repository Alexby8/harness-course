<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AnalyticsGoal;
use App\Http\Requests\Bot\CreateBotRequest;
use App\Http\Requests\Bot\UpdateBotRequest;
use App\Models\Bot;
use App\Models\User;
use App\Services\BotService;
use App\Services\TelegramService;
use App\Services\TelegramWebhookUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class BotController extends Controller
{
    public function __construct(
        private readonly BotService $botService,
        private readonly TelegramService $telegram,
        private readonly TelegramWebhookUrl $webhookUrls,
    ) {}

    public function create(): View
    {
        return view('bots.create');
    }

    public function store(CreateBotRequest $request): RedirectResponse
    {
        try {
            ['bot' => $bot] = $this->botService->create($this->currentUser(), $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['token' => $e->getMessage()]);
        }

        return redirect()
            ->route('bots.show', $bot->public_id)
            ->with('metrika_goal', AnalyticsGoal::BotCreate->event())
            ->with('success', 'Бот успешно добавлен.');
    }

    public function show(string $publicId): View
    {
        $bot = $this->findOwnBot($publicId);

        $telegramWebhook = $this->telegram->getWebhookInfo($bot->telegram_token_encrypted);
        $webhookStatus = $this->resolveWebhookStatus($bot, $telegramWebhook);

        return view('bots.show', compact('bot', 'telegramWebhook', 'webhookStatus'));
    }

    public function activateWebhook(Request $request, string $publicId): RedirectResponse
    {
        $request->validate([
            'webhook_url' => ['required', 'url', 'max:500', 'starts_with:https://'],
        ]);

        $bot = $this->findOwnBot($publicId);

        try {
            $this->botService->activateWebhook($bot, $request->string('webhook_url')->toString());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Вебхук подключён. Telegram будет отправлять обновления через BotGate.');
    }

    public function deactivateWebhook(string $publicId): RedirectResponse
    {
        $bot = $this->findOwnBot($publicId);

        $this->botService->deactivateWebhook($bot);

        return back()->with('success', 'Вебхук отключён.');
    }

    public function resyncWebhook(string $publicId): RedirectResponse
    {
        $bot = $this->findOwnBot($publicId);

        if (empty($bot->webhook_url)) {
            return back()->with('error', 'URL доставки не задан — нечего синхронизировать.');
        }

        try {
            $this->botService->registerTelegramWebhook($bot, $bot->telegram_token_encrypted);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Вебхук пересинхронизирован с Telegram.');
    }

    /** @param array<string, mixed>|null $telegramWebhook */
    private function resolveWebhookStatus(Bot $bot, ?array $telegramWebhook): string
    {
        if ($telegramWebhook === null) {
            return 'unavailable';
        }

        $telegramUrl = is_string($telegramWebhook['url'] ?? null) ? $telegramWebhook['url'] : '';
        $isRegistered = $telegramUrl !== '';
        $isOurs = $isRegistered && $this->webhookUrls->isManaged($bot, $telegramUrl);
        $hasTarget = ! empty($bot->webhook_url);

        if (! $isRegistered) {
            return $hasTarget ? 'not_registered' : 'none';
        }

        if ($isOurs) {
            return $hasTarget ? 'active' : 'stale_registered';
        }

        return $hasTarget ? 'conflict' : 'foreign';
    }

    public function edit(string $publicId): View
    {
        $bot = $this->findOwnBot($publicId);

        return view('bots.edit', compact('bot'));
    }

    public function update(UpdateBotRequest $request, string $publicId): RedirectResponse
    {
        $bot = $this->findOwnBot($publicId);

        try {
            $this->botService->update($bot, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['token' => $e->getMessage()]);
        }

        return redirect()
            ->route('bots.show', $bot->public_id)
            ->with('success', 'Бот обновлён.');
    }

    public function disable(string $publicId): RedirectResponse
    {
        $bot = $this->findOwnBot($publicId);
        $this->botService->disable($bot);

        return back()->with('success', 'Бот отключён.');
    }

    public function enable(string $publicId): RedirectResponse
    {
        $bot = $this->findOwnBot($publicId);
        $this->botService->enable($bot);

        return back()->with('success', 'Бот включён.');
    }

    public function destroy(string $publicId): RedirectResponse
    {
        $bot = $this->findOwnBot($publicId);
        $bot->delete();

        return redirect()->route('dashboard')->with('success', 'Бот удалён.');
    }

    private function findOwnBot(string $publicId): Bot
    {
        return $this->currentUser()->bots()->where('public_id', $publicId)->firstOrFail();
    }

    private function currentUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
