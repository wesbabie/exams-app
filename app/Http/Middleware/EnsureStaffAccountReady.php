<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signed-in staff must have an active account, and must set their own password
 * before doing anything else if an admin created the account or reset the password.
 */
class EnsureStaffAccountReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account has been deactivated.']);
        }

        if ($user && $user->must_change_password && ! $request->routeIs('account.password.*', 'logout')) {
            return redirect()->route('account.password.edit');
        }

        return $next($request);
    }
}
