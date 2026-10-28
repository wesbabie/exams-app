@extends('layouts.staff')

@section('title', 'Edit category')

@php
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
@endphp

@section('content')
<a href="{{ route('officer.categories.index') }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← All categories</a>

<div class="mt-3 max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <h1 class="text-xl font-bold">Edit category</h1>
    @if ($errors->any())
        <div class="my-3 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('officer.categories.update', $category) }}">
        @csrf @method('PUT')

        <label for="kind" class="mb-1 mt-3 block text-sm font-semibold">Type</label>
        <select id="kind" name="kind" class="{{ $input }}">
            @foreach (\App\Models\ExamCategory::KINDS as $value => $label)
                <option value="{{ $value }}" @selected(old('kind', $category->kind) === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <label for="name" class="mb-1 mt-3 block text-sm font-semibold">Name</label>
        <input id="name" name="name" value="{{ old('name', $category->name) }}" required maxlength="255" class="{{ $input }}">

        <label for="code" class="mb-1 mt-3 block text-sm font-semibold">Code</label>
        <input id="code" name="code" value="{{ old('code', $category->code) }}" required maxlength="50" class="{{ $input }} font-mono uppercase">

        <label class="mt-3 flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="h-4 w-4 accent-blue-700">
            <span>Active (inactive categories cannot be chosen for new exams)</span>
        </label>

        <button type="submit" class="mt-5 rounded-lg bg-blue-700 px-6 py-2.5 font-semibold text-white hover:bg-blue-800">Save</button>
    </form>
</div>
@endsection
