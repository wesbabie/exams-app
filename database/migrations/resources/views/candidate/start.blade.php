@extends('layouts.candidate')

@section('title', 'Welcome')

@section('content')
<div class="card">
  <h1>Welcome, {{ $registration->candidate->full_name }}</h1>
  <p class="muted">You are signed in. The exam instructions will appear here next.</p>

  <div style="margin-top:12px">
    <div class="row"><span class="muted">Exam</span><b>{{ $registration->exam->title }}</b></div>
    <div class="row"><span class="muted">Sitting</span><b>{{ $registration->examSession->name }}</b></div>
    @if ($registration->examSession->venue)
      <div class="row"><span class="muted">Venue</span><b>{{ $registration->examSession->venue }}</b></div>
    @endif
    <div class="row"><span class="muted">Closes</span><b>{{ $registration->examSession->ends_at->format('D j M Y, g:i A') }}</b></div>
  </div>

  <form method="POST" action="{{ route('candidate.logout') }}">
    @csrf
    <button type="submit" class="btn ghost" style="padding:13px 22px">Sign out</button>
  </form>
</div>
@endsection
