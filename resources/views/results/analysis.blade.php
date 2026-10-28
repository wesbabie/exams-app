@extends('layouts.staff')

@section('title', 'Question analysis')

@php
    $card = 'panel';
    $muted = 'text-slate-500 dark:text-slate-400';
@endphp

@section('content')
<a href="{{ route('results.exams.show', $exam) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← {{ $exam->title }}</a>
<h1 class="mb-1 mt-3 text-2xl font-bold">Question analysis</h1>
<p class="mb-5 text-sm {{ $muted }}">How each question performed, hardest first. Very low scores can mean a wrong answer key or a badly worded question. Very high scores may mean it is too easy to tell candidates apart.</p>

<section class="{{ $card }}">
    @if ($rows->isEmpty())
        <p class="text-sm {{ $muted }}">No submitted attempts yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="ledger w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="py-2 pr-2">Question</th><th class="px-2 py-2">Section</th><th class="px-2 py-2 text-right">Asked</th><th class="px-2 py-2 text-right">Correct</th><th class="px-2 py-2 text-right">Blank</th><th class="px-2 py-2 text-right">Timed out</th><th class="px-2 py-2">Note</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($rows as $r)
                    <tr class="border-b border-slate-200 align-top dark:border-slate-700">
                        <td class="py-2.5 pr-2"><a href="{{ route('bank.questions.show', $r['question']) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">{{ \Illuminate\Support\Str::limit($r['question']->stem, 110) }}</a><div class="text-xs {{ $muted }}">{{ $r['question']->topic->name }}</div></td>
                        <td class="px-2 py-2.5">{{ $r['section']?->title }}</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $r['total'] }}</td>
                        <td class="px-2 py-2.5 text-right font-bold tabular-nums {{ $r['correctPct'] < 30 ? 'text-red-700 dark:text-red-400' : ($r['correctPct'] > 95 ? 'text-amber-700 dark:text-amber-400' : '') }}">{{ $r['correctPct'] }}%</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $r['unansweredPct'] }}%</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $r['timedOutPct'] }}%</td>
                        <td class="px-2 py-2.5 text-xs">
                            @if ($r['correctPct'] < 30)<span class="text-red-700 dark:text-red-400">Very hard. Check the answer key.</span>
                            @elseif ($r['correctPct'] > 95)<span class="text-amber-700 dark:text-amber-400">Very easy.</span>
                            @elseif ($r['timedOutPct'] >= 20)<span class="text-amber-700 dark:text-amber-400">Often runs out of time.</span>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
