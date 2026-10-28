@extends('layouts.staff')

@section('title', 'Intern applicants')

@php
    $input = 'rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $card = 'panel';
    $tabs = ['submitted' => 'To review', 'returned' => 'Returned', 'approved' => 'Approved', 'rejected' => 'Rejected', 'draft' => 'Draft', 'all' => 'All'];
@endphp

@section('content')
<h1 class="text-2xl font-bold">Intern applicants</h1>
<p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Check each applicant's documents, then approve, reject with a reason, or return for correction.</p>

<nav class="mb-4 flex flex-wrap gap-1 text-sm font-semibold" aria-label="Status">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('officer.applicants.index', array_merge(request()->except('page'), ['status' => $key])) }}"
           class="rounded-lg px-3 py-1.5 {{ $status === $key ? 'bg-blue-50 text-blue-700 dark:bg-slate-700 dark:text-blue-400' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' }}">
            {{ $label }}@if ($key !== 'all') <span class="font-normal text-slate-500">({{ $counts[$key] ?? 0 }})</span>@endif
        </a>
    @endforeach
</nav>

<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="hidden" name="status" value="{{ $status }}">
    <input name="q" value="{{ $term }}" placeholder="Name, email or application no." class="{{ $input }} w-64">
    <select name="intake" class="{{ $input }}">
        <option value="">All windows</option>
        @foreach ($intakes as $i)<option value="{{ $i->id }}" @selected((string) request('intake') === (string) $i->id)>{{ $i->name }}</option>@endforeach
    </select>
    <select name="discipline" class="{{ $input }}">
        <option value="">All disciplines</option>
        @foreach ($disciplines as $d)<option value="{{ $d->id }}" @selected((string) request('discipline') === (string) $d->id)>{{ $d->name }}</option>@endforeach
    </select>
    <button class="btn btn-outline btn-sm">Filter</button>
</form>

<section class="{{ $card }}">
    @if ($applicants->isEmpty())
        <p class="text-sm text-slate-500 dark:text-slate-400">No applicants match.</p>
    @else
        <div class="overflow-x-auto">
            <table class="ledger w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="py-2 pr-2">Application no.</th><th class="px-2 py-2">Name</th><th class="px-2 py-2">Discipline</th><th class="px-2 py-2">Status</th><th class="px-2 py-2">Submitted</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($applicants as $a)
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <td class="py-2.5 pr-2 font-mono text-xs">{{ $a->application_number }}</td>
                        <td class="px-2 py-2.5 font-semibold">{{ $a->surname }}, {{ $a->first_name }}</td>
                        <td class="px-2 py-2.5">{{ $a->category?->name ?? '—' }}</td>
                        <td class="px-2 py-2.5">{{ $a->statusLabel() }}</td>
                        <td class="px-2 py-2.5">{{ $a->submitted_at?->format('j M Y') ?? '—' }}</td>
                        <td class="py-2.5 pl-2 text-right"><a href="{{ route('officer.applicants.show', $a) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">Open</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $applicants->links() }}</div>
    @endif
</section>
@endsection
