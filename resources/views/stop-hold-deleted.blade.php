
@extends('layouts.app')

@section('content')
<div class="container text-center mt-5">
    <div style="font-size: 4rem; color: green;">✔</div>
    <h2>{{ $status }}</h2>
    <p>The Stop/Hold record has been successfully deleted.</p>
    <a href="{{ route('stopHoldInq.index') }}" class="btn btnv>
@endsection
