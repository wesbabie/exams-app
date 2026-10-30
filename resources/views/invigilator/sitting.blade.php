@extends('layouts.staff')

@section('title', 'Check-in · '.$sitting->name)

@php
    $card = 'panel';
    $muted = 'text-slate-600 dark:text-slate-300';
    $mini = 'btn-mini';
    $labels = ['assigned' => 'Not arrived', 'checked_in' => 'Checked in', 'admitted' => 'Admitted', 'in_progress' => 'Writing', 'absent' => 'Absent', 'completed' => 'Finished'];
    $tone = ['assigned' => 'neutral', 'checked_in' => 'warn', 'admitted' => 'good', 'in_progress' => 'info', 'absent' => 'muted', 'completed' => 'info'];
    // Segment colours for the arrival bar. The counts beneath carry the meaning, not the colour.
    $seg = ['assigned' => 'bg-slate-300 dark:bg-slate-500', 'checked_in' => 'bg-marker-400', 'admitted' => 'bg-blue-600', 'in_progress' => 'bg-blue-400', 'completed' => 'bg-blue-900 dark:bg-blue-200', 'absent' => 'bg-slate-200 dark:bg-slate-700'];
    $order = ['assigned', 'checked_in', 'admitted', 'in_progress', 'completed', 'absent'];
    $total = max(1, collect($order)->sum(fn ($k) => $counts[$k] ?? 0));
    $now = now();
    $open = $sitting->status !== 'closed' && $now->gte($checkInOpensAt) && $now->lte($sitting->ends_at);
    $waiting = $counts['checked_in'] ?? 0;
@endphp

@section('content')
<x-page-head :title="$sitting->exam->title" :subtitle="$sitting->name.($sitting->venue ? ' · '.$sitting->venue : '').' · '.$sitting->starts_at->format('D j M, g:i A').' to '.$sitting->ends_at->format('g:i A')" :back="route('invigilator.sittings.index')" back-label="All sittings">
    <x-slot:actions>
        @if ($waiting > 0)
            <form method="POST" action="{{ route('invigilator.sittings.admit-all', $sitting) }}" onsubmit="return confirm('Admit all {{ $waiting }} checked-in candidate(s)? They will be able to sign in.')">
                @csrf
                <button class="btn btn-good">Admit all checked in ({{ $waiting }})</button>
            </form>
        @endif
    </x-slot:actions>
</x-page-head>

@if ($sitting->status === 'closed')
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">This sitting is closed.</div>
@elseif ($now->lt($checkInOpensAt))
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">Check-in opens at {{ $checkInOpensAt->format('g:i A, j M') }}.</div>
@elseif ($now->gt($sitting->ends_at))
    <div class="mb-5 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm dark:border-slate-600 dark:bg-slate-800">This sitting has ended.</div>
@endif

@if ($errors->any())
    <div class="alert-error !mt-0 mb-5" role="alert">{{ $errors->first() }}</div>
@endif

{{-- Where everyone is, at a glance --}}
<section class="{{ $card }} mb-5" aria-label="Arrival summary">
    <div class="flex h-2.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" role="img" aria-label="Candidates by stage">
        @foreach ($order as $key)
            @if (($counts[$key] ?? 0) > 0)<span class="{{ $seg[$key] }} block h-full" style="width: {{ round(($counts[$key] ?? 0) / $total * 100, 2) }}%"></span>@endif
        @endforeach
    </div>
    <dl class="mt-4 grid grid-cols-3 gap-x-4 gap-y-4 sm:grid-cols-7">
        @foreach ($order as $key)
            <div>
                <dt class="flex items-center gap-1.5 text-xs {{ $muted }}"><span class="{{ $seg[$key] }} h-2.5 w-2.5 rounded-full" aria-hidden="true"></span>{{ $labels[$key] }}</dt>
                <dd class="font-display text-3xl font-bold leading-tight tabular-nums">{{ $counts[$key] ?? 0 }}</dd>
            </div>
        @endforeach
        <div>
            <dt class="flex items-center gap-1.5 text-xs {{ $muted }}"><span class="h-2.5 w-2.5 rounded-full bg-red-600" aria-hidden="true"></span>Locked</dt>
            <dd class="font-display text-3xl font-bold leading-tight tabular-nums {{ $lockedCount ? 'text-red-700 dark:text-red-400' : '' }}">{{ $lockedCount }}</dd>
        </div>
    </dl>
</section>

{{-- Find a candidate: the first thing an invigilator does --}}
<form method="GET" action="{{ route('invigilator.sittings.show', $sitting) }}" class="mb-5 flex gap-2" role="search">
    <div class="relative flex-1">
        <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" name="q" value="{{ request('q') }}" autofocus placeholder="Type a file number, application number or name" aria-label="Find a candidate" class="field !py-3.5 !pl-12 text-lg">
    </div>
    <button class="btn btn-primary px-7 text-lg">Find</button>
    @if (request('q'))<a href="{{ route('invigilator.sittings.show', $sitting) }}" class="btn btn-neutral">Clear</a>@endif
</form>

@if ($elsewhere)
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        That candidate is not in this sitting. They are assigned to <b>{{ $elsewhere->examSession?->name ?? 'no sitting' }}</b>@if ($elsewhere->examSession) ({{ $elsewhere->examSession->starts_at->format('D j M, g:i A') }})@endif.
    </div>
@endif

<section class="{{ $card }} !p-0">
    @if ($rows->isEmpty())
        <p class="p-5 text-sm {{ $muted }}">{{ request('q') ? 'No one matches that search in this sitting.' : 'No candidates are assigned to this sitting yet.' }}</p>
    @else
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            @foreach ($rows as $r)
                @php $c = $cands[$r->candidate_type][$r->candidate_id] ?? null; @endphp
                <li class="flex flex-wrap items-center gap-x-5 gap-y-3 border-l-4 px-5 py-4 {{ ['checked_in' => 'border-marker-400', 'admitted' => 'border-blue-600', 'in_progress' => 'border-blue-400'][$r->status] ?? 'border-transparent' }}">
                    <div class="w-14 shrink-0 text-center" aria-label="Seat {{ $r->seat_number }}">
                        <div class="font-display text-3xl font-bold leading-none tabular-nums">{{ $r->seat_number }}</div>
                        <div class="mt-1 text-xs {{ $muted }}">Seat</div>
                    </div>

                    <div class="min-w-[12rem] flex-1">
                        <p class="font-display text-xl font-bold leading-tight">{{ $c?->full_name ?? '–' }}</p>
                        <p class="text-sm tabular-nums {{ $muted }}">{{ $c?->file_number ?? $c?->application_number }}</p>
                        @if ($c && trim(($c->cadre ?? '').' '.($c->department ?? '')))<p class="text-sm {{ $muted }}">{{ trim(($c->cadre ?? '').' '.($c->level ?? '')) }}{{ ($c->department ?? '') ? ' · '.$c->department : '' }}</p>@endif
                    </div>

                    <div class="w-36 shrink-0">
                        <span class="chip chip-{{ $tone[$r->status] ?? 'neutral' }}">{{ $labels[$r->status] ?? $r->status }}</span>
                        @if ($r->locked_at)<span class="chip chip-bad ml-1">Locked</span>@endif
                        @if ($r->checked_in_at)<p class="mt-1.5 text-xs {{ $muted }}">in at {{ $r->checked_in_at->format('g:i A') }}</p>@endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2 lg:w-[27rem] lg:justify-end">
                        @if ($r->status === 'assigned' && $sitting->status !== 'closed')
                            <form method="POST" action="{{ route('invigilator.check-in', $r) }}" class="flex flex-wrap items-center gap-3">
                                @csrf
                                <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="identity_confirmed" value="1" class="h-5 w-5 accent-blue-700" @disabled(! $open)> <span>ID checked</span></label>
                                <button class="btn btn-primary btn-sm" @disabled(! $open)>Check in</button>
                            </form>
                            <form method="POST" action="{{ route('invigilator.absent', $r) }}" onsubmit="return confirm('Mark this candidate absent?')">@csrf<button class="{{ $mini }}">Absent</button></form>
                        @endif

                        @if ($r->status === 'checked_in')
                            <form method="POST" action="{{ route('invigilator.admit', $r) }}">@csrf<button class="btn btn-good btn-sm">Admit</button></form>
                            <form method="POST" action="{{ route('invigilator.undo-check-in', $r) }}" onsubmit="return confirm('Undo this check-in? The PIN will stop working.')">@csrf<button class="{{ $mini }}">Undo</button></form>
                        @endif

                        @if ($r->status === 'admitted')
                            <form method="POST" action="{{ route('invigilator.revoke', $r) }}" onsubmit="return confirm('Withdraw this candidate’s admission?')">@csrf<button class="{{ $mini }}">Withdraw admission</button></form>
                        @endif

                        @if (in_array($r->status, ['checked_in', 'admitted'], true))
                            <form method="POST" action="{{ route('invigilator.reissue-pin', $r) }}" onsubmit="return confirm('Issue a new PIN? The old one will stop working.')">@csrf<button class="{{ $mini }}">New PIN</button></form>
                        @endif

                        @if ($r->locked_at)
                            <form method="POST" action="{{ route('invigilator.unlock', $r) }}">@csrf<button class="btn btn-sm bg-red-700 text-white hover:bg-red-800">Unlock</button></form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
@endsection
