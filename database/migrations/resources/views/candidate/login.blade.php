@extends('layouts.candidate')

@section('title', 'Candidate sign-in')

@section('content')
<div class="card">
  <h1>Candidate sign-in</h1>
  <p class="muted">Enter your file number (staff) or application number (interns), and the 6-digit PIN you were given at check-in.</p>

  @if ($errors->any())
    <div class="alert" role="alert">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('candidate.login.store') }}" id="login-form" autocomplete="off">
    @csrf

    <label for="exam_number">File number or application number</label>
    <input id="exam_number" name="exam_number" type="text" value="{{ old('exam_number') }}"
           required autofocus maxlength="30" autocomplete="off" autocapitalize="characters" spellcheck="false">

    <label for="pin">6-digit PIN</label>
    <div class="pin-row">
      <input id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{6}"
             maxlength="6" required autocomplete="off">
      <button type="button" class="ghost" id="toggle-pin" aria-pressed="false">Show</button>
    </div>

    <button type="submit" class="btn" id="submit-btn">Sign in</button>
  </form>

  <p class="note" style="margin-top:16px">No PIN yet? An invigilator will give you one when you check in at the venue.</p>
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
