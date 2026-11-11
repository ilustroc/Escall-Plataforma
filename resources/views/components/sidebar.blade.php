@props(['user'])
@php
    $path = '/'.request()->path();
    $items = [
        ['label' => 'Inicio y clientes', 'href' => route('dashboard'), 'active' => $path === '/inicio' || $path === '/clientes', 'icon' => 'home'],
        ['label' => 'Seguimientos', 'href' => route('seguimientos.index'), 'active' => $path === '/seguimientos', 'icon' => 'calendar'],
        ['label' => 'Reportes', 'href' => route('reportes.index'), 'active' => $path === '/reportes', 'icon' => 'report'],
    ];
    if ($user->isAdmin()) {
        array_splice($items, 2, 0, [['label' => 'Cargas', 'href' => route('cargas.index'), 'active' => str_starts_with($path, '/cargas'), 'icon' => 'upload']]);
        $items[] = ['label' => 'Usuarios', 'href' => route('admin.index'), 'active' => $path === '/admin', 'icon' => 'users'];
    }
@endphp

<div class="sidebar-overlay" data-mobile-overlay hidden></div>
<aside class="app-sidebar" data-sidebar aria-label="Navegación principal">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="sidebar-logo" aria-label="ESCALL Perú - Inicio">
            <img src="{{ asset('assets/img/logo.png') }}" alt="ESCALL Perú" class="sidebar-logo-full">
            <img src="{{ asset('assets/img/logo-tipo.png') }}" alt="" class="sidebar-logo-mark">
        </a>
        <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-label="Contraer menú" aria-expanded="true">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6M20 12H9" /></svg>
        </button>
        <button type="button" class="sidebar-close-mobile" data-mobile-menu-toggle aria-label="Cerrar menú">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
        </button>
    </div>

    <nav class="sidebar-nav">
        <p class="sidebar-section-label">Operación</p>
        @foreach ($items as $item)
            <a href="{{ $item['href'] }}" class="sidebar-link {{ $item['active'] ? 'is-active' : '' }}" data-sidebar-link aria-current="{{ $item['active'] ? 'page' : 'false' }}" data-tooltip="{{ $item['label'] }}">
                @if ($item['icon'] === 'home')
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.8 12 3l9 7.8v9.7a.5.5 0 0 1-.5.5H15v-6H9v6H3.5a.5.5 0 0 1-.5-.5v-9.7Z" /></svg>
                @elseif ($item['icon'] === 'calendar')
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01" /></svg>
                @elseif ($item['icon'] === 'upload')
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15V3m0 0 4 4m-4-4L8 7M4 14v5a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5" /></svg>
                @elseif ($item['icon'] === 'report')
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><path d="M8 16v-4m4 4V8m4 8v-6" /></svg>
                @else
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                @endif
                <span class="sidebar-label">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-link sidebar-logout" data-tooltip="Cerrar sesión">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3m9 9h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-7" /></svg>
                <span class="sidebar-label">Cerrar sesión</span>
            </button>
        </form>
    </div>
</aside>
