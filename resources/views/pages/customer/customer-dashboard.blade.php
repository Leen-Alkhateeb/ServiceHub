@extends('layouts.app')
@section('title', 'نظرة عامة')

@section('content')

@include('pages.partials.market-assets')

<div class="profile-layout">
    @include('pages.customer.menu')

    <section class="stack">
        <h2>أهلاً {{ auth()->user()->first_name }}</h2>
        <div class="grid stats">
            @foreach ($stats as $label => $value)
                <div class="card stat"><span class="muted">{{ $label }}</span><strong>{{ $value }}</strong></div>
            @endforeach
        </div>
    </section>
</div>

@endsection