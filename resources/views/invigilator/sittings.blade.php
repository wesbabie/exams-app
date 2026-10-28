@extends('layouts.staff')

@section('title', 'Check-in')

@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
    $muted = 'text-slate-500 dark:text-slate-400';
    $now = now();
@endphp

@section('content')
<h1 class="text-2xl font-bold">Check-in</h1>
<p class="mb-5 text-sm {{ $muted }}">Sittings happening today and in the coming week. Open one to check candidates in, admit them and unlock accounts.</p>

<h2 class="mb-3 text-lg font-semibold">Today</h2>
@if ($today->isEmpty())
    <div class="{{ $card }} mb-6 text-sm {{ $muted }}">No sittings today.</div>
@else
    <div class="mb-6 grid gap-4 md:grid-cols-2">
        @foreach ($today as $s)
            @php
                $c = $counts->get($s->id, collect());
                $opens = $s->starts_at->copy()->subHours(2);
                $state = $now->lt($opens) ? ['Check-in opens '.$opens->format('g:i A'), 'text-slate-500 dark:text-slate-400']
                    : ($now->gt($s->ends_at) ? ['Ended', 'text-slate-500 dark:text-slate-400'] : ['Check-in open', 'text-green-700 dark:text-green-400']);
            @endphp
            <a href="{{ route('invigilator.sittings.show', $s) }}" class="{{ $card }} block hover:border-blue-600 dark:hover:border-blue-400">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-lg font-semibold">{{ $s->exam->title }}</div>
                        <div class="text-sm {{ $muted }}">{{ $s->name }}@if ($s->venue) · {{ $s->venue }}@endif</div>
                    </div>
                    <span class="shrink-0 text-sm font-semibold {{ $state[1] }}">{{ $state[0] }}</span>
                </div>
                <div class="mt-1 text-sm">{{ $s->starts_at->format('D j M, g:i A') }} – {{ $s->ends_at->format('g:i A') }}</div>
                <div class="mt-3 grid grid-cols-4 gap-2 text-center">
                    <div class="rounded-lg bg-slate-100 py-2 dark:bg-slate-700"><b class="block text-lg tabular-nums">{{ $c['assigned'] ?? 0 }}</b><span class="text-xs {{ $muted }}">Not arrived</span></div>
                    <div class="rounded-lg bg-amber-50 py-2 dark:bg-amber-950"><b class="block text-lg tabular-nums">{{ $c['checked_in'] ?? 0 }}</b><span class="text-xs {{ $muted }}">Checked in</span></div>
                    <div class="rounded-lg bg-green-50 py-2 dark:bg-green-950"><b class="block text-lg tabular-nums">{{ $c['admitted'] ?? 0 }}</b><span class="text-xs {{ $muted }}">Admitted</span></div>
                    <div class="rounded-lg bg-slate-100 py-2 dark:bg-slate-700"><b class="block text-lg tabular-nums">{{ $c['absent'] ?? 0 }}</b><span class="text-xs {{ $muted }}">Absent</span></div>
                </div>
            </a>
        @endforeach
    </div>
@endif

@if ($upcoming->isNotEmpty())
    <h2 class="mb-3 text-lg font-semibold">Coming up</h2>
    <section class="{{ $card }}">
        <ul class="divide-y divide-slate-200 text-sm dark:divide-slate-700">
            @foreach ($upcoming as $s)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 first:pt-0 last:pb-0">
                    <span><b>{{ $s->exam->title }}</b> · {{ $s->name }}</span>
                    <span class="{{ $muted }}">{{ $s->starts_at->format('D j M, g:i A') }}</span>
                    <a href="{{ route('invigilator.sittings.show', $s) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">Open</a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
@endsection
