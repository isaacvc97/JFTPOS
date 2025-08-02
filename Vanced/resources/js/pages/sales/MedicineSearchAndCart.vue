<template>
  <div class="p-4">
    <n-auto-complete
      v-model:value="searchQuery"
      :options="medicineOptions"
      :get-label="getLabel"
      @select="handleSelectMedicine"
      placeholder="Buscar medicamento..."
      clearable
      filterable
    />

    <n-data-table :columns="columns" :data="cartItems" :pagination="false" class="mt-4" />

    <PresentationSelectModal
      v-if="selectedMedicine"
      :medicine="selectedMedicine"
      @select="addToCart"
      @close="selectedMedicine = null"
    />
  </div>
</template>
<script setup>
import { h, ref, computed } from "vue";
import { NAutoComplete, NDataTable } from "naive-ui";
import PresentationSelectModal from "./PresentationSelectModal.vue";

// Datos obtenidos desde Laravel vía props o fetch
const medicines = ref([
  {
    id: 1,
    name: "Umbral",
    generic_name: "Paracetamol",
    dosages: [
      {
        id: 1,
        concentration: "500mg",
        form: { id: 1, name: "Tabletas" },
        presentations: [
          {
            id: 1,
            unit_type: "Fraccion",
            quantity: 1,
            price: "0.25",
            stock: 12,
            cost: "0.00",
          },
        ],
      },
      {
        id: 2,
        concentration: "250mg",
        form: { id: 2, name: "Capsula líquida" },
        presentations: [
          {
            id: 2,
            unit_type: "Fraccion",
            quantity: 1,
            price: "0.30",
            stock: 12,
            cost: "0.00",
          },
        ],
      },
    ],
  },
  // más medicamentos...
]);

const searchQuery = ref("");
const selectedMedicine = ref(null);
const cartItems = ref([]);

const medicineOptions = computed(() =>
  medicines.value.map((m) => ({
    label: `${m.name} (${m.generic_name})`,
    value: m.id,
    raw: m,
  }))
);

const getLabel = (option) => option.label;

function handleSelectMedicine(medicineId) {
  const med = medicineOptions.value.find((m) => m.value === medicineId)?.raw;
  if (med) selectedMedicine.value = med;
}

function addToCart(presentationItem) {
  const exists = cartItems.value.find(
    (i) => i.presentation.id === presentationItem.presentation.id
  );
  if (!exists) {
    cartItems.value.push({ ...presentationItem, quantity: 1 });
  }
  selectedMedicine.value = null;
}

const columns = [
  {
    title: "Medicamento",
    key: "name",
    render: (row) =>
      `${row.medicine.name} ${row.dosage.concentration} ${row.dosage.form.name}`,
  },
  { title: "Presentación", key: "presentation.unit_type" },
  {
    title: "Cantidad",
    key: "quantity",
    render: (row) =>
      h("input", {
        type: "number",
        value: row.quantity,
        class: "w-16 border rounded px-1",
        onInput: (e) => (row.quantity = parseInt(e.target.value)),
      }),
  },
  {
    title: "Precio",
    key: "presentation.price",
    render: (row) => `$${parseFloat(row.presentation.price).toFixed(2)}`,
  },
  {
    title: "Total",
    key: "total",
    render: (row) => `$${(row.quantity * parseFloat(row.presentation.price)).toFixed(2)}`,
  },
];
</script>
