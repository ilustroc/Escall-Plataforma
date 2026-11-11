<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('assets/img/logo-tipo.png') }}">
    <title>@yield('title', 'Plataforma ESCALL')</title>
    @vite('resources/js/dashboard.js')
</head>
<body class="bg-[#F1F5F9] font-sans text-[#0F172A] antialiased">
    <a href="#main-content" class="sr-only z-[90] bg-white p-3 focus:not-sr-only focus:fixed">Saltar al contenido</a>
    <div class="admin-shell" data-admin-shell>
        <x-sidebar :user="auth()->user()" />
        <div class="admin-content">
            <x-topbar :user="auth()->user()" />
            <main id="main-content" class="admin-main">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
