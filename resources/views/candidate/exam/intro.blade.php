@extends('layouts.focus')

@section('title', $section->title)

@section('content')
<div class="panel p-6 sm:p-8">
    <p class="text-sm font-semibold text-blue-700 dark:text-blue-300">Section {{ $section->position }}</p>
    <h1 class="mt-1 text-3xl">{{ $section->title }}</h1>

    <dl class="mt-6 divide-y divide-slate-200 text-sm dark:divide-slate-700">
        <div class="flex items-baseline justify-between gap-4 py-3"><dt class="text-slate-600 dark:text-slate-300">Questions in this section</dt><dd class="font-display text-2xl font-bold">{{ $count }}</dd></div>
        <div class="flex items-baseline justify-between gap-4 py-3"><dt class="text-slate-600 dark:text-slate-300">Time for each question</dt><dd class="font-display text-2xl font-bold">{{ $secondsEach }} seconds</dd></div>
        <div class="flex items-baseline justify-between gap-4 py-3"><dt class="text-slate-600 dark:text-slate-300">You start at question</dt><dd class="font-display text-2xl font-bold">{{ $number }} of {{ $total }}</dd></div>
    </dl>

    @if ($section->instructions)
        <p class="mt-4 whitespace-pre-line rounded-lg bg-slate-100 p-4 text-sm leading-relaxed dark:bg-slate-900">{{ $section->instructions }}</p>
    @endif

    <p class="mt-5 text-sm text-slate-600 dark:text-slate-300">The timer for the first question starts when you press the button. You cannot go back to an earlier question.</p>

    <form method="POST" action="{{ route('candidate.exam.section-begin') }}">
        @csrf
        <button type="submit" class="btn btn-primary mt-6 w-full py-3.5 text-lg">Begin section</button>
    </form>
</div>
@endsection
