@extends('layouts.app')

@push('styles')
    @vite([
        'resources/css/checkbox.css',
        'resources/css/addPassword.css',
        'resources/js/generator.js',
        'resources/css/header_logged.css',
        'resources/css/footer_logged.css'
    ])
@endpush

@section('title','Lock In - Generator')

@section('header')
    <x-header_logged/>
@endsection

@section('content')
    <main class="main">
        <section class="hero">
            <h1>PASSWORD<br>GENERATOR</h1>
        </section>

        <x-generator.output/>

        <section class="grid">
            <div class="left">
                <x-generator.entropyLevel/>
                <x-generator.charSets/>
                <x-generator.save/>
            </div>
            <x-generator.analysis/>
        </section>
    </main>
@endsection

@section('footer')
    <x-footer/>
@endsection


