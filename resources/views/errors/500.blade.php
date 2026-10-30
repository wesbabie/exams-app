@extends('errors::layout')

@section('code', '500')
@section('heading', 'Something went wrong on our side')
@section('message')
<p>It is not your fault, and nothing you entered has been lost if you were taking an exam. Wait a moment and reload. If it keeps happening, tell an invigilator or the IT team.</p>
@endsection
@section('actions')
<a href="javascript:location.reload()" class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Reload</a>
@endsection
