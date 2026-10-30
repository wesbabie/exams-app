@extends('errors::layout')

@section('code', '404')
@section('heading', 'We can’t find that page')
@section('message')
<p>The link may be old or mistyped. Nothing has been lost.</p>
@endsection
@section('actions')
<a href="{{ url('/') }}" class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Go to the start page</a>
@endsection
