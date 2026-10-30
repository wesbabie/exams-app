@extends('layouts.guest')

@section('title', 'Apply for the internship')

@section('content')
<h1 class="text-3xl">Internship application</h1>

@if (! $intake)
    <p class="mt-3 text-slate-600 dark:text-slate-300">Registration is not open right now. Check back when the next intake is announced.</p>
    <a href="{{ route('intern.login') }}" class="mt-5 inline-block font-semibold text-blue-700 hover:underline dark:text-blue-300">Already applied? Sign in</a>
@else
    <p class="mt-2 text-slate-600 dark:text-slate-300">{{ $intake->name }}. Registration closes {{ $intake->closes_at->format('D j M Y, g:i A') }}.</p>

    @if ($errors->any())
        <div class="alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('intern.register.store') }}">
        @csrf
        <div class="grid gap-x-4 sm:grid-cols-2">
            <div>
                <label for="surname" class="field-label">Surname</label>
                <input id="surname" name="surname" value="{{ old('surname') }}" required maxlength="255" autocomplete="family-name" class="field">
            </div>
            <div>
                <label for="first_name" class="field-label">First name</label>
                <input id="first_name" name="first_name" value="{{ old('first_name') }}" required maxlength="255" autocomplete="given-name" class="field">
            </div>
        </div>

        <label for="other_names" class="field-label">Other names <span class="font-normal text-slate-500">(optional)</span></label>
        <input id="other_names" name="other_names" value="{{ old('other_names') }}" maxlength="255" class="field">

        <label for="email" class="field-label">Email address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="field">
        <p class="field-hint">We send a code to this address to confirm it.</p>

        <label for="phone" class="field-label">Phone number</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required maxlength="30" autocomplete="tel" placeholder="08012345678" class="field">

        <label for="password" class="field-label">Password</label>
        <input id="password" name="password" type="password" required autocomplete="new-password" class="field">
        <p class="field-hint">At least 8 characters, with letters and numbers.</p>

        <label for="password_confirmation" class="field-label">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="field">

        <button type="submit" class="btn btn-primary mt-6 w-full py-3">Create my application</button>
    </form>

    <p class="mt-6 text-sm text-slate-600 dark:text-slate-300">Already applied? <a href="{{ route('intern.login') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">Sign in</a></p>
@endif
@endsection
