@extends('layouts.dashboard')

@section('title', 'My results')
@section('who', $candidate?->full_name)
@section('logout_action', route('myresults.logout'))

@section('content')
<div class="mx-auto max-w-2xl">
    <h1 class="page-title">My results</h1>
    <p class="mb-6 mt-1.5 text-slate-600 dark:text-slate-300">{{ $candidate?->full_name }} · {{ isset($candidate->file_number) ? 'file number '.$candidate->file_number : 'application '.$candidate?->application_number }}</p>

    @forelse ($results as $result)
        <div class="mb-5">@include('candidate.partials.result-card', ['result' => $result])</div>
    @empty
        <div class="panel text-slate-600 dark:text-slate-300">No results are available to you yet. The exam office releases them after marking, and the ones you can see will appear here.</div>
    @endforelse

    <div class="mt-6 flex flex-wrap gap-2 print:hidden">
        @if ($results->isNotEmpty())<button type="button" onclick="window.print()" class="btn btn-neutral btn-sm">Print this page</button>@endif
        <p class="w-full text-sm text-slate-600 dark:text-slate-300">For your privacy this page signs you out after 30 minutes. Sign out when you finish on a shared computer.</p>
    </div>
</div>
@endsection
