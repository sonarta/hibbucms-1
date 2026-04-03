<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('auth/two-factor-challenge');
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = $request->session()->get('two_factor_login.id');
        $remember = (bool) $request->session()->get('two_factor_login.remember', false);

        $user = User::find($userId);
        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['two_factor_login.id', 'two_factor_login.remember']);

            return redirect()->route('login');
        }

        $code = $request->string('code')->toString();

        $valid = $twoFactor->verifyTotp($user->two_factor_secret, $code)
            || $twoFactor->consumeRecoveryCode($user, $code);

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => __('The provided code is invalid.'),
            ]);
        }

        $request->session()->forget(['two_factor_login.id', 'two_factor_login.remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }
}
