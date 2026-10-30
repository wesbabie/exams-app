@extends('errors::layout')

@section('code', '429')
@section('heading', 'Too many tries')
@section('message')
<p>Please wait a minute, then try again.</p>
@endsection
@section('actions')
<a href="javascript:history.back()" class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Go back</a>
@endsection
