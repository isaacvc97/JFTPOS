<template>
  <n-modal
    v-model:show="modalVisible"
    preset="card"
    :title="clienteActual.id ? 'Editar cliente' : 'Nuevo cliente'"
    style="width: 500px; max-width: 90vw; border-radius: 10px"
    :close-on-esc="true"
    @close="modalVisible = false"
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
          <n-input :update-value="clienteActual.phone" />
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
          v-if="clienteActual.id"
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
          {{ clienteActual.id ? "Actualizar" : "Guardar" }}
        </n-button>
      </div>
    </n-form>
  </n-modal>
</template>
<script setup lang="ts">
import axios from "axios";
import { ref, watch } from "vue";
import { NInput, NButton, NModal, NForm, NFormItem, NSpace, NIcon } from "naive-ui";
import { Trash } from "lucide-vue-next";
import dialog from "@/composables/useDialogGlobal";
import { useMessageGlobal } from "@/composables/useMessageGlobal";
import { emptyClient, type Client } from "../sales/sale";

interface Props {
  client: Client;
}

const props = defineProps<Props>();

const message = useMessageGlobal();

const wasUpdate = ref(false);
const modalVisible = ref(false);
const clienteActual = ref<Client>(props.client);
const errores = ref(emptyClient());

watch(
  () => props.client,
  (newClient) => {
    clienteActual.value = newClient;
  }
)


watch(clienteActual, (newValue, oldValue) => {
  if(
    newValue.id &&
    oldValue.id &&
    newValue.id !== oldValue.id
  ) return wasUpdate.value = false;

  // console.log('Object has been modified!');
  // console.log('New value:', newValue);
  // console.log('Old value:', oldValue);
  wasUpdate.value = true;
}, { deep: true });

async function saveClient() {
  try {

    if(!wasUpdate.value) return modalVisible.value = false;

    const editMode = !!clienteActual.value.id;

    errores.value = emptyClient();
    const url = editMode ? `/clients/${clienteActual.value.id}` : "/clients";

    const metodo = editMode ? "put" : "post";

    const response = await axios[metodo](url, clienteActual.value);

    if (response.status === 200) {
      console.info("Create cliente: ", response);
      modalVisible.value = false;
      emit("update");
    }

    message.success("Cliente guardado correctamente");

    emit("update");

    modalVisible.value = false;
  } catch (err: any) {
    if (err.response?.status === 422) {
      errores.value = err.response.data.errors;
    } else {
      message.error("Error al guardar el cliente");
    }
  }
}

function deleteUser(id: number) {
  dialog.error({
    title: "Confirmar",
    content: "Seguro desea eliminar el cliente?",
    positiveText: "Si, eliminar.",
    negativeText: "No, cancelar",
    draggable: true,
    onPositiveClick: async () => {
      const response = axios.delete(route("clients.delete", id));

      if ((await response).status == 200) {
        message.success("Cliente eliminado correctamente");
        emit("update");
      } else {
        message.error("Error al eliminar el cliente");
      }
    },
    onNegativeClick: () => {
      message.error("Accion cancelada.");
    },
  });
}

const emit = defineEmits(["update" ,"close"]);
defineExpose({modalVisible})
</script>
