@extends('errors::layout')

@section('code', '503')
@section('heading', 'Back in a few minutes')
@section('message')
<p>ExamSphere is being updated. Please try again shortly.</p>
@endsection
@section('actions')
<a href="javascript:location.reload()" class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Try again</a>
@endsection
