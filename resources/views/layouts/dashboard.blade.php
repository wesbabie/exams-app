@extends('layouts.app')

@section('body')
<header class="border-b border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3">
        <div class="font-bold text-blue-700 dark:text-blue-400">UATH Examination System</div>
        <div class="flex items-center gap-3 text-sm">
            <span class="hidden sm:inline">@yield('who')</span>
            <form method="POST" action="@yield('logout_action')">
                @csrf
                <button type="submit" class="rounded-lg border border-blue-700 px-4 py-2 font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">Sign out</button>
            </form>
        </div>
    </div>
</header>
<main class="mx-auto max-w-5xl px-4 py-6 pb-12">
    @yield('content')
</main>
@endsection
