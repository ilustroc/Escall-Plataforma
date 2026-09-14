<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
defineProps({ title: { type: String, default: 'No hay resultados' }, description: { type: String, default: 'Prueba con otros filtros.' } });
</script>
<template>
    <div class="px-6 py-14 text-center">
        <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-escall-50"><MagnifyingGlassIcon class="h-6 w-6 text-escall-500" /></span>
        <h3 class="font-semibold text-slate-800">{{ title }}</h3><p class="mx-auto mt-2 max-w-md text-sm text-slate-500">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5"><slot /></div>
    </div>
</template>
