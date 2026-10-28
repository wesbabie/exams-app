@extends('layouts.dashboard')

@section('title', 'Dashboard')

@php
    $cand = $registration->candidate;
    $isStaff = $registration->candidate_type === 'staff';
    $initials = \Illuminate\Support\Str::of($cand->full_name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $passValue = rtrim(rtrim(number_format((float) $exam->pass_mark, 2), '0'), '.');
    $passLabel = $exam->pass_mark_type === 'percentage' ? $passValue.'%' : $passValue.' marks';
    $duration = \Carbon\CarbonInterval::seconds($totals['seconds'])->cascade()->forHumans(['short' => true, 'parts' => 2]);
    $session = $registration->examSession;
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
    $row = 'flex justify-between gap-3 border-b border-slate-200 py-2 text-sm last:border-0 dark:border-slate-700';
@endphp

@section('logout_action', route('candidate.logout'))
@section('who', $cand->full_name)

@section('content')
@if (session('notice'))
    <div class="mb-5 rounded-lg border border-blue-300 bg-blue-50 px-4 py-3 text-sm text-blue-900 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100" role="status">{{ session('notice') }}</div>
@endif

<div class="grid items-start gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">

        <section class="{{ $card }}">
            <span class="inline-block rounded-full border border-green-600 px-3 py-0.5 text-sm font-semibold text-green-700 dark:border-green-400 dark:text-green-400">{{ $attempt ? 'Exam in progress' : 'Ready to begin' }}</span>
            <h1 class="mt-2.5 text-2xl font-bold">{{ $exam->title }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Read the overview and the rules below, then start when you are ready.</p>

            <div class="mt-4 grid grid-cols-3 gap-3">
                <div class="rounded-xl bg-blue-50 px-3 py-2.5 dark:bg-slate-700"><b class="block text-xl">{{ $totals['questions'] }}</b><span class="text-xs text-slate-500 dark:text-slate-400">Questions</span></div>
                <div class="rounded-xl bg-blue-50 px-3 py-2.5 dark:bg-slate-700"><b class="block text-xl">{{ $duration ?: '–' }}</b><span class="text-xs text-slate-500 dark:text-slate-400">Total time</span></div>
                <div class="rounded-xl bg-blue-50 px-3 py-2.5 dark:bg-slate-700"><b class="block text-xl">{{ $passLabel }}</b><span class="text-xs text-slate-500 dark:text-slate-400">Pass mark</span></div>
            </div>
            @if ($multiplier > 1)
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Your timers include extra time (×{{ rtrim(rtrim(number_format($multiplier, 2), '0'), '.') }}).</p>
            @endif
        </section>

        <section class="{{ $card }}">
            <h2 class="mb-3 text-lg font-semibold">Exam overview</h2>
            @if ($sections->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400">The sections for this exam have not been set up yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                <th class="py-2 pr-2">Section</th><th class="px-2 py-2 text-right">Questions</th><th class="px-2 py-2 text-right">Time per question</th><th class="py-2 pl-2 text-right">Marks</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($sections as $s)
                            @php $secs = (int) round($s->seconds_per_question * $multiplier); @endphp
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <td class="py-2.5 pr-2">{{ $loop->iteration }}. {{ $s->title }}</td>
                                <td class="px-2 py-2.5 text-right tabular-nums">{{ $s->question_count }}</td>
                                <td class="px-2 py-2.5 text-right tabular-nums">{{ $secs }} sec</td>
                                <td class="py-2.5 pl-2 text-right tabular-nums">{{ rtrim(rtrim(number_format($s->question_count * $s->marks_per_question, 2), '0'), '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-bold">
                                <td class="py-2.5 pr-2">Total</td><td class="px-2 py-2.5 text-right tabular-nums">{{ $totals['questions'] }}</td><td></td>
                                <td class="py-2.5 pl-2 text-right tabular-nums">{{ rtrim(rtrim(number_format($totals['marks'], 2), '0'), '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </section>

        <section class="{{ $card }}">
            <h2 class="mb-3 text-lg font-semibold">Rules</h2>
            <ul class="mb-4 list-disc space-y-1.5 pl-5">
                <li>You will see <b>one question at a time</b> and cannot go back to an earlier question.</li>
                <li>Each question has its own timer. When it runs out, your selection is saved and the next question appears.</li>
                <li>Questions and options appear in a different order for each candidate.</li>
                <li>Your answers are saved as you go. If you are disconnected, sign in again and you will continue where you stopped.</li>
                <li>Leaving the exam tab is recorded and flagged for review.</li>
                <li>Do not communicate with other candidates. Raise your hand if you need an invigilator.</li>
            </ul>

            @if (! $paperReady)
                <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">The paper for your sitting is not ready yet. Please tell the invigilator.</div>
            @else
                <form method="POST" action="{{ route('candidate.begin') }}">
                    @csrf
                    @unless ($attempt)
                        <label class="flex items-start gap-2.5">
                            <input type="checkbox" name="agree" value="1" id="agree" class="mt-1 h-5 w-5 accent-blue-700">
                            <span>I have read the rules and I am ready to begin.</span>
                        </label>
                    @endunless
                    <button type="submit" id="begin-btn" @disabled(! $attempt)
                            class="mt-4 rounded-lg bg-blue-700 px-6 py-3 font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">{{ $attempt ? 'Resume exam' : 'Start exam' }}</button>
                </form>
            @endif
        </section>
    </div>

    <aside class="space-y-5">
        <section class="{{ $card }}">
            <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-xl font-bold text-blue-700 dark:bg-slate-700 dark:text-blue-400" aria-hidden="true">{{ $initials }}</div>
            <h2 class="text-lg font-semibold">{{ $cand->full_name }}</h2>
            <p class="mb-2 text-sm text-slate-500 dark:text-slate-400">{{ $isStaff ? 'Staff candidate' : 'Intern applicant' }}</p>
            <div class="{{ $row }}"><span class="text-slate-500 dark:text-slate-400">{{ $isStaff ? 'File number' : 'Application no.' }}</span><b>{{ $isStaff ? $cand->file_number : $cand->application_number }}</b></div>
            @if ($isStaff)
                @if ($cand->cadre)<div class="{{ $row }}"><span class="text-slate-500 dark:text-slate-400">Cadre</span><b>{{ trim($cand->cadre.' '.$cand->level) }}</b></div>@endif
                @if ($cand->department)<div class="{{ $row }}"><span class="text-slate-500 dark:text-slate-400">Department</span><b>{{ $cand->department }}</b></div>@endif
            @endif
            @if ($registration->seat_number)<div class="{{ $row }}"><span class="text-slate-500 dark:text-slate-400">Seat</span><b>{{ $registration->seat_number }}</b></div>@endif
        </section>

        <section class="{{ $card }}">
            <h2 class="mb-1 text-lg font-semibold">Your sitting</h2>
            <div class="{{ $row }}"><span class="text-slate-500 dark:text-slate-400">Sitting</span><b>{{ $session->name }}</b></div>
            @if ($session->venue)<div class="{{ $row }}"><span class="text-slate-500 dark:text-slate-400">Venue</span><b>{{ $session->venue }}</b></div>@endif
            <div class="{{ $row }}"><span class="text-slate-500 dark:text-slate-400">Opened</span><b>{{ $session->starts_at->format('g:i A') }}</b></div>
            <div class="{{ $row }}"><span class="text-slate-500 dark:text-slate-400">Closes</span><b>{{ $session->ends_at->format('g:i A') }}</b></div>
            <div class="{{ $row }}"><span class="text-slate-500 dark:text-slate-400">Closes in</span><b id="closes-in" data-end="{{ $session->ends_at->toIso8601String() }}">–</b></div>
        </section>
    </aside>
</div>

<script>
    const agree = document.getElementById('agree'), btn = document.getElementById('begin-btn');
    if (agree) agree.addEventListener('change', () => { btn.disabled = !agree.checked; });

    const el = document.getElementById('closes-in'), end = new Date(el.dataset.end).getTime();
    function tick() {
        const s = Math.max(0, Math.floor((end - Date.now()) / 1000));
        const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60);
        el.textContent = s === 0 ? 'Closed' : (h ? h + ' h ' : '') + m + ' min';
    }
    tick(); setInterval(tick, 30000);
</script>
@endsection
