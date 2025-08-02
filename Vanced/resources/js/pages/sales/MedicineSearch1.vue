<!-- BuscadorMedicamento.vue -->
<template>
  <div class="w-full space-y-2">
    <n-auto-complete
      v-model:value="searchQuery"
      :options="groupedOptions"
      placeholder="Buscar medicamento (por nombre o genérico)..."
      @select="seleccionarMedicamento"
      clearable
      filterable
    />

    <div v-if="medicamentoSeleccionado" class="space-y-2">
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

const emit = defineEmits(["select"]);

const searchQuery = ref("");
const searchResults = ref([]);
const medicamentoSeleccionado = ref(null);
const presentationSeleccionada = ref(null);
const cantidad = ref(1);
const cantidadInput = ref(null);

watch(searchQuery, async (q) => {
  if (q.length < 2) return;
  const { data } = await axios.get("/medicines/search", { params: { search: q } });
  searchResults.value = data;
});

const groupedOptions = computed(() => {
  return searchResults.value.flatMap((med) =>
    med.dosages.map((d) => ({
      label: `${med.name} (${med.generic_name}) - ${d.concentration}`,
      value: `${med.id}_${d.id}`,
      med,
      dosage: d,
    }))
  );
});

const presentationOptions = computed(() => {
  if (!medicamentoSeleccionado.value) return [];
  return medicamentoSeleccionado.value.dosage.presentations.map((p) => ({
    id: p.id,
    label: `${p.unit_type} x ${p.quantity} - $${p.price}`,
    data: p,
  }));
});

function seleccionarMedicamento(val) {
  const found = groupedOptions.value.find((opt) => opt.value === val);
  if (!found) return;
  medicamentoSeleccionado.value = found;

  // Auto-selecciona primera presentación
  const first = found.dosage.presentations[0];
  if (first) {
    presentationSeleccionada.value = first.id;
    nextTick(() => cantidadInput.value?.focus());
  }
}

function focusCantidad() {
  nextTick(() => cantidadInput.value?.focus());
}

function agregarAlCarrito() {
  const { med, dosage } = medicamentoSeleccionado.value;
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
    form_id: dosage.form.id,
    form_name: dosage.form.name,
    presentation_id: presentation.id,
    presentations: presentationOptions.value,
    unit_type: presentation.unit_type,
    quantity: presentation.quantity,
    price: parseFloat(presentation.price),
    stock: presentation.stock,
    descuento: 0,
    cantidad: cantidad.value,
    uid: `${med.id}_${dosage.id}_${presentation.id}_${Date.now()}`,
  });

  searchQuery.value = "";
  medicamentoSeleccionado.value = null;
  presentationSeleccionada.value = null;
  cantidad.value = 1;
  searchResults.value = [];
}
</script>
