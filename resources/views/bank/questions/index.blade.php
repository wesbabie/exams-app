@extends('layouts.staff')

@section('title', 'Question bank')

@php
    $user = auth()->user();
    $card = 'panel';
    $input = 'rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $canReview = in_array($user->role, ['reviewer', 'exam_officer', 'super_admin'], true);
    $chips = ['all' => 'All'] + \App\Models\Question::STATUSES;
    $isAuthor = $user->role === 'question_author';
@endphp

@section('content')
<x-page-head title="Question bank" :subtitle="$isAuthor ? 'Your questions. Write them, send them for review, and a reviewer approves them.' : 'All questions. Only approved questions can be used in exams.'">
    <x-slot:actions>
        @can('create', \App\Models\Question::class)
            <a href="{{ route('bank.import.create') }}" class="btn btn-outline">Import CSV</a>
            <a href="{{ route('bank.questions.create') }}" class="btn btn-primary">New question</a>
        @endcan
    </x-slot:actions>
</x-page-head>

@if ($myDrafts > 0)
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-300 bg-blue-50 px-4 py-3 text-sm dark:border-blue-700 dark:bg-blue-950">
        <span>You have <b>{{ $myDrafts }}</b> draft question(s).</span>
        <form method="POST" action="{{ route('bank.questions.submit-all') }}">@csrf
            <button class="rounded-lg bg-blue-700 px-4 py-1.5 font-semibold text-white hover:bg-blue-800">Send all my drafts for review</button>
        </form>
    </div>
@endif

<div class="mb-4 flex flex-wrap gap-2">
    @foreach ($chips as $key => $label)
        @php $n = $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0); @endphp
        <a href="{{ route('bank.questions.index', array_merge(request()->except(['page', 'status']), ['status' => $key])) }}"
           class="pill {{ $status === $key ? 'pill-on' : '' }}">{{ $label }} <span class="opacity-70">{{ $n }}</span></a>
    @endforeach
    <a href="{{ route('bank.questions.index', ['status' => 'approved', 'overdue' => 1]) }}" class="pill !border-amber-400 !text-amber-800 dark:!text-amber-300">Review overdue</a>
</div>

<form method="GET" action="{{ route('bank.questions.index') }}" class="mb-4 grid gap-2 sm:grid-cols-6">
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search the question text" class="{{ $input }} sm:col-span-2">
    <select name="topic" class="{{ $input }} sm:col-span-2">
        <option value="">All topics</option>
        @foreach ($topics as $t)
            <option value="{{ $t->id }}" @selected((string) request('topic') === (string) $t->id)>{{ $t->name }} ({{ $t->category?->name ?? 'Shared' }})</option>
        @endforeach
    </select>
    <select name="type" class="{{ $input }}">
        <option value="">All types</option>
        @foreach (\App\Models\Question::TYPES as $value => $label)
            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-700">Filter</button>
</form>

<form method="POST" action="{{ route('bank.questions.approve-selected') }}" class="{{ $card }} !p-4 sm:!p-5">
    @csrf

    @if ($canReview && $status === 'in_review' && $questions->isNotEmpty())
        <div class="mb-3 flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="select-all" class="h-4 w-4 accent-blue-700"> <span>Select all</span></label>
            <button class="rounded-lg bg-green-700 px-4 py-1.5 text-sm font-semibold text-white hover:bg-green-800">Approve selected</button>
        </div>
    @endif

    @if ($questions->isEmpty())
        <p class="text-sm text-slate-500 dark:text-slate-400">No questions here yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="ledger w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="w-6 py-2"></th><th class="py-2 pr-2">Question</th><th class="px-2 py-2">Topic</th><th class="px-2 py-2">Type</th>
                        <th class="px-2 py-2">Status</th><th class="px-2 py-2 text-right">Ver.</th><th class="px-2 py-2">Author</th><th class="px-2 py-2">Updated</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($questions as $q)
                    <tr class="border-b border-slate-200 align-top dark:border-slate-700">
                        <td class="py-2.5">@can('review', $q)<input type="checkbox" name="ids[]" value="{{ $q->id }}" class="row-check h-4 w-4 accent-blue-700" aria-label="Select question">@endcan</td>
                        <td class="py-2.5 pr-2">
                            <a href="{{ route('bank.questions.show', $q) }}" class="text-base font-semibold text-slate-900 hover:text-blue-700 hover:underline dark:text-slate-100 dark:hover:text-blue-300">{{ \Illuminate\Support\Str::limit($q->stem, 110) }}</a>
                            @if ($q->isReviewOverdue())<span class="chip chip-warn ml-1">Review due</span>@endif
                        </td>
                        <td class="px-2 py-2.5">{{ $q->topic->name }}<div class="text-xs text-slate-500">{{ $q->category?->name ?? 'Shared' }}</div></td>
                        <td class="px-2 py-2.5">{{ \App\Models\Question::TYPES[$q->type] ?? $q->type }}</td>
                        <td class="px-2 py-2.5">@include('bank.partials.status-badge', ['status' => $q->status])</td>
                        <td class="px-2 py-2.5 text-right tabular-nums">{{ $q->version }}</td>
                        <td class="px-2 py-2.5">{{ $q->creator?->name ?? '–' }}</td>
                        <td class="px-2 py-2.5 text-slate-500">{{ $q->updated_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $questions->links() }}</div>
    @endif
</form>

<script>
    const all = document.getElementById('select-all');
    if (all) all.addEventListener('change', () => document.querySelectorAll('.row-check').forEach(c => c.checked = all.checked));
</script>
@endsection
