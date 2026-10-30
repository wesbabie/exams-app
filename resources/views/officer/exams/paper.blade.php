@extends('layouts.staff')

@section('title', 'Paper · '.$sitting->name)

@php
    $card = 'panel';
    $muted = 'text-slate-500 dark:text-slate-400';
    $btn = 'btn btn-neutral btn-sm';
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
@endphp

@section('content')
<x-page-head :title="'Paper for '.$sitting->name" :subtitle="$exam->title.' · '.$paper->questions->count().' questions · generated '.$paper->generated_at?->diffForHumans().'. Candidates see these questions shuffled.'" :back="route('officer.exams.sittings.index', $exam)" back-label="Sittings">
    <x-slot:badge>
        @if ($paper->isFrozen())<span class="chip chip-good">Frozen {{ $paper->frozen_at->format('j M, g:i A') }}</span>@else<span class="chip chip-warn">Draft</span>@endif
    </x-slot:badge>
    <x-slot:actions>
        @unless ($paper->isFrozen())
            <form method="POST" action="{{ route('officer.exams.papers.generate', [$exam, $sitting]) }}">@csrf<button class="{{ $btn }}">Regenerate</button></form>
            <form method="POST" action="{{ route('officer.exams.papers.freeze', [$exam, $sitting]) }}" onsubmit="return confirm('Freeze this paper? It can no longer change.')">@csrf<button class="btn btn-good btn-sm">Freeze paper</button></form>
        @endunless
    </x-slot:actions>
</x-page-head>

<div class="space-y-5">
    @foreach ($exam->sections as $section)
        <section class="{{ $card }}">
            <h2 class="mb-4 text-xl font-bold">{{ $section->position }}. {{ $section->title }} <span class="text-sm font-normal {{ $muted }}">· {{ ($bySection[$section->id] ?? collect())->count() }} questions</span></h2>
            <ol class="space-y-3">
                @foreach ($bySection[$section->id] ?? [] as $pq)
                    @php $q = $pq->question; @endphp
                    <li class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-display text-lg font-semibold leading-snug">{{ $loop->iteration }}. {{ \Illuminate\Support\Str::limit($q->stem, 200) }}</div>
                            <div class="shrink-0 text-right text-xs {{ $muted }}">{{ $num($pq->marks) }} mark{{ (float) $pq->marks === 1.0 ? '' : 's' }}<br>{{ $pq->seconds_allowed }} sec</div>
                        </div>
                        <div class="mt-0.5 text-xs {{ $muted }}">{{ $q->topic->name }} · {{ \App\Models\Question::TYPES[$q->type] ?? $q->type }}</div>
                        <ul class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
                            @foreach ($q->options as $o)
                                <li class="flex items-start gap-2.5 rounded-lg px-2 py-1.5 {{ $o->is_correct ? 'bg-green-50 dark:bg-green-950' : '' }}">
                                    <span class="font-display grid h-6 w-6 shrink-0 place-items-center rounded-full border-[1.5px] text-xs font-bold {{ $o->is_correct ? 'border-green-700 bg-green-700 text-white' : 'border-slate-400 text-slate-600 dark:text-slate-300' }}" aria-hidden="true">@if ($o->is_correct)<x-icon name="check" class="h-3.5 w-3.5" />@else{{ chr(64 + $loop->iteration) }}@endif</span>
                                    <span class="pt-0.5 {{ $o->is_correct ? 'font-semibold' : '' }}">{{ $o->body }}@if ($o->is_correct)<span class="sr-only"> (correct answer)</span>@endif</span>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>
        </section>
    @endforeach
</div>
@endsection
