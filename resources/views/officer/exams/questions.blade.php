@extends('layouts.staff')

@section('title', 'Questions for '.$section->title)

@php
    $card = 'panel';
    $input = 'rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $muted = 'text-slate-500 dark:text-slate-400';
    $mini = 'rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-700';
    $st = $statuses[$section->id];
    $room = $section->question_count - $selected->count();
@endphp

@section('content')
<a href="{{ route('officer.exams.show', $exam) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← {{ $exam->title }}</a>
<h1 class="mb-1 mt-3 text-2xl font-bold">{{ $section->title }}</h1>
<p class="mb-4 text-sm {{ $muted }}">Choose the {{ $section->question_count }} questions every candidate will answer in this section. Only approved questions are listed.</p>

@include('officer.exams.partials.section-tabs')

@unless ($editable)
    <div class="mb-5 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm dark:border-slate-600 dark:bg-slate-800">This exam is no longer a draft, so its questions are locked.</div>
@endunless

@if ($st['note'])
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">{{ $st['note'] }}</div>
@endif

<div class="grid items-start gap-5 lg:grid-cols-2">
    {{-- Chosen --}}
    <section class="{{ $card }}">
        <div class="mb-3 flex items-baseline justify-between gap-3">
            <h2 class="text-lg font-semibold">Chosen for this section</h2>
            <span class="text-sm font-semibold {{ $st['ok'] ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">{{ $selected->count() }} / {{ $section->question_count }}</span>
        </div>

        @if ($selected->isEmpty())
            <p class="text-sm {{ $muted }}">Nothing chosen yet. Pick questions from the list on the right.</p>
        @else
            <ol class="space-y-2">
                @foreach ($selected as $row)
                    @php $q = $row->question; @endphp
                    <li class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <span class="mt-0.5 w-6 shrink-0 text-right text-sm font-bold {{ $muted }}">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            @if ($q)
                                <a href="{{ route('bank.questions.show', $q) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">{{ \Illuminate\Support\Str::limit($q->stem, 120) }}</a>
                                <div class="mt-0.5 text-xs {{ $muted }}">{{ $q->topic->name }} · {{ \App\Models\Question::TYPES[$q->type] ?? $q->type }}</div>
                                @if ($q->status !== 'approved')
                                    <div class="mt-1 text-xs font-semibold text-red-700 dark:text-red-400">{{ \App\Models\Question::STATUSES[$q->status] ?? $q->status }}: replace this question</div>
                                @endif
                            @else
                                <span class="text-sm text-red-700 dark:text-red-400">This question no longer exists. Remove it.</span>
                            @endif
                        </div>
                        @if ($editable)
                            <div class="flex shrink-0 gap-1">
                                <form method="POST" action="{{ route('officer.exams.sections.questions.move', [$exam, $section, $row->question_id]) }}">@csrf<input type="hidden" name="direction" value="up"><button class="{{ $mini }}" aria-label="Move up" @disabled($loop->first)>↑</button></form>
                                <form method="POST" action="{{ route('officer.exams.sections.questions.move', [$exam, $section, $row->question_id]) }}">@csrf<input type="hidden" name="direction" value="down"><button class="{{ $mini }}" aria-label="Move down" @disabled($loop->last)>↓</button></form>
                                <form method="POST" action="{{ route('officer.exams.sections.questions.remove', [$exam, $section, $row->question_id]) }}">@csrf @method('DELETE')<button class="{{ $mini }} text-red-700 dark:text-red-400" aria-label="Remove">✕</button></form>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
            <p class="mt-3 text-xs {{ $muted }}">This is the base order. Candidates see the questions shuffled{{ $exam->shuffle_questions ? '' : ' only if shuffling is switched on for this exam' }}.</p>
        @endif
    </section>

    {{-- Available --}}
    @if ($editable)
        <section class="{{ $card }}">
            <h2 class="mb-3 text-lg font-semibold">Add from the question bank</h2>

            <form method="GET" action="{{ route('officer.exams.sections.questions.edit', [$exam, $section]) }}" class="mb-3 grid gap-2 sm:grid-cols-3">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search" class="{{ $input }}">
                <select name="topic" class="{{ $input }}">
                    <option value="">All topics</option>
                    @foreach ($topics as $t)
                        <option value="{{ $t->id }}" @selected((string) request('topic') === (string) $t->id)>{{ $t->name }} ({{ $t->approved_count }})</option>
                    @endforeach
                </select>
                <button class="{{ $mini }} py-2 text-sm">Filter</button>
            </form>

            @if ($room <= 0)
                <p class="text-sm text-green-700 dark:text-green-400">This section is full. Remove a question to make room.</p>
            @elseif ($available->isEmpty())
                <p class="text-sm {{ $muted }}">No more approved questions match. Questions already used in this exam are hidden.</p>
            @else
                <form method="POST" action="{{ route('officer.exams.sections.questions.add', [$exam, $section]) }}">
                    @csrf
                    <ul class="space-y-2">
                        @foreach ($available as $q)
                            <li>
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700">
                                    <input type="checkbox" name="ids[]" value="{{ $q->id }}" class="mt-1 h-4 w-4 accent-blue-700">
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold">{{ \Illuminate\Support\Str::limit($q->stem, 120) }}</span>
                                        <span class="block text-xs {{ $muted }}">{{ $q->topic->name }} · {{ \App\Models\Question::TYPES[$q->type] ?? $q->type }}</span>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <span class="text-xs {{ $muted }}">Room for {{ $room }} more.</span>
                        <button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Add ticked questions</button>
                    </div>
                </form>
                <div class="mt-3">{{ $available->links() }}</div>
            @endif
        </section>
    @endif
</div>
@endsection
