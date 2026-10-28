@extends('layouts.staff')

@section('title', $mode === 'create' ? 'New question' : 'Edit question')

@php
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $label = 'mb-1 block text-sm font-semibold';
    $hint = 'mt-1 text-xs text-slate-500 dark:text-slate-400';
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
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
<a href="{{ $mode === 'create' ? route('bank.questions.index') : route('bank.questions.show', $question) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← Back</a>
<h1 class="mb-5 mt-3 text-2xl font-bold">{{ $mode === 'create' ? 'New question' : 'Edit question' }}@if ($question->exists && $question->version > 1) <span class="text-base font-normal text-slate-500">(version {{ $question->version }})</span>@endif</h1>

@if ($question->exists && $question->review_note)
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        <b>Returned by the reviewer:</b> {{ $question->review_note }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">
        <b>Please fix the following:</b>
        <ul class="mt-1 list-disc pl-5">
            @foreach (collect($errors->all())->unique()->take(6) as $message)<li>{{ $message }}</li>@endforeach
        </ul>
    </div>
@endif

@if ($topics->isEmpty())
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        There are no topics yet. An exam officer needs to add topics before questions can be written.
    </div>
@endif

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($mode === 'edit') @method('PUT') @endif

    <section class="{{ $card }}">
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
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-1 text-lg font-semibold">Answer options</h2>

        <div id="tf-block" class="hidden">
            <p class="mb-2 text-sm text-slate-500 dark:text-slate-400">Is the statement above true or false?</p>
            <div class="flex gap-6 text-sm">
                <label class="flex items-center gap-2"><input type="radio" name="correct_tf" value="true" @checked($tfValue === 'true') class="h-5 w-5 accent-blue-700"> <span>True</span></label>
                <label class="flex items-center gap-2"><input type="radio" name="correct_tf" value="false" @checked($tfValue === 'false') class="h-5 w-5 accent-blue-700"> <span>False</span></label>
            </div>
        </div>

        <div id="opts-block">
            <p class="mb-3 text-sm text-slate-500 dark:text-slate-400">Between 2 and 6 options. Mark the correct one. Options are shuffled for each candidate, so do not refer to them by letter ("both A and B"). "All of the above" and "None of the above" are kept last automatically.</p>
            <div id="options" class="space-y-2"></div>
            <button type="button" id="add-option" class="mt-3 rounded-lg border border-blue-700 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">+ Add option</button>
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-3 text-lg font-semibold">More</h2>
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
    </section>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="rounded-lg bg-blue-700 px-6 py-3 font-semibold text-white hover:bg-blue-800">{{ $mode === 'create' ? 'Save as draft' : 'Save changes' }}</button>
        <a href="{{ $mode === 'create' ? route('bank.questions.index') : route('bank.questions.show', $question) }}" class="rounded-lg border border-slate-300 px-6 py-3 font-semibold hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-700">Cancel</a>
    </div>
</form>

<script>
(() => {
    const inputCls = @json($input);
    const rows = @json($rows);
    const single = @json($singleIdx);
    const wrap = document.getElementById('options');
    const typeSel = document.getElementById('type');
    const MIN = 2, MAX = 6;
    let n = 0;

    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    const hints = {
        single_choice: 'One correct answer.',
        multi_select: 'One or more correct answers. Candidates tick every correct option.',
        true_false: 'A statement that is either true or false.',
    };

    function addRow(d = {}, isSingle = false) {
        const i = n++;
        const el = document.createElement('div');
        el.className = 'option-row flex items-center gap-2';
        el.innerHTML = `
            <span data-ctl="single"><input type="radio" name="correct_single" value="${i}" ${isSingle ? 'checked' : ''} class="h-5 w-5 accent-blue-700" aria-label="This is the correct answer"></span>
            <span data-ctl="multi"><input type="checkbox" name="options[${i}][is_correct]" value="1" ${d.is_correct ? 'checked' : ''} class="h-5 w-5 accent-blue-700" aria-label="This is a correct answer"></span>
            <input name="options[${i}][body]" value="${esc(d.body)}" maxlength="1000" placeholder="Option text" class="${inputCls}">
            <button type="button" data-act="remove" class="rounded-lg border border-slate-300 px-2.5 py-2 text-xs font-semibold text-red-700 hover:bg-slate-100 dark:border-slate-600 dark:text-red-400 dark:hover:bg-slate-700" aria-label="Remove option">✕</button>`;
        wrap.appendChild(el);
    }

    function applyType() {
        const t = typeSel.value;
        document.getElementById('opts-block').classList.toggle('hidden', t === 'true_false');
        document.getElementById('tf-block').classList.toggle('hidden', t !== 'true_false');
        wrap.querySelectorAll('[data-ctl="single"]').forEach(e => e.classList.toggle('hidden', t !== 'single_choice'));
        wrap.querySelectorAll('[data-ctl="multi"]').forEach(e => e.classList.toggle('hidden', t !== 'multi_select'));
        document.getElementById('type-hint').textContent = hints[t] || '';
    }

    wrap.addEventListener('click', e => {
        const b = e.target.closest('[data-act="remove"]');
        if (b && wrap.children.length > MIN) b.closest('.option-row').remove();
    });
    document.getElementById('add-option').addEventListener('click', () => {
        if (wrap.children.length < MAX) { addRow(); applyType(); }
    });
    typeSel.addEventListener('change', applyType);

    rows.forEach((r, idx) => addRow(r, idx === single));
    applyType();
})();
</script>
@endsection
