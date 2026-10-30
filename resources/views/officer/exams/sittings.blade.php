@extends('layouts.staff')

@section('title', 'Sittings')

@php
    $card = 'panel';
    $input = 'field';
    $muted = 'text-slate-500 dark:text-slate-400';
    $mini = 'btn-mini';
    $paperTotal = $sittings->count();
    $paperFrozen = $sittings->filter(fn ($s) => $s->paper?->isFrozen())->count();
    $paperDraft = $sittings->filter(fn ($s) => $s->paper && ! $s->paper->isFrozen())->count();
    $examMinutes = (int) ceil($exam->totalSeconds() / 60);
@endphp

@section('content')
<x-page-head title="Sittings" :subtitle="'Each sitting is a date, a venue and a number of seats. The exam takes about '.$examMinutes.' minutes.'" :back="route('officer.exams.show', $exam)" :back-label="$exam->title">
    <x-slot:actions><a href="{{ route('officer.exams.candidates.index', $exam) }}" class="btn btn-outline btn-sm">Candidates</a></x-slot:actions>
</x-page-head>

<p class="mb-5 rounded-lg border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm dark:border-slate-600 dark:bg-slate-800">
    Times use the app timezone <b>{{ config('app.timezone') }}</b>. Server time now: <b>{{ now()->format('D j M Y, g:i A') }}</b>.
    If that is not your local time, set <code>APP_TIMEZONE=Africa/Lagos</code> in <code>.env</code> before creating sittings.
</p>

@if (in_array($exam->status, ['approved', 'scheduled'], true))
    <section class="panel mb-6 flex flex-wrap items-center gap-x-8 gap-y-4">
        <div class="min-w-[12rem] flex-1">
            <p class="font-display text-lg font-bold">Papers</p>
            <p class="text-sm {{ $muted }}">{{ $paperFrozen }} of {{ $paperTotal }} sitting{{ $paperTotal === 1 ? '' : 's' }} ready{{ $paperDraft ? ', '.$paperDraft.' in draft' : '' }}. A frozen paper never changes, and candidates cannot start until their sitting's paper is frozen.</p>
            <div class="mt-2.5 max-w-xs"><x-meter :value="$paperFrozen" :max="max(1, $paperTotal)" label="Papers frozen" /></div>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('officer.exams.papers.generate-all', $exam) }}">@csrf<button class="btn btn-outline btn-sm">Generate papers for all sittings</button></form>
            <form method="POST" action="{{ route('officer.exams.papers.freeze-all', $exam) }}" onsubmit="return confirm('Freeze every draft paper? They can no longer change.')">@csrf<button class="btn btn-good btn-sm">Freeze all draft papers</button></form>
        </div>
    </section>
@else
    <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">Papers can be generated once the exam is approved.</div>
@endif

<div class="grid items-start gap-5 lg:grid-cols-3">
    <section class="{{ $card }} !p-0 lg:col-span-2">
        @if ($sittings->isEmpty())
            <p class="p-5 text-sm {{ $muted }}">No sittings yet. Add the first one on the right.</p>
        @else
            <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach ($sittings as $s)
                    @php $full = $s->assigned_count >= $s->capacity; @endphp
                    <li class="flex flex-wrap items-start gap-x-5 gap-y-3 p-5">
                        <div class="w-14 shrink-0 rounded-xl bg-blue-50 py-2 text-center dark:bg-slate-700" aria-hidden="true">
                            <div class="font-display text-2xl font-bold leading-none">{{ $s->starts_at->format('j') }}</div>
                            <div class="mt-0.5 text-xs font-semibold text-blue-800 dark:text-blue-200">{{ $s->starts_at->format('M') }}</div>
                        </div>

                        <div class="min-w-[11rem] flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-display text-lg font-bold leading-tight">{{ $s->name }}</p>
                                @if ($s->status === 'closed')<span class="chip chip-muted">Closed</span>@else<span class="chip chip-info">Open</span>@endif
                            </div>
                            <p class="text-sm {{ $muted }}">{{ $s->starts_at->format('D j M Y') }}, {{ $s->starts_at->format('g:i A') }} to {{ $s->ends_at->format('g:i A') }}</p>
                            <p class="text-sm {{ $muted }}">{{ $s->venue ?? 'No venue set' }}</p>
                        </div>

                        <div class="w-52 shrink-0">
                            <div class="mb-1 flex items-baseline justify-between text-sm"><span class="{{ $muted }}">Seats</span><b class="tabular-nums {{ $full ? 'text-red-700 dark:text-red-400' : '' }}">{{ $s->assigned_count }} / {{ $s->capacity }}</b></div>
                            <x-meter :value="$s->assigned_count" :max="max(1, $s->capacity)" :tone="$full ? 'bad' : null" :label="$s->name.' seats'" />
                            <div class="mt-2.5 flex items-center gap-2 text-sm">
                                <span class="{{ $muted }}">Paper</span>
                                @if (! $s->paper)<span class="chip chip-neutral">None</span>
                                @elseif ($s->paper->isFrozen())<span class="chip chip-good">Frozen</span>
                                @else<span class="chip chip-warn">Draft</span>@endif
                                @if ($s->paper)<a href="{{ route('officer.exams.papers.preview', [$exam, $s]) }}" class="text-xs font-semibold text-blue-700 hover:underline dark:text-blue-300">Preview</a>@endif
                            </div>
                        </div>

                        <div class="flex w-full flex-wrap justify-end gap-1.5 border-t border-slate-100 pt-3 dark:border-slate-700">
                            @if (! $s->paper?->isFrozen())
                                <form method="POST" action="{{ route('officer.exams.papers.generate', [$exam, $s]) }}">@csrf<button class="{{ $mini }}">{{ $s->paper ? 'Regenerate' : 'Generate paper' }}</button></form>
                            @endif
                            @if ($s->paper && ! $s->paper->isFrozen())
                                <form method="POST" action="{{ route('officer.exams.papers.freeze', [$exam, $s]) }}">@csrf<button class="btn btn-good !px-2.5 !py-1 text-xs">Freeze</button></form>
                            @endif
                            <a href="{{ route('officer.exams.sittings.edit', [$exam, $s]) }}" class="{{ $mini }}">Edit</a>
                            @if ($s->status === 'closed')
                                <form method="POST" action="{{ route('officer.exams.sittings.reopen', [$exam, $s]) }}">@csrf<button class="{{ $mini }}">Reopen</button></form>
                            @else
                                <form method="POST" action="{{ route('officer.exams.sittings.close', [$exam, $s]) }}" onsubmit="return confirm('Close this sitting? Candidates will not be able to sign in to it.')">@csrf<button class="{{ $mini }}">Close</button></form>
                            @endif
                            @if ($s->assigned_count === 0)
                                <form method="POST" action="{{ route('officer.exams.sittings.destroy', [$exam, $s]) }}" onsubmit="return confirm('Delete this sitting?')">@csrf @method('DELETE')<button class="{{ $mini }} text-red-700 dark:text-red-400">Delete</button></form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-1 text-lg font-bold">Add a sitting</h2>
        @if ($errors->any())
            <div class="my-2 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('officer.exams.sittings.store', $exam) }}">
            @csrf
            <label for="name" class="mb-1 mt-3 block text-sm font-semibold">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Session 1" class="{{ $input }}">

            <label for="venue" class="mb-1 mt-3 block text-sm font-semibold">Venue</label>
            <input id="venue" name="venue" value="{{ old('venue') }}" maxlength="255" placeholder="ICT Lab" class="{{ $input }}">

            <label for="starts_at" class="mb-1 mt-3 block text-sm font-semibold">Starts</label>
            <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" required class="{{ $input }}">

            <label for="ends_at" class="mb-1 mt-3 block text-sm font-semibold">Ends</label>
            <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}" required class="{{ $input }}">
            <p class="mt-1 text-xs {{ $muted }}">Candidates can sign in between these times.</p>

            <label for="capacity" class="mb-1 mt-3 block text-sm font-semibold">Seats</label>
            <input id="capacity" name="capacity" type="number" min="1" max="2000" value="{{ old('capacity') }}" required class="{{ $input }}">

            <button type="submit" class="btn btn-primary mt-4 w-full">Add sitting</button>
        </form>
    </section>
</div>
@endsection
