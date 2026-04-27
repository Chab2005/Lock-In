@extends('layouts.app')

@push('styles')
    @vite([
        'resources/css/dashboard.css',
        'resources/css/header_logged.css',
        'resources/css/footer_logged.css'
    ])
@endpush

@section('title','Lock In - Dashboard')

@section('header')
    <x-header_logged/>
@endsection

@section('content')
        <x-dashboard.hero/>

        <x-dashboard.sectionHeader/>

        <section class="grid">
            <x-dashboard.cardPassword/>
            <x-dashboard.cardPassword/>
            <x-dashboard.cardPassword/>
            <x-dashboard.cardPassword/>
        </section>
@endsection

@section('footer')
    <x-footer/>
@endsection

