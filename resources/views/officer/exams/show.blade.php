@extends('layouts.staff')

@section('title', $exam->title)

@php
    $card = 'panel';
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
<x-page-head :title="$exam->title" :subtitle="$exam->category->name.($exam->cycle_year ? ' · '.$exam->cycle_year : '')" :back="route('officer.exams.index')" back-label="All exams">
    <x-slot:badge>@include('officer.partials.status-badge', ['status' => $exam->status])</x-slot:badge>
    <x-slot:actions>
        <a href="{{ route('officer.exams.candidates.index', $exam) }}" class="btn btn-neutral btn-sm">Candidates</a>
        <a href="{{ route('officer.exams.sittings.index', $exam) }}" class="btn btn-neutral btn-sm">Sittings</a>
        <a href="{{ route('results.exams.show', $exam) }}" class="btn btn-neutral btn-sm">Results</a>
        @if ($exam->status === 'draft')
            <a href="{{ route('officer.exams.edit', $exam) }}" class="btn btn-outline btn-sm">Edit</a>
            <form method="POST" action="{{ route('officer.exams.submit', $exam) }}">@csrf
                <button class="btn btn-primary btn-sm">Send for review</button>
            </form>
        @elseif ($exam->status === 'in_review')
            <form method="POST" action="{{ route('officer.exams.reopen', $exam) }}">@csrf
                <button class="btn btn-neutral btn-sm">Return to draft</button>
            </form>
            @unless ($isCreator)
                <form method="POST" action="{{ route('officer.exams.approve', $exam) }}">@csrf
                    <button class="btn btn-good btn-sm">Approve</button>
                </form>
            @endunless
        @endif
    </x-slot:actions>
</x-page-head>

<x-lifecycle :status="$exam->status" />

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <x-stat label="Questions" :value="$totalQuestions" />
    <x-stat label="Time to answer" :value="$duration" />
    <x-stat label="Total marks" :value="$num($totalMarks)" />
    <x-stat label="Pass mark" :value="$num($exam->pass_mark).($exam->pass_mark_type === 'percentage' ? '%' : '')" :sub="$exam->pass_mark_type === 'percentage' ? null : 'marks'" />
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
        <section class="{{ $card }} !p-0">
            <h2 class="px-5 pb-3 pt-5 text-lg font-bold">The paper</h2>
            @if ($exam->sections->isEmpty())
                <p class="px-5 pb-5 text-sm {{ $muted }}">No sections yet.</p>
            @else
                <ol class="divide-y divide-slate-200 border-t border-slate-200 dark:divide-slate-700 dark:border-slate-700">
                    @foreach ($exam->sections as $s)
                        @php $st = $sectionStatus[$s->id] ?? ['have' => 0, 'need' => $s->question_count, 'ok' => false]; @endphp
                        <li class="flex flex-wrap items-center gap-x-5 gap-y-3 px-5 py-4">
                            <span class="font-display grid h-9 w-9 shrink-0 place-items-center rounded-full border-2 border-slate-300 text-base font-bold dark:border-slate-500" aria-hidden="true">{{ $s->position }}</span>
                            <div class="min-w-[12rem] flex-1">
                                <p class="font-display text-lg font-bold leading-tight">{{ $s->title }}</p>
                                <p class="text-sm {{ $muted }}">{{ $s->question_count }} questions · {{ $s->seconds_per_question }} sec each · {{ $num($s->marks_per_question) }} mark{{ (float) $s->marks_per_question === 1.0 ? '' : 's' }} each · {{ $num($s->question_count * $s->marks_per_question) }} marks</p>
                            </div>
                            <div class="w-44 shrink-0">
                                <div class="mb-1 flex items-baseline justify-between text-sm">
                                    <span class="{{ $st['ok'] ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }} font-semibold tabular-nums">{{ $st['have'] }} of {{ $st['need'] }}</span>
                                    <a href="{{ route('officer.exams.sections.questions.edit', [$exam, $s]) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">{{ $exam->status === 'draft' ? ($exam->paper_mode === 'fixed_set' ? 'Choose' : 'Set counts') : 'View' }}</a>
                                </div>
                                <x-meter :value="$st['have']" :max="max(1, $st['need'])" :tone="$st['ok'] ? null : 'warn'" :label="$s->title.' questions chosen'" />
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        @if (in_array($exam->status, ['draft', 'in_review'], true))
            <section class="{{ $card }}">
                <h2 class="mb-2 text-lg font-bold">Ready for review?</h2>
                @if (empty($issues))
                    <p class="flex items-center gap-2 text-sm font-semibold text-green-700 dark:text-green-400"><x-icon name="check" class="h-4 w-4" />Every section has its questions.</p>
                @else
                    <ul class="space-y-1.5 text-sm">
                        @foreach ($issues as $issue)
                            <li class="flex items-start gap-2 text-amber-800 dark:text-amber-300"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-marker-400" aria-hidden="true"></span>{{ $issue }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif

        <section class="{{ $card }} !p-0">
            <h2 class="px-5 pb-2 pt-5 text-lg font-bold">Then</h2>
            <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                <li><a href="{{ route('officer.exams.candidates.index', $exam) }}" class="block px-5 py-3 hover:bg-blue-50 dark:hover:bg-slate-700"><span class="font-semibold">Candidates</span><span class="block text-sm {{ $muted }}">Import the eligible list, or see approved applicants.</span></a></li>
                <li><a href="{{ route('officer.exams.sittings.index', $exam) }}" class="block px-5 py-3 hover:bg-blue-50 dark:hover:bg-slate-700"><span class="font-semibold">Sittings and papers</span><span class="block text-sm {{ $muted }}">Schedule sittings, assign seats, then generate and freeze each paper.</span></a></li>
            </ul>
        </section>
    </div>

    <aside class="{{ $card }}">
        <h2 class="mb-1 text-lg font-bold">Rules</h2>
        <p class="mt-3 text-xs font-semibold {{ $muted }}">Paper</p>
        <div class="{{ $row }}"><span class="{{ $muted }}">Paper mode</span><b>{{ $exam->paper_mode === 'fixed_set' ? 'Fixed set' : 'Pool draw' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Shuffle questions</span><b>{{ $exam->shuffle_questions ? 'Yes' : 'No' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Shuffle options</span><b>{{ $exam->shuffle_options ? 'Yes' : 'No' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Forward only</span><b>{{ $exam->forward_only ? 'Yes' : 'No' }}</b></div>
        <p class="mt-4 text-xs font-semibold {{ $muted }}">Scoring</p>
        <div class="{{ $row }}"><span class="{{ $muted }}">Negative marking</span><b>{{ (float) $exam->negative_mark_fraction > 0 ? '−'.$num($exam->negative_mark_fraction).' of a mark' : 'Off' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Tie-break</span><b>{{ ['field_section_score' => 'Field section score', 'earliest_submission' => 'Earliest submission'][$exam->tie_break_rule] ?? 'None' }}</b></div>
        @if ($exam->top_n)<div class="{{ $row }}"><span class="{{ $muted }}">Shortlist size</span><b>Top {{ $exam->top_n }}</b></div>@endif
        <p class="mt-4 text-xs font-semibold {{ $muted }}">Results</p>
        <div class="{{ $row }}"><span class="{{ $muted }}">Shown to candidates</span><b>{{ ['hidden' => 'Hidden until released', 'pass_fail' => 'Pass or fail only', 'full_breakdown' => 'Full breakdown'][$exam->result_release] ?? $exam->result_release }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Created by</span><b>{{ $exam->creator?->name ?? '–' }}</b></div>
    </aside>
</div>
@endsection
