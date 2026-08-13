<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController
{
    /**
     * Log the user out and invalidate the session (proposal §18.5).
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('identity.login');
    }
}
