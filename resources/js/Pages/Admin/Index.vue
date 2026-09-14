<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import AppModal from '../../Components/AppModal.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import FormField from '../../Components/FormField.vue';
import Pagination from '../../Components/Pagination.vue';
import EmptyState from '../../Components/EmptyState.vue';
import { useApiForm } from '../../Composables/useApiForm';
import { PlusIcon, PencilSquareIcon, PowerIcon, TrashIcon } from '@heroicons/vue/24/outline';
const props = defineProps({ users: Object, filters: Object });
const showForm = ref(false), editing = ref(null), confirm = ref(null), search = ref(props.filters?.buscar || '');
const form = useForm({ id: null, nombre: '', usuario: '', rol: 'asesor', contrasena: '', activo: true });
const { busy, message, submit } = useApiForm();
function open(user = null) { editing.value = user; form.reset(); form.clearErrors(); if (user) form.defaults({ id:user.id,nombre:user.nombre,usuario:user.usuario,rol:user.rol,contrasena:'',activo:Boolean(user.activo) }); form.reset(); showForm.value = true; }
function save() { form.post(editing.value ? '/admin/actualizar' : '/admin/crear', { preserveScroll: true, onSuccess: () => { showForm.value = false; form.reset(); }, }); }
function searchUsers() { router.get('/admin', { buscar: search.value || undefined }, { preserveState: true, replace: true }); }
async function perform() { if (!confirm.value) return; const endpoint = confirm.value.kind === 'delete' ? '/admin/eliminar' : '/admin/toggle'; const result = await submit(endpoint, { id: confirm.value.user.id }); if (result?.success) { confirm.value = null; router.reload({ only: ['users'] }); } }
</script>
<template>
 <AppLayout title="Usuarios" subtitle="Administra las cuentas de acceso y los roles de la plataforma.">
  <template #actions><button class="btn-primary" @click="open()"><PlusIcon class="h-4 w-4" />Nuevo usuario</button></template>
  <p v-if="message" class="error">{{ message }}</p>
  <section class="card overflow-hidden"><div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5"><form class="flex gap-2" @submit.prevent="searchUsers"><input v-model="search" class="field" placeholder="Buscar por nombre o usuario" /><button class="btn-secondary">Buscar</button></form><span class="text-sm text-slate-500">{{ users?.total || 0 }} usuario(s)</span></div><div v-if="users?.data?.length" class="table-shell"><table><thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><tr v-for="user in users.data" :key="user.id"><td class="font-medium">{{ user.nombre }}</td><td>{{ user.usuario }}</td><td><span class="badge capitalize">{{ user.rol }}</span></td><td><span class="badge" :class="user.activo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ user.activo ? 'Activo' : 'Inactivo' }}</span></td><td><div class="flex gap-3"><button class="text-escall-600" title="Editar" @click="open(user)"><PencilSquareIcon class="h-5 w-5" /></button><button class="text-amber-600" :title="user.activo ? 'Desactivar' : 'Activar'" @click="confirm={kind:'toggle',user}"><PowerIcon class="h-5 w-5" /></button><button class="text-red-600" title="Eliminar" @click="confirm={kind:'delete',user}"><TrashIcon class="h-5 w-5" /></button></div></td></tr></tbody></table></div><EmptyState v-else title="No hay usuarios" description="Crea la primera cuenta para comenzar." /><Pagination :paginator="users" /></section>
  <AppModal :open="showForm" :title="editing ? 'Editar usuario' : 'Nuevo usuario'" @close="showForm=false"><form class="space-y-4" @submit.prevent="save"><FormField id="nombre" label="Nombre" :error="form.errors.nombre" required><input id="nombre" v-model="form.nombre" class="field" required /></FormField><FormField id="usuario" label="Usuario" :error="form.errors.usuario" required><input id="usuario" v-model="form.usuario" class="field" required /></FormField><FormField id="rol" label="Rol" :error="form.errors.rol" required><select id="rol" v-model="form.rol" class="field"><option value="asesor">Asesor</option><option value="supervisor">Supervisor</option><option value="administrador">Administrador</option></select></FormField><FormField id="contrasena" :label="editing ? 'Nueva contraseña (opcional)' : 'Contraseña'" :error="form.errors.contrasena" :required="!editing"><input id="contrasena" v-model="form.contrasena" class="field" type="password" :required="!editing" minlength="12" /></FormField><label class="flex items-center gap-2 text-sm"><input v-model="form.activo" type="checkbox" />Cuenta activa</label><div class="flex justify-end gap-3"><button type="button" class="btn-secondary" @click="showForm=false">Cancelar</button><button class="btn-primary" :disabled="form.processing">{{ form.processing ? 'Guardando…' : 'Guardar usuario' }}</button></div></form></AppModal>
  <ConfirmDialog :open="Boolean(confirm)" :busy="busy" :title="confirm?.kind === 'delete' ? 'Eliminar usuario' : 'Cambiar estado'" :message="confirm?.kind === 'delete' ? 'Esta acción elimina el usuario.' : 'La cuenta cambiará su estado de acceso.'" :confirm-label="confirm?.kind === 'delete' ? 'Eliminar' : 'Confirmar'" @close="confirm=null" @confirm="perform" />
 </AppLayout>
</template>
