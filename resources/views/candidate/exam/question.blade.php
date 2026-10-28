@extends('layouts.app')

@section('title', 'Question '.$number)

@section('body')
<div id="exam" class="mx-auto max-w-3xl px-4 pb-10 pt-6 sm:px-6"
     data-csrf="{{ csrf_token() }}"
     data-question="{{ $aq->id }}"
     data-remaining-ms="{{ $remainingMs }}"
     data-total-ms="{{ $aq->seconds_allowed * 1000 }}"
     data-save-url="{{ route('candidate.exam.answer') }}"
     data-event-url="{{ route('candidate.exam.event') }}">

    <div class="drip" aria-hidden="true"><div id="bar" data-level="ok"></div></div>

    <header class="mb-5 flex items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $section->title }}</p>
            <p class="font-display text-3xl font-bold leading-none tracking-tight sm:text-4xl">Question {{ $number }} <span class="text-xl font-semibold text-slate-400 sm:text-2xl">of {{ $total }}</span></p>
        </div>
        <div class="shrink-0 text-right">
            <div id="timer" class="clock text-4xl sm:text-5xl" data-level="ok" aria-live="off">0:00</div>
            <p id="timer-note" class="mt-1 hidden text-xs font-semibold text-slate-600 dark:text-slate-300">Time is almost up</p>
        </div>
    </header>

    <form id="answer-form" method="POST" action="{{ route('candidate.exam.next') }}" class="panel p-5 sm:p-7">
        @csrf
        <input type="hidden" name="question" value="{{ $aq->id }}">

        <p class="font-display whitespace-pre-line text-xl font-semibold leading-snug sm:text-2xl">{{ $question->stem }}</p>
        <p class="mt-2.5 text-sm text-slate-600 dark:text-slate-300">{{ $multi ? 'Choose every answer that is correct.' : 'Choose one answer.' }}</p>

        <div class="mt-5 space-y-2.5" role="{{ $multi ? 'group' : 'radiogroup' }}">
            @foreach ($options as $option)
                <label class="choice {{ $multi ? 'choice-multi' : '' }}">
                    <input type="{{ $multi ? 'checkbox' : 'radio' }}" name="selected[]" value="{{ $option->id }}" @checked(in_array($option->id, $selected, true)) class="sr-only">
                    <span class="choice-bubble" aria-hidden="true">{{ chr(65 + $loop->index) }}</span>
                    <span class="pt-1 text-base leading-snug sm:text-lg">{{ $option->body }}</span>
                </label>
            @endforeach
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-5 dark:border-slate-700">
            <span id="save-status" class="text-sm text-slate-600 dark:text-slate-300" aria-live="polite">&nbsp;</span>
            <button type="submit" id="next-btn" class="btn btn-primary px-8 py-3 text-lg">{{ $isLast ? 'Finish exam' : 'Next question' }}</button>
        </div>
    </form>

    <p class="mt-4 text-center text-xs text-slate-500 dark:text-slate-400">You cannot go back to earlier questions. Your answer is saved as you choose it.</p>
</div>

<script>
(() => {
    const root = document.getElementById('exam');
    const csrf = root.dataset.csrf, qid = +root.dataset.question, totalMs = +root.dataset.totalMs;
    const form = document.getElementById('answer-form'), status = document.getElementById('save-status');
    const timer = document.getElementById('timer'), bar = document.getElementById('bar'), note = document.getElementById('timer-note');
    const isLast = @json($isLast);
    const end = performance.now() + (+root.dataset.remainingMs);
    let timeUp = false;

    // ---- Timer (the server holds the real deadline; this only displays it) ----
    function tick() {
        const ms = Math.max(0, end - performance.now());
        const s = Math.ceil(ms / 1000);
        timer.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        bar.style.width = Math.min(100, ms / totalMs * 100) + '%';

        const level = s <= 5 ? 'bad' : s <= 10 ? 'warn' : 'ok';
        timer.dataset.level = level;
        bar.dataset.level = level;
        note.classList.toggle('hidden', level === 'ok' || timeUp);

        if (ms <= 0 && !timeUp) {
            timeUp = true;
            timer.textContent = 'Time up';
            form.querySelectorAll('input').forEach(i => i.disabled = true);
            status.textContent = 'Time is up. Moving to the next question…';
            setTimeout(() => location.reload(), 900);   // the server closes the question and serves the next one
        }
    }
    setInterval(tick, 200); tick();

    // ---- Autosave ----
    let saving = Promise.resolve();
    function selected() { return [...form.querySelectorAll('input[name="selected[]"]:checked')].map(i => +i.value); }

    function save() {
        saving = saving.then(async () => {
            status.textContent = 'Saving…';
            try {
                const r = await fetch(root.dataset.saveUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ question: qid, selected: selected() }),
                });
                if (r.status === 409) { location.reload(); return; }
                if (!r.ok) throw new Error('save failed');
                status.textContent = 'Saved ✓';
            } catch (e) {
                status.textContent = 'Not saved. Check your connection. Your choice will be sent when you press Next.';
            }
        });
    }
    form.addEventListener('change', save);

    // ---- Next / Finish ----
    form.addEventListener('submit', e => {
        if (!timeUp) {
            if (!selected().length && !confirm('You have not chosen an answer. Move on without one?')) { e.preventDefault(); return; }
            if (isLast && !confirm('This is the last question. Finish and submit the exam?')) { e.preventDefault(); return; }
        }
        document.getElementById('next-btn').disabled = true;
    });

    // ---- Integrity events (flagged for review, never an automatic penalty) ----
    function send(type) {
        fetch(root.dataset.eventUrl, {
            method: 'POST', keepalive: true,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ type }),
        }).catch(() => {});
    }
    document.addEventListener('visibilitychange', () => send(document.hidden ? 'tab_hidden' : 'tab_visible'));
})();
</script>
@endsection
