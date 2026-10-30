@extends('layouts.staff')

@section('title', 'Edit sitting')

@php
    $input = 'field';
@endphp

@section('content')
<x-page-head title="Edit sitting" :subtitle="$sitting->name" :back="route('officer.exams.sittings.index', $exam)" back-label="Sittings" />

<div class="panel max-w-lg">
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

        <button type="submit" class="btn btn-primary mt-5 px-6">Save</button>
    </form>
</div>
@endsection
