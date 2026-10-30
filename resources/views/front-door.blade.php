@extends('layouts.app')

@section('title', 'Welcome')

@section('body')
<div class="omr-field flex min-h-screen flex-col px-5 py-8 text-white sm:px-10 lg:px-16">
    <x-brand :dark="true" />

    <main id="main" class="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center gap-10 py-12 lg:flex-row lg:items-center lg:gap-16">
        <div class="relative isolate max-w-xl lg:flex-1">
            <div class="pointer-events-none absolute -inset-x-16 -inset-y-16 -z-10 bg-[radial-gradient(closest-side,var(--color-blue-900)_60%,transparent)]" aria-hidden="true"></div>
            <h1 class="font-display text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">Promotion and internship exams, on screen.</h1>
            <p class="mt-5 max-w-md text-lg text-blue-100">Pick the option that fits you. You will need your file number or application number on exam day.</p>
        </div>

        <nav class="w-full rounded-2xl bg-white p-2 text-slate-800 shadow-xl lg:max-w-md dark:bg-slate-800 dark:text-slate-100" aria-label="Where do you want to go?">
            <a href="{{ route('candidate.login') }}" class="group flex items-center justify-between gap-4 rounded-xl p-4 hover:bg-blue-50 dark:hover:bg-slate-700">
                <span>
                    <span class="font-display block text-xl font-bold">I am taking an exam today</span>
                    <span class="mt-0.5 block text-sm text-slate-500 dark:text-slate-400">Sign in with your file or application number and the PIN from check-in.</span>
                </span>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-blue-700 text-white group-hover:bg-blue-800" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </a>

            <a href="{{ $intake ? route('intern.register') : route('intern.login') }}" class="group flex items-center justify-between gap-4 rounded-xl p-4 hover:bg-blue-50 dark:hover:bg-slate-700">
                <span>
                    <span class="font-display block text-xl font-bold">{{ $intake ? 'Apply for the internship' : 'Check my internship application' }}</span>
                    <span class="mt-0.5 block text-sm text-slate-500 dark:text-slate-400">
                        @if ($intake)
                            {{ $intake->name }} is open until {{ $intake->closes_at->format('j F Y') }}.
                        @else
                            Registration is closed. Sign in to see the status of an application.
                        @endif
                    </span>
                </span>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-slate-300 group-hover:border-blue-700 group-hover:text-blue-700 dark:border-slate-500" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </a>

            @if ($intake)
                <a href="{{ route('intern.login') }}" class="block rounded-xl px-4 pb-3 text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300">Already applied? Sign in</a>
            @endif

            <div class="mx-4 my-1 border-t border-slate-200 dark:border-slate-700"></div>

            <a href="{{ route('login') }}" class="flex items-center justify-between gap-4 rounded-xl p-4 text-sm hover:bg-blue-50 dark:hover:bg-slate-700">
                <span><span class="font-semibold">Exam office and staff sign-in</span><span class="block text-slate-500 dark:text-slate-400">Officers, invigilators, authors, reviewers, committee.</span></span>
            </a>
        </nav>
    </main>

    <p class="text-xs text-blue-200">ExamSphere</p>
</div>
@endsection
