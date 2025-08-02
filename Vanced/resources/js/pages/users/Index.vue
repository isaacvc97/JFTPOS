<script setup>
import axios from "axios";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import {
  NCard,
  NTable,
  NInput,
  NButton,
  NModal,
  NForm,
  NFormItem,
  NFlex,
  NSpace,
  NIcon,
} from "naive-ui";
import { Search, UserPen, Trash } from "lucide-vue-next";
import message from "@/composables/useMessageGlobal";
import dialog from "@/composables/useDialogGlobal";
import AppLayout from "@/layouts/AppLayout.vue";
import ButtonMenu from "./ButtonMenu.vue";

const props = defineProps({
  users: Array,
});

const search = ref("");
const modalVisible = ref(false);
const editMode = ref(false);
const clienteActual = ref({});
const errores = ref({});

// Filtro dinámico
const usersFiltrados = computed(() => {
  return props.users.filter(
    (c) =>
      (c.name || "").toLowerCase().includes(search.value.toLowerCase()) ||
      (c.identification || "").toLowerCase().includes(search.value.toLowerCase())
  );
});

// Abrir modal para crear o editar
function createClient() {
  clienteActual.value = { nombre: "", cedula: "", telefono: "", direccion: "" };
  errores.value = {};
  editMode.value = false;
  modalVisible.value = true;
}

function editClient(cliente) {
  clienteActual.value = { ...cliente };
  errores.value = {};
  editMode.value = true;
  modalVisible.value = true;
}

const reloadTable = () => {
  router.reload({ only: ["users"] });
};

// Guardar cliente
async function saveClient() {
  try {
    errores.value = {};
    const url = editMode.value ? `/users/${clienteActual.value.id}` : "/users";

    const metodo = editMode.value ? "post" : "put";

    const response = await axios[metodo](url, clienteActual.value);
    if (response?.status === 200) {
      message.success("Cliente guardado correctamente");
    } else {
      errores.value = response.errors;
      message.error("Error al guardar el cliente");
    }

    reloadTable();

    modalVisible.value = false;
  } catch (err) {
    if (err.response?.status === 422) {
      errores.value = err.response.data.errors;
    } else {
      message.error("Error al guardar el cliente");
    }
  }
}

function deleteUser(id) {
  dialog.error({
    title: "Confirmar",
    content: "Seguro desea eliminar el cliente?",
    positiveText: "Si, eliminar.",
    negativeText: "No, cancelar",
    draggable: true,
    onPositiveClick: async () => {
      const response = axios.delete(route("users.delete", id));

      if ((await response).status == 200) {
        message.success("Cliente eliminado correctamente");
        reloadTable();
      } else {
        message.error("Error al eliminar el cliente");
      }
    },
    onNegativeClick: () => {
      message.error("Accion cancelada.");
    },
  });
}
</script>

<template>
  <AppLayout>
    <n-card class="space-y-4" size="medium" title="Usuarios">
      <n-space vertical>
        <n-flex>
          <n-input
            v-model:value="search"
            size="large"
            placeholder="Buscar cliente por nombre o cedula"
            round
            clearable
          >
            <template #prefix>
              <n-icon :component="Search" />
            </template>
          </n-input>
          <!-- <n-button type="primary" @click="nuevoCliente">Nuevo cliente</n-button> -->
        </n-flex>

        <n-table :bordered="false" :single-line="false">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Cédula</th>
              <th>Teléfono</th>
              <th>Dirección</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="client in usersFiltrados" :key="client.id">
              <td>{{ client.name }}</td>
              <td>{{ client.identification }}</td>
              <td>{{ client.phone }}</td>
              <td>{{ client.address }}</td>
              <td>
                <n-button size="large" :bordered="false" @click="editClient(client)">
                  <template #icon>
                    <n-icon>
                      <UserPen />
                    </n-icon>
                  </template>
                </n-button>
              </td>
            </tr>
            <tr v-if="usersFiltrados.length === 0">
              <td colspan="5" class="text-center text-gray-500 py-4">
                No se encontraron clientes
              </td>
            </tr>
          </tbody>
        </n-table>
      </n-space>
    </n-card>
    <!-- Modal -->
    <n-modal
      v-model:show="modalVisible"
      preset="card"
      :title="editMode ? 'Editar cliente' : 'Nuevo cliente'"
      style="width: 500px; max-width: 90vw; border-radius: 10px"
    >
      <n-form :model="clienteActual" label-placement="top">
        <n-space vertical>
          <n-form-item
            label="Nombre"
            :feedback="errores.name?.[0]"
            :validation-status="errores.name ? 'error' : undefined"
          >
            <n-input v-model:value="clienteActual.name" />
          </n-form-item>

          <n-form-item
            label="Identificacion"
            :feedback="errores.identification?.[0]"
            :validation-status="errores.identification ? 'error' : undefined"
          >
            <n-input
              v-model:value="clienteActual.identification"
              placeholder="09999999999"
            />
          </n-form-item>

          <n-form-item
            label="Teléfono"
            :feedback="errores.phone?.[0]"
            :validation-status="errores.phone ? 'error' : undefined"
          >
            <n-input v-model:value="clienteActual.phone" />
          </n-form-item>

          <n-form-item
            label="Correo"
            :feedback="errores.email?.[0]"
            :validation-status="errores.email ? 'error' : undefined"
          >
            <n-input v-model:value="clienteActual.email" />
          </n-form-item>

          <n-form-item
            label="Dirección"
            :feedback="errores.address?.[0]"
            :validation-status="errores.address ? 'error' : undefined"
          >
            <n-input v-model:value="clienteActual.address" />
          </n-form-item>
        </n-space>

        <div class="flex justify-between space-x-4 mt-4">
          <n-button
            v-if="editMode"
            text
            type="error"
            class="hover:bg-red-600"
            @click="deleteUser(clienteActual.id)"
          >
            <template #icon>
              <n-icon size="15">
                <Trash />
              </n-icon>
            </template>
            <p class="text-sm">Eliminar</p>
          </n-button>

          <!-- <n-button @click="modalVisible = false">Cancelar</n-button> -->
          <n-button type="primary" @click="saveClient">
            {{ editMode ? "Actualizar" : "Guardar" }}
          </n-button>
        </div>
      </n-form>
    </n-modal>

    <!-- Menu -->
    <ButtonMenu @create="createClient" />
  </AppLayout>
</template>
