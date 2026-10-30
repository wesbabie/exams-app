@extends('layouts.staff')

@section('title', 'Edit topic')

@php
    $input = 'field';
@endphp

@section('content')
<x-page-head title="Edit topic" :back="route('bank.topics.index')" back-label="All topics" />

<div class="panel max-w-lg">
    <x-form-errors />
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

        <button type="submit" class="btn btn-primary mt-6 px-6">Save</button>
    </form>
</div>
@endsection
