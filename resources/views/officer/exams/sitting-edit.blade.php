@extends('layouts.staff')

@section('title', 'Edit sitting')

@php
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900';
@endphp

@section('content')
<a href="{{ route('officer.exams.sittings.index', $exam) }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← Sittings</a>

<div class="mt-3 max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <h1 class="text-xl font-bold">Edit sitting</h1>
    @if ($errors->any())
        <div class="my-3 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('officer.exams.sittings.update', [$exam, $sitting]) }}">
        @csrf @method('PUT')

        <label for="name" class="mb-1 mt-3 block text-sm font-semibold">Name</label>
        <input id="name" name="name" value="{{ old('name', $sitting->name) }}" required maxlength="255" class="{{ $input }}">

        <label for="venue" class="mb-1 mt-3 block text-sm font-semibold">Venue</label>
        <input id="venue" name="venue" value="{{ old('venue', $sitting->venue) }}" maxlength="255" class="{{ $input }}">

        <label for="starts_at" class="mb-1 mt-3 block text-sm font-semibold">Starts</label>
        <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $sitting->starts_at->format('Y-m-d\TH:i')) }}" required class="{{ $input }}">

        <label for="ends_at" class="mb-1 mt-3 block text-sm font-semibold">Ends</label>
        <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $sitting->ends_at->format('Y-m-d\TH:i')) }}" required class="{{ $input }}">

        <label for="capacity" class="mb-1 mt-3 block text-sm font-semibold">Seats</label>
        <input id="capacity" name="capacity" type="number" min="1" max="2000" value="{{ old('capacity', $sitting->capacity) }}" required class="{{ $input }}">

        <button type="submit" class="mt-5 rounded-lg bg-blue-700 px-6 py-2.5 font-semibold text-white hover:bg-blue-800">Save</button>
    </form>
</div>
@endsection
