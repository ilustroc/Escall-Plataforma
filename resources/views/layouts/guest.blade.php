<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" href="{{ asset('assets/img/logo-tipo.png') }}">
    <title>@yield('title', 'Acceso') · Plataforma ESCALL</title>
    @vite(['resources/css/app.css', 'resources/js/login.js'])
</head>
<body class="min-w-[320px] bg-white font-sans text-slate-900 antialiased">
    @yield('content')
</body>
</html>
