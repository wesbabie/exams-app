@extends('layouts.guest')

@section('title', 'Candidate sign-in')

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <h1 class="text-2xl font-bold">Candidate sign-in</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        Enter your file number (staff) or application number (interns), and the 6-digit PIN you were given at check-in.
    </p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-300 bg-red-50 px-3.5 py-2.5 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('candidate.login.store') }}" id="login-form" autocomplete="off" class="mt-2">
        @csrf

        <label for="exam_number" class="mb-1.5 mt-4 block font-semibold">File number or application number</label>
        <input id="exam_number" name="exam_number" type="text" value="{{ old('exam_number') }}"
               required autofocus maxlength="30" autocomplete="off" autocapitalize="characters" spellcheck="false"
               class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900">

        <label for="pin" class="mb-1.5 mt-4 block font-semibold">6-digit PIN</label>
        <div class="flex gap-2">
            <input id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="off"
                   class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-xl tracking-[0.3em] focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900">
            <button type="button" id="toggle-pin" aria-pressed="false"
                    class="rounded-lg border border-blue-700 px-4 font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">Show</button>
        </div>

        <button type="submit" id="submit-btn"
                class="mt-5 w-full rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800 disabled:opacity-60">Sign in</button>
    </form>

    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">No PIN yet? An invigilator will give you one when you check in at the venue.</p>
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
    document.getElementById('login-form').addEventListener('submit', () => {
        const b = document.getElementById('submit-btn'); b.disabled = true; b.textContent = 'Signing in…';
    });
</script>
@endsection
