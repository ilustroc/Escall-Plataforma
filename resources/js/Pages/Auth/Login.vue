<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon, ArrowRightIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';
import FormField from '../../Components/FormField.vue';
const form = useForm({ usuario: '', contrasena: '' });
const visible = ref(false);
function login() { form.post('/login', { onFinish: () => form.reset('contrasena') }); }
</script>
<template>
    <Head title="Iniciar sesión" />
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="relative hidden flex-col justify-between overflow-hidden bg-escall-950 p-14 text-white lg:flex">
            <div class="relative z-10 flex items-center gap-3"><span class="flex h-12 w-12 items-center justify-center rounded-xl bg-escall-500 text-2xl font-black">E</span><span class="text-2xl font-bold tracking-wider">ESCALL <span class="font-normal text-slate-400">PERÚ</span></span></div>
            <div class="relative z-10 max-w-lg"><p class="mb-5 text-xs font-bold uppercase tracking-[.25em] text-blue-300">Plataforma de gestión</p><h1 class="text-5xl font-bold leading-tight tracking-tight">Cada acuerdo.<br />Un nuevo comienzo.</h1><p class="mt-6 max-w-md text-base leading-relaxed text-slate-300">Consulta tu cartera, organiza los seguimientos y gestiona propuestas y documentos desde un mismo lugar.</p><div class="mt-9 flex items-center gap-2 text-sm text-slate-400"><ShieldCheckIcon class="h-5 w-5 text-blue-300" />Acceso exclusivo para el equipo ESCALL</div></div>
            <p class="relative z-10 text-xs text-slate-500">ESCALL Perú · Plataforma interna</p>
            <div aria-hidden="true" class="absolute -bottom-40 -right-40 h-[550px] w-[550px] rounded-full border-[80px] border-blue-500/10" />
        </section>
        <section class="flex items-center justify-center p-6 sm:p-12"><div class="w-full max-w-sm"><div class="mb-10 text-lg font-bold tracking-wider text-escall-500 lg:hidden">ESCALL PERÚ</div><p class="text-xs font-bold uppercase tracking-[.2em] text-escall-500">Bienvenido a Plataforma</p><h2 class="mt-3 text-3xl font-bold tracking-tight text-escall-950">Inicia sesión</h2><p class="mt-3 text-sm text-slate-500">Ingresa tus credenciales para continuar.</p>
            <form class="mt-8 space-y-5" @submit.prevent="login">
                <FormField id="usuario" label="Usuario o correo electrónico" :error="form.errors.usuario" required><input id="usuario" v-model="form.usuario" name="usuario" type="text" class="field" autocomplete="username" autofocus required maxlength="255" :aria-invalid="!!form.errors.usuario" /></FormField>
                <FormField id="contrasena" label="Contraseña" :error="form.errors.contrasena" required><div class="relative"><input id="contrasena" v-model="form.contrasena" name="contrasena" :type="visible ? 'text' : 'password'" class="field pr-12" autocomplete="current-password" required maxlength="512" :aria-invalid="!!form.errors.contrasena" /><button class="absolute right-2 top-1.5 rounded-md p-2 text-slate-400" type="button" :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" @click="visible = !visible"><component :is="visible ? EyeSlashIcon : EyeIcon" class="h-5 w-5" /></button></div></FormField>
                <p v-if="form.errors.message" role="alert" class="error">{{ form.errors.message }}</p>
                <button class="btn-primary w-full" :disabled="form.processing">{{ form.processing ? 'Iniciando sesión…' : 'Ingresar a la plataforma' }}<ArrowRightIcon class="h-4 w-4" /></button>
            </form>
            <p class="mt-8 text-center text-xs leading-relaxed text-slate-400">Si necesitas ayuda con tu acceso,<br />contacta al administrador de tu equipo.</p>
        </div></section>
    </main>
</template>
