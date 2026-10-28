@extends('layouts.staff')

@section('title', $applicant->full_name)

@php
    $card = 'panel';
    $input = 'field';
    $link = 'font-semibold text-blue-700 hover:underline dark:text-blue-400';
    $isPdf = $applicant->credential_path && str_ends_with(strtolower($applicant->credential_path), '.pdf');
@endphp

@section('content')
<a href="{{ route('officer.applicants.index') }}" class="{{ $link }} text-sm">← All applicants</a>
<h1 class="mt-1 text-2xl font-bold">{{ $applicant->surname }}, {{ $applicant->first_name }} {{ $applicant->other_names }}</h1>
<p class="mb-5 text-sm text-slate-500 dark:text-slate-400"><span class="font-mono">{{ $applicant->application_number }}</span> · {{ $applicant->statusLabel() }} · {{ $applicant->intake?->name }}</p>

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
@endif

<div class="grid items-start gap-5 lg:grid-cols-3">
    <section class="{{ $card }} lg:col-span-2">
        <h2 class="mb-3 text-lg font-semibold">Details</h2>
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Discipline</dt><dd class="font-semibold">{{ $applicant->category?->name ?? 'Not chosen' }}</dd></div>
            <div><dt class="text-slate-500">Email</dt><dd class="font-semibold">{{ $applicant->email }} @if ($applicant->hasVerifiedEmail())<span class="font-normal text-green-700 dark:text-green-400">(verified)</span>@endif</dd></div>
            <div><dt class="text-slate-500">Phone</dt><dd class="font-semibold">{{ $applicant->phone }} <span class="font-normal text-slate-500">(not verified)</span></dd></div>
            <div><dt class="text-slate-500">Professional registration no.</dt><dd class="font-semibold">{{ $applicant->professional_reg_no ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Submitted</dt><dd class="font-semibold">{{ $applicant->submitted_at?->format('j M Y, g:i A') ?? '—' }}</dd></div>
            @if ($applicant->reviewed_at)
                <div><dt class="text-slate-500">Last reviewed</dt><dd class="font-semibold">{{ $applicant->reviewed_at->format('j M Y, g:i A') }} by {{ $applicant->reviewer?->name ?? 'unknown' }}</dd></div>
            @endif
        </dl>

        @if ($applicant->review_note)
            <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200"><b>Note to applicant:</b> {{ $applicant->review_note }}</p>
        @endif

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div>
                <h3 class="mb-2 text-sm font-semibold">Passport photograph</h3>
                @if ($applicant->passport_path)
                    <a href="{{ route('officer.applicants.documents', [$applicant, 'passport']) }}" target="_blank">
                        <img src="{{ route('officer.applicants.documents', [$applicant, 'passport']) }}" alt="Passport photograph of {{ $applicant->full_name }}" class="max-h-64 rounded-lg border border-slate-200 dark:border-slate-600">
                    </a>
                @else
                    <p class="text-sm text-slate-500">Not uploaded.</p>
                @endif
            </div>
            <div>
                <h3 class="mb-2 text-sm font-semibold">Credential</h3>
                @if (! $applicant->credential_path)
                    <p class="text-sm text-slate-500">Not uploaded.</p>
                @elseif ($isPdf)
                    <a href="{{ route('officer.applicants.documents', [$applicant, 'credential']) }}" target="_blank" class="{{ $link }}">Open the PDF</a>
                @else
                    <a href="{{ route('officer.applicants.documents', [$applicant, 'credential']) }}" target="_blank">
                        <img src="{{ route('officer.applicants.documents', [$applicant, 'credential']) }}" alt="Credential of {{ $applicant->full_name }}" class="max-h-64 rounded-lg border border-slate-200 dark:border-slate-600">
                    </a>
                @endif
            </div>
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-2 text-lg font-semibold">Review</h2>

        @if ($applicant->status === 'submitted')
            @if ($exam)
                <p class="text-sm text-slate-600 dark:text-slate-300">Approving adds this applicant to <b>{{ $exam->title }}</b> and locks their discipline.</p>
            @else
                <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">No open exam exists for {{ $applicant->category?->name }} in {{ $applicant->intake?->year }}. Create it before approving.</p>
            @endif

            <form method="POST" action="{{ route('officer.applicants.approve', $applicant) }}" class="mt-3">
                @csrf
                <button type="submit" @disabled(! $exam) class="w-full btn btn-primary disabled:opacity-50">Approve</button>
            </form>

            <form method="POST" action="{{ route('officer.applicants.return', $applicant) }}" class="mt-5">
                @csrf
                <label for="note-return" class="mb-1 block text-sm font-semibold">Return for correction</label>
                <textarea id="note-return" name="note" rows="3" required maxlength="1000" placeholder="What does the applicant need to fix?" class="{{ $input }}"></textarea>
                <button type="submit" class="mt-2 w-full rounded-lg border border-blue-700 px-5 py-2 font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">Return to applicant</button>
            </form>

            <form method="POST" action="{{ route('officer.applicants.reject', $applicant) }}" class="mt-5">
                @csrf
                <label for="note-reject" class="mb-1 block text-sm font-semibold">Reject</label>
                <textarea id="note-reject" name="note" rows="3" required maxlength="1000" placeholder="Reason (the applicant will see this)" class="{{ $input }}"></textarea>
                <button type="submit" class="mt-2 w-full rounded-lg border border-red-600 px-5 py-2 font-semibold text-red-700 hover:bg-red-50 dark:border-red-400 dark:text-red-300 dark:hover:bg-slate-700">Reject application</button>
            </form>
        @elseif ($applicant->status === 'approved')
            <p class="text-sm">Approved. Documents verified {{ $applicant->documents_verified_at?->format('j M Y') }}.</p>
            @if ($registration)
                <p class="mt-2 text-sm">On the list for <b>{{ $registration->exam->title }}</b>@if ($registration->examSession), sitting <b>{{ $registration->examSession->name }}</b>@else, not yet assigned to a sitting @endif.</p>
                <a href="{{ route('officer.exams.candidates.index', $registration->exam) }}" class="{{ $link }} mt-2 inline-block text-sm">Open the candidate list</a>
            @endif
        @else
            <p class="text-sm text-slate-600 dark:text-slate-300">Nothing to review. The status is “{{ $applicant->statusLabel() }}”.</p>
        @endif
    </section>
</div>
@endsection
