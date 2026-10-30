@extends('layouts.app')

@section('body')
<div class="grid min-h-screen lg:grid-cols-[5fr_6fr]">
    <aside class="omr-field relative hidden flex-col justify-between overflow-hidden p-10 text-white lg:sticky lg:top-0 lg:flex lg:h-screen" aria-hidden="false">
        <x-brand :dark="true" />

        <div class="relative max-w-md rounded-2xl bg-blue-950 p-7">
            <p class="font-display text-3xl font-bold leading-tight tracking-tight">Same questions for everyone in your category. Different order for each candidate.</p>
            <p class="mt-4 text-sm leading-relaxed text-blue-100">The server keeps the clock, so refreshing the page never gives you extra time. Your answers save as you choose them.</p>
        </div>

        <p class="text-xs text-blue-200">Fair, timed, on screen.</p>
    </aside>

    <main id="main" class="flex min-h-screen flex-col justify-center bg-slate-50 px-5 py-10 sm:px-10 dark:bg-slate-900">
        <div class="mx-auto w-full max-w-md">
            <x-brand class="mb-8 text-blue-800 dark:text-blue-100 lg:hidden" />
            @yield('content')
        </div>
    </main>
</div>
@endsection
