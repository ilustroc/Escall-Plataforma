<script setup>
defineProps({ label: String, error: String, hint: String, id: String, required: Boolean });
</script>
<template>
    <div>
        <label v-if="label" :for="id" class="label">{{ label }}<span v-if="required" class="ml-1 text-red-600" aria-hidden="true">*</span></label>
        <slot />
        <p v-if="error" :id="id ? `${id}-error` : undefined" class="error" role="alert">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>
