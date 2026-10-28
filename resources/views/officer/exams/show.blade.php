@extends('layouts.staff')

@section('title', $exam->title)

@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
    $row = 'flex justify-between gap-4 border-b border-slate-200 py-2 text-sm last:border-0 dark:border-slate-700';
    $muted = 'text-slate-500 dark:text-slate-400';
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $totalQuestions = $exam->sections->sum('question_count');
    $totalMarks = $exam->sections->sum(fn ($s) => $s->question_count * $s->marks_per_question);
    $totalSeconds = $exam->sections->sum(fn ($s) => $s->question_count * $s->seconds_per_question);
    $duration = \Carbon\CarbonInterval::seconds($totalSeconds)->cascade()->forHumans(['short' => true, 'parts' => 2]);
    $isCreator = $exam->created_by === auth()->id();
@endphp

@section('content')
<a href="{{ route('officer.exams.index') }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← All exams</a>

<div class="mb-5 mt-3 flex flex-wrap items-start justify-between gap-4">
    <div>
        @include('officer.partials.status-badge', ['status' => $exam->status])
        <h1 class="mt-2 text-2xl font-bold">{{ $exam->title }}</h1>
        <p class="text-sm {{ $muted }}">{{ $exam->category->name }}@if ($exam->cycle_year) · {{ $exam->cycle_year }}@endif</p>
    </div>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('officer.exams.candidates.index', $exam) }}" class="rounded-lg border border-slate-400 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">Candidates</a>
        <a href="{{ route('officer.exams.sittings.index', $exam) }}" class="rounded-lg border border-slate-400 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">Sittings</a>
        <a href="{{ route('results.exams.show', $exam) }}" class="rounded-lg border border-slate-400 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">Results</a>
        @if ($exam->status === 'draft')
            <a href="{{ route('officer.exams.edit', $exam) }}" class="rounded-lg border border-blue-700 px-4 py-2 font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">Edit</a>
            <form method="POST" action="{{ route('officer.exams.submit', $exam) }}">@csrf
                <button class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white hover:bg-blue-800">Send for review</button>
            </form>
        @elseif ($exam->status === 'in_review')
            <form method="POST" action="{{ route('officer.exams.reopen', $exam) }}">@csrf
                <button class="rounded-lg border border-slate-400 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">Return to draft</button>
            </form>
            @unless ($isCreator)
                <form method="POST" action="{{ route('officer.exams.approve', $exam) }}">@csrf
                    <button class="rounded-lg bg-green-700 px-4 py-2 font-semibold text-white hover:bg-green-800">Approve</button>
                </form>
            @endunless
        @endif
    </div>
</div>

@if ($exam->status === 'in_review' && $isCreator)
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        You created this exam, so another exam officer needs to approve it.
    </div>
@endif
@if ($exam->status === 'approved')
    <div class="mb-5 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-900 dark:border-green-700 dark:bg-green-950 dark:text-green-100">
        Approved{{ $exam->approver ? ' by '.$exam->approver->name : '' }}{{ $exam->approved_at ? ' on '.$exam->approved_at->format('j M Y, g:i A') : '' }}. Sections, timers and marks are locked.
    </div>
@endif

<div class="grid items-start gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <section class="{{ $card }}">
            <h2 class="mb-3 text-lg font-semibold">Sections</h2>
            @if ($exam->sections->isEmpty())
                <p class="text-sm {{ $muted }}">No sections yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                <th class="py-2 pr-2">Section</th><th class="px-2 py-2 text-right">Questions</th><th class="px-2 py-2 text-right">Time per question</th><th class="px-2 py-2 text-right">Mark each</th><th class="py-2 pl-2 text-right">Section marks</th><th class="py-2 pl-4">Question set</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($exam->sections as $s)
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <td class="py-2.5 pr-2">{{ $s->position }}. {{ $s->title }}</td>
                                <td class="px-2 py-2.5 text-right tabular-nums">{{ $s->question_count }}</td>
                                <td class="px-2 py-2.5 text-right tabular-nums">{{ $s->seconds_per_question }} sec</td>
                                <td class="px-2 py-2.5 text-right tabular-nums">{{ $num($s->marks_per_question) }}</td>
                                <td class="py-2.5 pl-2 text-right tabular-nums">{{ $num($s->question_count * $s->marks_per_question) }}</td>
                                @php $st = $sectionStatus[$s->id] ?? ['have' => 0, 'need' => $s->question_count, 'ok' => false]; @endphp
                                <td class="py-2.5 pl-4 whitespace-nowrap">
                                    <a href="{{ route('officer.exams.sections.questions.edit', [$exam, $s]) }}" class="inline-flex items-center gap-2 font-semibold text-blue-700 hover:underline dark:text-blue-400">
                                        <span class="{{ $st['ok'] ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">{{ $st['have'] }} / {{ $st['need'] }}</span>
                                        <span>{{ $exam->status === 'draft' ? ($exam->paper_mode === 'fixed_set' ? 'Choose' : 'Set counts') : 'View' }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-bold">
                                <td class="py-2.5 pr-2">Total</td><td class="px-2 py-2.5 text-right tabular-nums">{{ $totalQuestions }}</td><td class="px-2 py-2.5 text-right">{{ $duration }}</td><td></td><td class="py-2.5 pl-2 text-right tabular-nums">{{ $num($totalMarks) }}</td><td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </section>

        @if (in_array($exam->status, ['draft', 'in_review'], true))
            <section class="{{ $card }}">
                <h2 class="mb-2 text-lg font-semibold">Readiness</h2>
                @if (empty($issues))
                    <p class="text-sm text-green-700 dark:text-green-400">Every section has its questions. The exam is ready for review.</p>
                @else
                    <p class="mb-2 text-sm {{ $muted }}">Before this exam can go for review:</p>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-amber-800 dark:text-amber-300">
                        @foreach ($issues as $issue)<li>{{ $issue }}</li>@endforeach
                    </ul>
                @endif
            </section>
        @endif

        <section class="{{ $card }}">
            <h2 class="mb-3 text-lg font-semibold">Still to set up</h2>
            <ul class="space-y-2 text-sm">
                <li class="flex items-center justify-between gap-3"><span>The paper for each sitting</span><span class="rounded-full border border-slate-300 px-2.5 py-0.5 text-xs {{ $muted }} dark:border-slate-600">Coming soon</span></li>
            </ul>
        </section>
    </div>

    <aside class="{{ $card }}">
        <h2 class="mb-1 text-lg font-semibold">Settings</h2>
        <div class="{{ $row }}"><span class="{{ $muted }}">Paper mode</span><b>{{ $exam->paper_mode === 'fixed_set' ? 'Fixed set' : 'Pool draw' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Pass mark</span><b>{{ $num($exam->pass_mark) }}{{ $exam->pass_mark_type === 'percentage' ? '%' : ' marks' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Negative marking</span><b>{{ (float) $exam->negative_mark_fraction > 0 ? '−'.$num($exam->negative_mark_fraction).' of a mark' : 'Off' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Shuffle questions</span><b>{{ $exam->shuffle_questions ? 'Yes' : 'No' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Shuffle options</span><b>{{ $exam->shuffle_options ? 'Yes' : 'No' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Forward only</span><b>{{ $exam->forward_only ? 'Yes' : 'No' }}</b></div>
        @if ($exam->top_n)<div class="{{ $row }}"><span class="{{ $muted }}">Shortlist size</span><b>Top {{ $exam->top_n }}</b></div>@endif
        <div class="{{ $row }}"><span class="{{ $muted }}">Tie-break</span><b>{{ ['field_section_score' => 'Field section score', 'earliest_submission' => 'Earliest submission'][$exam->tie_break_rule] ?? 'None' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Results shown</span><b>{{ ['hidden' => 'Hidden until released', 'pass_fail' => 'Pass or fail only', 'full_breakdown' => 'Full breakdown'][$exam->result_release] ?? $exam->result_release }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Created by</span><b>{{ $exam->creator?->name ?? '–' }}</b></div>
    </aside>
</div>
@endsection
