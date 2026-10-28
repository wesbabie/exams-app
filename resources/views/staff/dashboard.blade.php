@extends('layouts.staff')

@section('title', 'Staff dashboard')

@section('content')
<div class="mb-6">
    <span class="inline-block rounded-full bg-blue-50 px-3 py-0.5 text-sm font-semibold text-blue-700 dark:bg-slate-700 dark:text-blue-400">{{ $role->label() }}</span>
    <h1 class="mt-2 text-2xl font-bold">Welcome, {{ $user->name }}</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400">Your work areas. The ones marked "Coming soon" will switch on as they are built.</p>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    @foreach ($role->workAreas() as [$title, $text, $route])
        @if ($route)
            <a href="{{ route($route) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-400">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold">{{ $title }}</h2>
                    <span class="shrink-0 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-slate-700 dark:text-blue-400">Open</span>
                </div>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $text }}</p>
            </a>
        @else
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold">{{ $title }}</h2>
                    <span class="shrink-0 rounded-full border border-slate-300 px-2.5 py-0.5 text-xs text-slate-500 dark:border-slate-600 dark:text-slate-400">Coming soon</span>
                </div>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $text }}</p>
            </section>
        @endif
    @endforeach
</div>
@endsection
