@extends('layouts.app')

@section('title', 'Exam slip')

@section('body')
<div class="mx-auto max-w-md px-4 py-8 print:py-0">
    <div class="mb-4 flex flex-wrap gap-2 print:hidden">
        <button type="button" onclick="window.print()" class="rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Print slip</button>
        <a href="{{ route('intern.dashboard') }}" class="rounded-lg border border-slate-400 px-5 py-2.5 font-semibold hover:bg-slate-100 dark:hover:bg-slate-700">Back</a>
    </div>

    <div class="rounded-2xl border-2 border-slate-300 bg-white p-6 text-slate-900 print:rounded-none print:border-black">
        <div class="text-center">
            <div class="text-sm font-semibold uppercase tracking-wide text-slate-500">University of Abuja Teaching Hospital</div>
            <h1 class="mt-1 text-xl font-bold">Internship entrance exam slip</h1>
        </div>

        <dl class="mt-5 space-y-2 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Name</dt><dd class="text-right font-semibold">{{ $applicant->full_name }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Application no.</dt><dd class="font-mono font-semibold">{{ $applicant->application_number }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Discipline</dt><dd class="text-right font-semibold">{{ $applicant->category?->name }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Date and time</dt><dd class="text-right font-semibold">{{ $registration->examSession->starts_at->format('D j M Y, g:i A') }}</dd></div>
            @if ($registration->examSession->venue)
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Venue</dt><dd class="text-right font-semibold">{{ $registration->examSession->venue }}</dd></div>
            @endif
            @if ($registration->seat_number)
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Seat</dt><dd class="text-xl font-bold">{{ $registration->seat_number }}</dd></div>
            @endif
        </dl>

        <ol class="mt-6 list-decimal space-y-1 pl-5 text-sm">
            <li>Arrive early and bring this slip and your passport.</li>
            <li>An invigilator will check your identity and give you a 6-digit PIN.</li>
            <li>Sign in at <span class="font-mono">{{ url('/exam/login') }}</span> with your application number and the PIN.</li>
            <li>Do not share your PIN. It only works for this sitting.</li>
        </ol>
    </div>
</div>
@endsection
