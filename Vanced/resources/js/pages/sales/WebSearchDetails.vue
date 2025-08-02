<template>
  <n-modal
    v-model:show="showModal"
    title="Detalles del Producto"
    preset="card"
    style="max-width: 600px; border-radius: 10px"
    :mask-closable="false"
    :close-on-esc="true"
    @close="emit('cerrar')"
  >
    <template #header>
      <div class="flex justify-between items-center w-full">
        <span class="text-base font-semibold text-gray-900"> Detalles del Producto </span>
        <n-button text size="large" @click="emit('cerrar')"> × </n-button>
      </div>
    </template>

    <div class="text-xs xtext-gray-400 mb-3 italic">
      Vista basada en: {{ estructura }}
    </div>

    <n-scrollbar style="max-height: 60vh">
      <div v-if="bloques.length" class="space-y-4">
        <div v-for="(bloque, i) in bloques.details" :key="i" class="border-b pb-3">
          <div
            class="font-semibold"
            :class="estructura === 'estructura1' ? 'text-base' : 'text-sm'"
          >
            {{ bloque.titulo }}
          </div>
          <div
            class="xtext-gray-600 mt-1 whitespace-pre-line"
            :class="estructura === 'estructura1' ? 'text-md' : 'text-sm'"
          >
            {{ bloque.texto || "—" }}
          </div>
        </div>
      </div>

      <n-card
        v-else
        :title="data.name"
        size="large"
        class="max-w-2xl mx-auto"
        bordered
        hoverable
      >
        <n-space vertical size="small">
          <n-tag type="info" size="small" round>SKU: {{ data?.sku }}</n-tag>
          <n-tag type="success" size="small" round>Marca: {{ data?.brand }}</n-tag>
          <n-tag type="warning" size="small" round>Categoria: {{ data?.category }}</n-tag>

          <n-divider />

          <n-text strong style="font-size: 18px">
            Precio: <span class="text-green-600">{{ data?.price }}</span>
          </n-text>

          <n-divider>Detalles</n-divider>

          <n-collapse accordion>
            <n-collapse-item
              v-for="(valor, clave) in data.details"
              :key="clave"
              :title="clave"
            >
              <n-p>{{ valor }}</n-p>
            </n-collapse-item>
          </n-collapse>
        </n-space>
      </n-card>
    </n-scrollbar>

    <!-- <CrearMedicamentoDesdeBloques
      :bloques="bloques"
      :laboratorios="laboratorioOptions"
      :formasFarmaceuticas="formasOptions"
      @crear="guardarMedicamento"
    /> -->
    <template #footer>
      <div class="text-right">
        <n-button type="primary" ghost @click="emit('cerrar')">Cerrar</n-button>
      </div>
    </template>
  </n-modal>
</template>

<script setup>
import { ref, watch } from "vue";
import {
  NCard,
  NSpace,
  NDivider,
  NModal,
  NButton,
  NCollapse,
  NCollapseItem,
  NTag,
  NP,
  NText,
  NScrollbar,
} from "naive-ui";
// import CrearMedicamentoDesdeBloques from "./CrearMedicamentoDesdeBloques.vue";

const props = defineProps({
  show: Boolean,
  data: Object,
  estructura: String,
});

const emit = defineEmits(["cerrar", "update:show"]);

const showModal = ref(props.show);
const bloques = ref([]);

watch(
  () => props.show,
  (val) => {
    showModal.value = val;
  }
);

watch(showModal, (val) => {
  emit("update:show", val);
  if (!val) emit("cerrar");
});

watch(
  () => props.data,
  (val) => {
    bloques.value = val || [];
  },
  { immediate: true }
);
</script>
