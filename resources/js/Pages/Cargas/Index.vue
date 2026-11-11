<script setup>
import { computed, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import Alert from '../../Components/Alert.vue';
import FormField from '../../Components/FormField.vue';
import { useApiForm } from '../../Composables/useApiForm';
import { ArrowDownTrayIcon, ArrowUpTrayIcon, DocumentArrowUpIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ types: { type: Array, default: () => [] }, headers: { type: Object, default: () => ({}) }, isAdmin: Boolean });
const selected = ref(props.isAdmin ? 'data' : 'seguimientos');
const file = ref(null);
const mode = ref('merge');
const input = ref(null);
const { busy, message, success, submit } = useApiForm();
const labels = { data: 'DATA de cartera', pagos: 'Pagos', seguimientos: 'Seguimientos', evolutivo: 'Evolutivo' };
const accepted = computed(() => props.types.filter(type => props.isAdmin || ['seguimientos', 'evolutivo'].includes(type)));
const endpoint = computed(() => `/cargas/${selected.value}`);
const templateUrl = computed(() => `/cargas/plantilla?tipo=${encodeURIComponent(selected.value)}`);
function choose(event) { file.value = event.target.files?.[0] || null; }
async function upload() {
  if (!file.value) { message.value = 'Selecciona un archivo antes de cargar.'; return; }
  const body = new FormData(); body.append('file', file.value); body.append('modo', mode.value);
  const result = await submit(endpoint.value, body, { headers: { 'Content-Type': 'multipart/form-data' } });
  if (result?.success) {
    const count = result.summary || {};
    success.value = `Carga procesada: ${count.inserted || 0} nuevos, ${count.updated || 0} actualizados, ${count.skipped || 0} omitidos y ${count.failed || 0} fallidos.${count.period ? ` Período: ${count.period}.` : ''}`;
    file.value = null; if (input.value) input.value.value = '';
  }
}
</script>
<template>
  <AppLayout title="Cargas"
    subtitle="Importa información desde archivos privados y validados antes de incorporarla a la cartera.">
    <Alert :message="message" tone="error" />
    <Alert :message="success" />
    <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
      <form class="card p-5 sm:p-6" @submit.prevent="upload">
        <div class="mb-5 flex items-center gap-3 border-b border-slate-100 pb-4"><span class="rounded-md bg-blue-50 p-2.5">
            <DocumentArrowUpIcon class="h-6 w-6 text-escall-500" />
          </span>
          <div>
            <h2 class="font-semibold">Nueva carga</h2>
            <p class="mt-1 text-sm text-slate-500">Solo se aceptan XLS, XLSX y CSV UTF-8 hasta 10 MB.</p>
          </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <FormField id="tipo" label="Tipo de información" required><select id="tipo" v-model="selected" class="field">
              <option v-for="type in accepted" :key="type" :value="type">{{ labels[type] }}</option>
            </select></FormField>
          <FormField id="modo" label="Modo de importación" required><select id="modo" v-model="mode" class="field">
              <option value="merge">Integrar y actualizar coincidencias</option>
              <option v-if="selected === 'evolutivo'" value="replace">Reemplazar mis registros del período</option>
            </select></FormField>
        </div>
        <FormField id="archivo" class="mt-5" label="Archivo"
          hint="Se rechazan macros, fórmulas, enlaces externos, estructuras inválidas y archivos que superen los límites de seguridad."
          required><input id="archivo" ref="input" class="field" type="file"
            accept=".xlsx,.xls,.csv,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            required @change="choose" /></FormField>
        <div class="mt-6 flex flex-wrap justify-end gap-3"><a :href="templateUrl" class="btn-secondary">
            <ArrowDownTrayIcon class="h-4 w-4" />Descargar plantilla
          </a><button class="btn-primary" :disabled="busy || !file">
            <ArrowUpTrayIcon class="h-4 w-4" />{{ busy ? 'Validando y cargando…' : 'Procesar carga' }}
          </button></div>
      </form>
      <aside class="card p-5 sm:p-6">
        <h2 class="font-semibold">Columnas requeridas</h2>
        <p class="mt-1 text-sm text-slate-500">La primera fila debe contener estos encabezados.</p>
        <ul class="mt-4 grid grid-cols-2 gap-2 xl:block xl:space-y-2">
          <li v-for="header in headers[selected] || []" :key="header"
            class="rounded-lg bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700">{{ header }}</li>
        </ul>
      </aside>
    </section>
  </AppLayout>
</template>
