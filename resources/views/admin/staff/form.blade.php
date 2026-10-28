@extends('layouts.staff')

@section('title', $mode === 'create' ? 'New account' : 'Manage account')

@php
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 disabled:opacity-60 dark:border-slate-600 dark:bg-slate-900';
    $label = 'mb-1 mt-4 block text-sm font-semibold';
    $hint = 'mt-1 text-xs text-slate-500 dark:text-slate-400';
    $card = 'panel';
    $isSelf = $isSelf ?? false;
    $action = $mode === 'create' ? route('admin.staff.store') : route('admin.staff.update', $staff);
    $descriptions = [
        'super_admin' => 'Manages staff accounts and everything else.',
        'exam_officer' => 'Sets up exams, candidates, sittings, papers and results.',
        'question_author' => 'Writes and imports questions (sees only their own).',
        'reviewer' => 'Approves or returns questions.',
        'invigilator' => 'Checks candidates in, admits them and unlocks accounts.',
        'committee' => 'Reads results, exports and prints them.',
    ];
@endphp

@section('content')
<a href="{{ route('admin.staff.index') }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← Staff accounts</a>
<h1 class="mb-5 mt-3 text-2xl font-bold">{{ $mode === 'create' ? 'New account' : $staff->name }}</h1>

<div class="grid items-start gap-5 lg:grid-cols-3">
    <section class="{{ $card }} lg:col-span-2">
        @if ($errors->any())
            <div class="mb-3 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">
                <ul class="list-disc pl-5">@foreach (collect($errors->all())->unique()->take(5) as $m)<li>{{ $m }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}">
            @csrf
            @if ($mode === 'edit') @method('PUT') @endif

            <label for="name" class="{{ $label }} !mt-0">Full name</label>
            <input id="name" name="name" value="{{ old('name', $staff->name) }}" required maxlength="255" class="{{ $input }}">

            <label for="email" class="{{ $label }}">Email (used to sign in)</label>
            <input id="email" name="email" type="email" value="{{ old('email', $staff->email) }}" required maxlength="255" class="{{ $input }}">

            <label for="role" class="{{ $label }}">Role</label>
            <select id="role" name="role" required class="{{ $input }}" @disabled($isSelf)>
                @if ($mode === 'create')<option value="">Choose a role</option>@endif
                @foreach (\App\Enums\StaffRole::cases() as $r)
                    <option value="{{ $r->value }}" @selected(old('role', $staff->role) === $r->value)>{{ $r->label() }}: {{ $descriptions[$r->value] }}</option>
                @endforeach
            </select>
            @if ($isSelf)<p class="{{ $hint }}">You cannot change your own role or switch yourself off. Ask another super admin.</p>@endif

            <label for="file_number" class="{{ $label }}">File number (optional)</label>
            <input id="file_number" name="file_number" value="{{ old('file_number', $staff->file_number) }}" maxlength="30" class="{{ $input }} uppercase">
            <p class="{{ $hint }}">Fill this in if the person could also be a candidate in an exam. It stops them from running or invigilating an exam they are sitting.</p>

            @if ($mode === 'edit' && ! $isSelf)
                <label class="mt-4 flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $staff->is_active)) class="h-4 w-4 accent-blue-700">
                    <span>Active (an inactive person cannot sign in)</span>
                </label>
            @endif

            @if ($mode === 'create')
                <p class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm dark:bg-slate-900">A temporary password is generated and shown once. The person must choose their own password the first time they sign in.</p>
            @endif

            <button type="submit" class="mt-5 rounded-lg bg-blue-700 px-6 py-2.5 font-semibold text-white hover:bg-blue-800">{{ $mode === 'create' ? 'Create account' : 'Save changes' }}</button>
        </form>
    </section>

    @if ($mode === 'edit')
        <aside class="space-y-5">
            <section class="{{ $card }}">
                <h2 class="mb-2 text-lg font-semibold">Account</h2>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Status</dt><dd class="font-semibold">{{ $staff->is_active ? 'Active' : 'Inactive' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Last sign-in</dt><dd class="font-semibold">{{ $staff->last_login_at ? \Illuminate\Support\Carbon::parse($staff->last_login_at)->format('j M Y, g:i A') : 'Never' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Password</dt><dd class="font-semibold">{{ $staff->must_change_password ? 'Must be changed' : 'Set by the person' }}</dd></div>
                </dl>
            </section>

            @unless ($isSelf)
                <section class="{{ $card }}">
                    <h2 class="mb-2 text-lg font-semibold">Actions</h2>
                    <form method="POST" action="{{ route('admin.staff.reset-password', $staff) }}" onsubmit="return confirm('Reset this person’s password? They will be signed out and given a new temporary password.')">
                        @csrf
                        <button class="w-full rounded-lg border border-slate-400 px-4 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-700">Reset password</button>
                    </form>

                    @if ($staff->is_active)
                        <form method="POST" action="{{ route('admin.staff.deactivate', $staff) }}" class="mt-2" onsubmit="return confirm('Deactivate this account? They will be signed out straight away.')">
                            @csrf
                            <button class="w-full rounded-lg border border-red-400 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-slate-700">Deactivate account</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.staff.reactivate', $staff) }}" class="mt-2">
                            @csrf
                            <button class="w-full rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Reactivate account</button>
                        </form>
                    @endif
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Accounts are deactivated, never deleted, so the record of who did what stays intact.</p>
                </section>
            @endunless
        </aside>
    @endif
</div>
@endsection
