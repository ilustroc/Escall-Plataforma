<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { HomeIcon, FolderArrowDownIcon, ChartBarSquareIcon, UsersIcon, CalendarDaysIcon, Bars3Icon, XMarkIcon, ArrowRightOnRectangleIcon, ChevronRightIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import Alert from '../Components/Alert.vue';
defineProps({ title: String, subtitle: String, breadcrumbs: { type: Array, default: () => [] } });
const page = usePage(), sidebarOpen = ref(false), loading = ref(false), logoutBusy = ref(false);
const user = computed(() => page.props.auth?.user || {});
const initials = computed(() => (user.value.nombre || 'E').split(' ').slice(0, 2).map(value => value.charAt(0)).join('').toUpperCase());
const navigation = computed(() => [
    { label: 'Inicio y clientes', href: '/inicio', icon: HomeIcon, match: ['/inicio','/clientes','/propuestas'] },
    { label: 'Seguimientos', href: '/seguimientos', icon: CalendarDaysIcon },
    { label: 'Cargas', href: '/cargas', icon: FolderArrowDownIcon },
    { label: 'Reportes', href: '/reportes', icon: ChartBarSquareIcon },
    ...(user.value.rol === 'administrador' ? [ { label: 'Usuarios', href: '/admin', icon: UsersIcon } ] : []),
]);
const active = item => (item.match || [item.href]).some(path => page.url.split('?')[0] === path);
const removeStart = router.on('start', () => { loading.value = true; });
const removeFinish = router.on('finish', () => { loading.value = false; sidebarOpen.value = false; });
onBeforeUnmount(() => { removeStart(); removeFinish(); });
function logout() { if (logoutBusy.value) return; logoutBusy.value = true; router.post('/logout', {}, { onFinish: () => { logoutBusy.value = false; } }); }
</script>
<template>
    <Head :title="title" />
    <div class="min-h-screen">
        <a href="#main-content" class="sr-only z-[90] bg-white p-3 focus:not-sr-only focus:fixed">Saltar al contenido</a>
        <button v-if="sidebarOpen" class="fixed inset-0 z-40 bg-escall-950/60 lg:hidden" aria-label="Cerrar menú" @click="sidebarOpen = false" />
        <aside class="fixed inset-y-0 left-0 z-50 flex w-[264px] flex-col bg-escall-950 text-white transition-transform lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-20 items-center justify-between border-b border-white/10 px-6"><Link href="/inicio" class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-escall-500 text-xl font-black">E</span><span><strong class="block text-xl tracking-wider">ESCALL</strong><span class="block text-[10px] font-semibold uppercase tracking-[.2em] text-slate-400">Plataforma Perú</span></span></Link><button class="p-2 lg:hidden" aria-label="Cerrar menú" @click="sidebarOpen = false"><XMarkIcon class="h-5 w-5" /></button></div>
            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-7" aria-label="Navegación principal"><p class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-slate-500">Gestión de cartera</p><Link v-for="item in navigation" :key="item.href" :href="item.href" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition" :class="active(item) ? 'bg-escall-500 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'" :aria-current="active(item) ? 'page' : undefined"><component :is="item.icon" class="h-5 w-5" />{{ item.label }}</Link></nav>
            <div class="border-t border-white/10 p-4"><button class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm text-slate-300 hover:bg-white/10 disabled:opacity-50" :disabled="logoutBusy" @click="logout"><ArrowRightOnRectangleIcon class="h-5 w-5" />{{ logoutBusy ? 'Cerrando sesión…' : 'Cerrar sesión' }}</button></div>
        </aside>
        <div class="lg:pl-[264px]">
            <header class="flex h-20 items-center justify-between border-b border-slate-200 bg-white px-5 sm:px-8"><div class="flex items-center gap-3"><button class="rounded-lg p-2 lg:hidden" aria-label="Abrir menú" :aria-expanded="sidebarOpen" @click="sidebarOpen = true"><Bars3Icon class="h-6 w-6" /></button><p class="hidden text-sm font-medium text-slate-500 sm:block">Plataforma ESCALL</p><span v-if="loading" class="text-xs text-escall-500" role="status">Cargando…</span></div><div class="flex items-center gap-3"><div class="hidden text-right sm:block"><p class="text-sm font-semibold">{{ user.nombre }}</p><p class="text-xs capitalize text-slate-500">{{ user.rol }}</p></div><span class="flex h-10 w-10 items-center justify-center rounded-full bg-escall-50 text-sm font-bold text-escall-600">{{ initials }}</span></div></header>
            <main id="main-content" class="mx-auto max-w-[1600px] space-y-6 p-5 sm:p-8" :aria-busy="loading">
                <div><nav class="mb-3 flex flex-wrap items-center gap-2 text-xs text-slate-500" aria-label="Ruta de navegación"><Link href="/inicio">Inicio</Link><template v-for="crumb in breadcrumbs" :key="crumb.label"><ChevronRightIcon class="h-3 w-3" /><Link v-if="crumb.href" :href="crumb.href">{{ crumb.label }}</Link><span v-else>{{ crumb.label }}</span></template><ChevronRightIcon v-if="title !== 'Inicio'" class="h-3 w-3" /><span v-if="title !== 'Inicio'" class="text-slate-700">{{ title }}</span></nav><div class="flex flex-wrap items-start justify-between gap-4"><div><h1 class="text-2xl font-bold tracking-tight text-escall-950 sm:text-3xl">{{ title }}</h1><p v-if="subtitle" class="mt-2 text-sm text-slate-500">{{ subtitle }}</p></div><slot name="actions" /></div></div>
                <Alert :message="page.props.flash?.success" /><Alert :message="page.props.flash?.error" tone="error" />
                <slot />
            </main>
        </div>
    </div>
</template>
