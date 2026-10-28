@extends('layouts.intern')

@section('title', 'My details')

@php
    $input = 'field';
    $label = 'mb-1.5 mt-4 block font-semibold';
    $hint = 'mt-1 text-xs text-slate-500 dark:text-slate-400';
@endphp

@section('content')
<div class="panel p-6">
    <h1 class="text-2xl font-bold">My details</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400">Application {{ $applicant->application_number }}</p>

    @if ($applicant->review_note && $applicant->status === 'returned')
        <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
            <div class="font-semibold">What needs correcting</div>
            <p class="mt-1">{{ $applicant->review_note }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-300 bg-red-50 px-3.5 py-2.5 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('intern.profile.update') }}" enctype="multipart/form-data" class="mt-2">
        @csrf
        @method('PUT')

        <div class="grid gap-x-4 sm:grid-cols-2">
            <div>
                <label for="surname" class="{{ $label }}">Surname</label>
                <input id="surname" name="surname" value="{{ old('surname', $applicant->surname) }}" required maxlength="255" class="{{ $input }}">
            </div>
            <div>
                <label for="first_name" class="{{ $label }}">First name</label>
                <input id="first_name" name="first_name" value="{{ old('first_name', $applicant->first_name) }}" required maxlength="255" class="{{ $input }}">
            </div>
        </div>

        <label for="other_names" class="{{ $label }}">Other names <span class="font-normal text-slate-500">(optional)</span></label>
        <input id="other_names" name="other_names" value="{{ old('other_names', $applicant->other_names) }}" maxlength="255" class="{{ $input }}">

        <label for="phone" class="{{ $label }}">Phone number</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone', $applicant->phone) }}" required maxlength="30" class="{{ $input }}">

        <label for="exam_category_id" class="{{ $label }}">Discipline</label>
        <select id="exam_category_id" name="exam_category_id" required class="{{ $input }}">
            <option value="">Choose your discipline</option>
            @foreach ($disciplines as $d)
                <option value="{{ $d->id }}" @selected((string) old('exam_category_id', $applicant->exam_category_id) === (string) $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
        <p class="{{ $hint }}">This is fixed once your documents are approved.</p>

        <label for="professional_reg_no" class="{{ $label }}">Professional registration number <span class="font-normal text-slate-500">(if you have one)</span></label>
        <input id="professional_reg_no" name="professional_reg_no" value="{{ old('professional_reg_no', $applicant->professional_reg_no) }}" maxlength="50" class="{{ $input }} uppercase">

        <label for="passport" class="{{ $label }}">Passport photograph</label>
        @if ($applicant->passport_path)
            <p class="{{ $hint }}">Uploaded. <a href="{{ route('intern.documents.show', 'passport') }}" target="_blank" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">View</a>. Choose a file only to replace it.</p>
        @endif
        <input id="passport" name="passport" type="file" accept="image/png,image/jpeg" class="{{ $input }}">
        <p class="{{ $hint }}">JPG or PNG, up to 2 MB. A clear photo of your face.</p>

        <label for="credential" class="{{ $label }}">Credential</label>
        @if ($applicant->credential_path)
            <p class="{{ $hint }}">Uploaded. <a href="{{ route('intern.documents.show', 'credential') }}" target="_blank" class="font-semibold text-blue-700 hover:underline dark:text-blue-400">View</a>. Choose a file only to replace it.</p>
        @endif
        <input id="credential" name="credential" type="file" accept="application/pdf,image/png,image/jpeg" class="{{ $input }}">
        <p class="{{ $hint }}">Your degree, certificate or statement of result. PDF, JPG or PNG, up to 5 MB.</p>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg border border-blue-700 px-5 py-2.5 font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">Save</button>
            <button type="submit" formaction="{{ route('intern.profile.submit') }}"
                    onclick="return confirm('Submit your application for review? You will not be able to change it unless it is returned to you.')"
                    class="btn btn-primary">Save and submit for review</button>
            <a href="{{ route('intern.dashboard') }}" class="px-3 py-2.5 font-semibold text-slate-600 hover:underline dark:text-slate-300">Back</a>
        </div>
    </form>
</div>
@endsection
