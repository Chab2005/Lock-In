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

    {{-- Icon picker modal --}}
    <x-ui.modal id="icon-picker" title="CHOOSE ICON">
        <div class="icon-picker-grid">
            @foreach([
                ['lock', 'Default'],
                ['language', 'Website'],
                ['mail', 'Email'],
                ['smartphone', 'App'],
                ['credit_card', 'Finance'],
                ['shopping_cart', 'Shop'],
                ['work', 'Work'],
                ['school', 'School'],
                ['cloud', 'Cloud'],
                ['sports_esports', 'Gaming'],
                ['person', 'Social'],
                ['key', 'Other'],
            ] as [$iconName, $iconLabel])
                <button class="icon-option {{ $iconName === 'lock' ? 'selected' : '' }}" data-icon="{{ $iconName }}" type="button">
                    <span class="material-symbols-outlined">{{ $iconName }}</span>
                    <small>{{ $iconLabel }}</small>
                </button>
            @endforeach
        </div>
    </x-ui.modal>
@endsection

@section('footer')
    <x-footer/>
@endsection


