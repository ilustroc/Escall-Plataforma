@extends('layouts.app')
@section('title', 'Subir pagos · Plataforma ESCALL')
@push('scripts') @vite('resources/js/imports.js') @endpush
@section('content')
<div class="integration-heading"><p class="dashboard-eyebrow">Integración</p><h1>Carga de pagos</h1><p>Valida códigos, fechas e importes antes de registrar los pagos.</p></div>
@include('cargas.components.nav')
@include('cargas.components.upload-form', ['type' => 'pagos', 'title' => 'Archivo de pagos', 'description' => 'Los pagos conservan la clave de idempotencia existente: código, fecha, monto y operación.', 'templateUrl' => route('cargas.plantilla', ['tipo' => 'pagos']), 'uploadUrl' => route('cargas.pagos.store'), 'initialImportId' => session('import_id')])
@include('cargas.components.history', ['imports' => $imports])
@endsection
