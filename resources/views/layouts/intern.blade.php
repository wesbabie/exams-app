@extends('layouts.app')

@section('body')
@php $applicant = auth('applicant')->user(); @endphp
<header class="border-b border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
    <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 px-4 py-3">
        <div class="font-bold text-blue-700 dark:text-blue-400">UATH Internship Application</div>
        @if ($applicant)
            <div class="flex items-center gap-3 text-sm">
                <span class="hidden sm:inline">{{ $applicant->full_name }}</span>
                <form method="POST" action="{{ route('intern.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-blue-700 px-4 py-2 font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">Sign out</button>
                </form>
            </div>
        @endif
    </div>
</header>

<main class="mx-auto max-w-3xl px-4 py-6 pb-12">
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-950 dark:text-green-200" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ session('error') }}</div>
    @endif

    @yield('content')
</main>
@endsection
