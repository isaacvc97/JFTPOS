<template>
  <n-modal v-model:show="isOpen" transform-origin="center">
    <n-card
      style="width: 600px"
      :title="title || 'Presentaciones'"
      :bordered="true"
      size="huge"
      role="dialog"
      aria-modal="true"
    >
      <template #header-extra>
      </template>
      
      <template>
        <n-data-table :columns="columns" :data="data" />
      </template>
      <div class="flex flex-wrap gap-4 p-4">
        <!-- Presentations create -->
        <div>
        </div>
        <!-- Presentations list -->
        <!-- <div class="w-full max-w-5xl bg-white dark:text-gray-200 dark:bg-gray-800 rounded-xl shadow-md p-6">
          <table class="w-full table-auto">
            <thead>
              <tr>
                <th class="px-4 py-2 text-left">ID</th>
                <th class="px-4 py-2 text-left">Nombre</th>
                <th class="px-4 py-2 text-left">Precio</th>
                <th class="px-4 py-2 text-left">Stock</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="presentation in presentations" :key="presentation.id">
                <td class="border px-4 py-2">{{ presentation.id }}</td>
                <td class="border px-4 py-2">{{ presentation.name }}</td>
                <td class="border px-4 py-2">{{ presentation.price }}</td>
                <td class="border px-4 py-2">
                  <span :class="presentation.stock < 0 ? 'text-red-500' : 'text-gray-800 dark:text-gray-100'">
                    {{ presentation.stock }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
          </div>   -->
      </div>
    
      <template #footer>
        <n-Button @click="emits('update:show', false);" type="default">
          <span class="text-gray-500">Cerrar</span>
        </n-Button>
        <n-Button type="primary">
          <span class="text-white">Guardar</span>
        </n-Button>
      </template>
    </n-card>
  </n-modal> 
</template>
<script lang="ts" setup>
import { ref, watch, onMounted} from 'vue';
import { NButton, NModal, NCard, NDataTable } from "naive-ui";
import axios from 'axios';
import { Presentation } from './medicine';

const props = defineProps<{
  title?: string;
  show: boolean;
}>();

const emits = defineEmits<{
  (e: 'update:show', value: boolean): void;
}>();

const isOpen = ref(props.show);

// Si props.show cambia desde el padre, sincroniza isOpen
watch(() => props.show, (newVal) => {
  isOpen.value = newVal;
});

// Si isOpen cambia (por ej., se cierra el modal), emite al padre
watch(isOpen, (newVal) => {
  emits('update:show', newVal);
});

const createColumns = () => {
  return [
    {
      title: 'id',
      key: 'id'
    },
    {
      title: 'Nombre',
      key: 'unit_type'
    },
    {
      title: 'Action',
      key: 'actions'
    }
  ]
}
const columns = createColumns();
const data = ref<Presentation[]>([]);

async function fetchPresentations() {
  // Simulate fetching presentations from an API or store
  const response = await axios.get('/api/medicine-presentations');
  if (response.status === 200) {
    data.value = response.data;
  } else {
    console.error('Error fetching presentations:', response.statusText);
  }
}


onMounted(() => {
  // Fetch presentations from API or store
  fetchPresentations();
});
</script>