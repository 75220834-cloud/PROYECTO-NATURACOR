@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="py-4">
    <div class="nc-card">
        <div class="p-4">
            {{ __("You're logged in!") }}
        </div>
    </div>
</div>
@endsection
