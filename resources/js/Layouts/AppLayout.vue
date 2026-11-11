<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

import {
    HomeIcon,
    FolderArrowDownIcon,
    ChartBarSquareIcon,
    UsersIcon,
    CalendarDaysIcon,
    Bars3Icon,
    XMarkIcon,
    ArrowRightOnRectangleIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';

import Alert from '../Components/Alert.vue';

defineProps({
    title: String,
    subtitle: String,
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const sidebarOpen = ref(false);
const loading = ref(false);
const logoutBusy = ref(false);

const user = computed(() => page.props.auth?.user || {});

const initials = computed(() =>
    (user.value.nombre || 'E')
        .split(' ')
        .slice(0, 2)
        .map(value => value.charAt(0))
        .join('')
        .toUpperCase()
);

const navigation = computed(() => {
    const items = [
        {
            label: 'Inicio y clientes',
            href: '/inicio',
            icon: HomeIcon,
            match: ['/inicio', '/clientes', '/propuestas'],
        },
        {
            label: 'Seguimientos',
            href: '/seguimientos',
            icon: CalendarDaysIcon,
        },
    ];

    if (user.value.rol === 'administrador') {
        items.push({ label: 'Cargas', href: '/cargas', icon: FolderArrowDownIcon });
    }

    items.push({ label: 'Reportes', href: '/reportes', icon: ChartBarSquareIcon });

    if (user.value.rol === 'administrador') {
        items.push({ label: 'Usuarios', href: '/admin', icon: UsersIcon });
    }

    return items;
});

// Inertia visits must only target responses with the X-Inertia protocol.
// Blade destinations intentionally perform a normal document navigation.
const isBladeDestination = href => href === '/inicio' || href.startsWith('/cargas');

function visitBlade(event, href) {
    // Leave Inertia deliberately for server-rendered Blade routes. The native
    // href remains available for progressive enhancement and copied links.
    event.preventDefault();
    window.location.assign(href);
}

const active = item =>
    (item.match || [item.href]).some(
        path => page.url.split('?')[0] === path
    );

const removeStart = router.on('start', () => {
    loading.value = true;
});

const removeFinish = router.on('finish', () => {
    loading.value = false;
    sidebarOpen.value = false;
});

onBeforeUnmount(() => {
    removeStart();
    removeFinish();
});

function logout() {
    if (logoutBusy.value) return;

    logoutBusy.value = true;

    router.post(
        '/logout',
        {},
        {
            onFinish: () => {
                logoutBusy.value = false;
            },
        }
    );
}
</script>

<template>

    <Head :title="title" />

    <div class="min-h-screen">
        <!-- Accesibilidad -->
        <a href="#main-content" class="sr-only z-[90] bg-white p-3 focus:not-sr-only focus:fixed">
            Saltar al contenido
        </a>

        <!-- Fondo móvil -->
        <button v-if="sidebarOpen" class="fixed inset-0 z-40 bg-escall-950/60 lg:hidden" aria-label="Cerrar menú"
            @click="sidebarOpen = false" />

        <!-- SIDEBAR -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-[252px] flex-col bg-escall-950 text-white transition-transform lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <!-- LOGO -->
            <div class="relative flex h-20 items-center justify-center border-b border-white/10 px-5">
                <a href="/inicio" class="flex items-center justify-center" aria-label="ESCALL Perú - Inicio" @click="visitBlade($event, '/inicio')">
                    <img :src="'/assets/img/logo.png'" alt="ESCALL Perú" class="h-10 w-auto object-contain" />
                </a>

                <button class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-2 lg:hidden"
                    aria-label="Cerrar menú" @click="sidebarOpen = false">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>

            <!-- NAVEGACIÓN -->
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Navegación principal">
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.16em] text-slate-500">
                    Operación
                </p>

                <a v-for="item in navigation.filter(item => isBladeDestination(item.href))" :key="item.href" :href="item.href" @click="visitBlade($event, item.href)"
                    class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition" :class="active(item)
                        ? 'bg-escall-500 text-white shadow-sm'
                        : 'text-slate-300 hover:bg-white/10 hover:text-white'
                        " :aria-current="active(item) ? 'page' : undefined">
                    <component :is="item.icon" class="h-5 w-5" />

                    {{ item.label }}
                </a>

                <Link v-for="item in navigation.filter(item => !isBladeDestination(item.href))" :key="item.href" :href="item.href"
                    class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition" :class="active(item)
                        ? 'bg-escall-500 text-white shadow-sm'
                        : 'text-slate-300 hover:bg-white/10 hover:text-white'
                        " :aria-current="active(item) ? 'page' : undefined">
                    <component :is="item.icon" class="h-5 w-5" />

                    {{ item.label }}
                </Link>
            </nav>

            <!-- CERRAR SESIÓN -->
            <div class="border-t border-white/10 p-3">
                <div class="mb-2 px-3 text-xs text-slate-400">
                    Sesión segura
                </div>

                <button
                    class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm text-slate-300 hover:bg-white/10 disabled:opacity-50"
                    :disabled="logoutBusy" @click="logout">
                    <ArrowRightOnRectangleIcon class="h-5 w-5" />

                    {{
                        logoutBusy
                            ? 'Cerrando sesión…'
                            : 'Cerrar sesión'
                    }}
                </button>
            </div>
        </aside>

        <!-- CONTENIDO -->
        <div class="lg:pl-[252px]">
            <!-- TOPBAR -->
            <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-5 sm:px-7">
                <div class="flex items-center gap-3">
                    <button class="rounded-md p-2 lg:hidden" aria-label="Abrir menú" :aria-expanded="sidebarOpen"
                        @click="sidebarOpen = true">
                        <Bars3Icon class="h-5 w-5" />
                    </button>

                    <!-- Logo móvil -->
                    <img :src="'/assets/img/logo.png'" alt="ESCALL Perú" class="h-7 w-auto lg:hidden" />

                    <p class="hidden text-sm font-medium text-slate-500 sm:block">
                        Gestión de cartera
                    </p>

                    <span v-if="loading" class="text-xs font-medium text-escall-600" role="status">
                        Actualizando…
                    </span>
                </div>

                <!-- USUARIO -->
                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold">
                            {{ user.nombre }}
                        </p>

                        <p class="text-xs capitalize text-slate-500">
                            {{ user.rol }}
                        </p>
                    </div>

                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-escall-600">
                        {{ initials }}
                    </span>
                </div>
            </header>

            <!-- MAIN -->
            <main id="main-content" class="mx-auto max-w-[1720px] space-y-5 p-4 sm:p-6" :aria-busy="loading">
                <!-- CABECERA -->
                <div>
                    <!-- BREADCRUMBS -->
                    <nav class="mb-2 flex flex-wrap items-center gap-2 text-xs text-slate-500"
                        aria-label="Ruta de navegación">
                        <a href="/inicio" @click="visitBlade($event, '/inicio')">
                            Inicio
                        </a>

                        <template v-for="crumb in breadcrumbs" :key="crumb.label">
                            <ChevronRightIcon class="h-3 w-3" />

                            <a v-if="crumb.href && isBladeDestination(crumb.href)" :href="crumb.href" @click="visitBlade($event, crumb.href)">
                                {{ crumb.label }}
                            </a>

                            <Link v-else-if="crumb.href" :href="crumb.href">
                                {{ crumb.label }}
                            </Link>

                            <span v-else>
                                {{ crumb.label }}
                            </span>
                        </template>

                        <ChevronRightIcon v-if="title !== 'Inicio'" class="h-3 w-3" />

                        <span v-if="title !== 'Inicio'" class="text-slate-700">
                            {{ title }}
                        </span>
                    </nav>

                    <!-- TÍTULO -->
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                {{ title }}
                            </h1>

                            <p v-if="subtitle" class="mt-1 text-sm text-slate-500">
                                {{ subtitle }}
                            </p>
                        </div>

                        <slot name="actions" />
                    </div>
                </div>

                <!-- ALERTAS -->
                <Alert :message="page.props.flash?.success" />

                <Alert :message="page.props.flash?.error" tone="error" />

                <!-- CONTENIDO DE CADA PÁGINA -->
                <slot />
            </main>
        </div>
    </div>
</template>
