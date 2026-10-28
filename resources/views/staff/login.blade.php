@extends('layouts.guest')

@section('title', 'Staff sign-in')

@section('content')
<h1 class="text-3xl">Staff sign-in</h1>
<p class="mt-2 text-slate-600 dark:text-slate-300">For exam officers, invigilators, question authors, reviewers, the committee and administrators.</p>

@if ($errors->any())
    <div class="alert-error" role="alert">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('login.store') }}" id="staff-form">
    @csrf

    <label for="email" class="field-label">Email</label>
    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field">

    <label for="password" class="field-label">Password</label>
    <input id="password" name="password" type="password" required autocomplete="current-password" class="field">

    <label class="mt-5 flex items-center gap-2 text-sm">
        <input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-blue-700">
        <span>Keep me signed in on this computer</span>
    </label>

    <button type="submit" id="staff-submit" class="btn btn-primary mt-6 w-full py-3">Sign in</button>
</form>

<p class="mt-8 border-t border-slate-200 pt-5 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
    Taking an exam? <a href="{{ route('candidate.login') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">Candidate sign-in</a>
</p>

<script>
    document.getElementById('staff-form').addEventListener('submit', () => {
        const b = document.getElementById('staff-submit'); b.disabled = true; b.textContent = 'Signing in…';
    });
</script>
@endsection
