<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ paginator: { type: Object, default: () => ({}) } });
const label = (value) => String(value).replace(/&laquo;|&raquo;/g, '').replace(/Previous/g, 'Anterior').replace(/Next/g, 'Siguiente').trim();
</script>
<template>
    <nav v-if="paginator?.links?.length > 3" aria-label="Paginación" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4">
        <p class="text-xs text-slate-500">{{ paginator.from ?? 0 }}–{{ paginator.to ?? 0 }} de {{ paginator.total ?? 0 }}</p>
        <div class="flex flex-wrap gap-1"><template v-for="(link, index) in paginator.links" :key="index">
            <Link v-if="link.url" :href="link.url" preserve-scroll class="rounded-lg px-3 py-2 text-xs font-medium" :class="link.active ? 'bg-escall-500 text-white' : 'border border-slate-200 hover:bg-slate-50'" :aria-current="link.active ? 'page' : undefined">{{ label(link.label) }}</Link>
            <span v-else class="px-3 py-2 text-xs text-slate-400">{{ label(link.label) }}</span>
        </template></div>
    </nav>
</template>
