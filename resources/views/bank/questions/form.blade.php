@extends('layouts.staff')

@section('title', $mode === 'create' ? 'New question' : 'Edit question')

@php
    $input = 'field';
    $label = 'mb-1 block text-sm font-semibold';
    $hint = 'mt-1 text-xs text-slate-500 dark:text-slate-400';
    $card = 'panel';
    $action = $mode === 'create' ? route('bank.questions.store') : route('bank.questions.update', $question);

    // Starting rows for the options editor
    $oldOptions = old('options');
    if ($oldOptions !== null) {
        $keys = array_map('strval', array_keys($oldOptions));
        $rows = collect($oldOptions)->map(fn ($r) => ['body' => $r['body'] ?? '', 'is_correct' => ! empty($r['is_correct'])])->values()->all();
        $pos = array_search((string) old('correct_single'), $keys, true);
        $singleIdx = $pos === false ? -1 : $pos;
    } elseif ($question->exists && $question->type !== 'true_false') {
        $rows = $question->options->map(fn ($o) => ['body' => $o->body, 'is_correct' => $o->is_correct])->values()->all();
        $singleIdx = $question->options->values()->search(fn ($o) => $o->is_correct);
        $singleIdx = $singleIdx === false ? -1 : $singleIdx;
    } else {
        $rows = array_fill(0, 4, ['body' => '', 'is_correct' => false]);
        $singleIdx = -1;
    }

    $tfValue = old('correct_tf');
    if ($tfValue === null && $question->exists && $question->type === 'true_false') {
        $tfValue = $question->options->firstWhere('is_correct', true)?->body === 'True' ? 'true' : 'false';
    }

    $grouped = $topics->groupBy(fn ($t) => $t->category?->name ?? 'Shared')
        ->sortKeysUsing(fn ($a, $b) => $a === 'Shared' ? -1 : ($b === 'Shared' ? 1 : strcmp($a, $b)));
    $selectedType = old('type', $question->type);
@endphp

@section('content')
<x-page-head :title="$mode === 'create' ? 'New question' : 'Edit question'" :subtitle="$question->exists && $question->version > 1 ? 'Version '.$question->version : 'Write the question the way candidates will see it.'" :back="$mode === 'create' ? route('bank.questions.index') : route('bank.questions.show', $question)" :back-label="$mode === 'create' ? 'Question bank' : 'Back to the question'" />

@if ($question->exists && $question->review_note)
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        <b>Returned by the reviewer:</b> {{ $question->review_note }}
    </div>
@endif

<x-form-errors />

@if ($topics->isEmpty())
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        There are no topics yet. An exam officer needs to add topics before questions can be written.
    </div>
@endif

<form method="POST" action="{{ $action }}" id="question-form">
    @csrf
    @if ($mode === 'edit') @method('PUT') @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-6">
            <x-form-section title="The question">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="topic_id" class="{{ $label }}">Topic</label>
                        <select id="topic_id" name="topic_id" required class="{{ $input }}">
                            <option value="">Choose a topic</option>
                            @foreach ($grouped as $group => $items)
                                <optgroup label="{{ $group }}">
                                    @foreach ($items as $t)
                                        <option value="{{ $t->id }}" @selected((string) old('topic_id', $question->topic_id) === (string) $t->id)>{{ $t->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="type" class="{{ $label }}">Question type</label>
                        <select id="type" name="type" class="{{ $input }}">
                            @foreach (\App\Models\Question::TYPES as $value => $text)
                                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                        <p id="type-hint" class="{{ $hint }}"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="stem" class="{{ $label }}">Question</label>
                        <textarea id="stem" name="stem" rows="4" required maxlength="5000" class="{{ $input }}" placeholder="Write the question as candidates will see it">{{ old('stem', $question->stem) }}</textarea>
                    </div>
                </div>
            </x-form-section>

            <x-form-section title="Answer options">
                <div id="tf-block" class="hidden">
                    <p class="mb-3 text-sm text-slate-600 dark:text-slate-300">Is the statement above true or false? Tap the right answer.</p>
                    <div class="flex gap-6">
                        <label class="flex cursor-pointer items-center gap-3"><input type="radio" name="correct_tf" value="true" @checked($tfValue === 'true') class="sr-only"><span class="mark" aria-hidden="true">T</span><span class="font-semibold">True</span></label>
                        <label class="flex cursor-pointer items-center gap-3"><input type="radio" name="correct_tf" value="false" @checked($tfValue === 'false') class="sr-only"><span class="mark" aria-hidden="true">F</span><span class="font-semibold">False</span></label>
                    </div>
                </div>

                <div id="opts-block">
                    <p class="mb-4 max-w-prose text-sm text-slate-600 dark:text-slate-300">Between 2 and 6 options. <b>Tap the letter of the correct answer</b> to mark it. Options are shuffled for each candidate, so do not refer to them by letter ("both A and B"). "All of the above" and "None of the above" are kept last automatically.</p>
                    <div id="options" class="space-y-2.5"></div>
                    <button type="button" id="add-option" class="btn btn-outline btn-sm mt-4">+ Add option</button>
                </div>
            </x-form-section>

            <x-form-section title="More">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="explanation" class="{{ $label }}">Explanation (optional)</label>
                        <textarea id="explanation" name="explanation" rows="2" maxlength="5000" class="{{ $input }}">{{ old('explanation', $question->explanation) }}</textarea>
                        <p class="{{ $hint }}">For reviewers and the exam officer. It is not shown to candidates during the exam.</p>
                    </div>
                    <div>
                        <label for="time_seconds" class="{{ $label }}">Timer for this question (optional)</label>
                        <input id="time_seconds" name="time_seconds" type="number" min="5" max="1800" value="{{ old('time_seconds', $question->time_seconds) }}" class="{{ $input }}" placeholder="Seconds">
                        <p class="{{ $hint }}">Leave blank to use the section's timer. Useful for long case questions.</p>
                    </div>
                    <div>
                        <label for="review_due_on" class="{{ $label }}">Review again by (optional)</label>
                        <input id="review_due_on" name="review_due_on" type="date" value="{{ old('review_due_on', optional($question->review_due_on)->format('Y-m-d')) }}" class="{{ $input }}">
                        <p class="{{ $hint }}">For questions that can go out of date, such as civil service rules.</p>
                    </div>
                </div>
            </x-form-section>
        </div>

        {{-- What the candidate will see, updated as you type --}}
        <aside class="xl:sticky xl:top-6" aria-label="Candidate preview">
            <div class="panel !p-4">
                <p class="mb-3 flex items-center justify-between text-sm font-semibold text-slate-600 dark:text-slate-300"><span>How candidates see it</span><span id="pv-correct" class="chip chip-neutral">No correct answer yet</span></p>
                <p id="pv-stem" class="font-display whitespace-pre-line text-lg font-semibold leading-snug"></p>
                <p id="pv-mode" class="mt-2 text-sm text-slate-600 dark:text-slate-300"></p>
                <div id="pv-opts" class="mt-4 space-y-2" aria-hidden="true"></div>
            </div>
        </aside>
    </div>

    <div class="formbar flex flex-wrap items-center gap-3">
        <button type="submit" class="btn btn-primary px-7 py-3">{{ $mode === 'create' ? 'Save as draft' : 'Save changes' }}</button>
        <a href="{{ $mode === 'create' ? route('bank.questions.index') : route('bank.questions.show', $question) }}" class="btn btn-neutral py-3">Cancel</a>
    </div>
</form>

<script>
(() => {
    const inputCls = @json($input);
    const rows = @json($rows);
    const single = @json($singleIdx);
    const wrap = document.getElementById('options');
    const typeSel = document.getElementById('type');
    const stem = document.getElementById('stem');
    const MIN = 2, MAX = 6;
    let n = 0;

    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    const letter = i => String.fromCharCode(65 + i);
    const hints = {
        single_choice: 'One correct answer.',
        multi_select: 'One or more correct answers. Candidates tick every correct option.',
        true_false: 'A statement that is either true or false.',
    };

    function addRow(d = {}, isSingle = false) {
        const i = n++;
        const el = document.createElement('div');
        el.className = 'option-row flex items-center gap-3';
        el.innerHTML = `
            <span data-ctl="single"><label class="block"><input type="radio" name="correct_single" value="${i}" ${isSingle ? 'checked' : ''} class="sr-only" aria-label="This is the correct answer"><span class="mark" data-letter></span></label></span>
            <span data-ctl="multi"><label class="block"><input type="checkbox" name="options[${i}][is_correct]" value="1" ${d.is_correct ? 'checked' : ''} class="sr-only" aria-label="This is a correct answer"><span class="mark mark-multi" data-letter></span></label></span>
            <input name="options[${i}][body]" value="${esc(d.body)}" maxlength="1000" placeholder="Option text" class="${inputCls}">
            <button type="button" data-act="remove" class="btn-mini shrink-0 !px-3 !py-2 text-red-700 dark:text-red-400" aria-label="Remove option">✕</button>`;
        wrap.appendChild(el);
    }

    function renumber() {
        wrap.querySelectorAll('.option-row').forEach((row, k) => row.querySelectorAll('[data-letter]').forEach(m => { m.textContent = letter(k); }));
    }

    function preview() {
        const t = typeSel.value;
        document.getElementById('pv-stem').textContent = stem.value.trim() || 'Your question appears here.';
        document.getElementById('pv-mode').textContent = t === 'multi_select' ? 'Choose every answer that is correct.' : 'Choose one answer.';
        const box = document.getElementById('pv-opts');
        box.innerHTML = '';
        const items = [];
        if (t === 'true_false') {
            const tf = document.querySelector('[name="correct_tf"]:checked');
            items.push({ text: 'True', ok: tf && tf.value === 'true' }, { text: 'False', ok: tf && tf.value === 'false' });
        } else {
            wrap.querySelectorAll('.option-row').forEach(row => {
                const ok = t === 'single_choice' ? row.querySelector('[type=radio]').checked : row.querySelector('[type=checkbox]').checked;
                items.push({ text: row.querySelector('[name$="[body]"]').value.trim() || 'Option text', ok });
            });
        }
        items.forEach((it, k) => {
            const d = document.createElement('div');
            d.className = 'choice' + (t === 'multi_select' ? ' choice-multi' : '');
            const b = document.createElement('span'); b.className = 'choice-bubble'; b.textContent = letter(k);
            const x = document.createElement('span'); x.className = 'pt-1 leading-snug'; x.textContent = it.text;
            d.append(b, x); box.appendChild(d);
        });
        const right = items.map((it, k) => it.ok ? letter(k) : null).filter(Boolean);
        const chip = document.getElementById('pv-correct');
        chip.textContent = right.length ? 'Correct: ' + right.join(', ') : 'No correct answer yet';
        chip.className = 'chip ' + (right.length ? 'chip-good' : 'chip-warn');
    }

    function applyType() {
        const t = typeSel.value;
        document.getElementById('opts-block').classList.toggle('hidden', t === 'true_false');
        document.getElementById('tf-block').classList.toggle('hidden', t !== 'true_false');
        wrap.querySelectorAll('[data-ctl="single"]').forEach(e => e.classList.toggle('hidden', t !== 'single_choice'));
        wrap.querySelectorAll('[data-ctl="multi"]').forEach(e => e.classList.toggle('hidden', t !== 'multi_select'));
        document.getElementById('type-hint').textContent = hints[t] || '';
        preview();
    }

    wrap.addEventListener('click', e => {
        const b = e.target.closest('[data-act="remove"]');
        if (b && wrap.children.length > MIN) { b.closest('.option-row').remove(); renumber(); preview(); }
    });
    document.getElementById('add-option').addEventListener('click', () => {
        if (wrap.children.length < MAX) { addRow(); renumber(); applyType(); }
    });
    typeSel.addEventListener('change', applyType);
    const form = document.getElementById('question-form');
    form.addEventListener('input', preview);
    form.addEventListener('change', preview);

    rows.forEach((r, idx) => addRow(r, idx === single));
    renumber();
    applyType();
})();
</script>
@endsection
