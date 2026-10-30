@extends('layouts.staff')

@section('title', $mode === 'create' ? 'New exam' : 'Edit exam')

@php
    $input = 'field';
    $label = 'mb-1 block text-sm font-semibold';
    $hint = 'mt-1 text-xs text-slate-500 dark:text-slate-400';
    $card = 'panel';
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
<x-page-head :title="$mode === 'create' ? 'New exam' : 'Edit exam'" :subtitle="$mode === 'create' ? 'Set the details, the sections and the rules. You choose the questions afterwards.' : $exam->title" :back="$mode === 'create' ? route('officer.exams.index') : route('officer.exams.show', $exam)" :back-label="$mode === 'create' ? 'All exams' : 'Back to the exam'" />

<x-form-errors />

@if ($categories->isEmpty())
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        There are no active categories yet. <a href="{{ route('officer.categories.index') }}" class="font-semibold underline">Add one first</a>.
    </div>
@endif

<form method="POST" action="{{ $action }}" id="exam-form">
    @csrf
    @if ($mode === 'edit') @method('PUT') @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_17rem]">
        <div class="space-y-6">
            <x-form-section title="Exam details">
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
                        <p class="{{ $hint }}">For intern exams this must match the year of the registration window.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="paper_mode" class="{{ $label }}">How the paper is made</label>
                        <select id="paper_mode" name="paper_mode" class="{{ $input }}">
                            <option value="fixed_set" @selected(old('paper_mode', $exam->paper_mode) === 'fixed_set')>Fixed set: everyone in the category gets the same questions (staff exams)</option>
                            <option value="pool_draw" @selected(old('paper_mode', $exam->paper_mode) === 'pool_draw')>Pool draw: a topic-balanced draw for each sitting (intern exams)</option>
                        </select>
                    </div>
                </div>
            </x-form-section>

            <x-form-section title="Sections" hint="Every question in a section has the same mark and the same timer. Sections run in the order shown.">
                @if ($mode === 'create')
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">Start from</span>
                        <button type="button" id="preset-staff" class="pill">Staff promotion (3 sections)</button>
                        <button type="button" id="preset-single" class="pill">Single section (interns)</button>
                    </div>
                @endif

                <div id="sections" class="space-y-3"></div>

                <button type="button" id="add-section" class="btn btn-outline btn-sm mt-4">+ Add section</button>
            </x-form-section>

            <x-form-section title="Scoring and results">
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
            </x-form-section>

            <x-form-section title="Exam screen" hint="How candidates move through the paper.">
                <div class="-mx-2 space-y-1">
                    @foreach ([
                        ['shuffle_questions', 'Shuffle questions', 'Each candidate sees the questions of a section in a different order.'],
                        ['shuffle_options', 'Shuffle answer options', 'Each candidate sees the options of a question in a different order.'],
                        ['forward_only', 'Forward only', 'Candidates cannot go back to earlier questions.'],
                    ] as [$field, $title, $text])
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg p-2 hover:bg-slate-50 dark:hover:bg-slate-700">
                            <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $exam->{$field})) class="mt-0.5 h-5 w-5 shrink-0 accent-blue-700">
                            <span><b class="block">{{ $title }}</b><span class="text-sm text-slate-600 dark:text-slate-300">{{ $text }}</span></span>
                        </label>
                    @endforeach
                </div>
            </x-form-section>
        </div>

        {{-- Live totals follow the page while sections are edited --}}
        <aside class="panel xl:sticky xl:top-6" aria-label="Exam totals">
            <h2 class="font-display text-lg font-bold">This exam</h2>
            <dl class="mt-3 space-y-3">
                <div><dt class="text-sm text-slate-600 dark:text-slate-300">Questions</dt><dd id="sum-q" class="font-display text-3xl font-bold leading-tight tabular-nums">0</dd></div>
                <div><dt class="text-sm text-slate-600 dark:text-slate-300">Time to answer</dt><dd id="sum-t" class="font-display text-3xl font-bold leading-tight tabular-nums">0 s</dd></div>
                <div><dt class="text-sm text-slate-600 dark:text-slate-300">Total marks</dt><dd id="sum-m" class="font-display text-3xl font-bold leading-tight tabular-nums">0</dd></div>
            </dl>
            <p id="summary" class="sr-only" aria-live="polite"></p>
        </aside>
    </div>

    <div class="formbar flex flex-wrap items-center gap-3">
        <button type="submit" class="btn btn-primary px-7 py-3">{{ $mode === 'create' ? 'Create exam' : 'Save changes' }}</button>
        <a href="{{ $mode === 'create' ? route('officer.exams.index') : route('officer.exams.show', $exam) }}" class="btn btn-neutral py-3">Cancel</a>
    </div>
</form>

<script>
(() => {
    const inputCls = @json($input);
    const initial = @json($sectionRows);
    const wrap = document.getElementById('sections');
    let n = 0;

    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    const btn = 'btn-mini';

    function addRow(d = {}) {
        const i = n++;
        const el = document.createElement('div');
        el.className = 'section-row flex gap-4 rounded-xl border border-slate-200 p-4 dark:border-slate-700';
        el.innerHTML = `
            <input type="hidden" name="sections[${i}][id]" value="${esc(d.id)}">
            <span class="section-no font-display grid h-9 w-9 shrink-0 place-items-center rounded-full border-2 border-slate-300 text-base font-bold dark:border-slate-500" aria-hidden="true"></span>
            <div class="min-w-0 flex-1">
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
            </div>
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
        const time = fmtTime(t).trim() || '0 s', marks = Math.round(m * 100) / 100;
        document.getElementById('sum-q').textContent = q;
        document.getElementById('sum-t').textContent = time;
        document.getElementById('sum-m').textContent = marks;
        document.getElementById('summary').textContent = q + ' questions, ' + time + ', ' + marks + ' marks';
        wrap.querySelectorAll('.section-row').forEach((row, k) => { row.querySelector('.section-no').textContent = k + 1; });
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
