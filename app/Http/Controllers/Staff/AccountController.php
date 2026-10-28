<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.password', ['forced' => (bool) $request->user()->must_change_password]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(10)->mixedCase()->numbers()],
        ], [
            'current_password.current_password' => 'The current password is not correct.',
            'password.different' => 'The new password must be different from the current one.',
        ]);

        $user = $request->user();
        $user->forceFill(['password' => Hash::make($data['password']), 'must_change_password' => false])->save();

        StaffLog::record($user->id, 'password_changed');
        $request->session()->regenerate();

        return redirect()->route('staff.dashboard')->with('status', 'Your password has been changed.');
    }
}
