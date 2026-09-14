<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { DocumentTextIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import AppLayout from '../../Layouts/AppLayout.vue';
import AccountTable from '../../Components/AccountTable.vue';
import FormField from '../../Components/FormField.vue';
import Alert from '../../Components/Alert.vue';
import { useApiForm } from '../../Composables/useApiForm';
const props = defineProps({ cliente: { type: Object, required: true }, cuentas: { type: Array, default: () => [] }, cuentasDeposito: { type: Array, default: () => [] } });
const today = () => { const now = new Date(); return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`; };
const selected = ref([]), downloadUrl = ref('');
const form = reactive({ cliente_id: props.cliente.id, tipo_documento: 'convenio', tipo_facilidad: 'Convenio de Pago', fecha_solicitud: today(), cuota_inicial: '', monto_convenio: '', numero_cuotas: '', primera_cuota: '', f_cuota: today(), cuenta_banco: '', cancelacion: '', fecha_pago: today(), usar_cuotas: false, cuota_1: '', fecha_cuota_1: today(), cuota_2: '', fecha_cuota_2: today() });
const { busy, message, success, errors, submit } = useApiForm();
const convenio = computed(() => form.tipo_documento === 'convenio');
const paymentText = computed(() => form.f_cuota ? `${form.f_cuota.split('-')[2]} de cada mes` : '');
watch(() => form.tipo_documento, () => { form.tipo_facilidad = convenio.value ? 'Convenio de Pago' : 'Cancelación'; if (convenio.value) form.usar_cuotas = false; });
watch(() => [form.cancelacion, form.cuota_1, form.usar_cuotas], () => { form.cuota_2 = form.usar_cuotas ? Math.max(0, Number(form.cancelacion || 0) - Number(form.cuota_1 || 0)).toFixed(2) : ''; });
async function generate() {
    downloadUrl.value = '';
    if (!selected.value.length) { message.value = 'Selecciona al menos una cuenta.'; return; }
    if (form.usar_cuotas && (!(Number(form.cuota_1) > 0) || !(Number(form.cuota_2) > 0))) { message.value = 'Las dos cuotas deben ser mayores que cero y sumar el monto de cancelación.'; return; }
    const result = await submit('/clientes/generar-word', { ...form, cuentas_seleccionadas: selected.value, fecha_pago_hidden: convenio.value ? paymentText.value : form.fecha_pago });
    if (result?.download_url) { downloadUrl.value = result.download_url; success.value = 'Documento generado y guardado correctamente.'; window.location.assign(result.download_url); }
}
</script>
<template>
    <AppLayout :title="cliente.titular || 'Detalle del cliente'"
        :subtitle="`Documento ${cliente.dni} · ${cuentas.length} cuenta(s)`"
        :breadcrumbs="[{ label: 'Clientes', href: '/inicio' }]">
        <template #actions>
            <Link :href="`/propuestas?cliente_id=${cliente.id}`" class="btn-primary">Registrar propuesta
                <ArrowRightIcon class="h-4 w-4" />
            </Link>
        </template>
        <Alert :message="message" tone="error" />
        <Alert :message="success" />
        <form class="space-y-6" @submit.prevent="generate">
            <AccountTable v-model="selected" :cuentas="cuentas" />
            <p v-if="errors.cuentas_seleccionadas" class="error">{{ errors.cuentas_seleccionadas }}</p>
            <section class="card p-5 sm:p-6">
                <div class="mb-6 flex items-center gap-3"><span class="rounded-xl bg-escall-50 p-3">
                        <DocumentTextIcon class="h-5 w-5 text-escall-500" />
                    </span>
                    <div>
                        <h2 class="font-semibold">Generar documento</h2>
                        <p class="mt-1 text-xs text-slate-500">Convenio de pago o carta de cancelación en Word.</p>
                    </div>
                </div>
                <fieldset class="mb-6">
                    <legend class="label">Tipo de documento</legend>
                    <div class="flex gap-5"><label class="flex items-center gap-2 text-sm"><input
                                v-model="form.tipo_documento" value="convenio" type="radio"
                                name="tipo_documento" />Convenio</label><label
                            class="flex items-center gap-2 text-sm"><input v-model="form.tipo_documento"
                                value="cancelacion" type="radio" name="tipo_documento" />Cancelación</label></div>
                </fieldset>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <FormField id="tipo_facilidad" label="Tipo de facilidad" :error="errors.tipo_facilidad" required>
                        <input id="tipo_facilidad" v-model="form.tipo_facilidad" class="field" required
                            maxlength="150" />
                    </FormField>
                    <FormField id="fecha_solicitud" label="Fecha de solicitud" :error="errors.fecha_solicitud" required>
                        <input id="fecha_solicitud" v-model="form.fecha_solicitud" class="field" type="date" required />
                    </FormField>
                    <FormField id="cuenta_banco" label="Cuenta de depósito" :error="errors.cuenta_banco" required>
                        <select id="cuenta_banco" v-model="form.cuenta_banco" class="field" required>
                            <option value="">Selecciona un banco</option>
                            <option v-for="bank in cuentasDeposito" :key="bank.id" :value="bank.id">{{ bank.label }}
                            </option>
                        </select>
                    </FormField>
                    <template v-if="convenio">
                        <FormField id="cuota_inicial" label="Cuota inicial (S/)" :error="errors.cuota_inicial"><input
                                id="cuota_inicial" v-model="form.cuota_inicial" class="field" type="number" min="0"
                                step="0.01" /></FormField>
                        <FormField id="monto_convenio" label="Monto de convenio (S/)" :error="errors.monto_convenio"
                            required><input id="monto_convenio" v-model="form.monto_convenio" class="field"
                                type="number" min="0.01" step="0.01" required /></FormField>
                        <FormField id="numero_cuotas" label="Número de cuotas" :error="errors.numero_cuotas" required>
                            <input id="numero_cuotas" v-model="form.numero_cuotas" class="field" type="number" min="1"
                                max="360" required />
                        </FormField>
                        <FormField id="primera_cuota" label="Primera cuota (S/)" :error="errors.primera_cuota" required>
                            <input id="primera_cuota" v-model="form.primera_cuota" class="field" type="number"
                                min="0.01" step="0.01" required />
                        </FormField>
                        <FormField id="f_cuota" label="Fecha de primera cuota" :error="errors.f_cuota" required><input
                                id="f_cuota" v-model="form.f_cuota" class="field" type="date" required /></FormField>
                        <FormField id="pago_recurrente" label="Fecha de pago mensual"><input id="pago_recurrente"
                                :value="paymentText" class="field" readonly /></FormField>
                    </template>
                    <template v-else>
                        <FormField id="cancelacion" label="Monto de cancelación (S/)" :error="errors.cancelacion"
                            required><input id="cancelacion" v-model="form.cancelacion" class="field" type="number"
                                min="0.01" step="0.01" required /></FormField>
                        <FormField id="fecha_pago" label="Fecha de pago" :error="errors.fecha_pago" required><input
                                id="fecha_pago" v-model="form.fecha_pago" class="field" type="date" required />
                        </FormField>
                    </template>
                </div>
                <div v-if="!convenio" class="mt-6 rounded-xl border border-slate-200 p-4"><label
                        class="flex items-center gap-2 text-sm font-medium"><input v-model="form.usar_cuotas"
                            type="checkbox" />Dividir la cancelación en dos cuotas</label>
                    <div v-if="form.usar_cuotas" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <FormField id="cuota_1" label="Cuota 1 (S/)" :error="errors.cuota_1" required><input
                                id="cuota_1" v-model="form.cuota_1" class="field" type="number" min="0.01" step="0.01"
                                required />
                        </FormField>
                        <FormField id="fecha_cuota_1" label="Fecha de cuota 1" :error="errors.fecha_cuota_1" required>
                            <input id="fecha_cuota_1" v-model="form.fecha_cuota_1" class="field" type="date" required />
                        </FormField>
                        <FormField id="cuota_2" label="Cuota 2 (S/)" :error="errors.cuota_2"
                            hint="Calculada a partir del total."><input id="cuota_2" :value="form.cuota_2" class="field"
                                type="number" readonly /></FormField>
                        <FormField id="fecha_cuota_2" label="Fecha de cuota 2" :error="errors.fecha_cuota_2" required>
                            <input id="fecha_cuota_2" v-model="form.fecha_cuota_2" class="field" type="date" required />
                        </FormField>
                    </div>
                </div>
                <p v-if="!cuentasDeposito.length" class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">El
                    administrador debe configurar las cuentas de depósito antes de generar documentos.</p>
                <div class="mt-6 flex flex-wrap items-center justify-end gap-3"><a v-if="downloadUrl"
                        :href="downloadUrl" class="btn-secondary">Descargar nuevamente</a><button class="btn-primary"
                        :disabled="busy || !selected.length || !cuentasDeposito.length">
                        <DocumentTextIcon class="h-4 w-4" />
                        {{ busy ? 'Generando documento…' : 'Generar y descargar Word' }}
                    </button></div>
            </section>
        </form>
    </AppLayout>
</template>
