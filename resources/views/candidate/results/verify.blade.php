@extends('layouts.guest')

@section('title', 'Enter your code')

@section('aside')
    <p class="font-display text-3xl font-bold leading-tight tracking-tight">Your result is private to you.</p>
    <p class="mt-4 text-sm leading-relaxed text-blue-100">We send a one-time code to the email address the exam office has on file for you. Nobody can open your result without it.</p>
@endsection

@section('content')
<h1 class="text-3xl">Enter your code</h1>
<p class="mt-2 text-slate-600 dark:text-slate-300">File number <b>{{ $file }}</b>. The code lasts 15 minutes and works once.</p>

@if (session('status'))<div class="mt-5 rounded-lg border border-green-300 bg-green-50 px-3.5 py-2.5 text-sm text-green-900 dark:border-green-700 dark:bg-green-950 dark:text-green-200" role="status">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="alert-error" role="alert">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('myresults.verify.store') }}" autocomplete="off">
    @csrf
    <label for="code" class="field-label">6-digit code</label>
    <input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code" class="field font-display text-2xl font-bold tracking-[0.35em]">
    <button type="submit" class="btn btn-primary mt-6 w-full py-3">Show my results</button>
</form>

<div class="mt-8 space-y-2 border-t border-slate-200 pt-5 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
    <p>No email? Check your spam folder first. If it still does not come, the exam office may not have your email address: ask them for a printed result slip.</p>
    <p><a href="{{ route('myresults.request') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">Use a different file number or send a new code</a></p>
</div>
@endsection
