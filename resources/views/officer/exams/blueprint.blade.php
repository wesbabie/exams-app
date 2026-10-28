@extends('layouts.staff')

@section('title', 'Topic counts for '.$section->title)

@php
    $card = 'panel';
    $muted = 'text-slate-500 dark:text-slate-400';
    $st = $statuses[$section->id];
@endphp

@section('content')
<a href="{{ route('officer.exams.show', $exam) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← {{ $exam->title }}</a>
<h1 class="mb-1 mt-3 text-2xl font-bold">{{ $section->title }}</h1>
<p class="mb-4 text-sm {{ $muted }}">Each sitting gets its own paper, drawn at random from the topics below. Say how many questions to draw from each topic, so every paper has the same shape.</p>

@include('officer.exams.partials.section-tabs')

@unless ($editable)
    <div class="mb-5 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm dark:border-slate-600 dark:bg-slate-800">This exam is no longer a draft, so its topic counts are locked.</div>
@endunless

@if ($st['note'])
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">{{ $st['note'] }}</div>
@endif

<form method="POST" action="{{ route('officer.exams.sections.blueprint.update', [$exam, $section]) }}" class="{{ $card }}">
    @csrf @method('PUT')

    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-3">
        <h2 class="text-lg font-semibold">Questions drawn from each topic</h2>
        <span class="text-sm font-semibold" id="total-line" data-need="{{ $section->question_count }}"></span>
    </div>

    @if ($topics->isEmpty())
        <p class="text-sm {{ $muted }}">There are no topics for this exam's category yet. Add some under Topics.</p>
    @else
        <div class="overflow-x-auto">
            <table class="ledger w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="py-2 pr-2">Topic</th><th class="px-2 py-2">Scope</th><th class="px-2 py-2 text-right">Approved in pool</th><th class="px-2 py-2">Draw</th><th class="px-2 py-2">Pool size</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($topics as $t)
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <td class="py-2.5 pr-2 font-semibold">{{ $t->name }}</td>
                        <td class="px-2 py-2.5">{{ $t->category?->name ?? 'Shared' }}</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">
                            <a href="{{ route('bank.questions.index', ['topic' => $t->id, 'status' => 'approved']) }}" class="text-blue-700 hover:underline dark:text-blue-400">{{ $t->approved_count }}</a>
                        </td>
                        <td class="px-2 py-2.5">
                            <input type="number" min="0" max="{{ $t->approved_count }}" name="counts[{{ $t->id }}]" value="{{ $counts[$t->id] ?? 0 }}"
                                   data-pool="{{ $t->approved_count }}" @disabled(! $editable)
                                   class="count-input w-24 rounded-lg border border-slate-300 bg-white px-3 py-1.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900">
                        </td>
                        <td class="px-2 py-2.5 text-xs font-semibold pool-hint"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs {{ $muted }}">Aim for a pool of about 1.5 times the number drawn, so papers in different sittings are not nearly identical. Pool size shows that ratio for each topic.</p>

        @if ($editable)
            <button class="mt-4 rounded-lg bg-blue-700 px-6 py-2.5 font-semibold text-white hover:bg-blue-800">Save topic counts</button>
        @endif
    @endif
</form>

<script>
    const inputs = document.querySelectorAll('.count-input');
    const line = document.getElementById('total-line');
    function refresh() {
        let total = 0;
        inputs.forEach(i => {
            const draw = +i.value || 0, pool = +i.dataset.pool || 0;
            total += draw;
            const hint = i.closest('tr').querySelector('.pool-hint');
            if (!draw) { hint.textContent = ''; hint.className = 'px-2 py-2.5 text-xs font-semibold pool-hint'; return; }
            const ratio = pool / draw;
            hint.textContent = ratio >= 1.5 ? (ratio.toFixed(1) + '× ✓') : ratio >= 1 ? (ratio.toFixed(1) + '× (a little thin)') : 'Not enough questions';
            hint.className = 'px-2 py-2.5 text-xs font-semibold pool-hint ' + (ratio >= 1.5 ? 'text-green-700 dark:text-green-400' : ratio >= 1 ? 'text-amber-700 dark:text-amber-400' : 'text-red-700 dark:text-red-400');
        });
        const need = +line.dataset.need;
        line.textContent = total + ' of ' + need + ' questions';
        line.className = 'text-sm font-semibold ' + (total === need ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400');
    }
    inputs.forEach(i => i.addEventListener('input', refresh));
    refresh();
</script>
@endsection
