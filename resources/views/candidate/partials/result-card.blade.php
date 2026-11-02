{{-- One released result, as the candidate sees it. It shows what the exam is set to show and never a rank. --}}
@php
    $exam = $result->exam;
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $full = $exam->result_release === 'full_breakdown';
    $muted = 'text-slate-600 dark:text-slate-300';
@endphp
<article class="panel">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-display text-xl font-bold leading-tight">{{ $exam->title }}</h2>
            <p class="text-sm {{ $muted }}">Taken {{ $result->attempt->started_at->format('j M Y') }} · released {{ $exam->results_released_at->format('j M Y') }}</p>
        </div>
        @if ($exam->top_n && $result->shortlisted)<span class="chip chip-info">Shortlisted</span>@endif
    </div>

    <div class="mt-4 flex items-center gap-4 rounded-xl p-4 {{ $result->passed ? 'bg-green-50 dark:bg-green-950' : 'bg-red-50 dark:bg-red-950' }}">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full text-white {{ $result->passed ? 'bg-green-700' : 'bg-red-700' }}" aria-hidden="true">
            @if ($result->passed)<x-icon name="check" class="h-7 w-7" />@else<svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>@endif
        </span>
        <div>
            <p class="font-display text-3xl font-extrabold leading-none {{ $result->passed ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300' }}">{{ $result->passed ? 'Passed' : 'Did not pass' }}</p>
            @if ($full)<p class="mt-1 text-sm {{ $muted }}">Pass mark: {{ $num($exam->pass_mark) }}{{ $exam->pass_mark_type === 'percentage' ? '%' : ' marks' }}</p>@endif
        </div>
    </div>

    @if ($full)
        <table class="mt-5 w-full text-left text-sm">
            <caption class="sr-only">Marks by section</caption>
            <thead><tr class="border-b border-slate-200 text-xs font-semibold {{ $muted }} dark:border-slate-700"><th class="py-2 pr-2">Section</th><th class="py-2 pl-2 text-right">Marks</th></tr></thead>
            <tbody>
                @foreach ($exam->sections as $s)
                    @php $sr = $result->sections->firstWhere('exam_section_id', $s->id); @endphp
                    <tr class="border-b border-slate-100 dark:border-slate-700">
                        <td class="py-2.5 pr-2">
                            <div class="font-semibold">{{ $s->title }}</div>
                            <div class="mt-1.5 max-w-[14rem]"><x-meter :value="(float) ($sr?->marks ?? 0)" :max="max(1, (float) ($sr?->max_marks ?? 0))" :label="$s->title.' marks'" /></div>
                        </td>
                        <td class="py-2.5 pl-2 text-right align-top font-display text-lg font-bold tabular-nums">{{ $num($sr?->marks ?? 0) }} <span class="text-sm font-semibold {{ $muted }}">/ {{ $num($sr?->max_marks ?? 0) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td class="pt-3 font-bold">Total</td><td class="pt-3 text-right font-display text-xl font-extrabold tabular-nums">{{ $num($result->total_marks) }} <span class="text-sm font-semibold {{ $muted }}">/ {{ $num($result->max_marks) }} ({{ $num($result->percentage) }}%)</span></td></tr>
            </tfoot>
        </table>
    @endif
</article>
