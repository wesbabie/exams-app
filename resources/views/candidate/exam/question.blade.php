@extends('layouts.app')

@section('title', 'Question '.$number)

@section('body')
<div id="exam" class="mx-auto max-w-3xl px-4 py-4"
     data-csrf="{{ csrf_token() }}"
     data-question="{{ $aq->id }}"
     data-remaining-ms="{{ $remainingMs }}"
     data-total-ms="{{ $aq->seconds_allowed * 1000 }}"
     data-save-url="{{ route('candidate.exam.answer') }}"
     data-event-url="{{ route('candidate.exam.event') }}">

    <div class="mb-3 flex items-center justify-between gap-3">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $section->title }}</div>
            <div class="text-sm font-semibold">Question {{ $number }} of {{ $total }}</div>
        </div>
        <div id="timer" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-2xl font-bold tabular-nums dark:border-slate-600 dark:bg-slate-800" aria-live="off">0:00</div>
    </div>

    <div class="mb-4 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"><div id="bar" class="h-full w-full bg-blue-700 transition-[width] duration-200 ease-linear"></div></div>

    <form id="answer-form" method="POST" action="{{ route('candidate.exam.next') }}"
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        @csrf
        <input type="hidden" name="question" value="{{ $aq->id }}">

        <p class="whitespace-pre-line text-lg font-semibold leading-relaxed">{{ $question->stem }}</p>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $multi ? 'Choose every answer that is correct.' : 'Choose one answer.' }}</p>

        <div class="mt-4 space-y-2.5">
            @foreach ($options as $option)
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 border-slate-200 p-3.5 hover:border-blue-400 has-[:checked]:border-blue-700 has-[:checked]:bg-blue-50 dark:border-slate-600 dark:has-[:checked]:bg-slate-700">
                    <input type="{{ $multi ? 'checkbox' : 'radio' }}" name="selected[]" value="{{ $option->id }}" @checked(in_array($option->id, $selected, true)) class="mt-1 h-5 w-5 shrink-0 accent-blue-700">
                    <span class="text-base leading-snug">{{ $option->body }}</span>
                </label>
            @endforeach
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <span id="save-status" class="text-sm text-slate-500 dark:text-slate-400" aria-live="polite">&nbsp;</span>
            <button type="submit" id="next-btn" class="rounded-lg bg-blue-700 px-8 py-3 text-lg font-semibold text-white hover:bg-blue-800 disabled:opacity-60">{{ $isLast ? 'Finish exam' : 'Next question' }}</button>
        </div>
    </form>

    <p class="mt-3 text-center text-xs text-slate-500 dark:text-slate-400">You cannot go back to earlier questions. Your answer is saved as you choose it.</p>
</div>

<script>
(() => {
    const root = document.getElementById('exam');
    const csrf = root.dataset.csrf, qid = +root.dataset.question, totalMs = +root.dataset.totalMs;
    const form = document.getElementById('answer-form'), status = document.getElementById('save-status');
    const timer = document.getElementById('timer'), bar = document.getElementById('bar');
    const isLast = @json($isLast);
    const end = performance.now() + (+root.dataset.remainingMs);
    let timeUp = false;

    // ---- Timer (the server holds the real deadline; this only displays it) ----
    function tick() {
        const ms = Math.max(0, end - performance.now());
        const s = Math.ceil(ms / 1000);
        timer.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        bar.style.width = Math.min(100, ms / totalMs * 100) + '%';

        const level = s <= 5 ? 'bad' : s <= 10 ? 'warn' : '';
        timer.classList.toggle('text-amber-600', level === 'warn');
        timer.classList.toggle('text-red-600', level === 'bad');
        bar.classList.toggle('bg-amber-500', level === 'warn');
        bar.classList.toggle('bg-red-600', level === 'bad');
        bar.classList.toggle('bg-blue-700', level === '');

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
