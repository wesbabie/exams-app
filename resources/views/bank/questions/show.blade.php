@extends('layouts.staff')

@section('title', 'Question')

@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
    $row = 'flex justify-between gap-4 border-b border-slate-200 py-2 text-sm last:border-0 dark:border-slate-700';
    $muted = 'text-slate-500 dark:text-slate-400';
    $btnOutline = 'rounded-lg border border-blue-700 px-4 py-2 font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700';
    $btnGhost = 'rounded-lg border border-slate-400 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700';
@endphp

@section('content')
<a href="{{ route('bank.questions.index') }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← Question bank</a>

<div class="mb-5 mt-3 flex flex-wrap items-start justify-between gap-4">
    <div class="flex flex-wrap items-center gap-2">
        @include('bank.partials.status-badge', ['status' => $question->status])
        <span class="text-sm {{ $muted }}">Version {{ $question->version }}</span>
        @if ($question->isReviewOverdue())<span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-200">Review due</span>@endif
    </div>

    <div class="flex flex-wrap gap-2">
        @can('update', $question)<a href="{{ route('bank.questions.edit', $question) }}" class="{{ $btnOutline }}">Edit</a>@endcan
        @can('submit', $question)
            <form method="POST" action="{{ route('bank.questions.submit', $question) }}">@csrf<button class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white hover:bg-blue-800">Send for review</button></form>
        @endcan
        @can('withdraw', $question)
            <form method="POST" action="{{ route('bank.questions.withdraw', $question) }}">@csrf<button class="{{ $btnGhost }}">Take back to draft</button></form>
        @endcan
        @can('review', $question)
            <form method="POST" action="{{ route('bank.questions.approve', $question) }}">@csrf<button class="rounded-lg bg-green-700 px-4 py-2 font-semibold text-white hover:bg-green-800">Approve</button></form>
        @endcan
        @can('newVersion', $question)
            <form method="POST" action="{{ route('bank.questions.version', $question) }}">@csrf<button class="{{ $btnOutline }}">Create new version</button></form>
        @endcan
        @can('retire', $question)
            <form method="POST" action="{{ route('bank.questions.retire', $question) }}" onsubmit="return confirm('Retire this question? It will not be used in new papers.')">@csrf<button class="{{ $btnGhost }}">Retire</button></form>
        @endcan
        @can('delete', $question)
            <form method="POST" action="{{ route('bank.questions.destroy', $question) }}" onsubmit="return confirm('Delete this draft?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-400 px-4 py-2 font-semibold text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-slate-700">Delete</button></form>
        @endcan
    </div>
</div>

@if ($question->status === 'draft' && $question->review_note)
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        <b>Returned by {{ $question->reviewer?->name ?? 'the reviewer' }}:</b> {{ $question->review_note }}
    </div>
@endif

@if ($question->status === 'in_review' && $question->created_by === auth()->id())
    <div class="mb-5 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm dark:border-slate-600 dark:bg-slate-800">You wrote this question, so someone else needs to review it.</div>
@endif

<div class="grid items-start gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <section class="{{ $card }}">
            <p class="whitespace-pre-line text-lg font-semibold">{{ $question->stem }}</p>

            @if ($question->type === 'true_false')
                <p class="mt-3 text-sm {{ $muted }}">True / false statement</p>
            @endif

            <ul class="mt-4 space-y-2">
                @foreach ($question->options as $o)
                    <li class="flex items-start gap-3 rounded-lg border px-3.5 py-2.5 {{ $o->is_correct ? 'border-green-500 bg-green-50 dark:border-green-600 dark:bg-green-950' : 'border-slate-200 dark:border-slate-700' }}">
                        <span class="mt-0.5 w-5 shrink-0 font-bold {{ $o->is_correct ? 'text-green-700 dark:text-green-400' : $muted }}">{{ $o->is_correct ? '✓' : chr(64 + $loop->iteration) }}</span>
                        <span class="flex-1">{{ $o->body }}</span>
                        @if ($o->pin_last)<span class="shrink-0 text-xs {{ $muted }}">kept last</span>@endif
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs {{ $muted }}">Candidates see these options in a shuffled order.</p>
        </section>

        @if ($question->explanation)
            <section class="{{ $card }}">
                <h2 class="mb-1 text-lg font-semibold">Explanation</h2>
                <p class="whitespace-pre-line text-sm">{{ $question->explanation }}</p>
            </section>
        @endif

        @can('review', $question)
            <section class="{{ $card }}">
                <h2 class="mb-1 text-lg font-semibold">Return to the author</h2>
                <p class="mb-3 text-sm {{ $muted }}">If something needs fixing, tell the author what. The question goes back to draft.</p>
                <form method="POST" action="{{ route('bank.questions.return', $question) }}">
                    @csrf
                    <textarea name="note" rows="3" required maxlength="1000" placeholder="What needs to change?" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900"></textarea>
                    @error('note')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    <button class="mt-3 {{ $btnGhost }}">Return with note</button>
                </form>
            </section>
        @endcan
    </div>

    <aside class="{{ $card }}">
        <h2 class="mb-1 text-lg font-semibold">Details</h2>
        <div class="{{ $row }}"><span class="{{ $muted }}">Topic</span><b>{{ $question->topic->name }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Scope</span><b>{{ $question->category?->name ?? 'Shared' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Type</span><b>{{ \App\Models\Question::TYPES[$question->type] ?? $question->type }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Own timer</span><b>{{ $question->time_seconds ? $question->time_seconds.' sec' : 'Section timer' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Review again by</span><b>{{ $question->review_due_on?->format('j M Y') ?? '–' }}</b></div>
        <div class="{{ $row }}"><span class="{{ $muted }}">Written by</span><b>{{ $question->creator?->name ?? '–' }}</b></div>
        @if ($question->reviewed_at && $question->status !== 'draft')
            <div class="{{ $row }}"><span class="{{ $muted }}">Reviewed by</span><b>{{ $question->reviewer?->name ?? '–' }}</b></div>
            <div class="{{ $row }}"><span class="{{ $muted }}">Reviewed on</span><b>{{ $question->reviewed_at->format('j M Y') }}</b></div>
        @endif
        @if ($question->previousVersion)
            <div class="{{ $row }}"><span class="{{ $muted }}">Replaces</span><a href="{{ route('bank.questions.show', $question->previousVersion) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">Version {{ $question->previousVersion->version }}</a></div>
        @endif
        @if ($question->nextVersion)
            <div class="{{ $row }}"><span class="{{ $muted }}">Newer version</span><a href="{{ route('bank.questions.show', $question->nextVersion) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">Version {{ $question->nextVersion->version }}</a></div>
        @endif
    </aside>
</div>
@endsection
