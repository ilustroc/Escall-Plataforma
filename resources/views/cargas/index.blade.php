@extends('layouts.app')
@section('title', 'Integración · Plataforma ESCALL')
@section('content')
<div class="integration-heading"><p class="dashboard-eyebrow">Operación</p><h1>Integración</h1><p>Administra las importaciones de información de la plataforma.</p></div>
@include('cargas.components.nav')
<section class="integration-choice-grid"><a href="{{ route('cargas.asignacion') }}"><strong>Subir Asignación</strong><span>Importa la cartera mensual con histórico por FEC_PRO.</span></a><a href="{{ route('cargas.pagos') }}"><strong>Subir Pagos</strong><span>Procesa pagos de forma idempotente y segura.</span></a></section>
@include('cargas.components.history', ['imports' => $imports])
@endsection
