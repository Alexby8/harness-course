<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\AnalyticsGoal;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class EmailVerificationController extends Controller
{
    public function notice(): View|RedirectResponse
    {
        if ($this->currentUser()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($this->currentUser()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->fulfill();

        return redirect()->route('dashboard')
            ->with('success', 'Email успешно подтверждён!')
            ->with('metrika_goal', AnalyticsGoal::RegisterFinal->event());
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->currentUser();
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'Письмо с подтверждением отправлено повторно.');
    }

    private function currentUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
