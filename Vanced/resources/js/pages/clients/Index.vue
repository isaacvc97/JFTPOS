<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { NCard, NTable, NInput, NButton, NFlex, NSpace, NIcon } from "naive-ui";
import { Search, UserPen } from "lucide-vue-next";
import AppLayout from "@/layouts/AppLayout.vue";
import ClientModal from "./Show.vue";
import ButtonMenu from "./ButtonMenu.vue";
import  { type Client, emptyClient } from "../sales/sale";

interface Props {
  clients: Client[];
}
const props = defineProps<Props>();

const search = ref("");
// const modalVisible = ref(false);
const editMode = ref(false);
const clienteActual = ref<Client>(emptyClient());
const errores = ref({});

// Filtro dinámico
const clientsFiltrados = computed(() => {
  return props.clients.filter(
    (c) =>
      (c.name || "").toLowerCase().includes(search.value.toLowerCase()) ||
      (c.identification || "").toLowerCase().includes(search.value.toLowerCase())
  );
});

const clientModalRef = ref()

// Abrir modal para crear o editar
function createClient() {
  clienteActual.value = emptyClient();
  errores.value = {};
  editMode.value = false;
  clientModalRef.value.modalVisible = true;
}

function updateClient(cliente: Client) {
  clienteActual.value = { ...cliente };
  errores.value = {};
  editMode.value = true;
  clientModalRef.value.modalVisible = true;
}

const reloadTable = () => {
  router.reload({ only: ["clients"] });
};

// Guardar cliente
</script>

<template>
  <AppLayout>
    <n-card xclass="space-y-4" size="medium" title="Clientes">
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
            <tr v-for="client in clientsFiltrados" :key="client.id">
              <td>{{ client.name }}</td>
              <td>{{ client.identification }}</td>
              <td>{{ client.phone }}</td>
              <td>{{ client.address }}</td>
              <td>
                <n-button size="large" :bordered="false" @click="updateClient(client)">
                  <template #icon>
                    <n-icon>
                      <UserPen />
                    </n-icon>
                  </template>
                </n-button>
              </td>
            </tr>
            <tr v-if="clientsFiltrados.length === 0">
              <td colspan="5" class="text-center text-gray-500 py-4">
                No se encontraron clientes
              </td>
            </tr>
          </tbody>
        </n-table>
      </n-space>
    </n-card>
    <!-- Modal -->
    <ClientModal
      :client="clienteActual"
      @update:table="() => reloadTable()"
      ref="clientModalRef"
    />

    <!-- Menu -->
    <ButtonMenu @create="createClient" />
  </AppLayout>
</template>
