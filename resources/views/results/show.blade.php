@extends('layouts.staff')

@section('title', 'Results · '.$exam->title)

@php
    $card = 'panel';
    $input = 'rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $muted = 'text-slate-500 dark:text-slate-400';
    $btn = 'rounded-lg border border-slate-400 px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700';
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $seesLabels = ['hidden' => 'nothing', 'pass_fail' => 'pass or fail only', 'full_breakdown' => 'section scores and total'];
    $tieLabels = ['field_section_score' => 'higher '.($tieSection?->title ?? 'field section').' score', 'earliest_submission' => 'earliest submission'];
    $counts = $registrationCounts;
    $notStarted = ($counts['assigned'] ?? 0) + ($counts['checked_in'] ?? 0) + ($counts['admitted'] ?? 0);
    $chips = ['all' => 'All', 'pass' => 'Passed', 'fail' => 'Failed'] + ($exam->top_n ? ['shortlisted' => 'Shortlisted'] : []);
@endphp

@section('content')
<x-page-head :title="$exam->title" :subtitle="$exam->category->name.' · pass mark '.$num($exam->pass_mark).($exam->pass_mark_type === 'percentage' ? '%' : ' marks').($exam->top_n ? ' · shortlist: top '.$exam->top_n : '')" :back="route('results.index')" back-label="All results">
    <x-slot:actions>
        <a href="{{ route('results.exams.export', $exam) }}" class="btn btn-neutral btn-sm">Export CSV (Excel)</a>
        <a href="{{ route('results.exams.print', $exam) }}" target="_blank" class="btn btn-neutral btn-sm">Print ranked list</a>
        @if ($canManage)<a href="{{ route('results.exams.analysis', $exam) }}" class="btn btn-neutral btn-sm">Question analysis</a>@endif
    </x-slot:actions>
</x-page-head>

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
@endif

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <x-stat label="Submitted" :value="$stats['submitted']" />
    <x-stat label="Passed" :value="$stats['passed']" :sub="$stats['submitted'] ? round($stats['passed'] / $stats['submitted'] * 100).'%' : null">
        <div class="mt-3"><x-meter :value="$stats['passed']" :max="max(1, $stats['submitted'])" label="Pass rate" /></div>
    </x-stat>
    <x-stat label="Average score" :value="$stats['average'] !== null ? $stats['average'].'%' : '–'" />
    <x-stat label="Highest score" :value="$stats['highest'] !== null ? $num($stats['highest']).'%' : '–'" />
</div>

<div class="mb-5 grid items-start gap-5 lg:grid-cols-3">
    <section class="{{ $card }} lg:col-span-2">
        <h2 class="mb-3 text-lg font-bold">Release</h2>
        @if ($exam->results_released_at)
            <p class="text-sm"><span class="chip chip-good">Released {{ $exam->results_released_at->format('j M Y, g:i A') }}</span></p>
        @else
            <p class="text-sm"><span class="chip chip-warn">Not released</span> Candidates cannot see anything yet.</p>
        @endif
        <p class="mt-2 text-sm {{ $muted }}">When released, candidates see: <b>{{ $seesLabels[$exam->result_release] ?? $exam->result_release }}</b>. Ties are broken by {{ $tieLabels[$exam->tie_break_rule] ?? 'nothing (candidates tied on marks share a rank)' }}.</p>

        <p class="mt-2 text-sm {{ $muted }}">
            Registered: <b>{{ array_sum($counts->all()) - ($counts['removed'] ?? 0) }}</b> ·
            finished: <b>{{ $counts['completed'] ?? 0 }}</b> ·
            writing: <b>{{ $counts['in_progress'] ?? 0 }}</b> ·
            not started: <b>{{ $notStarted }}</b> ·
            absent: <b>{{ $counts['absent'] ?? 0 }}</b>
        </p>

        @if ($tiesAtCutoff)
            <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100">Candidates tied at the cut-off, so more than {{ $exam->top_n }} are shortlisted. Choose a tie-break on the exam if you want a stricter list.</p>
        @endif

        @if ($canManage)
            <div class="mt-4 flex flex-wrap gap-2">
                @unless ($exam->results_released_at)
                    <form method="POST" action="{{ route('results.exams.release', $exam) }}" onsubmit="return confirm('Release these results? Ranks and the shortlist will be saved.')">@csrf
                        <button class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Release results</button>
                    </form>
                @else
                    <a href="{{ route('results.exams.slips', $exam) }}" target="_blank" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Print all result slips</a>
                    <form method="POST" action="{{ route('results.exams.withdraw', $exam) }}" onsubmit="return confirm('Withdraw the release? Results are hidden again.')">@csrf<button class="{{ $btn }}">Withdraw release</button></form>
                @endunless
                <form method="POST" action="{{ route('results.exams.recalculate', $exam) }}">@csrf<button class="{{ $btn }}">Recalculate ranks</button></form>
            </div>
        @endif
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-3 text-lg font-bold">Average by sitting</h2>
        @if (count($sittingAverages) < 1)
            <p class="text-sm {{ $muted }}">Nothing yet.</p>
        @else
            <ul class="space-y-1.5 text-sm">
                @foreach ($sittingAverages as $s)
                    <li class="flex justify-between gap-3"><span>{{ $s['name'] }} <span class="text-xs {{ $muted }}">({{ $s['count'] }})</span></span><b class="tabular-nums">{{ $s['average'] }}%</b></li>
                @endforeach
            </ul>
            @if (count($sittingAverages) > 1)
                <p class="mt-2 text-xs {{ $muted }}">A large gap between sittings can mean one paper was harder. Check before finalising.</p>
            @endif
        @endif
    </section>
</div>

<div class="mb-3 flex flex-wrap items-center gap-2 text-sm">
    @foreach ($chips as $key => $label)
        <a href="{{ route('results.exams.show', array_merge(request()->except('outcome'), ['exam' => $exam, 'outcome' => $key])) }}"
           class="pill {{ $outcome === $key ? 'pill-on' : '' }}">{{ $label }}</a>
    @endforeach
    <form method="GET" action="{{ route('results.exams.show', $exam) }}" class="ml-auto flex gap-2">
        <input type="hidden" name="outcome" value="{{ $outcome }}">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or number" class="{{ $input }}">
        <button class="{{ $btn }} py-2">Find</button>
    </form>
</div>

<section class="{{ $card }} overflow-x-auto">
    @if ($rows->isEmpty())
        <p class="text-sm {{ $muted }}">No results match.</p>
    @else
        <div class="overflow-x-auto">
            <table class="ledger w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="py-2 pr-2">Rank</th><th class="px-2 py-2">Candidate</th><th class="px-2 py-2">Sitting</th>
                        @foreach ($exam->sections as $s)<th class="px-2 py-2 text-right">{{ $s->title }}</th>@endforeach
                        <th class="px-2 py-2 text-right">Total</th><th class="px-2 py-2 text-right">%</th><th class="px-2 py-2">Result</th><th class="px-2 py-2 text-right" title="Times the candidate left the exam tab">Flags</th>@if ($canManage)<th></th>@endif
                    </tr>
                </thead>
                <tbody>
                @foreach ($rows as $row)
                    @php $r = $row['result']; @endphp
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <td class="font-display py-2.5 pr-2 text-xl font-bold tabular-nums">{{ $row['rank'] }}</td>
                        <td class="px-2 py-2.5"><div class="font-semibold">{{ $row['name'] }}</div><div class="font-mono text-xs {{ $muted }}">{{ $row['number'] }}</div></td>
                        <td class="px-2 py-2.5">{{ $row['sitting'] ?? '–' }}</td>
                        @foreach ($exam->sections as $s)
                            @php $sr = $r->sections->firstWhere('exam_section_id', $s->id); @endphp
                            <td class="px-2 py-2.5 text-right tabular-nums">{{ $num($sr?->marks ?? 0) }}<span class="text-xs {{ $muted }}">/{{ $num($sr?->max_marks ?? 0) }}</span></td>
                        @endforeach
                        <td class="px-2 py-2.5 text-right font-bold tabular-nums">{{ $num($r->total_marks) }}<span class="text-xs font-normal {{ $muted }}">/{{ $num($r->max_marks) }}</span></td>
                        <td class="w-28 px-2 py-2.5">
                            <div class="text-right tabular-nums">{{ $num($r->percentage) }}</div>
                            <x-meter :value="(float) $r->percentage" :max="100" :tone="$r->passed ? null : 'bad'" :label="'Score percentage'" />
                        </td>
                        <td class="px-2 py-2.5">
                            <span class="chip {{ $r->passed ? 'chip-good' : 'chip-bad' }}">{{ $r->passed ? 'Pass' : 'Fail' }}</span>
                            @if ($row['shortlisted'])<span class="chip chip-info ml-1">Shortlisted</span>@endif
                        </td>
                        <td class="px-2 py-2.5 text-right tabular-nums {{ $row['flags'] >= 3 ? 'font-bold text-amber-700 dark:text-amber-400' : '' }}">{{ $row['flags'] ?: '–' }}</td>
                        @if ($canManage)<td class="py-2.5 pl-2 text-right"><a href="{{ route('results.attempts.show', $r->attempt_id) }}" class="text-xs font-semibold text-blue-700 hover:underline dark:text-blue-400">Review</a></td>@endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs {{ $muted }}">Flags count the times a candidate left the exam tab. They are for review only and never change a score.</p>
    @endif
</section>
@endsection
