<script setup lang="ts">
import axios from "axios";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import { NAutoComplete, NInput, NCard, NTag, useMessage, NIcon, NButton } from "naive-ui";
import {
  Search,
  Phone,
  MapPinHouseIcon,
  MailIcon,
  UserRoundPlus,
  UserRoundPen,
  UserRoundX,
} from "lucide-vue-next";
import clientModal from "@/pages/clients/Show.vue"
import debounce from "lodash/debounce";
import { emptyClient, type Client } from "./sale";

interface Props {
  client: Client;
}

const props = defineProps<Props>();

const emit = defineEmits(["select", "remove:client"]);

const input = ref("");
const options = ref([]);
const clientsMap = new Map();
const selectedClient = ref(props.client || null);
const message = useMessage();

const clientModalRef = ref();

const fetchClients = debounce(async (query) => {
  if (!query || query.length < 2) return;
  try {
    const { data } = await axios.get(route("clients.search", query), {
      params: { q: query },
    });

    options.value = data.map((client: Client) => {
      const key = String(client.id);
      clientsMap.set(key, client);
      return {
        label: `${client.name} - ${client.identification ?? "S/D"}`,
        value: key,
      };
    });
  } catch (e) {
    console.error("Error al buscar clientes", e);
    message.error("No se pudieron cargar los clientes.");
  }
}, 500);

function handleSelect(id: number) {
  const client = clientsMap.get(id);
  if (client) {
    selectedClient.value = client;
    emit("select", client.id);
  }
}
function handleCreate() {
  selectedClient.value = emptyClient()
  clientModalRef.value.modalVisible = true;

}
function handleUpdate() {
  clientModalRef.value.modalVisible = true;
}
function handleRemove() {
  selectedClient.value = emptyClient()
}
</script>

<template>
  <div class="space-y-4">
    <n-auto-complete
      v-model:value="input"
      :options="options"
      @update:value="fetchClients"
      @select="handleSelect"
      show-empty
      placeholder="Buscar cliente por nombre o cédula..."
      clearable
    >
      <template #default="{ handleInput, handleBlur, handleFocus, value: slotValue }">
        <n-input
          clearable
          round
          placeholder="Buscar cliente por nombre o cédula..."
          size="large"
          :value="slotValue"
          @input="handleInput"
          @focus="handleFocus"
          @blur="handleBlur"
        >
          <template #prefix>
            <n-icon :component="Search" />
          </template>
        </n-input>
      </template>
    </n-auto-complete>

    <n-card
      v-if="selectedClient"
      :title="selectedClient.name"
      size="small"
      xbordered="false"
      class="shadow-md"
    >
      <template #header-extra>
        <n-tag type="info">{{ selectedClient.identification ?? "S/D" }}</n-tag>
      </template>

      <div class="space-y-4">
        <!-- <div><strong>Nombre:</strong> {{ selectedClient.name }}</div> -->
        <div><n-icon :component="Phone" /> {{ selectedClient.phone || "N/A" }}</div>
        <div><n-icon :component="MailIcon" /> {{ selectedClient.email || "N/A" }}</div>
        <div>
          <n-icon :component="MapPinHouseIcon" /> {{ selectedClient.address || "N/A" }}
        </div>
      </div>

      <template #action>
        <div class="flex gap-2 flex-end">
          <NButton @click="handleCreate">
            <template #icon>
              <n-icon :component="UserRoundPlus" />
            </template>
          </NButton>
          <NButton @click="handleUpdate()">
            <template #icon>
              <n-icon :component="UserRoundPen" />
            </template>
          </NButton>
          <NButton @click="handleRemove">
            <template #icon>
              <n-icon :component="UserRoundX" />
            </template>
          </NButton>
        </div>
      </template>
    </n-card>
    <clientModal :client="selectedClient" ref="clientModalRef" />
  </div>
</template>
