<script setup>
import { computed } from 'vue';
import EmptyState from './EmptyState.vue';
const props = defineProps({ cuentas: { type: Array, default: () => [] }, modelValue: { type: Array, default: () => [] }, amounts: { type: Object, default: () => ({}) }, proposals: Boolean });
const emit = defineEmits(['update:modelValue', 'update:amounts']);
const total = computed(() => props.cuentas.filter(row => props.modelValue.includes(String(row.codigo))).reduce((sum, row) => sum + Number(row.deuda_total || 0), 0));
const money = value => new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(value || 0));
function toggle(code, checked) { emit('update:modelValue', checked ? [...props.modelValue, String(code)] : props.modelValue.filter(item => item !== String(code))); }
function amount(code, value) { emit('update:amounts', { ...props.amounts, [code]: value }); }
</script>
<template>
    <section class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
            <div>
                <h2 class="font-semibold">Cuentas del cliente</h2>
                <p class="mt-1 text-xs text-slate-500">{{ modelValue.length }} de {{ cuentas.length }} seleccionadas ·
                    Deuda {{ money(total) }}</p>
            </div>
            <div class="flex gap-2"><button type="button" class="btn-secondary text-xs"
                    @click="emit('update:modelValue', cuentas.slice(0, proposals ? 5 : cuentas.length).map(row => String(row.codigo)))">{{
                        proposals ? 'Seleccionar hasta 5' : 'Seleccionar todas' }}</button><button type="button"
                    class="btn-secondary text-xs" @click="emit('update:modelValue', [])">Limpiar</button></div>
        </div>
        <div v-if="cuentas.length" class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th><span class="sr-only">Seleccionar</span></th>
                        <th>Cuenta</th>
                        <th>Producto / Subproducto</th>
                        <th>Entidad</th>
                        <th>Departamento</th>
                        <th>Cosecha</th>
                        <th>Deuda</th>
                        <th>Capital</th>
                        <th>Campaña</th>
                        <th v-if="proposals">Propuesta</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in cuentas" :key="row.codigo">
                        <td><input type="checkbox" :aria-label="`Seleccionar cuenta ${row.codigo}`"
                                :checked="modelValue.includes(String(row.codigo))"
                                :disabled="proposals && modelValue.length >= 5 && !modelValue.includes(String(row.codigo))"
                                @change="toggle(row.codigo, $event.target.checked)" /></td>
                        <td><span class="badge">{{ row.codigo }}</span></td>
                        <td>{{ row.producto || '—' }}
                            <span class="mt-1 block text-xs text-slate-500">{{ row.sub_producto|| '—' }}</span>
                        </td>
                        <td>{{ row.entidad || '—' }}</td>
                        <td>{{ row.departamento || '—' }}</td>
                        <td>{{ row.cosecha || '—' }}</td>
                        <td class="whitespace-nowrap font-semibold">{{ money(row.deuda_total) }}</td>
                        <td class="whitespace-nowrap">{{ money(row.deuda_capital) }}</td>
                        <td>{{ row.campana || '—' }}</td>
                        <td v-if="proposals"><input :value="amounts[row.codigo] || ''"
                                :aria-label="`Monto propuesto para ${row.codigo}`" type="number" min="0.01" step="0.01"
                                class="field min-w-28" :disabled="!modelValue.includes(String(row.codigo))"
                                :required="modelValue.includes(String(row.codigo))"
                                @input="amount(row.codigo, $event.target.value)" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else title="Sin cuentas disponibles"
            description="Este cliente no tiene cuentas para seleccionar." />
    </section>
</template>
