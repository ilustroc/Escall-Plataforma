<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';
import EmptyState from '../../Components/EmptyState.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import { useApiForm } from '../../Composables/useApiForm';
import { ArrowDownTrayIcon, TrashIcon } from '@heroicons/vue/24/outline';
const props = defineProps({ tab: String, filters: Object, documents: Object, proposals: Object });
const filters = reactive({ desde: props.filters?.desde || '', hasta: props.filters?.hasta || '' });
const deleting = ref(null); const { busy, message, submit } = useApiForm(); const page = usePage();
const rows = computed(() => props.tab === 'propuestas' ? props.proposals : props.documents);
const isProposals = computed(() => props.tab === 'propuestas');
function changeTab(tab) { router.get('/reportes', { ...filters, tab }, { preserveState: true, preserveScroll: true }); }
function apply() { router.get('/reportes', { ...filters, tab: props.tab }, { preserveState: true, preserveScroll: true }); }
async function destroy() { if (!deleting.value) return; const url = isProposals.value ? '/propuestas/eliminar' : '/reportes/eliminar'; const result = await submit(url, { id: deleting.value.id }); if (result?.success) { deleting.value = null; router.reload({ only: ['documents','proposals'] }); } }
const date = value => value ? new Date(value).toLocaleDateString('es-PE') : '—';
</script>
<template>
  <AppLayout title="Reportes" subtitle="Consulta, descarga y administra documentos privados generados por el equipo.">
    <p v-if="message" class="error">{{ message }}</p>
    <section class="card overflow-hidden"><div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-5"><div class="flex gap-2"><button class="btn-secondary" :class="!isProposals ? 'border-escall-500 text-escall-600' : ''" @click="changeTab('acuerdos')">Acuerdos</button><button class="btn-secondary" :class="isProposals ? 'border-escall-500 text-escall-600' : ''" @click="changeTab('propuestas')">Propuestas</button></div><form class="flex flex-wrap items-end gap-3" @submit.prevent="apply"><label class="label mb-0">Desde<input v-model="filters.desde" class="field mt-1" type="date" /></label><label class="label mb-0">Hasta<input v-model="filters.hasta" class="field mt-1" type="date" /></label><button class="btn-primary">Filtrar</button></form></div>
      <div v-if="rows?.data?.length" class="table-shell"><table><thead><tr><th>Fecha</th><th>Cliente</th><th>Documento</th><th>Usuario</th><th v-if="isProposals">Monto</th><th>Acciones</th></tr></thead><tbody><tr v-for="row in rows.data" :key="row.id"><td>{{ date(row.creado_en) }}</td><td><p class="font-medium">{{ row.cliente?.titular || '—' }}</p><p class="text-xs text-slate-500">{{ row.cliente?.dni || '—' }}</p></td><td>{{ isProposals ? (row.nombre_archivo || `Propuesta #${row.id}`) : row.nombre_archivo }}</td><td>{{ row.usuario?.nombre || '—' }}</td><td v-if="isProposals">S/ {{ Number(row.propuesta_cliente || 0).toFixed(2) }}</td><td><div class="flex gap-3"><a class="inline-flex items-center gap-1 text-sm font-semibold text-escall-600 hover:underline" :href="isProposals ? `/propuestas/excel?propuesta_id=${row.id}` : `/reportes/descargar?id=${row.id}`"><ArrowDownTrayIcon class="h-4 w-4" />Descargar</a><button class="inline-flex items-center gap-1 text-sm font-semibold text-red-600 hover:underline" @click="deleting = row"><TrashIcon class="h-4 w-4" />Eliminar</button></div></td></tr></tbody></table></div><EmptyState v-else title="No hay registros para este filtro" description="Los documentos y propuestas nuevos aparecerán aquí." /><Pagination :paginator="rows" /></section>
    <ConfirmDialog :open="Boolean(deleting)" :busy="busy" message="Esta acción eliminará el documento de forma permanente." @close="deleting = null" @confirm="destroy" />
  </AppLayout>
</template>
