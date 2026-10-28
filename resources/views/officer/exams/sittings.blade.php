@extends('layouts.staff')

@section('title', 'Sittings')

@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $muted = 'text-slate-500 dark:text-slate-400';
    $mini = 'rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-700';
    $examMinutes = (int) ceil($exam->totalSeconds() / 60);
@endphp

@section('content')
<a href="{{ route('officer.exams.show', $exam) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← {{ $exam->title }}</a>
<div class="mb-5 mt-3 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">Sittings</h1>
        <p class="text-sm {{ $muted }}">Each sitting is a date, a venue and a number of seats. The exam takes about {{ $examMinutes }} minutes.</p>
    </div>
    <a href="{{ route('officer.exams.candidates.index', $exam) }}" class="rounded-lg border border-blue-700 px-4 py-2 font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">Candidates</a>
</div>

<div class="mb-5 rounded-lg border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm dark:border-slate-600 dark:bg-slate-800">
    Times use the app timezone <b>{{ config('app.timezone') }}</b>. Server time now: <b>{{ now()->format('D j M Y, g:i A') }}</b>.
    If that is not your local time, set <code>APP_TIMEZONE=Africa/Lagos</code> in <code>.env</code> before creating sittings.
</div>

@if (in_array($exam->status, ['approved', 'scheduled'], true))
    <div class="mb-5 flex flex-wrap items-center gap-2">
        <form method="POST" action="{{ route('officer.exams.papers.generate-all', $exam) }}">@csrf<button class="rounded-lg border border-blue-700 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">Generate papers for all sittings</button></form>
        <form method="POST" action="{{ route('officer.exams.papers.freeze-all', $exam) }}" onsubmit="return confirm('Freeze every draft paper? They can no longer change.')">@csrf<button class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Freeze all draft papers</button></form>
        <span class="text-xs {{ $muted }}">A frozen paper never changes. Candidates cannot start until their sitting's paper is frozen.</span>
    </div>
@else
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">Papers can be generated once the exam is approved.</div>
@endif

<div class="grid items-start gap-5 lg:grid-cols-3">
    <section class="{{ $card }} lg:col-span-2">
        @if ($sittings->isEmpty())
            <p class="text-sm {{ $muted }}">No sittings yet. Add the first one on the right.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            <th class="py-2 pr-2">Sitting</th><th class="px-2 py-2">When</th><th class="px-2 py-2 text-right">Seats</th><th class="px-2 py-2">Status</th><th class="px-2 py-2">Paper</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($sittings as $s)
                        <tr class="border-b border-slate-200 align-top dark:border-slate-700">
                            <td class="py-2.5 pr-2"><div class="font-semibold">{{ $s->name }}</div><div class="text-xs {{ $muted }}">{{ $s->venue ?? 'No venue' }}</div></td>
                            <td class="px-2 py-2.5">{{ $s->starts_at->format('D j M Y') }}<div class="text-xs {{ $muted }}">{{ $s->starts_at->format('g:i A') }} – {{ $s->ends_at->format('g:i A') }}</div></td>
                            <td class="px-2 py-2.5 text-right tabular-nums">{{ $s->assigned_count }} / {{ $s->capacity }}</td>
                            <td class="px-2 py-2.5">
                                @if ($s->status === 'closed')<span class="rounded-full bg-zinc-200 px-2.5 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">Closed</span>
                                @else<span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">Open</span>@endif
                            </td>
                            <td class="px-2 py-2.5">
                                @if (! $s->paper)
                                    <span class="text-xs text-slate-500 dark:text-slate-400">None</span>
                                @elseif ($s->paper->isFrozen())
                                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">Frozen</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-200">Draft</span>
                                @endif
                                @if ($s->paper)<a href="{{ route('officer.exams.papers.preview', [$exam, $s]) }}" class="ml-1 text-xs font-semibold text-blue-700 hover:underline dark:text-blue-400">Preview</a>@endif
                            </td>
                            <td class="py-2.5 pl-2">
                                <div class="flex flex-wrap justify-end gap-1.5">
                                    @if (! $s->paper?->isFrozen())
                                        <form method="POST" action="{{ route('officer.exams.papers.generate', [$exam, $s]) }}">@csrf<button class="{{ $mini }}">{{ $s->paper ? 'Regenerate' : 'Generate paper' }}</button></form>
                                    @endif
                                    @if ($s->paper && ! $s->paper->isFrozen())
                                        <form method="POST" action="{{ route('officer.exams.papers.freeze', [$exam, $s]) }}">@csrf<button class="rounded-lg bg-green-700 px-2.5 py-1 text-xs font-semibold text-white hover:bg-green-800">Freeze</button></form>
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
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-1 text-lg font-semibold">Add a sitting</h2>
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

            <button type="submit" class="mt-4 w-full rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Add sitting</button>
        </form>
    </section>
</div>
@endsection
