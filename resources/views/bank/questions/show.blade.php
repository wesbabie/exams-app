@extends('layouts.staff')

@section('title', 'Question')

@php
    $card = 'panel';
    $row = 'flex justify-between gap-4 border-b border-slate-200 py-2 text-sm last:border-0 dark:border-slate-700';
    $muted = 'text-slate-500 dark:text-slate-400';
    $btnOutline = 'btn btn-outline btn-sm';
    $btnGhost = 'btn btn-neutral btn-sm';
@endphp

@section('content')
<x-page-head :title="'Question '.$question->id" :subtitle="$question->topic->name" :back="route('bank.questions.index')" back-label="Question bank">
    <x-slot:badge>
        @include('bank.partials.status-badge', ['status' => $question->status])
        <span class="text-sm {{ $muted }}">Version {{ $question->version }}</span>
        @if ($question->isReviewOverdue())<span class="chip chip-warn">Review due</span>@endif
    </x-slot:badge>
    <x-slot:actions>
        @can('update', $question)<a href="{{ route('bank.questions.edit', $question) }}" class="btn btn-outline btn-sm">Edit</a>@endcan
        @can('submit', $question)
            <form method="POST" action="{{ route('bank.questions.submit', $question) }}">@csrf<button class="btn btn-primary btn-sm">Send for review</button></form>
        @endcan
        @can('withdraw', $question)
            <form method="POST" action="{{ route('bank.questions.withdraw', $question) }}">@csrf<button class="btn btn-neutral btn-sm">Take back to draft</button></form>
        @endcan
        @can('review', $question)
            <form method="POST" action="{{ route('bank.questions.approve', $question) }}">@csrf<button class="btn btn-good btn-sm">Approve</button></form>
        @endcan
        @can('newVersion', $question)
            <form method="POST" action="{{ route('bank.questions.version', $question) }}">@csrf<button class="btn btn-outline btn-sm">Create new version</button></form>
        @endcan
        @can('retire', $question)
            <form method="POST" action="{{ route('bank.questions.retire', $question) }}" onsubmit="return confirm('Retire this question? It will not be used in new papers.')">@csrf<button class="btn btn-neutral btn-sm">Retire</button></form>
        @endcan
        @can('delete', $question)
            <form method="POST" action="{{ route('bank.questions.destroy', $question) }}" onsubmit="return confirm('Delete this draft?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form>
        @endcan
    </x-slot:actions>
</x-page-head>

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
            <p class="font-display whitespace-pre-line text-2xl font-semibold leading-snug">{{ $question->stem }}</p>

            @if ($question->type === 'true_false')
                <p class="mt-3 text-sm {{ $muted }}">True / false statement</p>
            @endif

            <ul class="mt-5 space-y-2.5">
                @foreach ($question->options as $o)
                    <li class="flex items-start gap-3.5 rounded-xl border-[1.5px] px-4 py-3 {{ $o->is_correct ? 'border-green-600 bg-green-50 dark:border-green-500 dark:bg-green-950' : 'border-slate-200 dark:border-slate-700' }}">
                        <span class="font-display grid h-9 w-9 shrink-0 place-items-center rounded-full border-[1.75px] text-[0.95rem] font-bold {{ $o->is_correct ? 'border-green-700 bg-green-700 text-white' : 'border-slate-400 text-slate-600 dark:text-slate-300' }}" aria-hidden="true">
                            @if ($o->is_correct)<x-icon name="check" class="h-5 w-5" />@else{{ chr(64 + $loop->iteration) }}@endif
                        </span>
                        <span class="flex-1 pt-1 text-base">{{ $o->body }}@if ($o->is_correct)<span class="sr-only"> (correct answer)</span>@endif</span>
                        @if ($o->is_correct)<span class="chip chip-good shrink-0">Correct</span>@endif
                        @if ($o->pin_last)<span class="shrink-0 pt-1.5 text-xs {{ $muted }}">kept last</span>@endif
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
                    <textarea name="note" rows="3" required maxlength="1000" placeholder="What needs to change?" class="field"></textarea>
                    @error('note')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    <button class="mt-3 {{ $btnGhost }}">Return with note</button>
                </form>
            </section>
        @endcan
    </div>

    <aside class="{{ $card }}">
        <h2 class="mb-1 text-lg font-bold">Details</h2>
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
