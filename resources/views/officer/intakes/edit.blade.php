@extends('layouts.staff')

@section('title', 'Edit registration window')

@section('content')
<div class="mx-auto max-w-md">
    <h1 class="text-2xl font-bold">Edit registration window</h1>
    <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Moving the closing date later lets applicants keep editing, including those you returned for correction.</p>

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
                <a href="{{ route('officer.intakes.index') }}" class="px-3 py-2.5 font-semibold text-slate-600 hover:underline dark:text-slate-300">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
