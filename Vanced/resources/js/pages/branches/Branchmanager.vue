<template>
  <AppLayout>
    <div class="p-4 space-y-6">
      <n-card title="Sucursal">
        <n-descriptions bordered label-placement="top">
          <n-descriptions-item label="Nombre">{{ branch.nombre }}</n-descriptions-item>
          <n-descriptions-item label="RUC">{{ branch.ruc }}</n-descriptions-item>
          <n-descriptions-item label="Teléfono">{{
            branch.telefono || "—"
          }}</n-descriptions-item>
          <n-descriptions-item label="Dirección">{{
            branch.direccion || "—"
          }}</n-descriptions-item>
        </n-descriptions>
      </n-card>

      <n-card title="Sucursal">
        <n-form :model="form" label-placement="top" ref="formRef">
          <n-form-item label="Nombre">
            <n-input v-model:value="form.nombre" />
          </n-form-item>

          <n-form-item label="RUC">
            <n-input v-model:value="form.ruc" />
          </n-form-item>

          <n-form-item label="Teléfono">
            <n-input v-model:value="form.telefono" />
          </n-form-item>

          <n-form-item label="Dirección">
            <n-input v-model:value="form.direccion" />
          </n-form-item>

          <n-button type="primary" @click="guardarCambios">Guardar cambios</n-button>
        </n-form>
      </n-card>

      <n-card title="Usuarios en la sucursal">
        <n-table :bordered="false" :single-line="false">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Email</th>
              <th>Rol</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>
                <n-select
                  v-if="canEditRole(user)"
                  :value="user.role"
                  :options="roleOptions"
                  size="small"
                  @update:value="(val) => cambiarRol(user.id, val)"
                />
                <span v-else>{{ user.role }}</span>
              </td>
              <td>
                <n-popconfirm
                  @positive-click="quitarUsuario(user.id)"
                  :disabled="!canRemove(user)"
                >
                  <template #trigger>
                    <n-button size="small" type="error" :disabled="!canRemove(user)"
                      >Quitar</n-button
                    >
                  </template>
                  ¿Deseas quitar este usuario de la sucursal?
                </n-popconfirm>
              </td>
            </tr>
          </tbody>
        </n-table>
      </n-card>

      <n-card title="Invitaciones enviadas">
        <n-table :bordered="false" :single-line="false">
          <thead>
            <tr>
              <th>Email</th>
              <th>Estado</th>
              <th>Enviado</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="inv in invitaciones" :key="inv.id">
              <td>{{ inv.email }}</td>
              <td>
                <n-tag :type="statusType(inv.estado)">{{ inv.estado }}</n-tag>
              </td>
              <td>{{ new Date(inv.created_at).toLocaleDateString() }}</td>
              <td>
                <n-button
                  v-if="inv.estado === 'pendiente'"
                  type="error"
                  size="small"
                  @click="cancelarInvitacion(inv.id)"
                  >Cancelar</n-button
                >
              </td>
            </tr>
          </tbody>
        </n-table>

        <div class="mt-4">
          <n-input-group>
            <n-input
              v-model:value="email"
              placeholder="Correo electrónico para invitar"
            />
            <n-button type="primary" @click="enviarInvitacion" :disabled="!email"
              >Enviar</n-button
            >
          </n-input-group>
        </div>
      </n-card>
    </div>
    <Inbox />
  </AppLayout>
</template>

<script setup>
import axios from "axios";
import { ref } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import {
  NCard,
  NTable,
  NTag,
  NButton,
  NInputGroup,
  NInput,
  NForm,
  NFormItem,
  NDescriptions,
  NDescriptionsItem,
  NSelect,
  NPopconfirm,
} from "naive-ui";
import AppLayout from "@/layouts/AppLayout.vue";
import Inbox from "../Inbox.vue";

const props = defineProps({
  branch: Object,
  users: Array,
  invitaciones: Array,
  role: String,
});

const page = usePage();
const email = ref("");
const form = ref({ ...props.branch });
const formRef = ref(null);

const roleOptions = [
  { label: "Administrador", value: "administrador" },
  { label: "Vendedor", value: "vendedor" },
];

function statusType(estado) {
  return (
    {
      pendiente: "warning",
      aceptada: "success",
      rechazada: "error",
    }[estado] || "default"
  );
}

function guardarCambios() {
  router.put("/branches/" + form.value.id, form.value);
}

function enviarInvitacion() {
  router.post(
    "/invitations",
    { email: email.value },
    {
      onSuccess: () => (email.value = ""),
    }
  );
}

function cancelarInvitacion(id) {
  axios.delete(`/invitations/${id}`);
}

function cambiarRol(userId, role) {
  router.post(`/branches/${userId}/roles`, { role });
}

function quitarUsuario(userId) {
  router.delete(`/branches/users/${userId}`);
}

function canEditRole(user) {
  return page.props.auth.user.id !== user.id && props.role === "administrador";
}

function canRemove(user) {
  return page.props.auth.user.id !== user.id && props.role === "administrador";
}
</script>
