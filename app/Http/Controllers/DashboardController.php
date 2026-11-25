<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {}

    public function index(): View
    {
        $user = $this->currentUser();
        $bots = $user->bots()->orderByDesc('created_at')->get();
        $notification = $user->activeNotifications()->first();

        return view('dashboard.index', compact('user', 'bots', 'notification'));
    }

    public function dismissNotification(int $notificationId): RedirectResponse
    {
        $user = $this->currentUser();

        $user->notifications()
            ->wherePivot('notification_id', $notificationId)
            ->updateExistingPivot($notificationId, ['is_read' => true]);

        return redirect()->route('dashboard');
    }

    public function apiKey(): View
    {
        return view('dashboard.api-key', ['user' => $this->currentUser()]);
    }

    public function reissueApiKey(): RedirectResponse
    {
        $user = $this->currentUser();
        $apiKey = $this->apiKeyService->issueForUser($user);

        return redirect()
            ->route('dashboard.api-key')
            ->with('new_api_key', $apiKey)
            ->with('success', 'Новый API ключ сгенерирован — скопируйте его сейчас, он больше не будет показан.');
    }

    private function currentUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
