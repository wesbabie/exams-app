@extends('layouts.staff')

@section('title', 'Exams')

@section('content')
<x-page-head title="Exams" subtitle="Set up an exam, add its sections and timers, then send it for review.">
    <x-slot:actions><a href="{{ route('officer.exams.create') }}" class="btn btn-primary">New exam</a></x-slot:actions>
</x-page-head>

<div class="mb-4 flex flex-wrap gap-2">
    @php
        $chips = ['' => 'All', 'draft' => 'Draft', 'in_review' => 'In review', 'approved' => 'Approved', 'scheduled' => 'Scheduled', 'closed' => 'Closed'];
    @endphp
    @foreach ($chips as $value => $label)
        <a href="{{ route('officer.exams.index', $value ? ['status' => $value] : []) }}"
           class="pill {{ ($status ?? '') === $value ? 'pill-on' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="panel overflow-x-auto">
    @if ($exams->isEmpty())
        <p class="text-sm text-slate-600 dark:text-slate-300">No exams found. Add a category first if you have not, then create the exam.</p>
    @else
        <div class="overflow-x-auto">
            <table class="ledger w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="py-2 pr-2">Exam</th><th class="px-2 py-2">Category</th><th class="px-2 py-2">Mode</th>
                        <th class="px-2 py-2 text-right">Sections</th><th class="px-2 py-2 text-right">Questions</th><th class="px-2 py-2">Status</th><th class="px-2 py-2">Updated</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($exams as $exam)
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <td class="py-3 pr-2">
                            <a href="{{ route('officer.exams.show', $exam) }}" class="font-display text-base font-bold text-blue-800 hover:underline dark:text-blue-300">{{ $exam->title }}</a>
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
