@extends('layouts.guest')

@section('title', $section->title)

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <span class="inline-block rounded-full bg-blue-50 px-3 py-0.5 text-sm font-semibold text-blue-700 dark:bg-slate-700 dark:text-blue-400">Section {{ $section->position }}</span>
    <h1 class="mt-3 text-2xl font-bold">{{ $section->title }}</h1>

    <dl class="mt-4 space-y-2 text-sm">
        <div class="flex justify-between gap-4 border-b border-slate-200 pb-2 dark:border-slate-700"><dt class="text-slate-500 dark:text-slate-400">Questions in this section</dt><dd class="font-semibold">{{ $count }}</dd></div>
        <div class="flex justify-between gap-4 border-b border-slate-200 pb-2 dark:border-slate-700"><dt class="text-slate-500 dark:text-slate-400">Time for each question</dt><dd class="font-semibold">{{ $secondsEach }} seconds</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Starts at question</dt><dd class="font-semibold">{{ $number }} of {{ $total }}</dd></div>
    </dl>

    @if ($section->instructions)
        <p class="mt-4 whitespace-pre-line rounded-lg bg-slate-50 p-3 text-sm dark:bg-slate-900">{{ $section->instructions }}</p>
    @endif

    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">The timer for the first question starts when you press the button. You cannot go back to an earlier question.</p>

    <form method="POST" action="{{ route('candidate.exam.section-begin') }}">
        @csrf
        <button type="submit" class="mt-5 w-full rounded-lg bg-blue-700 px-5 py-3 text-lg font-semibold text-white hover:bg-blue-800">Begin section</button>
    </form>
</div>
@endsection
