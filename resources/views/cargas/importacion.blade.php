@extends('layouts.app')
@section('title', 'Detalle de importación · Plataforma ESCALL')
@push('scripts') @vite('resources/js/imports.js') @endpush
@section('content')
<div class="integration-heading"><p class="dashboard-eyebrow">Integración</p><h1>Detalle de importación</h1><p>{{ $import->nombre_archivo }}</p></div>
@include('cargas.components.nav')
<section class="integration-card" data-import-root data-import-id="{{ $import->id }}" data-status-url="{{ route('cargas.importaciones.estado', $import) }}"><dl class="import-detail-grid"><div><dt>Estado</dt><dd>{{ ucfirst($import->estado) }}</dd></div><div><dt>Tipo</dt><dd>{{ ucfirst($import->tipo) }}</dd></div><div><dt>Periodo</dt><dd>{{ $import->periodo ?? '—' }}</dd></div><div><dt>Archivo</dt><dd>{{ $import->nombre_archivo }}</dd></div><div><dt>Filas</dt><dd>{{ $import->filas_procesadas }} / {{ $import->total_filas ?? '—' }}</dd></div><div><dt>Resultado</dt><dd>{{ $import->insertadas }} insertadas · {{ $import->actualizadas }} actualizadas · {{ $import->fallidas }} fallidas</dd></div></dl>@if($import->errores_count)<a class="btn-secondary" href="{{ route('cargas.importaciones.errores', $import) }}">Descargar errores</a>@endif</section>
@endsection
