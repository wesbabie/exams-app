<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use App\Models\StaffLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffAccountController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->query('role');
        $status = $request->query('status', 'all');
        $term = trim((string) $request->query('q'));

        $users = User::query()
            ->when($role, fn ($q) => $role === 'none' ? $q->whereNull('role') : $q->where('role', $role))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('file_number', 'like', '%'.strtoupper($term).'%')))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.staff.index', [
            'users' => $users,
            'status' => $status,
            'log' => StaffLog::with(['actor', 'target'])->latest('id')->limit(15)->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.form', ['staff' => new User(['is_active' => true]), 'mode' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $temporary = $this->temporaryPassword();

        $user = new User();
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'file_number' => $data['file_number'],
            'password' => Hash::make($temporary),
            'is_active' => true,
            'must_change_password' => true,
            'email_verified_at' => now(),
        ])->save();

        StaffLog::record($user->id, 'created', "{$user->name} as ".StaffRole::from($data['role'])->label());

        return $this->flashCredentials($user, $temporary, 'created');
    }

    /** Shown once, straight after an account is created or a password is reset. */
    public function showCredentials(): RedirectResponse|View
    {
        $credentials = session('credentials');

        return $credentials
            ? view('admin.staff.credentials', compact('credentials'))
            : redirect()->route('admin.staff.index')->with('error', 'The temporary password is only shown once, straight after it is created. Use "Reset password" to issue a new one.');
    }

    public function edit(Request $request, User $staff): View
    {
        return view('admin.staff.form', ['staff' => $staff, 'mode' => 'edit', 'isSelf' => $staff->id === $request->user()->id]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $isSelf = $staff->id === $request->user()->id;
        $data = $this->validated($request, $staff);

        // You cannot change your own role or switch yourself off (that could lock everyone out)
        if ($isSelf) {
            $data['role'] = $staff->role;
            $active = (bool) $staff->is_active;
        } else {
            $active = $request->boolean('is_active');
        }

        $this->guardLastSuperAdmin($staff, $data['role'], $active);

        $changes = [];
        foreach (['name' => 'name', 'email' => 'email', 'role' => 'role', 'file_number' => 'file number'] as $field => $label) {
            if ((string) $staff->{$field} !== (string) $data[$field]) {
                $changes[] = "{$label}: ".($staff->{$field} ?: '–').' → '.($data[$field] ?: '–');
            }
        }
        if ((bool) $staff->is_active !== $active) {
            $changes[] = $active ? 'reactivated' : 'deactivated';
        }

        $staff->forceFill($data + ['is_active' => $active])->save();

        if (! $active) {
            $this->endSessions($staff);
        }
        if ($changes) {
            StaffLog::record($staff->id, 'updated', implode('; ', $changes));
        }

        return redirect()->route('admin.staff.index')->with('status', "{$staff->name} saved.");
    }

    public function deactivate(Request $request, User $staff): RedirectResponse
    {
        if ($staff->id === $request->user()->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }
        $this->guardLastSuperAdmin($staff, $staff->role, false);

        $staff->forceFill(['is_active' => false])->save();
        $this->endSessions($staff);
        StaffLog::record($staff->id, 'deactivated', $staff->name);

        return back()->with('status', "{$staff->name} deactivated and signed out.");
    }

    public function reactivate(User $staff): RedirectResponse
    {
        $staff->forceFill(['is_active' => true])->save();
        StaffLog::record($staff->id, 'reactivated', $staff->name);

        return back()->with('status', "{$staff->name} reactivated.");
    }

    public function resetPassword(Request $request, User $staff): RedirectResponse
    {
        if ($staff->id === $request->user()->id) {
            return back()->with('error', 'To change your own password, use your account page (click your name at the top).');
        }

        $temporary = $this->temporaryPassword();
        $staff->forceFill(['password' => Hash::make($temporary), 'must_change_password' => true])->save();
        $this->endSessions($staff);
        StaffLog::record($staff->id, 'password_reset', $staff->name);

        return $this->flashCredentials($staff, $temporary, 'reset');
    }

    // ---- Helpers ----

    private function flashCredentials(User $user, string $temporary, string $context): RedirectResponse
    {
        return redirect()->route('admin.staff.credentials')->with('credentials', [
            'name' => $user->name, 'email' => $user->email, 'password' => $temporary, 'context' => $context,
        ]);
    }


    private function validated(Request $request, ?User $staff = null): array
    {
        $request->merge(['file_number' => strtoupper(trim((string) $request->input('file_number')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff?->id)],
            'role' => ['required', Rule::in(array_map(fn ($r) => $r->value, StaffRole::cases()))],
            'file_number' => ['nullable', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9\/\-\. ]*$/', Rule::unique('users', 'file_number')->ignore($staff?->id)],
        ], [
            'email.unique' => 'Another account already uses this email address.',
            'file_number.unique' => 'Another account already has this file number.',
            'file_number.regex' => 'That file number has characters that are not allowed.',
        ]);

        $data['file_number'] = $data['file_number'] ?: null;

        return $data;
    }

    /** There must always be at least one active super admin. */
    private function guardLastSuperAdmin(User $staff, ?string $newRole, bool $willBeActive): void
    {
        $losesPower = $staff->role === 'super_admin' && $staff->is_active && ($newRole !== 'super_admin' || ! $willBeActive);

        if ($losesPower && ! User::where('role', 'super_admin')->where('is_active', true)->where('id', '!=', $staff->id)->exists()) {
            throw ValidationException::withMessages(['role' => 'This is the only active super admin. Make someone else a super admin first.']);
        }
    }

    /** Sign a person out everywhere (works when sessions are kept in the database, the Laravel default). */
    private function endSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }

    /** 12 characters with capitals, lower case and digits, without look-alike characters. */
    private function temporaryPassword(): string
    {
        $sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789'];
        $chars = [];

        foreach ($sets as $set) {
            for ($i = 0; $i < 3; $i++) {
                $chars[] = $set[random_int(0, strlen($set) - 1)];
            }
        }

        $all = implode('', $sets);
        for ($i = 0; $i < 3; $i++) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }
}
