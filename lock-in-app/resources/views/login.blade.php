@extends('layouts.auth')

@push('styles')
    @vite([
        'resources/css/root.css',
        'resources/css/login.css'
    ])
@endpush

@section('title','LOCK IN - Login')

@section('header')
    <x-auth.header/>
@endsection

@section('content')
        <x-auth.login/>
@endsection

@section('footer')
    <x-auth.footer/>
@endsection
