<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, ArrowRightIcon, UsersIcon, CreditCardIcon } from '@heroicons/vue/24/outline';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormField from '../../Components/FormField.vue';
import EmptyState from '../../Components/EmptyState.vue';
import Pagination from '../../Components/Pagination.vue';
const props = defineProps({ filters: { type: Object, default: () => ({}) }, clientes: { type: Object, default: () => ({ data: [] }) }, carteras: { type: Array, default: () => [] }, stats: { type: Object, default: () => ({}) } });
const form = useForm({ dni: props.filters.dni || '', codigo: props.filters.codigo || '', titular: props.filters.titular || '', cartera: props.filters.cartera || '' });
const searched = computed(() => Object.values(props.filters).some(Boolean));
function search() { form.get('/inicio', { preserveState: true, replace: true }); }
</script>
<template>
    <AppLayout title="Inicio" subtitle="Consulta clientes y cuentas para gestionar acuerdos y propuestas.">
        <div v-if="Object.keys(stats).length" class="grid gap-4 sm:grid-cols-2">
            <div class="card flex items-center gap-4 p-5"><span class="rounded-xl bg-escall-50 p-3">
                    <UsersIcon class="h-6 w-6 text-escall-500" />
                </span>
                <div>
                    <p class="text-sm text-slate-500">Clientes en cartera</p>
                    <p class="mt-1 text-2xl font-bold">{{ Number(stats.clientes || 0).toLocaleString('es-PE') }}</p>
                </div>
            </div>
            <div class="card flex items-center gap-4 p-5"><span class="rounded-xl bg-emerald-50 p-3">
                    <CreditCardIcon class="h-6 w-6 text-emerald-600" />
                </span>
                <div>
                    <p class="text-sm text-slate-500">Cuentas registradas</p>
                    <p class="mt-1 text-2xl font-bold">{{ Number(stats.cuentas || 0).toLocaleString('es-PE') }}</p>
                </div>
            </div>
        </div>
        <form class="card p-5 sm:p-6" @submit.prevent="search">
            <h2 class="mb-5 font-semibold">Buscar clientes</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <FormField id="dni" label="Documento" :error="form.errors.dni"><input id="dni" v-model="form.dni"
                        class="field" placeholder="Número de documento" maxlength="30" /></FormField>
                <FormField id="codigo" label="Código de cuenta" :error="form.errors.codigo"><input id="codigo"
                        v-model="form.codigo" class="field" placeholder="Código" maxlength="100" /></FormField>
                <FormField id="titular" label="Titular" :error="form.errors.titular"><input id="titular"
                        v-model="form.titular" class="field" placeholder="Nombre del cliente" maxlength="255" />
                </FormField>
                <FormField id="cartera" label="Cartera" :error="form.errors.cartera"><input id="cartera"
                        v-model="form.cartera" class="field" list="carteras" placeholder="Todas las carteras"
                        maxlength="100" /><datalist id="carteras">
                        <option v-for="cartera in carteras" :key="cartera" :value="cartera" />
                    </datalist></FormField>
            </div>
            <div class="mt-5 flex justify-end gap-3">
                <Link href="/inicio" class="btn-secondary">Limpiar</Link><button class="btn-primary"
                    :disabled="form.processing">
                    <MagnifyingGlassIcon class="h-4 w-4" />{{ form.processing ? 'Buscando…' : 'Buscar clientes' }}
                </button>
            </div>
        </form>
        <section class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold">{{ searched ? 'Resultados de búsqueda' : 'Tu cartera de clientes' }}<span
                        v-if="searched" class="badge ml-2">{{ clientes.total ?? clientes.data?.length ?? 0 }}</span>
                </h2>
            </div>
            <div v-if="clientes.data?.length" class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Titular</th>
                            <th>Cuentas</th>
                            <th>Carteras</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="cliente in clientes.data" :key="cliente.dni">
                            <td class="font-medium">{{ cliente.dni }}</td>
                            <td>{{ cliente.titular }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1"><span v-for="codigo in cliente.codigos" :key="codigo"
                                        class="badge">{{ codigo }}</span></div>
                            </td>
                            <td>{{ [...new Set(cliente.carteras)].join(', ') }}</td>
                            <td>
                                <Link :href="`/clientes?dni=${encodeURIComponent(cliente.dni)}`"
                                    class="inline-flex items-center gap-2 whitespace-nowrap font-semibold text-escall-500 hover:underline">
                                    Ver cliente
                                    <ArrowRightIcon class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else :title="searched ? 'No encontramos coincidencias' : 'Encuentra al cliente que necesitas'"
                :description="searched ? 'Revisa los datos o utiliza otros filtros para buscar.' : 'Ingresa un Documento, código, nombre o cartera para comenzar.'" />
            <Pagination :paginator="clientes" />
        </section>
    </AppLayout>
</template>
