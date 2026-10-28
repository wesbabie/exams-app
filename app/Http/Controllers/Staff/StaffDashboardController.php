<?php

namespace App\Http\Controllers\Staff;

use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $role = StaffRole::tryFrom((string) $user->role);

        abort_unless($role && $user->is_active, 403, 'You do not have access to this page.');

        return view('staff.dashboard', ['user' => $user, 'role' => $role]);
    }
}
