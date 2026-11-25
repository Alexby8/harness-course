<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Profile\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function show(): View
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function changePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        return redirect()
            ->route('profile.show')
            ->with('success', 'Пароль успешно изменён.');
    }
}
