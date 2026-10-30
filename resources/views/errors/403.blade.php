@extends('errors::layout')

@php
    $text = trim((string) ($exception?->getMessage() ?? ''));
    $generic = in_array($text, ['', 'This action is unauthorized.', 'Forbidden'], true);
@endphp

@section('code', '403')
@section('heading', 'You can’t open this page')
@section('message')
    <p>{{ $generic ? 'Your account does not have access to this page. If you think it should, ask an exam officer or the administrator.' : $text }}</p>
@endsection
@section('actions')
    <a href="{{ url('/') }}" class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Go to the start page</a>
@endsection
