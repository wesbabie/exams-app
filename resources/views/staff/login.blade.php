@extends('layouts.guest')

@section('title', 'Staff sign-in')

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <h1 class="text-2xl font-bold">Staff sign-in</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">For exam officers, invigilators, question authors, reviewers, the committee and administrators.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-300 bg-red-50 px-3.5 py-2.5 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" id="staff-form" class="mt-2">
        @csrf

        <label for="email" class="mb-1.5 mt-4 block font-semibold">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
               class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900">

        <label for="password" class="mb-1.5 mt-4 block font-semibold">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password"
               class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900">

        <label class="mt-4 flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-blue-700">
            <span>Keep me signed in on this computer</span>
        </label>

        <button type="submit" id="staff-submit"
                class="mt-5 w-full rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800 disabled:opacity-60">Sign in</button>
    </form>

    <p class="mt-5 border-t border-slate-200 pt-4 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
        Taking an exam? <a href="{{ route('candidate.login') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">Candidate sign-in</a>
    </p>
</div>

<script>
    document.getElementById('staff-form').addEventListener('submit', () => {
        const b = document.getElementById('staff-submit'); b.disabled = true; b.textContent = 'Signing in…';
    });
</script>
@endsection
