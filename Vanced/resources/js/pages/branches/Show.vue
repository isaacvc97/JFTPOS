<template>
  <AppLayout>
    <n-card :title="branch.nombre" class="p-4">
      <n-descriptions bordered column="1" size="small">
        <n-descriptions-item label="RUC">{{ branch.ruc }}</n-descriptions-item>
        <n-descriptions-item label="Teléfono">{{ branch.telefono }}</n-descriptions-item>
        <n-descriptions-item label="Dirección">{{
          branch.direccion
        }}</n-descriptions-item>
      </n-descriptions>

      <n-h2 class="mt-4">Usuarios activos</n-h2>
      <n-table :bordered="false" :single-line="false">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in branch.users" :key="user.id">
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>
              <n-select
                size="small"
                :value="user.pivot.role"
                :options="roles"
                @update:value="(val) => cambiarRol(user.id, val)"
              />
            </td>
            <td>
              <n-popconfirm @positive-click="quitarUsuario(user.id)">
                ¿Quitar usuario?
                <template #trigger>
                  <n-button size="tiny" type="error">Quitar</n-button>
                </template>
              </n-popconfirm>
            </td>
          </tr>
        </tbody>
      </n-table>

      <n-h2 class="mt-6">Invitaciones pendientes</n-h2>
      <n-table :bordered="false" :single-line="false">
        <thead>
          <tr>
            <th>Correo</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="inv in branch.invitaciones_pendientes" :key="inv.id">
            <td>{{ inv.email }}</td>
            <td>
              <n-tag type="warning">{{ inv.estado }}</n-tag>
            </td>
            <td>
              <n-button size="tiny" type="error" @click="cancelarInvitacion(inv.id)"
                >Cancelar</n-button
              >
            </td>
          </tr>
        </tbody>
      </n-table>
    </n-card>
  </AppLayout>
</template>

<script setup>
import axios from "axios";
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useMessageGlobal } from "@/composables/useMessageGlobal";
import {
  NCard,
  NDescriptions,
  NDescriptionsItem,
  NH2,
  NTable,
  NButton,
  NPopconfirm,
  NTag,
  NSelect,
} from "naive-ui";
import AppLayout from "@/layouts/AppLayout.vue";

const page = usePage();
const branch = ref(page.props.branch);
const message = useMessageGlobal();

const roles = [
  { label: "Administrador", value: "administrador" },
  { label: "Vendedor", value: "vendedor" },
];

function cambiarRol(userId, role) {
  axios.post(`/branches/${branch.value.id}/roles`, { user_id: userId, role }).then(() => {
    message.success("Rol actualizado");
  });
}

function quitarUsuario(userId) {
  axios.delete(`/branches/${branch.value.id}/users/${userId}`).then(() => {
    message.success("Usuario quitado");
    location.reload();
  });
}

function cancelarInvitacion(id) {
  axios.delete(`/invitations/${id}`).then(() => {
    message.success("Invitación cancelada");
    router.reload();
  });
}
</script>

<style scoped>
.mt-4 {
  margin-top: 1.5rem;
}
.mt-6 {
  margin-top: 2rem;
}
</style>
