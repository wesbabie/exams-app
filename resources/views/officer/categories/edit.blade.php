@extends('layouts.staff')

@section('title', 'Edit category')

@php
    $input = 'field';
@endphp

@section('content')
<x-page-head title="Edit category" :back="route('officer.categories.index')" back-label="All categories" />

<div class="panel max-w-lg">
    <x-form-errors />
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

        <button type="submit" class="btn btn-primary mt-6 px-6">Save</button>
    </form>
</div>
@endsection
