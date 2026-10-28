@extends('layouts.staff')

@section('title', 'Check-in · '.$sitting->name)

@php
    $card = 'panel';
    $input = 'rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $muted = 'text-slate-500 dark:text-slate-400';
    $mini = 'rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-700';
    $labels = ['assigned' => 'Not arrived', 'checked_in' => 'Checked in', 'admitted' => 'Admitted', 'in_progress' => 'Writing', 'absent' => 'Absent', 'completed' => 'Finished'];
    $badge = [
        'assigned' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        'checked_in' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        'admitted' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'absent' => 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
        'in_progress' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    ];
    $now = now();
    $open = $sitting->status !== 'closed' && $now->gte($checkInOpensAt) && $now->lte($sitting->ends_at);
    $waiting = $counts['checked_in'] ?? 0;
@endphp

@section('content')
<a href="{{ route('invigilator.sittings.index') }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← All sittings</a>

<div class="mb-4 mt-3 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">{{ $sitting->exam->title }}</h1>
        <p class="text-sm {{ $muted }}">{{ $sitting->name }}@if ($sitting->venue) · {{ $sitting->venue }}@endif · {{ $sitting->starts_at->format('D j M, g:i A') }} – {{ $sitting->ends_at->format('g:i A') }}</p>
    </div>
    @if ($waiting > 0)
        <form method="POST" action="{{ route('invigilator.sittings.admit-all', $sitting) }}" onsubmit="return confirm('Admit all {{ $waiting }} checked-in candidate(s)? They will be able to sign in.')">
            @csrf
            <button class="rounded-lg bg-green-700 px-5 py-2.5 font-semibold text-white hover:bg-green-800">Admit all checked in ({{ $waiting }})</button>
        </form>
    @endif
</div>

@if ($sitting->status === 'closed')
    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">This sitting is closed.</div>
@elseif ($now->lt($checkInOpensAt))
    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">Check-in opens at {{ $checkInOpensAt->format('g:i A, j M') }}.</div>
@elseif ($now->gt($sitting->ends_at))
    <div class="mb-4 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm dark:border-slate-600 dark:bg-slate-800">This sitting has ended.</div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
@endif

<div class="mb-4 grid grid-cols-3 gap-2 text-center sm:grid-cols-7">
    @foreach (['assigned' => 'Not arrived', 'checked_in' => 'Checked in', 'admitted' => 'Admitted', 'in_progress' => 'Writing', 'absent' => 'Absent', 'completed' => 'Finished'] as $key => $label)
        <div class="rounded-xl border border-slate-200 bg-white py-2.5 dark:border-slate-700 dark:bg-slate-800"><b class="block text-xl tabular-nums">{{ $counts[$key] ?? 0 }}</b><span class="text-xs {{ $muted }}">{{ $label }}</span></div>
    @endforeach
    <div class="rounded-xl border {{ $lockedCount ? 'border-red-400' : 'border-slate-200 dark:border-slate-700' }} bg-white py-2.5 dark:bg-slate-800"><b class="block text-xl tabular-nums {{ $lockedCount ? 'text-red-700 dark:text-red-400' : '' }}">{{ $lockedCount }}</b><span class="text-xs {{ $muted }}">Locked</span></div>
</div>

<form method="GET" action="{{ route('invigilator.sittings.show', $sitting) }}" class="mb-4 flex gap-2">
    <input type="search" name="q" value="{{ request('q') }}" autofocus placeholder="Type a file number or name" class="{{ $input }} w-full text-lg">
    <button class="rounded-lg bg-blue-700 px-5 font-semibold text-white hover:bg-blue-800">Find</button>
    @if (request('q'))<a href="{{ route('invigilator.sittings.show', $sitting) }}" class="{{ $mini }} flex items-center">Clear</a>@endif
</form>

@if ($elsewhere)
    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        That candidate is not in this sitting. They are assigned to <b>{{ $elsewhere->examSession?->name ?? 'no sitting' }}</b>@if ($elsewhere->examSession) ({{ $elsewhere->examSession->starts_at->format('D j M, g:i A') }})@endif.
    </div>
@endif

<section class="{{ $card }}">
    @if ($rows->isEmpty())
        <p class="text-sm {{ $muted }}">{{ request('q') ? 'No one matches that search in this sitting.' : 'No candidates are assigned to this sitting yet.' }}</p>
    @else
        <div class="overflow-x-auto">
            <table class="ledger w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="py-2 pr-2">Seat</th><th class="px-2 py-2">Candidate</th><th class="px-2 py-2">Status</th><th class="px-2 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($rows as $r)
                    @php $c = $cands[$r->candidate_type][$r->candidate_id] ?? null; @endphp
                    <tr class="border-b border-slate-200 align-top dark:border-slate-700">
                        <td class="py-3 pr-2 text-lg font-bold tabular-nums">{{ $r->seat_number }}</td>
                        <td class="px-2 py-3">
                            <div class="font-semibold">{{ $c?->full_name ?? '–' }}</div>
                            <div class="font-mono text-xs {{ $muted }}">{{ $c?->file_number ?? $c?->application_number }}</div>
                            @if ($c && trim(($c->cadre ?? '').' '.($c->department ?? '')))<div class="text-xs {{ $muted }}">{{ trim(($c->cadre ?? '').' '.($c->level ?? '')) }}{{ ($c->department ?? '') ? ' · '.$c->department : '' }}</div>@endif
                        </td>
                        <td class="px-2 py-3">
                            <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge[$r->status] ?? $badge['assigned'] }}">{{ $labels[$r->status] ?? $r->status }}</span>
                            @if ($r->locked_at)<span class="ml-1 inline-block rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900 dark:text-red-200">Locked</span>@endif
                            @if ($r->checked_in_at)<div class="mt-1 text-xs {{ $muted }}">in at {{ $r->checked_in_at->format('g:i A') }}</div>@endif
                        </td>
                        <td class="px-2 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($r->status === 'assigned' && $sitting->status !== 'closed')
                                    <form method="POST" action="{{ route('invigilator.check-in', $r) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <label class="flex items-center gap-1.5 text-xs"><input type="checkbox" name="identity_confirmed" value="1" class="h-4 w-4 accent-blue-700" @disabled(! $open)> <span>ID checked</span></label>
                                        <button class="rounded-lg bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-800 disabled:opacity-50" @disabled(! $open)>Check in</button>
                                    </form>
                                    <form method="POST" action="{{ route('invigilator.absent', $r) }}" onsubmit="return confirm('Mark this candidate absent?')">@csrf<button class="{{ $mini }}">Absent</button></form>
                                @endif

                                @if ($r->status === 'checked_in')
                                    <form method="POST" action="{{ route('invigilator.admit', $r) }}">@csrf<button class="rounded-lg bg-green-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-800">Admit</button></form>
                                    <form method="POST" action="{{ route('invigilator.undo-check-in', $r) }}" onsubmit="return confirm('Undo this check-in? The PIN will stop working.')">@csrf<button class="{{ $mini }}">Undo</button></form>
                                @endif

                                @if ($r->status === 'admitted')
                                    <form method="POST" action="{{ route('invigilator.revoke', $r) }}" onsubmit="return confirm('Withdraw this candidate’s admission?')">@csrf<button class="{{ $mini }}">Withdraw admission</button></form>
                                @endif

                                @if (in_array($r->status, ['checked_in', 'admitted'], true))
                                    <form method="POST" action="{{ route('invigilator.reissue-pin', $r) }}" onsubmit="return confirm('Issue a new PIN? The old one will stop working.')">@csrf<button class="{{ $mini }}">New PIN</button></form>
                                @endif

                                @if ($r->locked_at)
                                    <form method="POST" action="{{ route('invigilator.unlock', $r) }}">@csrf<button class="rounded-lg bg-red-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-800">Unlock</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
