@extends('layouts.staff')

@section('title', 'Intern registration windows')

@php
    $input = 'field';
    $card = 'panel';
@endphp

@section('content')
<h1 class="text-2xl font-bold">Intern registration windows</h1>
<p class="mb-5 text-sm text-slate-500 dark:text-slate-400">Applicants can only register and edit their applications while a window is open. The year must match the cycle year of the intern exams.</p>

<div class="grid items-start gap-5 lg:grid-cols-3">
    <section class="{{ $card }} lg:col-span-2">
        @if ($intakes->isEmpty())
            <p class="text-sm text-slate-500 dark:text-slate-400">No registration windows yet. Add the first one on the right.</p>
        @else
            <div class="overflow-x-auto">
                <table class="ledger w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            <th class="py-2 pr-2">Name</th><th class="px-2 py-2">Opens</th><th class="px-2 py-2">Closes</th><th class="px-2 py-2">State</th><th class="px-2 py-2 text-right">Applicants</th><th class="px-2 py-2 text-right">To review</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($intakes as $i)
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <td class="py-2.5 pr-2 font-semibold">{{ $i->name }} <span class="font-normal text-slate-500">({{ $i->year }})</span></td>
                            <td class="px-2 py-2.5">{{ $i->opens_at->format('j M Y, g:i A') }}</td>
                            <td class="px-2 py-2.5">{{ $i->closes_at->format('j M Y, g:i A') }}</td>
                            <td class="px-2 py-2.5">{{ $i->stateLabel() }}</td>
                            <td class="px-2 py-2.5 text-right tabular-nums">{{ $i->applicants_count }}</td>
                            <td class="px-2 py-2.5 text-right tabular-nums">
                                <a href="{{ route('officer.applicants.index', ['intake' => $i->id]) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">{{ $i->submitted_count }}</a>
                            </td>
                            <td class="py-2.5 pl-2 text-right"><a href="{{ route('officer.intakes.edit', $i) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">Edit</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-1 text-lg font-semibold">Add a window</h2>
        @if ($errors->any())
            <div class="my-2 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('officer.intakes.store') }}">
            @csrf
            @include('officer.intakes._fields', ['intake' => null])
            <button type="submit" class="mt-4 w-full btn btn-primary">Add window</button>
        </form>
    </section>
</div>
@endsection
