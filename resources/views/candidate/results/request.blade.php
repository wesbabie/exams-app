@extends('layouts.guest')

@section('title', 'See my results')

@section('aside')
    <p class="font-display text-3xl font-bold leading-tight tracking-tight">Your result is private to you.</p>
    <p class="mt-4 text-sm leading-relaxed text-blue-100">Use the number and PIN from your exam-day slip. Nobody can open your result without them.</p>
@endsection

@section('content')
<h1 class="text-3xl">See my results</h1>
<p class="mt-2 text-slate-600 dark:text-slate-300">Enter your file number (staff) or application number (interns) and the 6-digit PIN you were given at check-in.</p>

@if (session('status'))<div class="mt-5 rounded-lg border border-green-300 bg-green-50 px-3.5 py-2.5 text-sm text-green-900 dark:border-green-700 dark:bg-green-950 dark:text-green-200" role="status">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="alert-error" role="alert">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('myresults.pin') }}" id="pin-form" autocomplete="off">
    @csrf
    <label for="exam_number" class="field-label">File number or application number</label>
    <input id="exam_number" name="exam_number" value="{{ old('exam_number') }}" required autofocus maxlength="30" autocomplete="off" autocapitalize="characters" spellcheck="false" class="field">

    <label for="pin" class="field-label">6-digit PIN</label>
    <div class="flex gap-2">
        <input id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="off" class="field font-display text-2xl font-bold tracking-[0.35em]">
        <button type="button" id="toggle-pin" aria-pressed="false" class="btn btn-outline shrink-0 px-4">Show</button>
    </div>

    <button type="submit" id="pin-submit" class="btn btn-primary mt-6 w-full py-3">Show my results</button>
</form>

<div class="mt-8 space-y-2 border-t border-slate-200 pt-5 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
    <p>Results appear only after the exam office releases them.</p>
    <p><b>Lost your PIN?</b> Staff can <a href="{{ route('myresults.email') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">get a code by email</a>. Interns can <a href="{{ route('intern.login') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">sign in to their application</a>.</p>
</div>

<script>
    const pin = document.getElementById('pin');
    pin.addEventListener('input', () => { pin.value = pin.value.replace(/\D/g, '').slice(0, 6); });
    document.getElementById('toggle-pin').addEventListener('click', e => {
        const show = pin.type === 'password';
        pin.type = show ? 'text' : 'password';
        e.target.textContent = show ? 'Hide' : 'Show';
        e.target.setAttribute('aria-pressed', show);
    });
    document.getElementById('pin-form').addEventListener('submit', () => {
        const b = document.getElementById('pin-submit'); b.disabled = true; b.textContent = 'Checking…';
    });
</script>
@endsection
