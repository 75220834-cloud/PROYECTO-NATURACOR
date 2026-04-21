@extends('layouts.app')

@section('title', 'Perfil')
@section('page-title', 'Mi Perfil')

@section('content')
<div class="py-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="nc-card">
                <div class="nc-card-header">Información del Perfil</div>
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="col-12">
            <div class="nc-card">
                <div class="nc-card-header">Actualizar Contraseña</div>
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="col-12">
            <div class="nc-card">
                <div class="nc-card-header">Eliminar Cuenta</div>
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection
