@extends('layouts.guest')

@section('title', 'See my results')

@section('aside')
    <p class="font-display text-3xl font-bold leading-tight tracking-tight">Your result is private to you.</p>
    <p class="mt-4 text-sm leading-relaxed text-blue-100">We send a one-time code to the email address the exam office has on file for you. Nobody can open your result without it.</p>
@endsection

@section('content')
<h1 class="text-3xl">See my results</h1>
<p class="mt-2 text-slate-600 dark:text-slate-300">For staff who sat a promotion exam. Enter your file number and we will email you a code.</p>

@if (session('status'))<div class="mt-5 rounded-lg border border-green-300 bg-green-50 px-3.5 py-2.5 text-sm text-green-900 dark:border-green-700 dark:bg-green-950 dark:text-green-200" role="status">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="alert-error" role="alert">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('myresults.send') }}">
    @csrf
    <label for="file_number" class="field-label">File number</label>
    <input id="file_number" name="file_number" value="{{ old('file_number') }}" required autofocus maxlength="30" autocomplete="off" autocapitalize="characters" spellcheck="false" class="field">
    <button type="submit" class="btn btn-primary mt-6 w-full py-3">Email me a code</button>
</form>

<div class="mt-8 space-y-2 border-t border-slate-200 pt-5 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
    <p>Results appear only after the exam office releases them.</p>
    <p>An intern applicant? <a href="{{ route('intern.login') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">Sign in to your application</a>, where your result will show.</p>
</div>
@endsection
