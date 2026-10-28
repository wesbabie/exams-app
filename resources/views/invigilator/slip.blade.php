@extends('layouts.app')

@section('title', 'Candidate slip')

@section('body')
<div class="mx-auto max-w-md px-4 py-8 print:py-0">
    <div class="mb-4 flex flex-wrap gap-2 print:hidden">
        @if ($pin)
            <button type="button" onclick="window.print()" class="btn btn-primary">Print slip</button>
        @endif
        <a href="{{ route('invigilator.sittings.show', $sitting) }}" class="rounded-lg border border-slate-400 px-5 py-2.5 font-semibold hover:bg-slate-100 dark:hover:bg-slate-700">Back to the sitting</a>
    </div>

    <div class="rounded-2xl border-2 border-slate-300 bg-white p-6 text-slate-900 dark:border-slate-600 print:rounded-none print:border-black">
        <div class="text-center">
            <div class="text-sm font-semibold uppercase tracking-wide text-slate-500"> </div>
            <h1 class="mt-1 text-xl font-bold">Candidate slip</h1>
        </div>

        <dl class="mt-5 space-y-2 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Name</dt><dd class="text-right font-semibold">{{ $candidate?->full_name }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ $registration->candidate_type === 'staff' ? 'File number' : 'Application no.' }}</dt><dd class="font-mono font-semibold">{{ $candidate?->file_number ?? $candidate?->application_number }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Exam</dt><dd class="text-right font-semibold">{{ $registration->exam->title }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Sitting</dt><dd class="text-right font-semibold">{{ $sitting->name }}</dd></div>
            @if ($sitting->venue)<div class="flex justify-between gap-4"><dt class="text-slate-500">Venue</dt><dd class="text-right font-semibold">{{ $sitting->venue }}</dd></div>@endif
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Date and time</dt><dd class="text-right font-semibold">{{ $sitting->starts_at->format('D j M Y, g:i A') }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Seat</dt><dd class="text-xl font-bold">{{ $registration->seat_number }}</dd></div>
        </dl>

        <div class="mt-6 rounded-xl border-2 border-dashed border-slate-400 py-5 text-center">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Your PIN</div>
            @if ($pin)
                <div class="mt-1 font-mono text-5xl font-bold tracking-[0.35em] pl-[0.35em]">{{ $pin }}</div>
            @else
                <div class="mx-4 mt-2 text-sm text-slate-500">The PIN is only shown once, right after it is issued. Go back and choose <b>New PIN</b> to print another slip.</div>
            @endif
        </div>

        <ol class="mt-6 list-decimal space-y-1 pl-5 text-sm">
            <li>Go to your seat and wait. The invigilator will tell you when you may begin.</li>
            <li>Open <span class="font-mono">{{ url('/exam/login') }}</span>.</li>
            <li>Sign in with your {{ $registration->candidate_type === 'staff' ? 'file number' : 'application number' }} and the PIN above.</li>
            <li>Keep your PIN private. It only works for this sitting.</li>
        </ol>
    </div>
</div>
@endsection
