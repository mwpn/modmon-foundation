<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController
{
    /**
     * Show the password reset request form.
     */
    public function showForgotPasswordForm(): View
    {
        return view('identity::auth.forgot-password');
    }

    /**
     * Send a password reset link (rate-limited, proposal §18.3/§18.4).
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)])->onlyInput('email');
    }

    /**
     * Show the reset form.
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('identity::auth.reset-password', ['token' => $token]);
    }

    /**
     * Apply the new password (proposal §18.4).
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('identity.login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]])->onlyInput('email');
    }
}
