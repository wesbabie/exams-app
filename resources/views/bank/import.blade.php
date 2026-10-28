@extends('layouts.staff')

@section('title', 'Import questions')

@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800';
@endphp

@section('content')
<a href="{{ route('bank.questions.index') }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">← Question bank</a>
<h1 class="mb-1 mt-3 text-2xl font-bold">Import questions from a CSV file</h1>
<p class="mb-5 text-sm text-slate-500 dark:text-slate-400">Prepare the questions in Excel, then use <b>Save As → CSV (Comma delimited)</b>. Imported questions are saved as drafts for you to check before sending them for review.</p>

@if (session('import_errors'))
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">
        <b>{{ session('import_error_total') }} problem(s) found.</b> Nothing was imported.
        <ul class="mt-2 list-disc space-y-0.5 pl-5">
            @foreach (session('import_errors') as $message)<li>{{ $message }}</li>@endforeach
        </ul>
        @if (session('import_error_total') > count(session('import_errors')))
            <p class="mt-2">Showing the first {{ count(session('import_errors')) }}. Fix these and upload again to see the rest.</p>
        @endif
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
@endif

<div class="grid items-start gap-5 lg:grid-cols-3">
    <section class="{{ $card }} lg:col-span-2">
        <h2 class="mb-3 text-lg font-semibold">Upload</h2>
        <form method="POST" action="{{ route('bank.import.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv" required
                   class="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-blue-700 file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-blue-800">
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Up to 1,000 questions and 2 MB per file. If any row has a problem, nothing is imported.</p>
            <button type="submit" class="mt-4 rounded-lg bg-blue-700 px-6 py-2.5 font-semibold text-white hover:bg-blue-800">Import</button>
        </form>
    </section>

    <section class="{{ $card }}">
        <h2 class="mb-2 text-lg font-semibold">Template</h2>
        <a href="{{ route('bank.import.template') }}" class="inline-block rounded-lg border border-blue-700 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">Download template</a>
        <h3 class="mb-1 mt-4 text-sm font-semibold">Columns</h3>
        <ul class="space-y-1 text-sm text-slate-600 dark:text-slate-300">
            <li><b>topic</b>: must already exist (see list below)</li>
            <li><b>type</b>: single_choice, multi_select or true_false (blank means single_choice)</li>
            <li><b>question</b>: the question text</li>
            <li><b>option_a</b> to <b>option_f</b>: the options (2 to 6)</li>
            <li><b>correct</b>: a letter such as <code>B</code>, several such as <code>A;C</code>, or <code>true</code> / <code>false</code></li>
            <li><b>explanation</b>: optional</li>
        </ul>
    </section>
</div>

<section class="{{ $card }} mt-5">
    <h2 class="mb-2 text-lg font-semibold">Topics you can use</h2>
    @if ($topics->isEmpty())
        <p class="text-sm text-slate-500 dark:text-slate-400">There are no topics yet. An exam officer needs to add them first.</p>
    @else
        <div class="flex flex-wrap gap-2 text-sm">
            @foreach ($topics as $t)
                <span class="rounded-full border border-slate-300 px-3 py-1 dark:border-slate-600">{{ $t->name }} <span class="text-slate-500">· {{ $t->category?->name ?? 'Shared' }}</span></span>
            @endforeach
        </div>
    @endif
</section>
@endsection
