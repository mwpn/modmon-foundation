<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class LoginController
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('identity::auth.login');
    }

    /**
     * Authenticate the user via the session guard.
     *
     * Rate-limited to 5 attempts per minute, then the session is
     * regenerated to prevent session fixation (Laravel 13 security
     * requirements, proposal §18.3/§18.5).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->homeRoute());
    }

    /**
     * The authenticated redirect target: the host's dashboard route when
     * it exists, otherwise the app root (portable host fallback).
     */
    private function homeRoute(): string
    {
        if (Route::has('dashboard')) {
            return route('dashboard');
        }

        return '/';
    }
}
