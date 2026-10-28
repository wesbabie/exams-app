@extends('layouts.staff')

@section('title', 'Exams')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">Exams</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Set up an exam, add its sections and timers, then send it for review.</p>
    </div>
    <a href="{{ route('officer.exams.create') }}" class="rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">New exam</a>
</div>

<div class="mb-4 flex flex-wrap gap-2 text-sm">
    @php
        $chips = ['' => 'All', 'draft' => 'Draft', 'in_review' => 'In review', 'approved' => 'Approved', 'scheduled' => 'Scheduled', 'closed' => 'Closed'];
    @endphp
    @foreach ($chips as $value => $label)
        <a href="{{ route('officer.exams.index', $value ? ['status' => $value] : []) }}"
           class="rounded-full border px-3 py-1 font-semibold {{ ($status ?? '') === $value ? 'border-blue-700 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-slate-700 dark:text-blue-400' : 'border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    @if ($exams->isEmpty())
        <p class="text-sm text-slate-500 dark:text-slate-400">No exams found. Add a category first if you have not, then create the exam.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="py-2 pr-2">Exam</th><th class="px-2 py-2">Category</th><th class="px-2 py-2">Mode</th>
                        <th class="px-2 py-2 text-right">Sections</th><th class="px-2 py-2 text-right">Questions</th><th class="px-2 py-2">Status</th><th class="px-2 py-2">Updated</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($exams as $exam)
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <td class="py-2.5 pr-2">
                            <a href="{{ route('officer.exams.show', $exam) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">{{ $exam->title }}</a>
                            @if ($exam->cycle_year)<span class="text-slate-500"> · {{ $exam->cycle_year }}</span>@endif
                        </td>
                        <td class="px-2 py-2.5">{{ $exam->category->name }}</td>
                        <td class="px-2 py-2.5">{{ $exam->paper_mode === 'fixed_set' ? 'Fixed set' : 'Pool draw' }}</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $exam->sections_count }}</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $exam->total_questions ?? 0 }}</td>
                        <td class="px-2 py-2.5">@include('officer.partials.status-badge', ['status' => $exam->status])</td>
                        <td class="px-2 py-2.5 text-slate-500">{{ $exam->updated_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $exams->links() }}</div>
    @endif
</div>
@endsection
