@extends('layouts.staff')

@section('title', $mode === 'create' ? 'New exam' : 'Edit exam')

@php
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $label = 'mb-1 block text-sm font-semibold';
    $hint = 'mt-1 text-xs text-slate-500 dark:text-slate-400';
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
    $action = $mode === 'create' ? route('officer.exams.store') : route('officer.exams.update', $exam);
    $sectionRows = array_values(old('sections', $sections));
    $trim = function ($v) {
        $s = (string) $v;
        if ($s === '' || ! str_contains($s, '.')) { return $s; }
        $s = rtrim(rtrim($s, '0'), '.');
        return $s === '' ? '0' : $s;
    };
@endphp

@section('content')
<a href="{{ $mode === 'create' ? route('officer.exams.index') : route('officer.exams.show', $exam) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← Back</a>
<h1 class="mb-5 mt-3 text-2xl font-bold">{{ $mode === 'create' ? 'New exam' : 'Edit exam' }}</h1>

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">
        <b>Please fix the following:</b>
        <ul class="mt-1 list-disc pl-5">
            @foreach (collect($errors->all())->unique()->take(6) as $message)<li>{{ $message }}</li>@endforeach
        </ul>
    </div>
@endif

@if ($categories->isEmpty())
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        There are no active categories yet. <a href="{{ route('officer.categories.index') }}" class="font-semibold underline">Add one first</a>.
    </div>
@endif

<form method="POST" action="{{ $action }}" id="exam-form" class="space-y-5">
    @csrf
    @if ($mode === 'edit') @method('PUT') @endif

    <section class="{{ $card }}">
        <h2 class="mb-3 text-lg font-semibold">Exam details</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="title" class="{{ $label }}">Title</label>
                <input id="title" name="title" value="{{ old('title', $exam->title) }}" required maxlength="255" placeholder="Nursing Officer I Promotion Examination 2026" class="{{ $input }}">
            </div>

            <div>
                <label for="exam_category_id" class="{{ $label }}">Category</label>
                <select id="exam_category_id" name="exam_category_id" required class="{{ $input }}">
                    <option value="">Choose a category</option>
                    @foreach ($categories as $kind => $items)
                        <optgroup label="{{ \App\Models\ExamCategory::KINDS[$kind] ?? $kind }}">
                            @foreach ($items as $c)
                                <option value="{{ $c->id }}" @selected((string) old('exam_category_id', $exam->exam_category_id) === (string) $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="cycle_year" class="{{ $label }}">Year (optional)</label>
                <input id="cycle_year" name="cycle_year" type="number" min="2000" max="2100" value="{{ old('cycle_year', $exam->cycle_year) }}" class="{{ $input }}">
            </div>

            <div class="sm:col-span-2">
                <label for="paper_mode" class="{{ $label }}">How the paper is made</label>
                <select id="paper_mode" name="paper_mode" class="{{ $input }}">
                    <option value="fixed_set" @selected(old('paper_mode', $exam->paper_mode) === 'fixed_set')>Fixed set: everyone in the category gets the same questions (staff exams)</option>
                    <option value="pool_draw" @selected(old('paper_mode', $exam->paper_mode) === 'pool_draw')>Pool draw: a topic-balanced draw for each sitting (intern exams)</option>
                </select>
            </div>
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-1 text-lg font-semibold">Sections</h2>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Every question in a section has the same mark and the same timer. Sections run in the order shown.</p>

        @if ($mode === 'create')
            <div class="mb-4 flex flex-wrap gap-2 text-sm">
                <button type="button" id="preset-staff" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-700">Staff promotion layout (3 sections)</button>
                <button type="button" id="preset-single" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-700">Single section (interns)</button>
            </div>
        @endif

        <div id="sections" class="space-y-3"></div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <button type="button" id="add-section" class="rounded-lg border border-blue-700 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">+ Add section</button>
            <p id="summary" class="text-sm font-semibold" aria-live="polite"></p>
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-3 text-lg font-semibold">Scoring and results</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="pass_mark" class="{{ $label }}">Pass mark (overall)</label>
                <div class="flex gap-2">
                    <input id="pass_mark" name="pass_mark" type="number" step="0.01" min="0" required value="{{ old('pass_mark', $trim($exam->pass_mark)) }}" class="{{ $input }}">
                    <select name="pass_mark_type" aria-label="Pass mark type" class="{{ $input }} sm:w-40">
                        <option value="percentage" @selected(old('pass_mark_type', $exam->pass_mark_type) === 'percentage')>percent</option>
                        <option value="absolute" @selected(old('pass_mark_type', $exam->pass_mark_type) === 'absolute')>marks</option>
                    </select>
                </div>
                <p class="{{ $hint }}">One pass mark applies to the total score.</p>
            </div>

            <div>
                <label for="negative_mark_fraction" class="{{ $label }}">Negative marking</label>
                <input id="negative_mark_fraction" name="negative_mark_fraction" type="number" step="0.05" min="0" max="1" required value="{{ old('negative_mark_fraction', $trim($exam->negative_mark_fraction) ?: '0') }}" class="{{ $input }}">
                <p class="{{ $hint }}">Fraction of a question's mark taken off for a wrong answer. 0 means none. Unanswered scores zero.</p>
            </div>

            <div>
                <label for="top_n" class="{{ $label }}">Shortlist size (optional)</label>
                <input id="top_n" name="top_n" type="number" min="1" value="{{ old('top_n', $exam->top_n) }}" placeholder="e.g. 10" class="{{ $input }}">
                <p class="{{ $hint }}">For intern exams: how many top candidates to shortlist after the pass mark.</p>
            </div>

            <div>
                <label for="tie_break_rule" class="{{ $label }}">Tie-break (optional)</label>
                <select id="tie_break_rule" name="tie_break_rule" class="{{ $input }}">
                    <option value="">None</option>
                    <option value="field_section_score" @selected(old('tie_break_rule', $exam->tie_break_rule) === 'field_section_score')>Higher field-section score</option>
                    <option value="earliest_submission" @selected(old('tie_break_rule', $exam->tie_break_rule) === 'earliest_submission')>Earliest submission</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="result_release" class="{{ $label }}">What candidates see after the exam</label>
                <select id="result_release" name="result_release" class="{{ $input }}">
                    <option value="hidden" @selected(old('result_release', $exam->result_release) === 'hidden')>Nothing until the exam officer releases results</option>
                    <option value="pass_fail" @selected(old('result_release', $exam->result_release) === 'pass_fail')>Pass or fail only, once released</option>
                    <option value="full_breakdown" @selected(old('result_release', $exam->result_release) === 'full_breakdown')>Section scores and total, once released</option>
                </select>
            </div>
        </div>
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-3 text-lg font-semibold">Exam screen</h2>
        <div class="space-y-2.5 text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $exam->shuffle_questions)) class="h-4 w-4 accent-blue-700"> <span>Shuffle the order of questions within each section</span></label>
            <label class="flex items-center gap-2"><input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options', $exam->shuffle_options)) class="h-4 w-4 accent-blue-700"> <span>Shuffle the order of answer options</span></label>
            <label class="flex items-center gap-2"><input type="checkbox" name="forward_only" value="1" @checked(old('forward_only', $exam->forward_only)) class="h-4 w-4 accent-blue-700"> <span>Forward only: candidates cannot go back to earlier questions</span></label>
        </div>
    </section>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="rounded-lg bg-blue-700 px-6 py-3 font-semibold text-white hover:bg-blue-800">{{ $mode === 'create' ? 'Create exam' : 'Save changes' }}</button>
        <a href="{{ $mode === 'create' ? route('officer.exams.index') : route('officer.exams.show', $exam) }}" class="rounded-lg border border-slate-300 px-6 py-3 font-semibold hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-700">Cancel</a>
    </div>
</form>

<script>
(() => {
    const inputCls = @json($input);
    const initial = @json($sectionRows);
    const wrap = document.getElementById('sections');
    let n = 0;

    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    const btn = 'rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-700';

    function addRow(d = {}) {
        const i = n++;
        const el = document.createElement('div');
        el.className = 'section-row rounded-xl border border-slate-200 p-4 dark:border-slate-700';
        el.innerHTML = `
            <input type="hidden" name="sections[${i}][id]" value="${esc(d.id)}">
            <div class="grid gap-3 sm:grid-cols-12">
                <div class="sm:col-span-12">
                    <label class="mb-1 block text-sm font-semibold">Section title</label>
                    <input name="sections[${i}][title]" value="${esc(d.title)}" required maxlength="255" placeholder="General Knowledge" class="${inputCls}">
                </div>
                <div class="sm:col-span-4">
                    <label class="mb-1 block text-sm font-semibold">Questions</label>
                    <input name="sections[${i}][question_count]" type="number" min="1" max="500" value="${esc(d.question_count)}" required class="${inputCls}">
                </div>
                <div class="sm:col-span-4">
                    <label class="mb-1 block text-sm font-semibold">Seconds per question</label>
                    <input name="sections[${i}][seconds_per_question]" type="number" min="5" max="1800" value="${esc(d.seconds_per_question ?? 60)}" required class="${inputCls}">
                </div>
                <div class="sm:col-span-4">
                    <label class="mb-1 block text-sm font-semibold">Mark per question</label>
                    <input name="sections[${i}][marks_per_question]" type="number" min="0.25" max="100" step="0.25" value="${esc(d.marks_per_question ?? 1)}" required class="${inputCls}">
                </div>
                <div class="sm:col-span-12">
                    <label class="mb-1 block text-sm font-semibold">Instructions for candidates (optional)</label>
                    <input name="sections[${i}][instructions]" value="${esc(d.instructions)}" maxlength="2000" class="${inputCls}">
                </div>
            </div>
            <div class="mt-3 flex gap-2">
                <button type="button" data-act="up" class="${btn}" aria-label="Move section up">↑ Up</button>
                <button type="button" data-act="down" class="${btn}" aria-label="Move section down">↓ Down</button>
                <button type="button" data-act="remove" class="${btn} text-red-700 dark:text-red-400">Remove</button>
            </div>`;
        wrap.appendChild(el);
        summary();
    }

    function fmtTime(s) {
        const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), r = s % 60;
        return (h ? h + ' h ' : '') + (m ? m + ' min ' : '') + (!h && r ? r + ' s' : '');
    }

    function summary() {
        let q = 0, t = 0, m = 0;
        wrap.querySelectorAll('.section-row').forEach(row => {
            const c = +row.querySelector('[name$="[question_count]"]').value || 0;
            const s = +row.querySelector('[name$="[seconds_per_question]"]').value || 0;
            const k = +row.querySelector('[name$="[marks_per_question]"]').value || 0;
            q += c; t += c * s; m += c * k;
        });
        document.getElementById('summary').textContent =
            q + ' questions · ' + (fmtTime(t).trim() || '0 s') + ' · ' + (Math.round(m * 100) / 100) + ' marks';
    }

    function setRows(rows) { wrap.innerHTML = ''; rows.forEach(addRow); }

    wrap.addEventListener('click', e => {
        const b = e.target.closest('[data-act]');
        if (!b) return;
        const row = b.closest('.section-row');
        if (b.dataset.act === 'remove' && wrap.children.length > 1) row.remove();
        if (b.dataset.act === 'up' && row.previousElementSibling) wrap.insertBefore(row, row.previousElementSibling);
        if (b.dataset.act === 'down' && row.nextElementSibling) wrap.insertBefore(row.nextElementSibling, row);
        summary();
    });
    wrap.addEventListener('input', summary);
    document.getElementById('add-section').addEventListener('click', () => addRow());

    const staff = document.getElementById('preset-staff'), single = document.getElementById('preset-single');
    if (staff) staff.addEventListener('click', () => setRows([
        { title: 'General Knowledge', question_count: 20 },
        { title: 'Civil Service Rules', question_count: 20 },
        { title: 'Field-Related', question_count: 30 },
    ]));
    if (single) single.addEventListener('click', () => setRows([{ title: 'Entrance Test', question_count: 50 }]));

    setRows(initial.length ? initial : [{}]);
})();
</script>
@endsection
