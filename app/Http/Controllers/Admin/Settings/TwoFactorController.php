<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    public function edit(Request $request, TwoFactorService $twoFactor): Response
    {
        $user = $request->user();
        $codes = is_array($user->two_factor_recovery_codes) ? $user->two_factor_recovery_codes : [];

        $twoFactorSetup = $request->session()->pull('twoFactorSetup');
        if ($twoFactorSetup === null && $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null) {
            $twoFactorSetup = [
                'qrSvg' => $twoFactor->qrCodeSvg($user->two_factor_secret, $user),
                'secret' => $user->two_factor_secret,
            ];
        }

        return Inertia::render('Admin/Settings/Security', [
            'twoFactorEnabled' => $user->hasTwoFactorEnabled(),
            'awaitingConfirmation' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
            'recoveryCodesRemaining' => count($codes),
            'twoFactorSetup' => $twoFactorSetup,
            'recoveryCodesFlash' => $request->session()->pull('recoveryCodes'),
        ]);
    }

    public function start(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $user = $request->user();

        $secret = $twoFactor->generateSecretKey();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        $qrSvg = $twoFactor->qrCodeSvg($secret, $user);

        return redirect()->route('admin.settings.security')->with('twoFactorSetup', [
            'qrSvg' => $qrSvg,
            'secret' => $secret,
        ]);
    }

    public function confirm(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if ($user->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'code' => __('Start the setup again before confirming.'),
            ]);
        }

        if (! $twoFactor->verifyTotp($user->two_factor_secret, $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => __('The provided code is invalid.'),
            ]);
        }

        $plainCodes = $twoFactor->generatePlainRecoveryCodes();
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = $twoFactor->hashRecoveryCodes($plainCodes);
        $user->save();

        return redirect()->route('admin.settings.security')->with('recoveryCodes', $plainCodes);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('admin.settings.security');
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages([
                'password' => __('Two-factor authentication is not enabled.'),
            ]);
        }

        $plainCodes = $twoFactor->generatePlainRecoveryCodes();
        $user->two_factor_recovery_codes = $twoFactor->hashRecoveryCodes($plainCodes);
        $user->save();

        return redirect()->route('admin.settings.security')->with('recoveryCodes', $plainCodes);
    }

    public function cancelSetup(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.settings.security');
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return redirect()->route('admin.settings.security');
    }
}
