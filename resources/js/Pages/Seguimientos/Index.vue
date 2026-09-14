<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from '../../bootstrap';
import AppLayout from '../../Layouts/AppLayout.vue';
import AppModal from '../../Components/AppModal.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import FormField from '../../Components/FormField.vue';
import EmptyState from '../../Components/EmptyState.vue';
import { useApiForm } from '../../Composables/useApiForm';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
const props = defineProps({ isAdmin: Boolean });
const rows = ref([]), evolutivo = ref([]), loading = ref(false), view = ref('seguimientos'), show = ref(false), deleting = ref(null);
const filter = reactive({ todos: false, fecha: '' }); const form = reactive({ codigo: '', fecha: new Date().toISOString().slice(0, 10), hora: '09:00', tipo: 'seguimiento', descripcion: '', estado: '' });
const { busy, message, success, errors, submit } = useApiForm();
async function load() { loading.value = true; try { const endpoint = view.value === 'seguimientos' ? '/api/seguimientos' : '/api/evolutivo'; const { data } = await axios.get(endpoint, { params: { todos: filter.todos ? 1 : undefined, fecha: filter.fecha || undefined } }); if (view.value === 'seguimientos') rows.value = data.data; else evolutivo.value = data.data; } finally { loading.value = false; } }
function setView(next) { view.value = next; load(); }
async function save() { const result = await submit('/api/seguimientos', form); if (result?.success) { show.value = false; success.value = 'Seguimiento registrado.'; Object.assign(form, { codigo: '', fecha: new Date().toISOString().slice(0, 10), hora: '09:00', tipo: 'seguimiento', descripcion: '', estado: '' }); load(); } }
async function destroy() { const result = await submit('/api/seguimientos/eliminar', { id: deleting.value?.id }); if (result?.success) { deleting.value = null; load(); } }
onMounted(load);
</script>
<template>
    <AppLayout title="Seguimientos" subtitle="Registra gestiones, recordatorios y consulta el avance de tus acuerdos.">
        <template #actions><button class="btn-primary" @click="show = true">
                <PlusIcon class="h-4 w-4" />Registrar seguimiento
            </button></template>
        <p v-if="message" class="error">{{ message }}</p>
        <p v-if="success" class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ success }}</p>
        <section class="card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
                <div class="flex gap-2"><button class="btn-secondary"
                        :class="view === 'seguimientos' ? 'border-escall-500 text-escall-600' : ''"
                        @click="setView('seguimientos')">Seguimientos</button><button class="btn-secondary"
                        :class="view === 'evolutivo' ? 'border-escall-500 text-escall-600' : ''"
                        @click="setView('evolutivo')">Evolutivo</button></div>
                <div class="flex items-center gap-3"><label v-if="isAdmin"
                        class="flex items-center gap-2 text-sm"><input v-model="filter.todos" type="checkbox"
                            @change="load" />Todos los usuarios</label><input v-model="filter.fecha" class="field"
                        type="date" @change="load" /></div>
            </div>
            <div v-if="loading" class="p-8 text-center text-sm text-slate-500">Cargando…</div>
            <div v-else-if="view === 'seguimientos' && rows.length" class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Cuenta</th>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td>{{ row.fecha }}</td>
                            <td>{{ row.hora || '—' }}</td>
                            <td><span class="badge">{{ row.codigo }}</span><span class="ml-2 text-xs text-slate-500">{{
                                    row.cliente?.titular || '' }}</span></td>
                            <td class="capitalize">{{ row.tipo }}</td>
                            <td>{{ row.descripcion || '—' }}</td>
                            <td>{{ row.estado || '—' }}</td>
                            <td><button class="text-red-600" title="Eliminar" @click="deleting = row">
                                    <TrashIcon class="h-5 w-5" />
                                </button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else-if="view === 'evolutivo' && evolutivo.length" class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Documento</th>
                            <th>Acuerdo</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Gestión</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in evolutivo" :key="row.id">
                            <td>{{ row.fecha }}</td>
                            <td>{{ row.dni }}</td>
                            <td>{{ row.tipo_acuerdo }}</td>
                            <td>S/ {{ Number(row.monto || 0).toFixed(2) }}</td>
                            <td>{{ row.estado }}</td>
                            <td>{{ row.gestion }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else :title="view === 'seguimientos' ? 'Sin seguimientos registrados' : 'Sin datos evolutivos'"
                description="Los registros que correspondan a tus filtros aparecerán aquí." />
        </section>
        <AppModal :open="show" title="Registrar seguimiento" @close="show = false">
            <form class="space-y-4" @submit.prevent="save">
                <FormField id="codigo" label="Código de cuenta" :error="errors.codigo" required><input id="codigo"
                        v-model="form.codigo" class="field" required /></FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField id="fecha" label="Fecha" :error="errors.fecha" required><input id="fecha"
                            v-model="form.fecha" class="field" type="date" required /></FormField>
                    <FormField id="hora" label="Hora" :error="errors.hora" required><input id="hora" v-model="form.hora"
                            class="field" type="time" required /></FormField>
                </div>
                <FormField id="tipo" label="Tipo" :error="errors.tipo"><select id="tipo" v-model="form.tipo"
                        class="field">
                        <option value="seguimiento">Seguimiento</option>
                        <option value="recordatorio">Recordatorio</option>
                    </select></FormField>
                <FormField id="descripcion" label="Descripción" :error="errors.descripcion"><textarea id="descripcion"
                        v-model="form.descripcion" class="field" rows="4" /></FormField>
                <FormField id="estado" label="Estado" :error="errors.estado"><input id="estado" v-model="form.estado"
                        class="field" /></FormField>
                <div class="flex justify-end gap-3"><button type="button" class="btn-secondary"
                        @click="show = false">Cancelar</button><button class="btn-primary" :disabled="busy">{{
                            busy ?'Guardando…':'Registrar' }}</button></div>
            </form>
        </AppModal>
        <ConfirmDialog :open="Boolean(deleting)" :busy="busy" message="Esta gestión se eliminará permanentemente."
            @close="deleting = null" @confirm="destroy" />
    </AppLayout>
</template>
