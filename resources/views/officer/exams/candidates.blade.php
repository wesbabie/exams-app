@extends('layouts.staff')

@section('title', 'Candidates')

@php
    $card = 'panel';
    $input = 'rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $muted = 'text-slate-500 dark:text-slate-400';
    $mini = 'rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-700';
    $labels = ['registered' => 'Awaiting approval', 'approved' => 'Approved', 'assigned' => 'Assigned', 'removed' => 'Removed'];
    $badge = [
        'registered' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'assigned' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'removed' => 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
    ];
    $chips = ['all' => 'All'] + $labels;
    $openSittings = $sittings->where('status', '!=', 'closed');
    $waiting = $counts['approved'] ?? 0;
    $me = auth()->id();
    $closed = $exam->status === 'closed';
@endphp

@section('content')
<a href="{{ route('officer.exams.show', $exam) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← {{ $exam->title }}</a>
<div class="mb-5 mt-3 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">Candidates</h1>
        <p class="text-sm {{ $muted }}">{{ $exam->category->name }} · the eligible list, late additions and seats.</p>
    </div>
    <a href="{{ route('officer.exams.sittings.index', $exam) }}" class="btn btn-outline btn-sm">Sittings</a>
</div>

@if (session('import_errors'))
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">
        <b>{{ session('import_error_total') }} problem(s) found.</b> Nothing was imported.
        <ul class="mt-2 list-disc space-y-0.5 pl-5">
            @foreach (session('import_errors') as $message)<li>{{ $message }}</li>@endforeach
        </ul>
        @if (session('import_error_total') > count(session('import_errors')))
            <p class="mt-2">Showing the first {{ count(session('import_errors')) }}. Fix these and upload again to see the rest.</p>
        @endif
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
@endif

<div class="mb-5 grid items-start gap-5 lg:grid-cols-3">
    {{-- Eligible lists --}}
    <section class="{{ $card }}">
        <h2 class="mb-2 text-lg font-semibold">Eligible lists</h2>

        @if (! $isStaffExam)
            <p class="text-sm {{ $muted }}">This is an intern exam. Interns register themselves, so there is no list to import here.</p>
        @elseif (! $closed)
            <p class="mb-3 text-sm {{ $muted }}">Admin staff decide who is eligible. Import their list from a CSV file; another exam officer then approves it.</p>
            <form method="POST" action="{{ route('officer.exams.candidates.import', $exam) }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" accept=".csv,text/csv" required
                       class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-700 file:px-3 file:py-1.5 file:font-semibold file:text-white hover:file:bg-blue-800">
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Import list</button>
                    <a href="{{ route('officer.exams.candidates.template', $exam) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">Download template</a>
                </div>
            </form>
        @endif

        @if ($lists->isNotEmpty())
            <ul class="mt-4 space-y-3">
                @foreach ($lists as $list)
                    <li class="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="truncate font-semibold">{{ $list->original_filename ?? 'List' }}</div>
                                <div class="text-xs {{ $muted }}">{{ $list->row_count }} candidates · imported by {{ $list->importer?->name ?? '–' }} {{ $list->created_at->diffForHumans() }}</div>
                            </div>
                            @if ($list->isApproved())
                                <span class="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">Approved</span>
                            @else
                                <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-200">Pending</span>
                            @endif
                        </div>
                        @if ($list->isApproved())
                            <div class="mt-1 text-xs {{ $muted }}">Approved by {{ $list->approver?->name ?? '–' }} · locked</div>
                        @else
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if ($list->imported_by === $me)
                                    <span class="text-xs {{ $muted }}">You imported this list, so another exam officer needs to approve it.</span>
                                @else
                                    <form method="POST" action="{{ route('officer.exams.lists.approve', [$exam, $list]) }}">@csrf<button class="rounded-lg bg-green-700 px-3 py-1 text-xs font-semibold text-white hover:bg-green-800">Approve list</button></form>
                                @endif
                                <form method="POST" action="{{ route('officer.exams.lists.discard', [$exam, $list]) }}" onsubmit="return confirm('Discard this list and its candidates?')">@csrf @method('DELETE')<button class="{{ $mini }} text-red-700 dark:text-red-400">Discard</button></form>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Sittings and seats --}}
    <section class="{{ $card }}">
        <h2 class="mb-2 text-lg font-semibold">Sittings and seats</h2>
        @if ($sittings->isEmpty())
            <p class="text-sm {{ $muted }}">No sittings yet. <a href="{{ route('officer.exams.sittings.index', $exam) }}" class="font-semibold text-blue-700 underline dark:text-blue-400">Add a sitting</a> first.</p>
        @else
            <ul class="space-y-2 text-sm">
                @foreach ($sittings as $s)
                    <li class="flex items-baseline justify-between gap-3">
                        <span>{{ $s->name }} <span class="text-xs {{ $muted }}">{{ $s->starts_at->format('j M, g:i A') }}</span>@if ($s->status === 'closed') <span class="text-xs text-red-600">closed</span>@endif</span>
                        <b class="tabular-nums {{ $s->assigned_count >= $s->capacity ? 'text-red-700 dark:text-red-400' : '' }}">{{ $s->assigned_count }} / {{ $s->capacity }}</b>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($waiting > 0 && $openSittings->isNotEmpty() && ! $closed)
            <form method="POST" action="{{ route('officer.exams.candidates.auto-assign', $exam) }}" class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-700">
                @csrf
                <p class="mb-2 text-sm {{ $muted }}">{{ $waiting }} approved candidate(s) have no sitting yet.</p>
                <button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Assign them automatically</button>
                <p class="mt-1 text-xs {{ $muted }}">Fills the sittings in date order, by file number, with seats from 1.</p>
            </form>
        @endif
    </section>

    {{-- Late addition --}}
    <section class="{{ $card }}">
        <h2 class="mb-2 text-lg font-semibold">Add a candidate</h2>
        @if (! $isStaffExam)
            <p class="text-sm {{ $muted }}">Not available for intern exams.</p>
        @elseif ($closed)
            <p class="text-sm {{ $muted }}">This exam is closed.</p>
        @else
            <p class="mb-3 text-sm {{ $muted }}">For someone missed from the list. A reason is required and is logged.</p>
            <details {{ $errors->has('file_number') || $errors->has('reason') ? 'open' : '' }}>
                <summary class="cursor-pointer text-sm font-semibold text-blue-700 dark:text-blue-400">Add one candidate</summary>
                <form method="POST" action="{{ route('officer.exams.candidates.add', $exam) }}" class="mt-3 space-y-2">
                    @csrf
                    <input name="file_number" value="{{ old('file_number') }}" required maxlength="30" placeholder="File number" class="{{ $input }} w-full uppercase">
                    <div class="grid grid-cols-2 gap-2">
                        <input name="surname" value="{{ old('surname') }}" required placeholder="Surname" class="{{ $input }}">
                        <input name="first_name" value="{{ old('first_name') }}" required placeholder="First name" class="{{ $input }}">
                    </div>
                    <input name="department" value="{{ old('department') }}" placeholder="Department (optional)" class="{{ $input }} w-full">
                    <div class="grid grid-cols-2 gap-2">
                        <input name="phone" value="{{ old('phone') }}" placeholder="Phone (optional)" class="{{ $input }}">
                        <input name="email" type="email" value="{{ old('email') }}" placeholder="Email (optional)" class="{{ $input }}">
                    </div>
                    <input name="reason" value="{{ old('reason') }}" required maxlength="500" placeholder="Reason for adding" class="{{ $input }} w-full">
                    <button class="w-full rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Add candidate</button>
                </form>
            </details>
        @endif
    </section>
</div>

{{-- Status chips and filters --}}
<div class="mb-3 flex flex-wrap gap-2 text-sm">
    @foreach ($chips as $key => $label)
        @php $n = $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0); @endphp
        <a href="{{ route('officer.exams.candidates.index', array_merge(request()->except(['page', 'status']), ['exam' => $exam, 'status' => $key])) }}"
           class="pill {{ $status === $key ? 'pill-on' : '' }}">{{ $label }} <span class="opacity-70">{{ $n }}</span></a>
    @endforeach
</div>

<form method="GET" action="{{ route('officer.exams.candidates.index', $exam) }}" class="mb-3 grid gap-2 sm:grid-cols-4">
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search file number or name" class="{{ $input }} sm:col-span-2">
    <select name="sitting" class="{{ $input }}">
        <option value="">All sittings</option>
        @foreach ($sittings as $s)<option value="{{ $s->id }}" @selected((string) request('sitting') === (string) $s->id)>{{ $s->name }}</option>@endforeach
    </select>
    <button class="{{ $mini }} py-2 text-sm">Filter</button>
</form>

<section class="{{ $card }}">
    @if ($registrations->isEmpty())
        <p class="text-sm {{ $muted }}">No candidates here yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="ledger w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="py-2 pr-2">Candidate</th><th class="px-2 py-2">Cadre / department</th><th class="px-2 py-2">Status</th><th class="px-2 py-2">Sitting</th><th class="px-2 py-2">Seat</th><th class="px-2 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($registrations as $r)
                    @php $c = $cands[$r->candidate_type][$r->candidate_id] ?? null; @endphp
                    <tr class="border-b border-slate-200 align-top dark:border-slate-700">
                        <td class="py-2.5 pr-2">
                            <div class="font-semibold">{{ $c?->full_name ?? '–' }}</div>
                            <div class="font-mono text-xs {{ $muted }}">{{ $c?->file_number ?? $c?->application_number }}</div>
                        </td>
                        <td class="px-2 py-2.5">{{ trim(($c->cadre ?? '').' '.($c->level ?? '')) ?: '–' }}<div class="text-xs {{ $muted }}">{{ $c->department ?? '' }}</div></td>
                        <td class="px-2 py-2.5"><span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge[$r->status] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200' }}">{{ $labels[$r->status] ?? ucfirst(str_replace('_', ' ', $r->status)) }}</span>
                            @if ((float) $r->extra_time_multiplier > 1)<div class="mt-1 text-xs font-semibold text-blue-700 dark:text-blue-400">Extra time ×{{ rtrim(rtrim(number_format((float) $r->extra_time_multiplier, 2), '0'), '.') }}</div>@endif
                            @if (! $closed && in_array($r->status, ['registered', 'approved', 'assigned', 'checked_in', 'admitted'], true))
                                <details class="mt-1">
                                    <summary class="cursor-pointer text-xs {{ $muted }}">Extra time</summary>
                                    <form method="POST" action="{{ route('officer.exams.candidates.extra-time', [$exam, $r]) }}" class="mt-1 space-y-1">
                                        @csrf
                                        <select name="multiplier" class="{{ $input }} py-1 text-xs" aria-label="Extra time">
                                            @foreach (['1.00' => 'None', '1.25' => '×1.25', '1.50' => '×1.5', '2.00' => '×2'] as $value => $label)
                                                <option value="{{ $value }}" @selected(number_format((float) $r->extra_time_multiplier, 2, '.', '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input name="reason" maxlength="500" placeholder="Reason" class="{{ $input }} w-full py-1 text-xs" aria-label="Reason for extra time">
                                        <button class="{{ $mini }}">Save</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                        <td class="px-2 py-2.5">{{ $r->examSession?->name ?? '–' }}</td>
                        <td class="px-2 py-2.5 tabular-nums">{{ $r->seat_number ?? '–' }}</td>
                        <td class="px-2 py-2.5">
                            @if (! $closed)
                                <div class="flex flex-wrap items-start gap-2">
                                    @if (in_array($r->status, ['approved', 'assigned'], true) && $openSittings->isNotEmpty())
                                        <form method="POST" action="{{ route('officer.exams.candidates.assign', [$exam, $r]) }}" class="flex gap-1">
                                            @csrf
                                            <select name="sitting_id" class="{{ $input }} py-1 text-xs" aria-label="Sitting">
                                                @foreach ($openSittings as $s)
                                                    <option value="{{ $s->id }}" @selected($r->exam_session_id === $s->id)>{{ $s->name }} ({{ max(0, $s->capacity - $s->assigned_count) }} free)</option>
                                                @endforeach
                                            </select>
                                            <button class="{{ $mini }}">{{ $r->status === 'assigned' ? 'Move' : 'Assign' }}</button>
                                        </form>
                                    @endif
                                    @if ($r->status === 'assigned')
                                        <form method="POST" action="{{ route('officer.exams.candidates.unassign', [$exam, $r]) }}">@csrf<button class="{{ $mini }}">Unassign</button></form>
                                    @endif
                                    @if (in_array($r->status, ['registered', 'approved', 'assigned'], true))
                                        <details>
                                            <summary class="cursor-pointer text-xs font-semibold text-red-700 dark:text-red-400">Remove</summary>
                                            <form method="POST" action="{{ route('officer.exams.candidates.remove', [$exam, $r]) }}" class="mt-1 flex gap-1">
                                                @csrf @method('DELETE')
                                                <input name="reason" required maxlength="500" placeholder="Reason" class="{{ $input }} py-1 text-xs" aria-label="Reason for removing">
                                                <button class="{{ $mini }} text-red-700 dark:text-red-400">Remove</button>
                                            </form>
                                        </details>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $registrations->links() }}</div>
    @endif
</section>

<section class="{{ $card }} mt-5">
    <h2 class="mb-2 text-lg font-semibold">Recent activity</h2>
    @if ($log->isEmpty())
        <p class="text-sm {{ $muted }}">Nothing yet.</p>
    @else
        <ul class="space-y-1.5 text-sm">
            @foreach ($log as $entry)
                <li class="flex flex-wrap gap-x-2">
                    <span class="{{ $muted }}">{{ $entry->created_at->format('j M, g:i A') }}</span>
                    <b>{{ $entry->user?->name ?? 'System' }}</b>
                    <span>{{ str_replace('_', ' ', $entry->action) }}</span>
                    @if ($entry->detail)<span class="font-mono text-xs">{{ $entry->detail }}</span>@endif
                    @if ($entry->reason)<span class="{{ $muted }}">· “{{ $entry->reason }}”</span>@endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
@endsection
