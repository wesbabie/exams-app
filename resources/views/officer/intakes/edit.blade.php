@extends('layouts.staff')

@section('title', 'Edit registration window')

@section('content')
<x-page-head title="Edit registration window" subtitle="Moving the closing date later lets applicants keep editing, including those you returned for correction." :back="route('officer.intakes.index')" back-label="Registration windows" />

<div class="max-w-md">
    <div class="panel">
        @if ($errors->any())
            <div class="mb-2 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('officer.intakes.update', $intake) }}">
            @csrf
            @method('PUT')
            @include('officer.intakes._fields')
            <div class="mt-4 flex gap-3">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('officer.intakes.index') }}" class="btn btn-neutral">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
