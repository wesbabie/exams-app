@extends('layouts.staff')

@section('title', 'Results')

@php
    $card = 'panel';
    $muted = 'text-slate-500 dark:text-slate-400';
@endphp

@section('content')
<x-page-head title="Results" subtitle="Choose an exam to see its ranked results, export them or, for exam officers, release them to candidates." />

<section class="{{ $card }} overflow-x-auto">
    @if ($exams->isEmpty())
        <p class="text-sm {{ $muted }}">No exam has results yet. Results appear here once candidates submit their exams.</p>
    @else
        <div class="overflow-x-auto">
            <table class="ledger w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="py-2 pr-2">Exam</th><th class="px-2 py-2">Category</th><th class="px-2 py-2 text-right">Registered</th><th class="px-2 py-2 text-right">Submitted</th>
                        <th class="px-2 py-2 text-right">Passed</th><th class="px-2 py-2 text-right">Average</th><th class="px-2 py-2">Released</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($exams as $exam)
                    @php $r = $withResults[$exam->id] ?? null; @endphp
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <td class="py-3 pr-2"><a href="{{ route('results.exams.show', $exam) }}" class="font-display text-base font-bold text-blue-800 hover:underline dark:text-blue-300">{{ $exam->title }}</a></td>
                        <td class="px-2 py-2.5">{{ $exam->category->name }}</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $registered[$exam->id] ?? 0 }}</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $r?->submitted ?? 0 }}</td>
                        <td class="w-40 px-2 py-2.5">
                            @if ($r)
                                <div class="mb-1 flex justify-between text-sm tabular-nums"><b>{{ $r->passed }}</b><span class="{{ $muted }}">{{ round($r->passed / max(1, $r->submitted) * 100) }}%</span></div>
                                <x-meter :value="$r->passed" :max="max(1, $r->submitted)" :label="'Passed of submitted'" />
                            @else – @endif
                        </td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $r ? round($r->average, 1).'%' : '–' }}</td>
                        <td class="px-2 py-2.5">
                            @if ($exam->results_released_at)<span class="chip chip-good">{{ $exam->results_released_at->format('j M Y') }}</span>
                            @else<span class="text-xs {{ $muted }}">Not yet</span>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
