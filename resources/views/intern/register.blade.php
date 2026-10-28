@extends('layouts.guest')

@section('title', 'Apply for the internship')

@php
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
@endphp

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <h1 class="text-2xl font-bold">Internship application</h1>

    @if (! $intake)
        <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">Registration is not open right now. Please check back when the hospital announces the next intake.</p>
        <a href="{{ route('intern.login') }}" class="mt-4 inline-block font-semibold text-blue-700 hover:underline dark:text-blue-400">Already applied? Sign in</a>
    @else
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $intake->name }}. Registration closes {{ $intake->closes_at->format('D j M Y, g:i A') }}.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-300 bg-red-50 px-3.5 py-2.5 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('intern.register.store') }}" class="mt-2">
            @csrf
            <div class="grid gap-x-4 sm:grid-cols-2">
                <div>
                    <label for="surname" class="mb-1.5 mt-4 block font-semibold">Surname</label>
                    <input id="surname" name="surname" value="{{ old('surname') }}" required maxlength="255" autocomplete="family-name" class="{{ $input }}">
                </div>
                <div>
                    <label for="first_name" class="mb-1.5 mt-4 block font-semibold">First name</label>
                    <input id="first_name" name="first_name" value="{{ old('first_name') }}" required maxlength="255" autocomplete="given-name" class="{{ $input }}">
                </div>
            </div>

            <label for="other_names" class="mb-1.5 mt-4 block font-semibold">Other names <span class="font-normal text-slate-500">(optional)</span></label>
            <input id="other_names" name="other_names" value="{{ old('other_names') }}" maxlength="255" class="{{ $input }}">

            <label for="email" class="mb-1.5 mt-4 block font-semibold">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="{{ $input }}">
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">We will send a code to this address to confirm it.</p>

            <label for="phone" class="mb-1.5 mt-4 block font-semibold">Phone number</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required maxlength="30" autocomplete="tel" placeholder="08012345678" class="{{ $input }}">

            <label for="password" class="mb-1.5 mt-4 block font-semibold">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="{{ $input }}">
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">At least 8 characters, with letters and numbers.</p>

            <label for="password_confirmation" class="mb-1.5 mt-4 block font-semibold">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="{{ $input }}">

            <button type="submit" class="mt-6 w-full rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Create my application</button>
        </form>

        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Already applied? <a href="{{ route('intern.login') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">Sign in</a></p>
    @endif
</div>
@endsection
