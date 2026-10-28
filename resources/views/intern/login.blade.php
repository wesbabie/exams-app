@extends('layouts.guest')

@section('title', 'Applicant sign-in')

@php
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
@endphp

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <h1 class="text-2xl font-bold">Applicant sign-in</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sign in to continue your internship application or check its status.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-300 bg-red-50 px-3.5 py-2.5 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('intern.login.store') }}" class="mt-2">
        @csrf
        <label for="email" class="mb-1.5 mt-4 block font-semibold">Email address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="{{ $input }}">

        <label for="password" class="mb-1.5 mt-4 block font-semibold">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password" class="{{ $input }}">

        <button type="submit" class="mt-5 w-full rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Sign in</button>
    </form>

    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">New here? <a href="{{ route('intern.register') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">Start an application</a></p>
    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Taking the exam today? Use the <a href="{{ route('candidate.login') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">exam sign-in</a> with your application number and PIN.</p>
</div>
@endsection
