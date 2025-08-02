<template>
  <AppLayout>
    <n-card title="Mis sucursales" class="p-4">
      <n-space justify="space-between" align="center">
        <n-button @click="showModal = true" type="primary">+ Crear sucursal</n-button>
      </n-space>

      <n-list class="mt-4">
        <n-list-item v-for="branch in branches" :key="branch.id" :show-divider="true">
          <n-thing :title="branch.nombre" :description="branch.ruc">
            <template #header-extra>
              <n-tag @click="router.get(route('branch.show', branch.id))">{{
                branch.direccion
              }}</n-tag>
              <n-button size="small" @click="openInvite(branch)">Invitar</n-button>
            </template>
          </n-thing>
        </n-list-item>
      </n-list>

      <n-modal v-model:show="showModal">
        <n-card title="Nueva Sucursal" closable @close="showModal = false">
          <n-form :model="form">
            <n-form-item label="Nombre"
              ><n-input v-model:value="form.nombre"
            /></n-form-item>
            <n-form-item label="RUC"><n-input v-model:value="form.ruc" /></n-form-item>
            <n-form-item label="Teléfono"
              ><n-input v-model:value="form.telefono"
            /></n-form-item>
            <n-form-item label="Dirección"
              ><n-input v-model:value="form.direccion"
            /></n-form-item>
          </n-form>
          <template #footer>
            <n-space justify="end">
              <n-button @click="crearSucursal" type="primary">Guardar</n-button>
            </n-space>
          </template>
        </n-card>
      </n-modal>

      <n-modal v-model:show="showInvite">
        <n-card title="Invitar usuario a {{ selectedBranch?.nombre }}">
          <n-input v-model:value="inviteEmail" placeholder="Correo electrónico" />
          <n-select v-model:value="inviteRole" :options="roleOptions" class="mt-2" />
          <template #footer>
            <n-space justify="end">
              <n-button type="primary" @click="enviarInvitacion">Enviar</n-button>
            </n-space>
          </template>
        </n-card>
      </n-modal>
    </n-card>
    <Inbox></Inbox>
  </AppLayout>
</template>

<script setup>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import {
  NCard,
  NModal,
  NInput,
  NSelect,
  NButton,
  NList,
  NListItem,
  NForm,
  NFormItem,
  NTag,
  NThing,
  NSpace,
} from "naive-ui";
import { useMessageGlobal } from "@/composables/useMessageGlobal";
import AppLayout from "@/layouts/AppLayout.vue";
import Inbox from "../Inbox.vue";

// interface Branch {
//   id: number;
//   nombre: string;
//   direccion: string;
//   usuarios: number;
//   telefono: string;
//   email: string;
// }

// interface Props {
//   branches: any[]
// }
// defineProps<Props>();

defineProps({
  branches: Object,
});

const showModal = ref(false);
const showInvite = ref(false);
const inviteEmail = ref("");
const inviteRole = ref("vendedor");
const selectedBranch = ref(null);
// const branches = ref([]);
const form = ref({ nombre: "", ruc: "", telefono: "", direccion: "" });
const message = useMessageGlobal();

const roleOptions = [
  { label: "Administrador", value: "administrador" },
  { label: "Vendedor", value: "vendedor" },
];

function getBranches() {
  router.reload({ only: ["branches"] });
  // axios.get("/branches").then((res) => (branches.value = res.data));
}

function crearSucursal() {
  axios.post("/branches", form.value).then(() => {
    message.success("Sucursal creada");
    showModal.value = false;
    getBranches();
  });
}

function openInvite(branch) {
  selectedBranch.value = branch;
  showInvite.value = true;
}

function enviarInvitacion() {
  axios
    .post("/invitations", {
      branch_id: selectedBranch.value.id,
      email: inviteEmail.value,
      role: inviteRole.value,
    })
    .then(() => {
      message.success("Invitación enviada");
      showInvite.value = false;
      inviteEmail.value = "";
    });
}

// onMounted(() => getBranches());
</script>

<style scoped>
.mt-4 {
  margin-top: 1rem;
}
</style>
