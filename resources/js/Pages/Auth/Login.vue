<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon, ArrowRightIcon, ShieldCheckIcon, ChartBarSquareIcon, UserGroupIcon, DocumentTextIcon, LockClosedIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';
import FormField from '../../Components/FormField.vue';

const form = useForm({ usuario: '', contrasena: '' });
const visible = ref(false);
function login() { form.post('/login', { onFinish: () => form.reset('contrasena') }); }
</script>

<template>

  <Head title="Iniciar sesión" />
  <main class="min-h-screen bg-slate-100 lg:grid lg:grid-cols-[minmax(0,1.08fr)_minmax(440px,.92fr)]">
    <section
      class="relative hidden overflow-hidden bg-escall-950 px-10 py-9 text-white lg:flex lg:flex-col xl:px-16 xl:py-12">
      <div aria-hidden="true" class="absolute inset-0 opacity-30"
        style="background-image:linear-gradient(rgba(96,165,250,.12) 1px,transparent 1px),linear-gradient(90deg,rgba(96,165,250,.12) 1px,transparent 1px);background-size:42px 42px" />
      <div aria-hidden="true" class="absolute -right-20 top-24 h-72 w-72 rounded-full border border-blue-300/15" />
      <div aria-hidden="true" class="absolute -right-8 top-40 h-52 w-52 rounded-full border border-blue-300/10" />
      <div class="relative z-10 flex items-center justify-between"><img :src="'/assets/img/logo.png'" alt="ESCALL Perú"
          class="h-12 w-auto" /></div>

      <div class="relative z-10 my-auto max-w-xl pt-12">
        <p class="text-xs font-bold uppercase tracking-[.22em] text-blue-200">ESCALL PERÚ · MÁS QUE UN CALL</p>
        <div class="mt-8 grid max-w-xl grid-cols-3 gap-3">
          <div class="border border-white/10 bg-white/[.045] p-3">
            <ChartBarSquareIcon class="h-5 w-5 text-blue-200" />
            <p class="mt-4 text-sm font-semibold">Gestión de cartera</p>
            <p class="mt-1 text-xs leading-5 text-slate-400">Consulta y prioriza cuentas.</p>
          </div>
          <div class="border border-white/10 bg-white/[.045] p-3">
            <UserGroupIcon class="h-5 w-5 text-blue-200" />
            <p class="mt-4 text-sm font-semibold">Seguimiento de clientes</p>
            <p class="mt-1 text-xs leading-5 text-slate-400">Mantén cada gestión visible.</p>
          </div>
          <div class="border border-white/10 bg-white/[.045] p-3">
            <DocumentTextIcon class="h-5 w-5 text-blue-200" />
            <p class="mt-4 text-sm font-semibold">Reportes y documentos</p>
            <p class="mt-1 text-xs leading-5 text-slate-400">Acceso privado y ordenado.</p>
          </div>
        </div>
      </div>

      <div class="relative z-10 mt-8 max-w-xl border border-white/10 bg-slate-950/30 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Operación de cobranza</p>
            <p class="mt-1 text-sm font-medium">Una vista para la gestión diaria</p>
          </div><span
            class="flex h-8 w-8 items-center justify-center rounded-full border border-blue-300/20 bg-blue-400/10">
            <ChartBarSquareIcon class="h-4 w-4 text-blue-200" />
          </span>
        </div><svg class="mt-4 h-14 w-full" viewBox="0 0 360 56" fill="none" aria-hidden="true">
          <path
            d="M0 47C35 43 43 31 70 35C96 39 110 19 142 25C170 30 179 12 211 17C240 22 253 7 280 12C311 18 325 5 360 8"
            stroke="#60A5FA" stroke-width="2" />
          <path
            d="M0 56V47C35 43 43 31 70 35C96 39 110 19 142 25C170 30 179 12 211 17C240 22 253 7 280 12C311 18 325 5 360 8V56H0Z"
            fill="url(#area)" />
          <defs>
            <linearGradient id="area" x1="180" y1="0" x2="180" y2="56" gradientUnits="userSpaceOnUse">
              <stop stop-color="#3B82F6" stop-opacity=".2" />
              <stop offset="1" stop-color="#3B82F6" stop-opacity="0" />
            </linearGradient>
          </defs>
        </svg>
      </div>
      <div class="relative z-10 mt-5 flex items-center gap-2 text-xs text-slate-400">
        <ShieldCheckIcon class="h-4 w-4 text-blue-200" />Acceso seguro exclusivo para el equipo ESCALL
      </div>
    </section>

    <section class="flex min-h-screen items-center justify-center bg-white px-5 py-10 sm:px-10 lg:px-14 xl:px-20">
      <div class="w-full max-w-md"><img :src="'/assets/img/logo.png'" alt="ESCALL Perú"
          class="mb-10 h-11 w-auto lg:hidden" />
        <div class="mb-8">
          <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-md bg-escall-50 text-escall-600">
            <LockClosedIcon class="h-5 w-5" />
          </div>
          <p class="text-xs font-bold uppercase tracking-[.2em] text-escall-600">Acceso seguro</p>
          <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Bienvenido de nuevo</h2>
          <p class="mt-2 text-sm leading-6 text-slate-500">Ingresa a tu cuenta para continuar.</p>
        </div>
        <form class="space-y-5" @submit.prevent="login">
          <FormField id="usuario" label="Usuario o correo electrónico" :error="form.errors.usuario" required><input
              id="usuario" v-model="form.usuario" name="usuario" type="text"
              class="field h-11 border-slate-300 transition hover:border-slate-400 focus:border-escall-500"
              autocomplete="username" autofocus required maxlength="255" :aria-invalid="!!form.errors.usuario" />
          </FormField>
          <FormField id="contrasena" label="Contraseña" :error="form.errors.contrasena" required>
            <div class="relative"><input id="contrasena" v-model="form.contrasena" name="contrasena"
                :type="visible ? 'text' : 'password'"
                class="field h-11 border-slate-300 pr-12 transition hover:border-slate-400 focus:border-escall-500"
                autocomplete="current-password" required maxlength="512"
                :aria-invalid="!!form.errors.contrasena" /><button
                class="absolute right-1 top-1 flex h-9 w-9 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                type="button" :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                @click="visible = !visible">
                <component :is="visible ? EyeSlashIcon : EyeIcon" class="h-5 w-5" />
              </button></div>
          </FormField>
          <p v-if="form.errors.message" role="alert"
            class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ form.errors.message }}
          </p>
          <button class="btn-primary mt-2 h-11 w-full text-sm shadow-sm transition hover:shadow-md"
            :disabled="form.processing">{{ form.processing ? 'Ingresando…' : 'Ingresar a la plataforma' }}
            <ArrowRightIcon class="h-4 w-4" />
          </button>
        </form>
        <div class="mt-8 border-t border-slate-200 pt-5">
          <p class="text-center text-xs leading-5 text-slate-500">¿Necesitas ayuda con tu acceso?<br /><span
              class="font-medium text-slate-700">Contacta al administrador de tu equipo.</span></p>
        </div>
      </div>
    </section>
  </main>
</template>
