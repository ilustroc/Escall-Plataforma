<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
const props = defineProps({ open: Boolean, title: String });
const emit = defineEmits(['close']);
const panel = ref(null);
let previousFocus;
const onKeydown = (event) => {
    if (!props.open) return;
    if (event.key === 'Escape') emit('close');
    if (event.key !== 'Tab') return;
    const focusables = [...(panel.value?.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href], [tabindex="0"]') ?? [])].filter(el => el.offsetParent !== null);
    const first = focusables[0], last = focusables.at(-1);
    if (!first) { event.preventDefault(); return; }
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
};
watch(() => props.open, async (open) => {
    if (open) {
        previousFocus = document.activeElement;
        document.addEventListener('keydown', onKeydown);
        await nextTick();
        panel.value?.querySelector('input,select,textarea,button')?.focus();
    } else { document.removeEventListener('keydown', onKeydown); previousFocus?.focus?.(); }
});
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>
<template>
    <Teleport to="body"><div v-if="open" class="fixed inset-0 z-[70] flex items-center justify-center overflow-y-auto bg-escall-950/60 p-4" @click.self="emit('close')">
        <section ref="panel" role="dialog" aria-modal="true" :aria-label="title" class="my-auto max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white shadow-xl">
            <header class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><h2 class="text-lg font-semibold">{{ title }}</h2><button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Cerrar diálogo" @click="emit('close')"><XMarkIcon class="h-5 w-5" /></button></header>
            <div class="p-6"><slot /></div>
        </section>
    </div></Teleport>
</template>
