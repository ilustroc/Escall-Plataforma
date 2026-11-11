@extends('layouts.app')

@section('title', 'Inicio · Plataforma ESCALL')

@section('content')
<div class="dashboard-heading">
    <div>
        <p class="dashboard-eyebrow">Operación</p>
        <h1>Resumen de cobranzas</h1>
        <p>Consulta clientes desde el buscador superior y revisa la actividad reciente.</p>
    </div>
</div>

<section class="dashboard-grid" aria-label="Resumen del dashboard">
    <article class="generation-card">
        <span class="generation-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M8 13h8M8 17h6" /></svg>
        </span>
        <div>
            <p class="dashboard-card-label">Generaciones realizadas</p>
            <p class="generation-total">{{ number_format($generationStats['total']) }}</p>
            <p class="generation-summary">{{ number_format($generationStats['documents']) }} documentos · {{ number_format($generationStats['proposals']) }} propuestas</p>
        </div>
    </article>

    <section class="recent-payments-card" aria-labelledby="recent-payments-title">
        <header class="dashboard-card-header">
            <div>
                <h2 id="recent-payments-title">Actividad reciente</h2>
                <p>Últimos pagos registrados</p>
            </div>
            <span class="recent-payments-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M3 10h18M7 15h2m4 0h4M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" /></svg>
            </span>
        </header>

        @if (count($recentPayments))
            <div class="recent-payment-list">
                @foreach ($recentPayments as $payment)
                    <article class="recent-payment">
                        <div class="recent-payment-copy">
                            <p class="recent-payment-label">Pago registrado</p>
                            <p class="recent-payment-name">{{ $payment->cliente_titular ?: 'Cuenta histórica' }}</p>
                            <p class="recent-payment-meta">
                                @if ($payment->cliente_dni) Documento: {{ $payment->cliente_dni }} · @endif
                                Cuenta: {{ $payment->codigo }}@if ($payment->cliente_cartera) · {{ $payment->cliente_cartera }}@endif
                            </p>
                        </div>
                        <div class="recent-payment-amount">
                            <time datetime="{{ $payment->fecha }}">{{ \Illuminate\Support\Carbon::parse($payment->fecha)->format('d/m/Y') }}</time>
                            <strong>S/ {{ number_format((float) $payment->monto, 2) }}</strong>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="recent-payments-empty">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10h18M7 15h2m4 0h4M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" /></svg>
                <div><p>Sin pagos recientes</p><span>Los pagos registrados aparecerán aquí.</span></div>
            </div>
        @endif
    </section>
</section>
@endsection
