@extends('layouts.app')

@section('body')
<main id="main" class="mx-auto flex min-h-screen max-w-xl flex-col justify-center px-4 py-10">
    <x-brand class="mb-6 self-center text-blue-800 dark:text-blue-100" :sub="false" />
    @yield('content')
</main>
@endsection
