@extends('layouts.staff')

@section('title', 'Results')

@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
    $muted = 'text-slate-500 dark:text-slate-400';
@endphp

@section('content')
<h1 class="text-2xl font-bold">Results</h1>
<p class="mb-5 text-sm {{ $muted }}">Choose an exam to see its ranked results, export them or, for exam officers, release them to candidates.</p>

<section class="{{ $card }}">
    @if ($exams->isEmpty())
        <p class="text-sm {{ $muted }}">No exam has results yet. Results appear here once candidates submit their exams.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
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
                        <td class="py-2.5 pr-2"><a href="{{ route('results.exams.show', $exam) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">{{ $exam->title }}</a></td>
                        <td class="px-2 py-2.5">{{ $exam->category->name }}</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $registered[$exam->id] ?? 0 }}</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $r?->submitted ?? 0 }}</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $r ? $r->passed.' ('.round($r->passed / max(1, $r->submitted) * 100).'%)' : '–' }}</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $r ? round($r->average, 1).'%' : '–' }}</td>
                        <td class="px-2 py-2.5">
                            @if ($exam->results_released_at)<span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">{{ $exam->results_released_at->format('j M Y') }}</span>
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
