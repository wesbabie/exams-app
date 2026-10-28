@extends('layouts.app')

@section('body')
@php $applicant = auth('applicant')->user(); @endphp
<header class="bg-blue-900 text-white">
    <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 px-5 py-3.5">
        <x-brand :sub="false" class="text-white" />
        @if ($applicant)
            <div class="flex items-center gap-3 text-sm">
                <span class="hidden sm:inline">{{ $applicant->full_name }}</span>
                <form method="POST" action="{{ route('intern.logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg border border-white/30 px-3.5 py-2 font-semibold hover:bg-white/10"><x-icon name="logout" class="h-4 w-4" />Sign out</button>
                </form>
            </div>
        @endif
    </div>
</header>

<main id="main" class="mx-auto max-w-3xl px-5 py-8 pb-14">
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-950 dark:text-green-200" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ session('error') }}</div>
    @endif

    @yield('content')
</main>
@endsection
