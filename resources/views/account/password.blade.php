@extends('layouts.staff')

@section('title', 'Change password')

@php
    $input = 'field';
    $label = 'mb-1 mt-4 block text-sm font-semibold';
@endphp

@section('content')
<div class="mx-auto max-w-md panel p-6">
    <h1 class="text-2xl font-bold">{{ $forced ? 'Choose your own password' : 'Change your password' }}</h1>

    @if ($forced)
        <p class="mt-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">You are using a temporary password. Choose your own before you continue.</p>
    @endif

    @if ($errors->any())
        <div class="mt-3 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">
            <ul class="list-disc pl-5">@foreach (collect($errors->all())->unique()->take(4) as $m)<li>{{ $m }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('account.password.update') }}">
        @csrf @method('PUT')

        <label for="current_password" class="{{ $label }}">{{ $forced ? 'Temporary password' : 'Current password' }}</label>
        <input id="current_password" name="current_password" type="password" required autocomplete="current-password" autofocus class="{{ $input }}">

        <label for="password" class="{{ $label }}">New password</label>
        <input id="password" name="password" type="password" required autocomplete="new-password" class="{{ $input }}">
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">At least 10 characters, with capital and small letters and a number.</p>

        <label for="password_confirmation" class="{{ $label }}">Repeat the new password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="{{ $input }}">

        <button type="submit" class="mt-5 btn btn-primary w-full py-3">Save password</button>
    </form>
</div>
@endsection
