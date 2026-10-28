@extends('layouts.guest')

@section('title', 'Applicant sign-in')

@section('content')
<h1 class="text-3xl">Applicant sign-in</h1>
<p class="mt-2 text-slate-600 dark:text-slate-300">Continue your internship application or check its status.</p>

@if ($errors->any())
    <div class="alert-error" role="alert">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('intern.login.store') }}">
    @csrf
    <label for="email" class="field-label">Email address</label>
    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="field">

    <label for="password" class="field-label">Password</label>
    <input id="password" name="password" type="password" required autocomplete="current-password" class="field">

    <button type="submit" class="btn btn-primary mt-6 w-full py-3">Sign in</button>
</form>

<div class="mt-8 space-y-2 border-t border-slate-200 pt-5 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
    <p>New here? <a href="{{ route('intern.register') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">Start an application</a></p>
    <p>Taking the exam today? Use the <a href="{{ route('candidate.login') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">exam sign-in</a> with your application number and PIN.</p>
</div>
@endsection
