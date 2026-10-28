@extends('layouts.guest')

@section('title', 'Candidate sign-in')

@section('content')
<h1 class="text-3xl">Exam sign-in</h1>
<p class="mt-2 text-slate-600 dark:text-slate-300">Enter your file number (staff) or application number (interns), and the 6-digit PIN you were given at check-in.</p>

@if ($errors->any())
    <div class="alert-error" role="alert">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('candidate.login.store') }}" id="login-form" autocomplete="off">
    @csrf

    <label for="exam_number" class="field-label">File number or application number</label>
    <input id="exam_number" name="exam_number" type="text" value="{{ old('exam_number') }}"
           required autofocus maxlength="30" autocomplete="off" autocapitalize="characters" spellcheck="false" class="field">

    <label for="pin" class="field-label">6-digit PIN</label>
    <div class="flex gap-2">
        <input id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="off"
               class="field font-display text-2xl font-bold tracking-[0.35em]">
        <button type="button" id="toggle-pin" aria-pressed="false" class="btn btn-outline shrink-0 px-4">Show</button>
    </div>

    <button type="submit" id="submit-btn" class="btn btn-primary mt-6 w-full py-3">Sign in</button>
</form>

<p class="mt-8 border-t border-slate-200 pt-5 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">No PIN yet? An invigilator gives you one when you check in at the venue.</p>

<script>
    const pin = document.getElementById('pin');
    pin.addEventListener('input', () => { pin.value = pin.value.replace(/\D/g, '').slice(0, 6); });
    document.getElementById('toggle-pin').addEventListener('click', e => {
        const show = pin.type === 'password';
        pin.type = show ? 'text' : 'password';
        e.target.textContent = show ? 'Hide' : 'Show';
        e.target.setAttribute('aria-pressed', show);
    });
    document.getElementById('login-form').addEventListener('submit', () => {
        const b = document.getElementById('submit-btn'); b.disabled = true; b.textContent = 'Signing in…';
    });
</script>
@endsection
