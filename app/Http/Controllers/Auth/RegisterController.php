<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\AnalyticsGoal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AcquisitionTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class RegisterController extends Controller
{
    public function showForm(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request, AcquisitionTracker $acquisition): RedirectResponse
    {
        $user = DB::transaction(function () use ($request, $acquisition): User {
            $user = User::create([
                'email' => $request->email,
                'password' => $request->password,
            ]);
            $acquisition->attach($request, $user);

            return $user;
        });

        Auth::login($user);

        $user->sendEmailVerificationNotification();

        $response = redirect()->route('verification.notice')->with('metrika_goal', AnalyticsGoal::Register->event());
        $acquisition->forgetCookies($request, $response);

        return $response;
    }
}
