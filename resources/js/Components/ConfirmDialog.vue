<script setup>
import AppModal from './AppModal.vue';
defineProps({ open: Boolean, busy: Boolean, title: { type: String, default: 'Confirmar eliminación' }, message: String, confirmLabel: { type: String, default: 'Eliminar' } });
const emit = defineEmits(['close', 'confirm']);
</script>
<template><AppModal :open="open" :title="title" @close="!busy && emit('close')"><p class="text-sm leading-relaxed text-slate-600">{{ message }}</p><div class="mt-6 flex justify-end gap-3"><button class="btn-secondary" :disabled="busy" @click="emit('close')">Cancelar</button><button class="btn-danger" :disabled="busy" @click="emit('confirm')">{{ busy ? 'Procesando…' : confirmLabel }}</button></div></AppModal></template>
