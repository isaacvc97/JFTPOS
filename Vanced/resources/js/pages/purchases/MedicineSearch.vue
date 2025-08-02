<template>
  <div class="space-y-4">
    <n-input
      v-model:value="search"
      placeholder="Buscar medicamento..."
      @input="debouncedSearch"
      clearable
    />

    <n-data-table
      :columns="columns"
      :data="medicines"
      :loading="loading"
      :pagination="false"
    />

    <n-modal
      v-model:show="showDropdown"
      preset="card"
      title="Selecciona una presentación"
    >
      <div v-if="selectedPresentations.length">
        <n-form-item label="Presentación">
          <n-radio-group v-model:value="selectedPresentationId">
            <n-space vertical>
              <n-radio
                v-for="p in selectedPresentations"
                :key="p.presentation_id"
                :value="p.presentation_id"
              >
                {{ p.unit_type }} - ${{ p.price }} - Stock: {{ p.stock }}
              </n-radio>
            </n-space>
          </n-radio-group>
        </n-form-item>

        <n-form-item label="Cantidad">
          <n-input-number v-model:value="quantity" :min="1" />
        </n-form-item>

        <n-button type="primary" @click="addToCart">Agregar al carrito</n-button>
      </div>
      <div v-else>
        <n-spin> Cargando presentaciones... </n-spin>
      </div>
    </n-modal>
  </div>
</template>

<script setup>
import { ref, h } from "vue";
import {
  NInput,
  NDataTable,
  NModal,
  NRadioGroup,
  NRadio,
  NSpace,
  NButton,
  NSpin,
  NInputNumber,
  NFormItem,
} from "naive-ui";
import debounce from "lodash/debounce";
import axios from "axios";
import message from "@/composables/useMessageGlobal";

const emit = defineEmits(["item-added"]);

const search = ref("");
const medicines = ref([]);
const loading = ref(false);
const showDropdown = ref(false);
const selectedMedicine = ref(null);
const selectedPresentations = ref([]);
const selectedPresentationId = ref(null);
const quantity = ref(1);

const columns = [
  { title: "Medicamento", key: "medicine_name" },
  { title: "Genérico", key: "generic_name" },
  { title: "Concentración", key: "concentration" },
  { title: "Forma", key: "form_name" },
  { title: "Stock", key: "stock" },
  {
    title: "",
    key: "actions",
    render(row) {
      return h(
        NButton,
        {
          size: "small",
          onClick: () => openPresentations(row),
        },
        { default: () => "Presentaciones" }
      );
    },
  },
];

const debouncedSearch = debounce(async () => {
  if (!search.value.trim()) return;

  loading.value = true;
  try {
    const { data } = await axios.get(route("medicine.search"), {
      params: { search: search.value },
    });
    medicines.value = data;
  } catch (err) {
    message.error("Error al buscar medicamentos" + err);
  } finally {
    loading.value = false;
  }
}, 300);

const openPresentations = async (medicine) => {
  selectedMedicine.value = medicine;
  showDropdown.value = true;
  selectedPresentations.value = [];
  selectedPresentationId.value = null;
  quantity.value = 1;

  try {
    const { data } = await axios.get(`/medicines/${medicine.medicine_id}/presentations`);
    selectedPresentations.value = data;
  } catch (err) {
    message.error("No se pudieron cargar las presentaciones" + err);
  }
};

const addToCart = async () => {
  if (!selectedPresentationId.value || !quantity.value) {
    message.warning("Selecciona una presentación y cantidad");
    return;
  }

  try {
    const response = await axios.post("/api/cart/items", {
      presentation_id: selectedPresentationId.value,
      quantity: quantity.value,
    });

    emit("item-added", response.data);
    message.success("Producto agregado");
    showDropdown.value = false;
  } catch (err) {
    message.error("Error al agregar al carrito" + err);
  }
};
</script>
