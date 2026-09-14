<script setup>
import { CheckCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline';
defineProps({ message: String, tone: { type: String, default: 'success' } });
</script>
<template>
    <div v-if="message" role="status" class="flex items-start gap-3 rounded-xl border p-4 text-sm" :class="tone === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'">
        <component :is="tone === 'error' ? ExclamationCircleIcon : CheckCircleIcon" class="h-5 w-5 shrink-0" />
        <p class="break-words">{{ message }}</p>
    </div>
</template>
