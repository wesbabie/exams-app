@extends('layouts.focus')

@section('title', 'Exam submitted')

@section('content')
<div class="panel p-7 text-center sm:p-9">
    <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-full bg-blue-700 text-white" aria-hidden="true"><x-icon name="check" class="h-7 w-7" /></div>
    <h1 class="text-3xl">Your exam has been submitted</h1>
    <p class="mt-2 font-semibold text-slate-600 dark:text-slate-300">{{ $registration->exam->title }}</p>
    <p class="mt-5 text-sm">Your answers are saved. The exam office will release the results. You do not need to do anything else.</p>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Please sign out and leave quietly so others can finish.</p>

    <form method="POST" action="{{ route('candidate.logout') }}">
        @csrf
        <button type="submit" class="btn btn-primary mt-6 w-full py-3">Sign out</button>
    </form>
</div>
@endsection
