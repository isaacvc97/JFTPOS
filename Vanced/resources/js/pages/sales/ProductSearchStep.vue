<template>
  <div class="w-full space-y-2">
    <n-auto-complete
      v-model:value="searchQuery"
      :options="medicineOptions"
      placeholder="Buscar medicamento..."
      @select="seleccionarMedicamento"
      clearable
      filterable
    />

    <div v-if="medicamentoSeleccionado" class="space-y-2">
      <n-select
        v-model:value="dosageSeleccionado"
        :options="dosageOptions"
        label-field="label"
        value-field="id"
        placeholder="Seleccionar dosificación"
        @update:value="seleccionarDosage"
      />

      <n-select
        v-model:value="presentationSeleccionada"
        :options="presentationOptions"
        label-field="label"
        value-field="id"
        placeholder="Seleccionar presentación"
        @update:value="focusCantidad"
      />

      <n-input-number
        v-if="presentationSeleccionada"
        ref="cantidadInput"
        v-model:value="cantidad"
        :min="1"
        @keydown.enter="agregarAlCarrito"
        placeholder="Cantidad"
      />
    </div>
  </div>
</template>

<script setup>
import axios from "axios";
import { ref, computed, watch, nextTick } from "vue";
import { NAutoComplete, NSelect, NInputNumber } from "naive-ui";

const emit = defineEmits(["add"]);

const searchQuery = ref("");
const searchResults = ref([]);
const medicamentoSeleccionado = ref(null);
const dosageSeleccionado = ref(null);
const presentationSeleccionada = ref(null);
const cantidad = ref(1);

const cantidadInput = ref(null);

const medicineOptions = computed(() =>
  searchResults.value.map((m) => ({
    label: `${m.name} (${m.generic_name})`,
    value: m.id,
  }))
);

const dosageOptions = computed(() => {
  if (!medicamentoSeleccionado.value) return [];
  return medicamentoSeleccionado.value.dosages.map((d) => ({
    id: d.id,
    label: `${d.form.name} - ${d.concentration}`,
    data: d,
  }));
});

const presentationOptions = computed(() => {
  if (!dosageSeleccionado.value) return [];
  const d = dosageOptions.value.find((d) => d.id === dosageSeleccionado.value);
  return (
    d?.data.presentations.map((p) => ({
      id: p.id,
      label: `${p.unit_type} x ${p.quantity} - $${p.price}`,
      data: p,
    })) || []
  );
});

watch(searchQuery, async (q) => {
  if (q.length < 2) return;
  const { data } = await axios.get("/medicines/search", { params: { search: q } });
  searchResults.value = data;
});

function seleccionarMedicamento(id) {
  medicamentoSeleccionado.value = searchResults.value.find((m) => m.id === id);
  dosageSeleccionado.value = null;
  presentationSeleccionada.value = null;
  cantidad.value = 1;
}

function seleccionarDosage() {
  presentationSeleccionada.value = null;
  nextTick(() => {
    const el = document.querySelector(".n-base-select-option");
    if (el) el.focus();
  });
}

function focusCantidad() {
  nextTick(() => cantidadInput.value?.focus());
}

function agregarAlCarrito() {
  const med = medicamentoSeleccionado.value;
  const dosage = med.dosages.find((d) => d.id === dosageSeleccionado.value);
  const presentation = presentationOptions.value.find(
    (p) => p.id === presentationSeleccionada.value
  )?.data;

  if (!med || !dosage || !presentation) return;

  emit("select", {
    medicine_id: med.id,
    medicine_name: med.name,
    generic_name: med.generic_name,
    dosage_id: dosage.id,
    concentration: dosage.concentration,
    form_id: null,
    form_name: dosage.form.name,
    presentation_id: presentation.id,
    presentations: presentationOptions.value,
    unit_type: presentation.unit_type,
    quantity: presentation.quantity,
    price: parseFloat(presentation.price),
    stock: presentation.stock,
  });

  searchQuery.value = "";
  medicamentoSeleccionado.value = null;
  dosageSeleccionado.value = null;
  presentationSeleccionada.value = null;
  cantidad.value = 1;
  searchResults.value = [];
}
</script>
