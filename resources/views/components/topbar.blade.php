@props(['user'])
@php
    $initials = collect(preg_split('/\s+/', trim($user->nombre ?: 'E')))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
@endphp

<header class="app-topbar">
    <div class="topbar-start">
        <button type="button" class="topbar-icon-button mobile-menu-button" data-mobile-menu-toggle aria-label="Abrir menú" aria-expanded="false">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <button type="button" class="topbar-icon-button desktop-menu-button" data-sidebar-toggle aria-label="Contraer menú" aria-expanded="true">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
    </div>

    <x-client-search />

    <div class="topbar-user">
        <x-notifications />
        <div class="topbar-user-details">
            <p>{{ $user->nombre }}</p>
            <span>{{ $user->rol }}</span>
        </div>
        <span class="user-avatar" aria-label="{{ $user->nombre }}">{{ $initials }}</span>
    </div>
</header>
