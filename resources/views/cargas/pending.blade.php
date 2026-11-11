@extends('layouts.app')
@section('title', 'Subir '.$kind.' · Plataforma ESCALL')
@section('content')
<div class="integration-heading"><p class="dashboard-eyebrow">Integración</p><h1>Subir {{ $kind }}</h1><p>Administra las importaciones de información de la plataforma.</p></div>
@include('cargas.components.nav')
<section class="integration-card integration-pending"><h2>Formato pendiente de configuración</h2><p>El repositorio no contiene un contrato verificable, columnas ni destino de persistencia para {{ $kind }}. Esta pantalla no permite guardar datos hasta que esas reglas sean definidas y aprobadas.</p></section>
@endsection
