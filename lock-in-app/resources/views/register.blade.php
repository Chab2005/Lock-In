@extends('layouts.auth')

@push('styles')
    @vite([
        'resources/css/root.css',
        'resources/css/login.css'
    ])
@endpush

@section('title','LOCK IN - Register')

@section('header')
    <x-auth.header/>
@endsection

@section('content')
    <x-auth.register/>
@endsection

@section('footer')
    <x-auth.footer/>
@endsection
