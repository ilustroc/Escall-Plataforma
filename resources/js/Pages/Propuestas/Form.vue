<script setup>
import { computed, reactive, ref, watch } from "vue";
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";
import AppLayout from "../../Layouts/AppLayout.vue";
import AccountTable from "../../Components/AccountTable.vue";
import FormField from "../../Components/FormField.vue";
import Alert from "../../Components/Alert.vue";
import { useApiForm } from "../../Composables/useApiForm";
const props = defineProps({
    cliente: { type: Object, required: true },
    cuentas: { type: Array, default: () => [] },
    sustentos: { type: Array, default: () => [] },
});
const selected = ref([]),
    amounts = ref({}),
    downloadUrl = ref("");
const form = reactive({ fecha_pago: "", sustento: "", observacion: "" });
const { busy, message, success, errors, submit } = useApiForm();
const total = computed(() =>
    selected.value.reduce(
        (sum, code) => sum + Number(amounts.value[code] || 0),
        0,
    ),
);
const suggestion = computed(() => {
    const parts = [];
    if (total.value > 0 && form.fecha_pago)
        parts.push(
            `CLIENTE SOLICITA LA EVALUACION DE SU CASO, PROPONE S/. ${total.value.toFixed(2)} + S/.50.00 CNA, DE SER APROBADO CANCELA EL ${form.fecha_pago.split("-").reverse().join("/")}`,
        );
    if (form.sustento) parts.push(form.sustento);
    return parts.join("\n");
});
watch(suggestion, (value, previous) => {
    if (!form.observacion || form.observacion === previous)
        form.observacion = value;
});
async function save() {
    if (!selected.value.length || selected.value.length > 5) {
        message.value = "Selecciona entre una y cinco cuentas.";
        return;
    }
    if (selected.value.some((code) => !(Number(amounts.value[code]) > 0))) {
        message.value =
            "Ingresa un monto mayor que cero para cada cuenta seleccionada.";
        return;
    }
    const result = await submit("/propuestas", {
        ...form,
        cliente_id: props.cliente.id,
        propuesta_cliente: total.value.toFixed(2),
        cuentas_seleccionadas: selected.value,
        propuesta_por_cuenta: Object.fromEntries(
            selected.value.map((code) => [code, amounts.value[code]]),
        ),
    });
    if (result?.download_url) {
        downloadUrl.value = result.download_url;
        success.value = "Propuesta registrada correctamente.";
        window.location.assign(result.download_url);
    }
}
</script>
<template>
    <AppLayout title="Nueva propuesta" :subtitle="`${cliente.titular} · Documento ${cliente.dni}`" :breadcrumbs="[
        { label: 'Clientes', href: '/inicio' },
        {
            label: 'Detalle',
            href: `/clientes?dni=${encodeURIComponent(cliente.dni)}`,
        },
    ]">
        <Alert :message="message" tone="error" />
        <Alert :message="success" />
        <form class="space-y-6" @submit.prevent="save">
            <AccountTable v-model="selected" v-model:amounts="amounts" :cuentas="cuentas" proposals />
            <p v-if="errors.cuentas_seleccionadas" class="error">
                {{ errors.cuentas_seleccionadas }}
            </p>
            <p v-if="errors.propuesta_por_cuenta" class="error">
                {{ errors.propuesta_por_cuenta }}
            </p>
            <section class="card p-5 sm:p-6">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Datos de la propuesta</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Selecciona hasta cinco cuentas y asigna un monto a cada una.
                        </p>
                    </div>
                    <div class="rounded-xl bg-escall-50 px-5 py-3">
                        <p class="text-xs text-escall-600">Total propuesto</p>
                        <p class="mt-1 text-xl font-bold text-escall-600">
                            S/ {{ total.toFixed(2) }}
                        </p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField id="fecha_pago" label="Fecha de pago" :error="errors.fecha_pago" required><input
                            id="fecha_pago" v-model="form.fecha_pago" class="field" type="date" required /></FormField>
                    <FormField id="sustento" label="Sustento" :error="errors.sustento"><select id="sustento"
                            v-model="form.sustento" class="field">
                            <option value="">Selecciona un sustento</option>
                            <option v-for="s in sustentos" :key="s.id || s.sustento" :value="s.texto">
                                {{ s.sustento }}
                            </option>
                        </select></FormField>
                </div>
                <div class="mt-5">
                    <FormField id="observacion" label="Observación" :error="errors.observacion">
                        <textarea id="observacion" v-model="form.observacion" rows="5" maxlength="10000"
                            class="field" />
                    </FormField><button v-if="suggestion" type="button"
                        class="mt-2 text-xs font-semibold text-escall-500 hover:underline"
                        @click="form.observacion = suggestion">
                        Usar texto sugerido con monto, fecha y sustento
                    </button>
                </div>
                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <a v-if="downloadUrl" :href="downloadUrl" class="btn-secondary">Descargar nuevamente</a><button
                        class="btn-primary" :disabled="busy || !selected.length">
                        <ArrowDownTrayIcon class="h-4 w-4" />{{
                            busy ? "Registrando propuesta…" : "Registrar y descargarExcel"
                        }}
                    </button>
                </div>
            </section>
        </form>
    </AppLayout>
</template>
