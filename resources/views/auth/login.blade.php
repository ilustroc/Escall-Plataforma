@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
<main class="login-shell">
    <div class="login-ambient" aria-hidden="true">
        <span class="login-flow login-flow--horizontal login-flow--left-right login-flow--top"></span>
        <span class="login-flow login-flow--horizontal login-flow--right-left login-flow--upper"></span>
        <span class="login-flow login-flow--horizontal login-flow--left-right login-flow--bottom"></span>
        <span class="login-flow login-flow--horizontal login-flow--right-left login-flow--lower"></span>
        <span class="login-flow login-flow--vertical login-flow--top-bottom login-flow--left"></span>
        <span class="login-flow login-flow--vertical login-flow--bottom-top login-flow--right"></span>
        <span class="login-flow login-flow--vertical login-flow--top-bottom login-flow--center-left"></span>
        <span class="login-flow login-flow--vertical login-flow--bottom-top login-flow--center-right"></span>
    </div>

    <section class="relative z-10 flex w-full justify-center py-8 sm:py-12" aria-labelledby="login-title">
        <div class="login-card motion-safe:animate-[login-enter_420ms_ease-out_both]">
            <header class="text-center">
                <img src="{{ asset('assets/img/logo.png') }}" alt="ESCALL Perú" class="mx-auto h-auto w-[172px] max-w-full" width="500" height="176">
                <p class="mt-8 text-xs font-bold uppercase tracking-[0.18em] text-[#155EEF]">Acceso seguro</p>
                <h1 id="login-title" class="mt-3 text-3xl font-semibold tracking-tight text-[#0F172A] sm:text-[2rem]">Bienvenido de nuevo</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">Ingresa tus credenciales para acceder a la plataforma</p>
            </header>

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5" novalidate>
                @csrf

                <div>
                    <label for="usuario" class="mb-2 block text-sm font-medium text-[#0F172A]">Usuario</label>
                    <input id="usuario" name="usuario" type="text" value="{{ old('usuario') }}" autocomplete="username" autofocus required maxlength="190" @error('usuario') aria-invalid="true" aria-describedby="usuario-error" @enderror class="login-input @error('usuario') border-red-400 focus:border-red-500 focus:ring-red-500/15 @enderror">
                    @error('usuario')
                        <p id="usuario-error" class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="contrasena" class="mb-2 block text-sm font-medium text-[#0F172A]">Contraseña</label>
                    <div class="relative">
                        <input id="contrasena" name="contrasena" type="password" autocomplete="current-password" required maxlength="72" @error('contrasena') aria-invalid="true" aria-describedby="contrasena-error" @enderror data-password-input class="login-input pr-20 @error('contrasena') border-red-400 focus:border-red-500 focus:ring-red-500/15 @enderror">
                        <button type="button" data-password-toggle aria-label="Mostrar contraseña" aria-pressed="false" class="absolute inset-y-1 right-1 rounded-md px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-[#0F172A] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#155EEF]">
                            <span data-password-toggle-label>Mostrar</span>
                        </button>
                    </div>
                    @error('contrasena')
                        <p id="contrasena-error" class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="login-submit">Iniciar sesión</button>
            </form>

            <p class="mt-8 border-t border-slate-200 pt-5 text-center text-xs leading-5 text-slate-500">Acceso exclusivo para el personal autorizado de ESCALL PERÚ</p>
        </div>
    </section>
</main>
@endsection
