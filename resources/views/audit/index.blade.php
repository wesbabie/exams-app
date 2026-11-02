@extends('layouts.staff')

@section('title', 'Audit log')

@php
    $muted = 'text-slate-600 dark:text-slate-300';
    $query = fn (array $extra = []) => array_filter(array_merge(request()->except('page'), $extra), fn ($v) => $v !== null && $v !== '');
    $roles = collect(\App\Enums\StaffRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()]);
@endphp

@section('content')
<x-page-head title="Audit log" subtitle="Who did what, and when. Entries are added by the system and cannot be edited or deleted from the app.">
    <x-slot:actions>
        <a href="{{ route('audit.export', $query()) }}" class="btn btn-outline btn-sm">Export CSV (Excel)</a>
    </x-slot:actions>
</x-page-head>

<x-form-errors />

<div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
    <x-stat label="Entries in the last 24 hours" :value="$last24" />
    <x-stat label="Sensitive actions this week" :value="$sensitive7" />
    <x-stat label="Failed staff sign-ins this week" :value="$failed7" />
</div>

<nav class="mb-4 flex flex-wrap gap-2" aria-label="Area">
    <a href="{{ route('audit.index', $query(['category' => null])) }}" class="pill {{ empty($filters['category']) ? 'pill-on' : '' }}">All areas</a>
    @foreach ($categories as $key => $label)
        <a href="{{ route('audit.index', $query(['category' => $key])) }}" class="pill {{ ($filters['category'] ?? null) === $key ? 'pill-on' : '' }}">{{ $label }}</a>
    @endforeach
</nav>

<form method="GET" action="{{ route('audit.index') }}" class="panel mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-6" role="search">
    @if (! empty($filters['category']))<input type="hidden" name="category" value="{{ $filters['category'] }}">@endif
    <div class="lg:col-span-2">
        <label for="q" class="mb-1 block text-sm font-semibold">Search</label>
        <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Person, exam, candidate or action" class="field-sm w-full">
    </div>
    <div>
        <label for="actor" class="mb-1 block text-sm font-semibold">Person</label>
        <select id="actor" name="actor" class="field-sm w-full">
            <option value="">Anyone</option>
            @foreach ($people as $p)<option value="{{ $p->id }}" @selected((string) ($filters['actor'] ?? '') === (string) $p->id)>{{ $p->name }}</option>@endforeach
        </select>
    </div>
    <div>
        <label for="exam" class="mb-1 block text-sm font-semibold">Exam</label>
        <select id="exam" name="exam" class="field-sm w-full">
            <option value="">Any exam</option>
            @foreach ($exams as $e)<option value="{{ $e->id }}" @selected((string) ($filters['exam'] ?? '') === (string) $e->id)>{{ \Illuminate\Support\Str::limit($e->title, 40) }}</option>@endforeach
        </select>
    </div>
    <div>
        <label for="from" class="mb-1 block text-sm font-semibold">From</label>
        <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="field-sm w-full">
    </div>
    <div>
        <label for="to" class="mb-1 block text-sm font-semibold">To</label>
        <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="field-sm w-full">
    </div>
    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 sm:col-span-2 lg:col-span-6">
        <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="sensitive" value="1" @checked(! empty($filters['sensitive'])) class="h-4 w-4 accent-blue-700"> Sensitive actions only</label>
        <button class="btn btn-primary btn-sm">Apply</button>
        @if (array_filter($filters))<a href="{{ route('audit.index') }}" class="btn btn-neutral btn-sm">Clear</a>@endif
    </div>
</form>

<section class="panel !p-0 overflow-hidden">
    @if ($logs->isEmpty())
        <p class="p-5 text-sm {{ $muted }}">Nothing matches. {{ array_filter($filters) ? 'Try clearing a filter.' : 'Entries appear here as people use the system.' }}</p>
    @else
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            @foreach ($logs as $log)
                @php $hasDetail = $log->reason || ! empty($log->meta['changes']) || $log->ip_address || ! empty($log->meta['email']) || ! empty($log->meta['filters']) || ! empty($log->meta['failed_attempts']) || ! empty($log->meta['count']); @endphp
                <li class="grid gap-x-6 gap-y-1 px-5 py-3.5 sm:grid-cols-[9.5rem_11rem_1fr]">
                    <div class="text-sm tabular-nums {{ $muted }}">
                        <time datetime="{{ $log->created_at->toIso8601String() }}"><span class="block font-semibold text-slate-800 dark:text-slate-100">{{ $log->created_at->format('j M Y') }}</span>{{ $log->created_at->format('g:i:s A') }}</time>
                    </div>
                    <div class="text-sm">
                        <span class="block font-semibold">{{ $log->actor_name ?? 'System or signed out' }}</span>
                        @if ($log->actor_role)<span class="{{ $muted }}">{{ $roles[$log->actor_role] ?? $log->actor_role }}</span>@endif
                    </div>
                    <div class="min-w-0">
                        <p class="break-words">
                            {{ $log->summary }}
                            @if ($log->isSensitive())<span class="chip chip-warn ml-1.5 align-middle">Sensitive</span>@endif
                        </p>
                        <p class="text-xs {{ $muted }}">{{ $log->categoryLabel() }}@if ($log->subject_type === 'registration' && $log->subject_id) · <a href="{{ route('registrations.history', $log->subject_id) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">Candidate history</a>@endif</p>

                        @if ($hasDetail)
                            <details class="mt-1.5 text-sm">
                                <summary class="cursor-pointer font-semibold text-blue-700 hover:underline dark:text-blue-300">Details</summary>
                                <div class="mt-2 space-y-2 rounded-lg bg-slate-50 p-3 dark:bg-slate-900">
                                    @if ($log->reason)<p><b>Reason:</b> {{ $log->reason }}</p>@endif
                                    @if (! empty($log->meta['changes']))@include('audit._changes', ['changes' => $log->meta['changes']])@endif
                                    @if (! empty($log->meta['email']))<p><b>Email tried:</b> {{ $log->meta['email'] }}</p>@endif
                                    @if (! empty($log->meta['count']))<p><b>Questions:</b> {{ $log->meta['count'] }}</p>@endif
                                    @if (! empty($log->meta['failed_attempts']))<p><b>Wrong PINs:</b> {{ $log->meta['failed_attempts'] }}</p>@endif
                                    @if ($log->ip_address)<p class="{{ $muted }}">From {{ $log->ip_address }}</p>@endif
                                </div>
                            </details>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="border-t border-slate-200 p-4 dark:border-slate-700">{{ $logs->links() }}</div>
    @endif
</section>
@endsection
