@extends('layouts.intern')

@section('title', 'My application')

@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
    $badge = match ($applicant->status) {
        'approved' => 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
        'returned' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
        default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    };
@endphp

@section('content')
<div class="{{ $card }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">My application</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $applicant->intake?->name }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $badge }}">{{ $applicant->statusLabel() }}</span>
    </div>

    <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
        <div><dt class="text-slate-500">Application number</dt><dd class="font-mono font-semibold">{{ $applicant->application_number }}</dd></div>
        <div><dt class="text-slate-500">Discipline</dt><dd class="font-semibold">{{ $applicant->category?->name ?? 'Not chosen yet' }}</dd></div>
    </dl>

    @if ($applicant->review_note && in_array($applicant->status, ['returned', 'rejected'], true))
        <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
            <div class="font-semibold">{{ $applicant->status === 'rejected' ? 'Reason for rejection' : 'What needs correcting' }}</div>
            <p class="mt-1">{{ $applicant->review_note }}</p>
        </div>
    @endif

    @switch($applicant->status)
        @case('draft')
        @case('returned')
            @if ($applicant->canEditProfile())
                <p class="mt-4 text-sm">Complete your details, choose your discipline and upload your passport photo and credential. Then submit for review.</p>
                <a href="{{ route('intern.profile.edit') }}" class="mt-3 inline-block rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">{{ $applicant->status === 'returned' ? 'Make corrections' : 'Continue my application' }}</a>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">You can make changes until {{ $applicant->intake->closes_at->format('D j M Y, g:i A') }}.</p>
            @else
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">The registration window has closed, so this application can no longer be changed.</p>
            @endif
            @break

        @case('submitted')
            <p class="mt-4 text-sm">Your application was submitted {{ $applicant->submitted_at?->format('j M Y') }}. Admin staff are checking your documents. Sign in again later to see the result.</p>
            @break

        @case('approved')
            <p class="mt-4 text-sm">Your documents are approved. Your discipline is now fixed.</p>
            @if ($registration?->examSession)
                <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm dark:bg-slate-700">
                    <div class="font-semibold">{{ $registration->exam->title }}</div>
                    <div>{{ $registration->examSession->starts_at->format('l j F Y, g:i A') }}</div>
                    @if ($registration->examSession->venue)<div>{{ $registration->examSession->venue }}</div>@endif
                </div>
                <a href="{{ route('intern.slip') }}" class="mt-3 inline-block rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">View exam slip</a>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Bring your slip and passport on the day. Your PIN is given to you at check-in.</p>
            @else
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">You will see your exam date, time and venue here once a sitting is assigned.</p>
            @endif
            @break

        @case('rejected')
            <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">This application was not accepted.</p>
            @break
    @endswitch
</div>
@endsection
