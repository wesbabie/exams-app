@extends('layouts.app')

@section('title', 'Result slips')

@section('body')
<div class="mx-auto max-w-md px-4 py-6 print:max-w-none print:px-0 print:py-0">
    <div class="mb-4 flex flex-wrap gap-2 print:hidden">
        <button type="button" onclick="window.print()" class="btn btn-primary">Print {{ $rows->count() > 1 ? 'all '.$rows->count().' slips' : 'slip' }}</button>
        <a href="{{ route('results.exams.show', $exam) }}" class="rounded-lg border border-slate-400 px-5 py-2.5 font-semibold hover:bg-slate-100 dark:hover:bg-slate-700">Back</a>
    </div>

    <div class="space-y-6 print:space-y-0">
        @forelse ($rows as $row)
            <div class="print:break-after-page print:pt-8">
                @include('results.partials.slip-card', ['row' => $row, 'exam' => $exam])
            </div>
        @empty
            <p class="text-sm text-slate-500">There are no results to print.</p>
        @endforelse
    </div>
</div>
@endsection
