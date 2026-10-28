@extends('layouts.staff')

@section('title', 'Temporary password')

@section('content')
<div class="mx-auto max-w-lg panel p-6">
    <span class="inline-block rounded-full bg-green-100 px-3 py-0.5 text-sm font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">{{ $credentials['context'] === 'created' ? 'Account created' : 'Password reset' }}</span>
    <h1 class="mt-3 text-2xl font-bold">{{ $credentials['name'] }}</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400">Give these details to the person in private. They sign in at the staff login and are asked to choose their own password straight away.</p>

    <dl class="mt-5 space-y-3">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</dt>
            <dd class="font-mono text-lg">{{ $credentials['email'] }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Temporary password</dt>
            <dd class="flex items-center gap-3">
                <span id="temp" class="rounded-lg bg-slate-100 px-3 py-2 font-mono text-2xl font-bold tracking-wider dark:bg-slate-900">{{ $credentials['password'] }}</span>
                <button type="button" id="copy" class="rounded-lg border border-blue-700 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-slate-700">Copy</button>
            </dd>
        </div>
    </dl>

    <p class="mt-5 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">This password is shown <b>only once</b>. If you lose it, use "Reset password" to issue a new one.</p>

    <a href="{{ route('admin.staff.index') }}" class="mt-5 inline-block rounded-lg bg-blue-700 px-6 py-2.5 font-semibold text-white hover:bg-blue-800">Done</a>
</div>

<script>
    document.getElementById('copy').addEventListener('click', e => {
        navigator.clipboard?.writeText(document.getElementById('temp').textContent.trim());
        e.target.textContent = 'Copied';
    });
</script>
@endsection
