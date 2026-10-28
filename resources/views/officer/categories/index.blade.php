@extends('layouts.staff')

@section('title', 'Exam categories')

@php
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
@endphp

@section('content')
<h1 class="text-2xl font-bold">Exam categories</h1>
<p class="mb-5 text-sm text-slate-500 dark:text-slate-400">A category is a staff cadre and level (for example Nursing Officer I) or an intern discipline. Every exam belongs to one category.</p>

<div class="grid items-start gap-5 lg:grid-cols-3">
    <section class="{{ $card }} lg:col-span-2">
        @if ($categories->isEmpty())
            <p class="text-sm text-slate-500 dark:text-slate-400">No categories yet. Add the first one on the right.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            <th class="py-2 pr-2">Name</th><th class="px-2 py-2">Code</th><th class="px-2 py-2">Type</th><th class="px-2 py-2 text-right">Exams</th><th class="px-2 py-2">Status</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($categories as $c)
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <td class="py-2.5 pr-2 font-semibold">{{ $c->name }}</td>
                            <td class="px-2 py-2.5 font-mono text-xs">{{ $c->code }}</td>
                            <td class="px-2 py-2.5">{{ $c->kindLabel() }}</td>
                            <td class="px-2 py-2.5 text-right tabular-nums">{{ $c->exams_count }}</td>
                            <td class="px-2 py-2.5">
                                @if ($c->is_active)<span class="text-green-700 dark:text-green-400">Active</span>@else<span class="text-slate-500">Inactive</span>@endif
                            </td>
                            <td class="py-2.5 pl-2 text-right"><a href="{{ route('officer.categories.edit', $c) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">Edit</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-1 text-lg font-semibold">Add a category</h2>
        @if ($errors->any())
            <div class="my-2 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('officer.categories.store') }}">
            @csrf
            <label for="kind" class="mb-1 mt-3 block text-sm font-semibold">Type</label>
            <select id="kind" name="kind" class="{{ $input }}">
                @foreach (\App\Models\ExamCategory::KINDS as $value => $label)
                    <option value="{{ $value }}" @selected(old('kind') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <label for="name" class="mb-1 mt-3 block text-sm font-semibold">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Nursing Officer I" class="{{ $input }}">

            <label for="code" class="mb-1 mt-3 block text-sm font-semibold">Code</label>
            <input id="code" name="code" value="{{ old('code') }}" required maxlength="50" placeholder="NO-I" class="{{ $input }} font-mono uppercase">
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">A short unique code. Stored in capitals.</p>

            <label class="mt-3 flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 accent-blue-700"> <span>Active</span>
            </label>

            <button type="submit" class="mt-4 w-full rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Add category</button>
        </form>
    </section>
</div>
@endsection
