@extends('errors::layout')

@php $inExam = request()->is('exam', 'exam/*'); @endphp

@section('code', '419')
@section('heading', 'This page expired')
@section('message')
    @if ($inExam)
        <p><b>Your answers are safe.</b> The page sat open too long, or you were signed out. Sign in again with your number and PIN and you will continue at the same question. The clock kept running on the server.</p>
        <p class="mt-2 text-sm">If your PIN no longer works, tell an invigilator.</p>
    @else
        <p>The page sat open for too long, so for your safety it was closed. Go back, refresh the page and try again.</p>
    @endif
@endsection
@section('actions')
    @if ($inExam)
        <a href="{{ url('/exam/login') }}" class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Sign in again</a>
    @else
        <a href="{{ url()->previous() ?: url('/') }}" class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Go back</a>
        <a href="{{ url('/') }}" class="inline-flex items-center rounded-lg border border-slate-400 px-5 py-2.5 font-semibold hover:bg-slate-100 dark:hover:bg-slate-700">Start page</a>
    @endif
@endsection
