@extends('layouts.staff')

@section('title', 'Check-in')

@php
    $muted = 'text-slate-600 dark:text-slate-300';
    $now = now();
@endphp

@section('content')
<x-page-head title="Check-in" subtitle="Sittings happening today and in the coming week. Open one to check candidates in, admit them and unlock accounts." />

<h2 class="mb-3 text-xl font-bold">Today</h2>
@if ($today->isEmpty())
    <div class="panel mb-8 text-sm {{ $muted }}">No sittings today.</div>
@else
    <div class="mb-8 grid gap-4 lg:grid-cols-2">
        @foreach ($today as $s)
            @php
                $c = $counts->get($s->id, collect());
                $total = max(1, ($c['assigned'] ?? 0) + ($c['checked_in'] ?? 0) + ($c['admitted'] ?? 0) + ($c['in_progress'] ?? 0) + ($c['completed'] ?? 0) + ($c['absent'] ?? 0));
                $arrived = $total - ($c['assigned'] ?? 0) - ($c['absent'] ?? 0);
                $opens = $s->starts_at->copy()->subHours(2);
                [$stateText, $stateChip] = $now->lt($opens) ? ['Check-in opens '.$opens->format('g:i A'), 'chip-neutral']
                    : ($now->gt($s->ends_at) ? ['Ended', 'chip-muted'] : ['Check-in open', 'chip-good']);
            @endphp
            <a href="{{ route('invigilator.sittings.show', $s) }}" class="panel group block transition-colors hover:border-blue-600 dark:hover:border-blue-400">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-display text-xl font-bold leading-tight group-hover:text-blue-800 dark:group-hover:text-blue-300">{{ $s->exam->title }}</p>
                        <p class="text-sm {{ $muted }}">{{ $s->name }}@if ($s->venue) · {{ $s->venue }}@endif</p>
                        <p class="text-sm {{ $muted }}">{{ $s->starts_at->format('g:i A') }} to {{ $s->ends_at->format('g:i A') }}</p>
                    </div>
                    <span class="chip {{ $stateChip }} shrink-0">{{ $stateText }}</span>
                </div>

                <div class="mt-4 flex items-baseline justify-between text-sm">
                    <span class="{{ $muted }}">Arrived</span>
                    <b class="font-display text-lg tabular-nums">{{ $arrived }} <span class="text-sm font-semibold {{ $muted }}">of {{ $total }}</span></b>
                </div>
                <div class="mt-1.5"><x-meter :value="$arrived" :max="$total" label="Candidates arrived" /></div>

                <dl class="mt-4 grid grid-cols-4 gap-2 text-center">
                    <div><dd class="font-display text-2xl font-bold tabular-nums">{{ $c['assigned'] ?? 0 }}</dd><dt class="text-xs {{ $muted }}">Not arrived</dt></div>
                    <div><dd class="font-display text-2xl font-bold tabular-nums">{{ $c['checked_in'] ?? 0 }}</dd><dt class="text-xs {{ $muted }}">Checked in</dt></div>
                    <div><dd class="font-display text-2xl font-bold tabular-nums">{{ $c['admitted'] ?? 0 }}</dd><dt class="text-xs {{ $muted }}">Admitted</dt></div>
                    <div><dd class="font-display text-2xl font-bold tabular-nums">{{ $c['absent'] ?? 0 }}</dd><dt class="text-xs {{ $muted }}">Absent</dt></div>
                </dl>
            </a>
        @endforeach
    </div>
@endif

@if ($upcoming->isNotEmpty())
    <h2 class="mb-3 text-xl font-bold">Coming up</h2>
    <section class="panel !p-0">
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            @foreach ($upcoming as $s)
                <li>
                    <a href="{{ route('invigilator.sittings.show', $s) }}" class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 px-5 py-3.5 hover:bg-blue-50 dark:hover:bg-slate-700">
                        <span><b class="font-display text-base">{{ $s->exam->title }}</b> <span class="{{ $muted }}">· {{ $s->name }}</span></span>
                        <span class="text-sm {{ $muted }}">{{ $s->starts_at->format('D j M, g:i A') }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
@endsection
