<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { BanknotesIcon, UsersIcon, CheckCircleIcon, CalendarDaysIcon, ClockIcon, MagnifyingGlassIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormField from '../../Components/FormField.vue';
import EmptyState from '../../Components/EmptyState.vue';
import Pagination from '../../Components/Pagination.vue';
const props = defineProps({ filters: Object, clientes: Object, carteras: Array, stats: Object, recentActivity: Array });
const form = useForm({ dni: props.filters?.dni || '', codigo: props.filters?.codigo || '', titular: props.filters?.titular || '', cartera: props.filters?.cartera || '' });
const searched = computed(() => Object.values(props.filters || {}).some(Boolean));
const money = value => new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN', maximumFractionDigits: 0 }).format(Number(value || 0));
const metrics = computed(() => [
    { label: 'Cartera total', value: money(props.stats?.cartera_total), help: 'Deuda total registrada', icon: BanknotesIcon, tone: 'text-escall-600 bg-blue-50' },
    { label: 'Clientes', value: Number(props.stats?.clientes || 0).toLocaleString('es-PE'), help: `${Number(props.stats?.cuentas || 0).toLocaleString('es-PE')} cuentas`, icon: UsersIcon, tone: 'text-slate-700 bg-slate-100' },
    { label: 'Pagos registrados', value: money(props.stats?.pagos_total), help: `${Number(props.stats?.pagos_registrados || 0)} registros`, icon: CheckCircleIcon, tone: 'text-emerald-700 bg-emerald-50' },
    { label: 'Seguimientos hoy', value: Number(props.stats?.seguimientos_hoy || 0).toLocaleString('es-PE'), help: `${Number(props.stats?.seguimientos_registrados || 0)} en total`, icon: CalendarDaysIcon, tone: 'text-amber-800 bg-amber-50' },
    { label: 'Pendientes', value: Number(props.stats?.pendientes || 0).toLocaleString('es-PE'), help: 'Acuerdos evolutivos', icon: ClockIcon, tone: 'text-amber-800 bg-amber-50' },
]);
let timer;
function search() { clearTimeout(timer); form.get('/inicio', { preserveState: true, preserveScroll: true, replace: true }); }
function queueSearch() { clearTimeout(timer); timer = setTimeout(search, 550); }
watch(() => [form.dni, form.codigo, form.titular, form.cartera], queueSearch);
onBeforeUnmount(() => clearTimeout(timer));
</script>
<template>
    <AppLayout title="Resumen de cobranzas"
        subtitle="Visión operativa de la cartera y acceso rápido a la gestión de clientes.">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <article v-for="metric in metrics" :key="metric.label" class="card min-w-0 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ metric.label }}</p>
                        <p class="mt-2 truncate text-xl font-bold tabular-nums text-slate-900">{{ metric.value }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ metric.help }}</p>
                    </div><span class="rounded-md p-2" :class="metric.tone">
                        <component :is="metric.icon" class="h-5 w-5" />
                    </span>
                </div>
            </article>
        </section>
        <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-5">
                <form class="card p-4" @submit.prevent="search">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-slate-900">Buscar cartera</h2>
                            <p class="mt-1 text-xs text-slate-500">Los filtros se aplican automáticamente tras una breve
                                pausa.</p>
                        </div>
                        <Link href="/inicio" class="text-xs font-semibold text-escall-600 hover:underline">Limpiar
                            filtros</Link>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <FormField id="dni" label="Documento"><input id="dni" v-model="form.dni" class="field"
                                placeholder="DNI o documento" maxlength="30" /></FormField>
                        <FormField id="codigo" label="Código"><input id="codigo" v-model="form.codigo" class="field"
                                placeholder="Código de cuenta" maxlength="100" /></FormField>
                        <FormField id="titular" label="Titular"><input id="titular" v-model="form.titular" class="field"
                                placeholder="Nombre del cliente" maxlength="255" /></FormField>
                        <FormField id="cartera" label="Cartera"><input id="cartera" v-model="form.cartera" class="field"
                                list="carteras" placeholder="Todas" /><datalist id="carteras">
                                <option v-for="cartera in carteras" :key="cartera" :value="cartera" />
                            </datalist></FormField>
                    </div>
                    <div class="mt-4 flex justify-end"><button class="btn-primary" :disabled="form.processing">
                            <MagnifyingGlassIcon class="h-4 w-4" />{{ form.processing ? 'Buscando…' : 'Buscar ahora' }}
                        </button></div>
                </form>
                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                        <h2 class="font-semibold">{{ searched ? 'Resultados de cartera' : 'Resultados de búsqueda' }}
                        </h2><span v-if="searched" class="badge">{{ clientes?.total || 0 }} clientes</span>
                    </div>
                    <div v-if="clientes?.data?.length" class="table-shell">
                        <table>
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Titular</th>
                                    <th>Cuentas</th>
                                    <th>Cartera</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cliente in clientes.data" :key="cliente.documento">
                                    <td class="font-medium tabular-nums">{{ cliente.documento }}</td>
                                    <td>{{ cliente.nombre }}</td>
                                    <td>
                                        <div class="flex max-w-xs flex-wrap gap-1"><span
                                                v-for="codigo in cliente.codigos" :key="codigo" class="badge">{{ codigo
                                                }}</span></div>
                                    </td>
                                    <td>{{ cliente.carteras?.join(', ') }}</td>
                                    <td>
                                        <Link :href="`/clientes?dni=${encodeURIComponent(cliente.documento)}`"
                                            class="inline-flex items-center gap-1 text-sm font-semibold text-escall-600 hover:underline">
                                            Gestionar
                                            <ArrowRightIcon class="h-4 w-4" />
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState v-else
                        :title="searched ? 'Sin coincidencias' : 'Aplica un filtro para consultar la cartera'"
                        :description="searched ? 'Revisa el documento, código o cartera ingresados.' : 'La cartera se consulta bajo demanda para reducir la carga inicial.'" />
                    <Pagination :paginator="clientes" />
                </section>
            </div>
            <aside class="card h-fit overflow-hidden">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h2 class="font-semibold">Actividad reciente</h2>
                    <p class="mt-1 text-xs text-slate-500">Últimos seguimientos disponibles.</p>
                </div>
                <div v-if="recentActivity?.length" class="divide-y divide-slate-100">
                    <div v-for="activity in recentActivity" :key="activity.id" class="p-4">
                        <div class="flex justify-between gap-3"><span class="badge capitalize">{{ activity.tipo
                                }}</span><span class="text-xs text-slate-500">{{ activity.fecha }}</span></div>
                        <p class="mt-2 text-sm font-medium text-slate-800">{{ activity.cliente?.nombre ||
                            activity.codigo }}</p>
                        <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ activity.descripcion || 'Sin descripción'
                            }}</p>
                    </div>
                </div>
                <EmptyState v-else title="Sin actividad reciente"
                    description="Los seguimientos registrados aparecerán aquí." />
                <Link href="/seguimientos"
                    class="block border-t border-slate-200 px-4 py-3 text-sm font-semibold text-escall-600 hover:bg-slate-50">
                    Ver seguimientos</Link>
            </aside>
        </section>
    </AppLayout>
</template>
