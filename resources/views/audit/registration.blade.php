@extends('layouts.staff')

@section('title', 'History · '.$name)

@php
    $muted = 'text-slate-600 dark:text-slate-300';
    $dot = ['staff' => 'bg-blue-600', 'warn' => 'bg-marker-400', 'candidate' => 'bg-slate-400 dark:bg-slate-500', 'flag' => 'bg-red-600'];
    $statusLabel = ['registered' => 'Registered', 'approved' => 'Approved', 'assigned' => 'Assigned', 'checked_in' => 'Checked in', 'admitted' => 'Admitted', 'in_progress' => 'Writing', 'completed' => 'Finished', 'absent' => 'Absent', 'removed' => 'Removed'];
@endphp

@section('content')
<x-page-head :title="$name" :subtitle="$registration->exam->title.($registration->examSession ? ' · '.$registration->examSession->name : '')" :back="url()->previous() !== url()->current() ? url()->previous() : route('staff.dashboard')" back-label="Back">
    <x-slot:badge>
        <span class="chip chip-neutral">{{ $statusLabel[$registration->status] ?? ucfirst($registration->status) }}</span>
        @if ($registration->locked_at)<span class="chip chip-bad">Locked</span>@endif
    </x-slot:badge>
</x-page-head>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <x-stat label="File or application no." :value="$number ?? '–'" />
    <x-stat label="Seat" :value="$registration->seat_number ?? '–'" />
    <x-stat label="Times left the exam tab" :value="$flags" />
    <x-stat label="Entries" :value="$timeline->count()" />
</div>

<section class="panel">
    <h2 class="mb-1 text-xl font-bold">Timeline</h2>
    <p class="mb-5 text-sm {{ $muted }}">Staff actions on this registration and everything recorded during the exam, oldest first. Leaving the exam tab is only a flag for review, never a score change.</p>

    @if ($timeline->isEmpty())
        <p class="text-sm {{ $muted }}">Nothing recorded yet.</p>
    @else
        <ol class="relative ml-2 border-l-2 border-slate-200 pl-6 dark:border-slate-700">
            @foreach ($timeline as $i)
                <li class="relative pb-5 last:pb-0">
                    <span class="absolute -left-[1.95rem] top-1.5 h-3 w-3 rounded-full ring-4 ring-white dark:ring-slate-800 {{ $dot[$i['kind']] ?? 'bg-slate-400' }}" aria-hidden="true"></span>
                    <p class="text-sm tabular-nums {{ $muted }}"><time datetime="{{ $i['at']->toIso8601String() }}">{{ $i['at']->format('D j M Y, g:i:s A') }}</time></p>
                    <p class="font-semibold">{{ $i['text'] }}@if ($i['kind'] === 'warn') <span class="chip chip-warn ml-1.5">Sensitive</span>@endif @if ($i['kind'] === 'flag') <span class="chip chip-bad ml-1.5">Flag</span>@endif</p>
                    <p class="text-sm {{ $muted }}">
                        {{ $i['who'] }}@if ($i['detail']) · {{ $i['detail'] }}@endif @if ($i['ip']) · {{ $i['ip'] }}@endif
                    </p>
                </li>
            @endforeach
        </ol>
    @endif
</section>
@endsection
