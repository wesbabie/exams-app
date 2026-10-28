@extends('layouts.staff')

@section('title', 'Edit topic')

@php
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
@endphp

@section('content')
<a href="{{ route('bank.topics.index') }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← All topics</a>

<div class="mt-3 max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <h1 class="text-xl font-bold">Edit topic</h1>
    @if ($errors->any())
        <div class="my-3 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('bank.topics.update', $topic) }}">
        @csrf @method('PUT')

        <label for="name" class="mb-1 mt-3 block text-sm font-semibold">Name</label>
        <input id="name" name="name" value="{{ old('name', $topic->name) }}" required maxlength="255" class="{{ $input }}">

        <label for="exam_category_id" class="mb-1 mt-3 block text-sm font-semibold">Scope</label>
        <select id="exam_category_id" name="exam_category_id" class="{{ $input }}">
            <option value="">Shared by all categories</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected((string) old('exam_category_id', $topic->exam_category_id) === (string) $c->id)>Only {{ $c->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Changing the scope does not move questions already filed under this topic.</p>

        <button type="submit" class="mt-5 rounded-lg bg-blue-700 px-6 py-2.5 font-semibold text-white hover:bg-blue-800">Save</button>
    </form>
</div>
@endsection
