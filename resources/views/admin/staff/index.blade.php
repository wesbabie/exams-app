@extends('layouts.staff')

@section('title', 'Staff accounts')

@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
    $input = 'rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $muted = 'text-slate-500 dark:text-slate-400';
    $mini = 'rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-700';
    $roleStyles = [
        'super_admin' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        'exam_officer' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'question_author' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
        'reviewer' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        'invigilator' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        'committee' => 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-200',
    ];
    $actionLabels = ['created' => 'created', 'updated' => 'updated', 'deactivated' => 'deactivated', 'reactivated' => 'reactivated', 'password_reset' => 'reset the password of', 'password_changed' => 'changed their own password'];
@endphp

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">Staff accounts</h1>
        <p class="text-sm {{ $muted }}">Everyone who signs in at the staff login: exam officers, authors, reviewers, invigilators and the committee.</p>
    </div>
    <a href="{{ route('admin.staff.create') }}" class="rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">New account</a>
</div>

<form method="GET" action="{{ route('admin.staff.index') }}" class="mb-4 grid gap-2 sm:grid-cols-5">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email or file number" class="{{ $input }} sm:col-span-2">
    <select name="role" class="{{ $input }}">
        <option value="">All roles</option>
        @foreach (\App\Enums\StaffRole::cases() as $r)<option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ $r->label() }}</option>@endforeach
        <option value="none" @selected(request('role') === 'none')>No role</option>
    </select>
    <select name="status" class="{{ $input }}">
        <option value="all" @selected($status === 'all')>Active and inactive</option>
        <option value="active" @selected($status === 'active')>Active only</option>
        <option value="inactive" @selected($status === 'inactive')>Inactive only</option>
    </select>
    <button class="{{ $mini }} py-2 text-sm">Filter</button>
</form>

<section class="{{ $card }}">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    <th class="py-2 pr-2">Name</th><th class="px-2 py-2">Role</th><th class="px-2 py-2">File number</th><th class="px-2 py-2">Status</th><th class="px-2 py-2">Last sign-in</th><th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($users as $u)
                @php $role = \App\Enums\StaffRole::tryFrom((string) $u->role); @endphp
                <tr class="border-b border-slate-200 dark:border-slate-700">
                    <td class="py-2.5 pr-2"><div class="font-semibold">{{ $u->name }}@if ($u->id === auth()->id()) <span class="text-xs font-normal {{ $muted }}">(you)</span>@endif</div><div class="text-xs {{ $muted }}">{{ $u->email }}</div></td>
                    <td class="px-2 py-2.5">
                        @if ($role)<span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $roleStyles[$u->role] ?? '' }}">{{ $role->label() }}</span>
                        @else<span class="text-xs text-red-600">No role</span>@endif
                    </td>
                    <td class="px-2 py-2.5 font-mono text-xs">{{ $u->file_number ?? '–' }}</td>
                    <td class="px-2 py-2.5">
                        @if ($u->is_active)<span class="text-green-700 dark:text-green-400">Active</span>@else<span class="text-slate-500">Inactive</span>@endif
                        @if ($u->must_change_password)<div class="text-xs text-amber-700 dark:text-amber-400">Must set a new password</div>@endif
                    </td>
                    <td class="px-2 py-2.5 text-xs {{ $muted }}">{{ $u->last_login_at ? \Illuminate\Support\Carbon::parse($u->last_login_at)->diffForHumans() : 'Never' }}</td>
                    <td class="py-2.5 pl-2 text-right"><a href="{{ route('admin.staff.edit', $u) }}" class="{{ $mini }}">Manage</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</section>

<section class="{{ $card }} mt-5">
    <h2 class="mb-2 text-lg font-semibold">Recent activity</h2>
    @if ($log->isEmpty())
        <p class="text-sm {{ $muted }}">Nothing yet.</p>
    @else
        <ul class="space-y-1.5 text-sm">
            @foreach ($log as $entry)
                <li class="flex flex-wrap gap-x-2">
                    <span class="{{ $muted }}">{{ $entry->created_at->format('j M, g:i A') }}</span>
                    <b>{{ $entry->actor?->name ?? 'System' }}</b>
                    <span>{{ $actionLabels[$entry->action] ?? $entry->action }}</span>
                    @if ($entry->action !== 'password_changed')<b>{{ $entry->target?->name ?? '–' }}</b>@endif
                    @if ($entry->detail && $entry->action === 'updated')<span class="{{ $muted }}">· {{ $entry->detail }}</span>@endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
@endsection
