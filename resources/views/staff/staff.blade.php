@extends('layouts.app')

@php
    $staffRole = \App\Enums\StaffRole::tryFrom((string) auth()->user()->role);
@endphp

@section('body')
<header class="border-b border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-3">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
            <div class="font-bold text-blue-700 dark:text-blue-400">Examination System</div>
            <nav class="flex gap-1 text-sm font-semibold" aria-label="Main">
                @foreach ($staffRole?->navLinks() ?? [] as [$label, $route, $pattern])
                    <a href="{{ route($route) }}"
                       class="rounded-lg px-3 py-1.5 {{ request()->routeIs($pattern) ? 'bg-blue-50 text-blue-700 dark:bg-slate-700 dark:text-blue-400' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <span class="hidden sm:inline">{{ auth()->user()->name }} · {{ $staffRole?->label() }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-blue-700 px-4 py-2 font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">Sign out</button>
            </form>
        </div>
    </div>
</header>

<main class="mx-auto max-w-6xl px-4 py-6 pb-12">
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-950 dark:text-green-200" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ session('error') }}</div>
    @endif

    @yield('content')
</main>
@endsection
