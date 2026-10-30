@php
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $r = $row['result'];
@endphp
<div class="rounded-2xl border-2 border-slate-300 bg-white p-6 text-slate-900 print:break-inside-avoid print:rounded-none print:border-black">
    <div class="text-center">
        <div class="text-sm font-semibold text-slate-600">ExamSphere</div>
        <h1 class="mt-1 text-xl font-bold">Result slip</h1>
    </div>

    <dl class="mt-4 space-y-1.5 text-sm">
        <div class="flex justify-between gap-4"><dt class="text-slate-500">Name</dt><dd class="text-right font-semibold">{{ $row['name'] }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ $row['registration']->candidate_type === 'staff' ? 'File number' : 'Application no.' }}</dt><dd class="font-mono font-semibold">{{ $row['number'] }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-slate-500">Examination</dt><dd class="text-right font-semibold">{{ $exam->title }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-slate-500">Date</dt><dd class="font-semibold">{{ $r->attempt->started_at->format('j M Y') }}</dd></div>
    </dl>

    @if ($exam->result_release === 'full_breakdown')
        <table class="mt-4 w-full text-left text-sm">
            <thead><tr class="border-b border-slate-400 text-xs uppercase text-slate-500"><th class="py-1">Section</th><th class="py-1 text-right">Marks</th></tr></thead>
            <tbody>
            @foreach ($exam->sections as $s)
                @php $sr = $r->sections->firstWhere('exam_section_id', $s->id); @endphp
                <tr class="border-b border-slate-200"><td class="py-1">{{ $s->title }}</td><td class="py-1 text-right tabular-nums">{{ $num($sr?->marks ?? 0) }} / {{ $num($sr?->max_marks ?? 0) }}</td></tr>
            @endforeach
            </tbody>
            <tfoot><tr class="font-bold"><td class="py-1.5">Total</td><td class="py-1.5 text-right tabular-nums">{{ $num($r->total_marks) }} / {{ $num($r->max_marks) }} ({{ $num($r->percentage) }}%)</td></tr></tfoot>
        </table>
    @endif

    <div class="mt-5 rounded-xl border-2 py-3 text-center {{ $r->passed ? 'border-green-600 text-green-800' : 'border-red-600 text-red-800' }}">
        <div class="text-sm font-semibold">Result</div>
        <div class="text-2xl font-bold">{{ $r->passed ? 'PASS' : 'FAIL' }}</div>
        @if ($exam->top_n && $row['shortlisted'])<div class="mt-1 text-sm font-semibold">Shortlisted</div>@endif
    </div>

    <p class="mt-4 text-center text-xs text-slate-500">Issued {{ $exam->results_released_at->format('j M Y') }}</p>
</div>
