@extends('layouts.staff')

@section('title', 'Topics')

@php
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
@endphp

@section('content')
<h1 class="text-2xl font-bold">Topics</h1>
<p class="mb-5 text-sm text-slate-500 dark:text-slate-400">Questions are filed under topics. A shared topic (such as General Knowledge or Civil Service Rules) can be used by every category. A category topic belongs to one cadre or discipline.</p>

<div class="grid items-start gap-5 lg:grid-cols-3">
    <section class="{{ $card }} lg:col-span-2">
        @if ($topics->isEmpty())
            <p class="text-sm text-slate-500 dark:text-slate-400">No topics yet. Add the first one on the right.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            <th class="py-2 pr-2">Topic</th><th class="px-2 py-2">Scope</th><th class="px-2 py-2 text-right">Questions</th><th class="px-2 py-2 text-right">Approved</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($topics as $t)
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <td class="py-2.5 pr-2 font-semibold">{{ $t->name }}</td>
                            <td class="px-2 py-2.5">{{ $t->category?->name ?? 'Shared' }}</td>
                            <td class="px-2 py-2.5 text-right tabular-nums">{{ $t->questions_count }}</td>
                            <td class="px-2 py-2.5 text-right tabular-nums">{{ $t->approved_count }}</td>
                            <td class="py-2.5 pl-2 text-right"><a href="{{ route('bank.topics.edit', $t) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">Edit</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-1 text-lg font-semibold">Add a topic</h2>
        @if ($errors->any())
            <div class="my-2 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('bank.topics.store') }}">
            @csrf
            <label for="name" class="mb-1 mt-3 block text-sm font-semibold">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Civil Service Rules" class="{{ $input }}">

            <label for="exam_category_id" class="mb-1 mt-3 block text-sm font-semibold">Scope</label>
            <select id="exam_category_id" name="exam_category_id" class="{{ $input }}">
                <option value="">Shared by all categories</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected((string) old('exam_category_id') === (string) $c->id)>Only {{ $c->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="mt-4 w-full rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Add topic</button>
        </form>
    </section>
</div>
@endsection
