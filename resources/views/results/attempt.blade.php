@extends('layouts.staff')

@section('title', 'Review attempt')

@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
    $muted = 'text-slate-500 dark:text-slate-400';
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $statusLabels = ['answered' => 'Answered', 'unanswered' => 'Left blank', 'timed_out' => 'Timed out', 'pending' => 'Not reached'];
@endphp

@section('content')
<a href="{{ route('results.exams.show', $exam) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← {{ $exam->title }}</a>

<div class="mb-5 mt-3 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">{{ $candidate?->full_name ?? 'Candidate' }}</h1>
        <p class="font-mono text-sm {{ $muted }}">{{ $candidate?->file_number ?? $candidate?->application_number }} · started {{ $attempt->started_at->format('j M, g:i A') }}@if ($attempt->submitted_at), submitted {{ $attempt->submitted_at->format('g:i A') }} ({{ str_replace('_', ' ', $attempt->submit_reason) }})@endif</p>
    </div>
    @if ($result)
        <div class="text-right">
            <div class="text-3xl font-bold tabular-nums">{{ $num($result->total_marks) }}<span class="text-lg font-normal {{ $muted }}"> / {{ $num($result->max_marks) }}</span></div>
            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $result->passed ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">{{ $result->passed ? 'Pass' : 'Fail' }} · {{ $num($result->percentage) }}%{{ $rank ? ' · rank '.$rank : '' }}</span>
        </div>
    @endif
</div>

<div class="grid items-start gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        @if ($result)
            <section class="{{ $card }}">
                <h2 class="mb-3 text-lg font-semibold">Sections</h2>
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400"><th class="py-2">Section</th><th class="px-2 py-2 text-right">Marks</th><th class="px-2 py-2 text-right">Right</th><th class="px-2 py-2 text-right">Wrong</th><th class="py-2 pl-2 text-right">Blank</th></tr></thead>
                    <tbody>
                    @foreach ($result->sections as $sr)
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <td class="py-2">{{ $exam->sections->firstWhere('id', $sr->exam_section_id)?->title }}</td>
                            <td class="px-2 py-2 text-right tabular-nums">{{ $num($sr->marks) }} / {{ $num($sr->max_marks) }}</td>
                            <td class="px-2 py-2 text-right tabular-nums">{{ $sr->correct_count }}</td><td class="px-2 py-2 text-right tabular-nums">{{ $sr->wrong_count }}</td><td class="py-2 pl-2 text-right tabular-nums">{{ $sr->unanswered_count }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        <section class="{{ $card }}">
            <h2 class="mb-3 text-lg font-semibold">Question by question</h2>
            <p class="mb-3 text-xs {{ $muted }}">In the order this candidate saw them. This page is for exam officers; it shows the answer key.</p>
            <ol class="space-y-3">
                @foreach ($questions as $aq)
                    @php
                        $q = $aq->paperQuestion->question;
                        $opts = $q->options->keyBy('id');
                        $picked = collect($aq->selected_option_ids ?? [])->map(fn ($id) => $opts[$id]->body ?? '?');
                        $right = $q->options->where('is_correct', true)->pluck('body');
                    @endphp
                    <li class="rounded-lg border p-3 {{ $aq->is_correct === true ? 'border-green-300 dark:border-green-700' : ($aq->is_correct === false ? 'border-red-300 dark:border-red-800' : 'border-slate-200 dark:border-slate-700') }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="text-sm font-semibold">{{ $aq->position }}. {{ \Illuminate\Support\Str::limit($q->stem, 220) }}</div>
                            <div class="shrink-0 text-right text-xs">
                                <b class="{{ $aq->is_correct === true ? 'text-green-700 dark:text-green-400' : ($aq->is_correct === false ? 'text-red-700 dark:text-red-400' : $muted) }}">{{ $num($aq->marks_awarded ?? 0) }} / {{ $num($aq->paperQuestion->marks) }}</b><br>
                                <span class="{{ $muted }}">{{ $statusLabels[$aq->status] ?? $aq->status }}</span>
                            </div>
                        </div>
                        <div class="mt-1 text-xs {{ $muted }}">{{ $aq->section?->title }}</div>
                        <div class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
                            <div><span class="text-xs {{ $muted }}">Chose:</span> {{ $picked->isEmpty() ? '—' : $picked->implode('; ') }}</div>
                            <div><span class="text-xs {{ $muted }}">Correct:</span> {{ $right->implode('; ') }}</div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    <aside class="{{ $card }}">
        <h2 class="mb-2 text-lg font-semibold">Activity</h2>
        @if ($events->isEmpty())
            <p class="text-sm {{ $muted }}">Nothing recorded.</p>
        @else
            <ul class="space-y-1.5 text-sm">
                @foreach ($events as $e)
                    <li class="flex justify-between gap-3"><span>{{ str_replace('_', ' ', $e->event_type) }}</span><span class="{{ $muted }}">{{ $e->created_at->format('g:i:s A') }}</span></li>
                @endforeach
            </ul>
        @endif
        <p class="mt-3 text-xs {{ $muted }}">Leaving the tab is recorded for review. It never changes a score.</p>
    </aside>
</div>
@endsection
