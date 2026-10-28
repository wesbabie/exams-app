@extends('layouts.app')

@section('title', 'Ranked results · '.$exam->title)

@php
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
@endphp

@section('body')
<div class="mx-auto max-w-6xl bg-white p-6 text-slate-900 print:max-w-none print:p-0">
    <div class="mb-4 flex gap-2 print:hidden">
        <button type="button" onclick="window.print()" class="btn btn-primary">Print or save as PDF</button>
        <a href="{{ route('results.exams.show', $exam) }}" class="rounded-lg border border-slate-400 px-5 py-2.5 font-semibold hover:bg-slate-100">Back</a>
    </div>

    <div class="mb-4 text-center">
        <div class="text-sm font-semibold uppercase tracking-wide text-slate-500"> </div>
        <h1 class="text-xl font-bold">{{ $exam->title }}</h1>
        <p class="text-sm text-slate-600">Ranked results · {{ $exam->category->name }} · pass mark {{ $num($exam->pass_mark) }}{{ $exam->pass_mark_type === 'percentage' ? '%' : ' marks' }}@if ($exam->top_n) · shortlist: top {{ $exam->top_n }}@endif · printed {{ now()->format('j M Y, g:i A') }}</p>
    </div>

    <table class="w-full border-collapse text-left text-sm">
        <thead>
            <tr class="border-y-2 border-slate-800 text-xs font-bold uppercase">
                <th class="py-1.5 pr-2">Rank</th><th class="px-2 py-1.5">File / app. no.</th><th class="px-2 py-1.5">Name</th><th class="px-2 py-1.5">Department</th>
                @foreach ($exam->sections as $s)<th class="px-2 py-1.5 text-right">{{ $s->title }}</th>@endforeach
                <th class="px-2 py-1.5 text-right">Total</th><th class="px-2 py-1.5 text-right">%</th><th class="px-2 py-1.5">Result</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            @php $r = $row['result']; @endphp
            <tr class="border-b border-slate-300">
                <td class="py-1.5 pr-2 font-bold tabular-nums">{{ $row['rank'] }}</td>
                <td class="px-2 py-1.5 font-mono text-xs">{{ $row['number'] }}</td>
                <td class="px-2 py-1.5">{{ $row['name'] }}</td>
                <td class="px-2 py-1.5">{{ $row['candidate']->department ?? '' }}</td>
                @foreach ($exam->sections as $s)<td class="px-2 py-1.5 text-right tabular-nums">{{ $num($r->sections->firstWhere('exam_section_id', $s->id)?->marks ?? 0) }}</td>@endforeach
                <td class="px-2 py-1.5 text-right font-bold tabular-nums">{{ $num($r->total_marks) }}/{{ $num($r->max_marks) }}</td>
                <td class="px-2 py-1.5 text-right tabular-nums">{{ $num($r->percentage) }}</td>
                <td class="px-2 py-1.5">{{ $r->passed ? 'Pass' : 'Fail' }}{{ $row['shortlisted'] ? ' · Shortlisted' : '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if ($rows->isEmpty())<p class="mt-4 text-sm text-slate-500">No results yet.</p>@endif
</div>
@endsection
