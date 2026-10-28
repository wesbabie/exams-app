@extends('layouts.guest')

@section('title', 'Exam submitted')

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-2xl text-green-700 dark:bg-green-900 dark:text-green-300" aria-hidden="true">✓</div>
    <h1 class="text-2xl font-bold">Your exam has been submitted</h1>
    <p class="mt-2 text-slate-500 dark:text-slate-400">{{ $registration->exam->title }}</p>
    <p class="mt-4 text-sm">Your answers have been saved. The exam office will release the results. You do not need to do anything else.</p>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Please sign out and leave quietly so others can finish.</p>

    <form method="POST" action="{{ route('candidate.logout') }}">
        @csrf
        <button type="submit" class="mt-5 w-full rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Sign out</button>
    </form>
</div>
@endsection
