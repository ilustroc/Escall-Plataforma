@extends('layouts.app')
@section('title', 'Subir asignación · Plataforma ESCALL')
@push('scripts') @vite('resources/js/imports.js') @endpush
@section('content')
<div class="integration-heading"><p class="dashboard-eyebrow">Integración</p><h1>Carga de asignación mensual</h1><p>Valida el archivo completo antes de actualizar la cartera y conserva las versiones históricas.</p></div>
@include('cargas.components.nav')
@include('cargas.components.upload-form', ['type' => 'asignacion', 'title' => 'Archivo de cartera mensual', 'description' => 'La identidad de cada versión es CODIGO + FEC_PRO. Las versiones anteriores no se eliminan.', 'templateUrl' => route('cargas.plantilla', ['tipo' => 'asignacion']), 'uploadUrl' => route('cargas.asignacion.store'), 'initialImportId' => session('import_id')])
@include('cargas.components.history', ['imports' => $imports])
@endsection
