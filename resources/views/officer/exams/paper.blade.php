@extends('layouts.staff')

@section('title', 'Paper · '.$sitting->name)

@php
    $card = 'panel';
    $muted = 'text-slate-500 dark:text-slate-400';
    $btn = 'btn btn-neutral btn-sm';
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
@endphp

@section('content')
<a href="{{ route('officer.exams.sittings.index', $exam) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← Sittings</a>

<div class="mb-5 mt-3 flex flex-wrap items-start justify-between gap-3">
    <div>
        @if ($paper->isFrozen())
            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">Frozen {{ $paper->frozen_at->format('j M, g:i A') }}</span>
        @else
            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-200">Draft</span>
        @endif
        <h1 class="mt-2 text-2xl font-bold">Paper for {{ $sitting->name }}</h1>
        <p class="text-sm {{ $muted }}">{{ $exam->title }} · {{ $paper->questions->count() }} questions · generated {{ $paper->generated_at?->diffForHumans() }}. Candidates see these questions shuffled.</p>
    </div>
    @unless ($paper->isFrozen())
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('officer.exams.papers.generate', [$exam, $sitting]) }}">@csrf<button class="{{ $btn }}">Regenerate</button></form>
            <form method="POST" action="{{ route('officer.exams.papers.freeze', [$exam, $sitting]) }}" onsubmit="return confirm('Freeze this paper? It can no longer change.')">@csrf<button class="btn btn-good btn-sm">Freeze paper</button></form>
        </div>
    @endunless
</div>

<div class="space-y-5">
    @foreach ($exam->sections as $section)
        <section class="{{ $card }}">
            <h2 class="mb-3 text-lg font-semibold">{{ $section->position }}. {{ $section->title }} <span class="text-sm font-normal {{ $muted }}">· {{ ($bySection[$section->id] ?? collect())->count() }} questions</span></h2>
            <ol class="space-y-3">
                @foreach ($bySection[$section->id] ?? [] as $pq)
                    @php $q = $pq->question; @endphp
                    <li class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold">{{ $loop->iteration }}. {{ \Illuminate\Support\Str::limit($q->stem, 200) }}</div>
                            <div class="shrink-0 text-right text-xs {{ $muted }}">{{ $num($pq->marks) }} mark{{ (float) $pq->marks === 1.0 ? '' : 's' }}<br>{{ $pq->seconds_allowed }} sec</div>
                        </div>
                        <div class="mt-0.5 text-xs {{ $muted }}">{{ $q->topic->name }} · {{ \App\Models\Question::TYPES[$q->type] ?? $q->type }}</div>
                        <ul class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
                            @foreach ($q->options as $o)
                                <li class="rounded px-2 py-1 {{ $o->is_correct ? 'bg-green-50 font-semibold text-green-800 dark:bg-green-950 dark:text-green-300' : '' }}">{{ $o->is_correct ? '✓' : '•' }} {{ $o->body }}</li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>
        </section>
    @endforeach
</div>
@endsection
