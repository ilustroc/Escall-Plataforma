<nav class="integration-nav" aria-label="Tipos de integración">
    <a href="{{ route('cargas.asignacion') }}" @class(['is-active' => request()->routeIs('cargas.asignacion*')])>Subir Asignación</a>
    <a href="{{ route('cargas.pagos') }}" @class(['is-active' => request()->routeIs('cargas.pagos*')])>Subir Pagos</a>
    <a href="{{ route('cargas.ccd') }}" @class(['is-active' => request()->routeIs('cargas.ccd')])>Subir CCD</a>
    <a href="{{ route('cargas.data.pending') }}" @class(['is-active' => request()->routeIs('cargas.data.pending')])>Subir Data</a>
</nav>
