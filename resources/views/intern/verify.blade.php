@extends('layouts.intern')

@section('title', 'Verify your email')

@section('content')
<div class="panel p-6">
    <h1 class="text-2xl font-bold">Verify your email</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Enter the 6-digit code we sent to <b>{{ $applicant->email }}</b>. It expires in {{ \App\Models\Applicant::OTP_MINUTES }} minutes.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-300 bg-red-50 px-3.5 py-2.5 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('intern.verify.store') }}" class="mt-2">
        @csrf
        <label for="code" class="mb-1.5 mt-4 block font-semibold">Verification code</label>
        <input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code"
               class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-xl tracking-[0.3em] focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-slate-600 dark:bg-slate-900">
        <button type="submit" class="mt-5 w-full rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Verify</button>
    </form>

    <form method="POST" action="{{ route('intern.verify.resend') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-400">Send me a new code</button>
    </form>
</div>
@endsection
