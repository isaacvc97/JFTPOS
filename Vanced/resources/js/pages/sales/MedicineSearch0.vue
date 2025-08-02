<template>
  <div class="w-full space-y-2">
    <!-- Paso 1: Buscar medicamento -->
    <n-auto-complete
      v-model:value="searchQuery"
      :options="medicineOptions"
      placeholder="Buscar medicamento..."
      @select="seleccionarMedicamento"
      clearable
      filterable
    />

    <!-- Paso 2: Seleccionar dosificación + presentación -->
    <div v-if="medicamentoSeleccionado" class="space-y-2">
      <n-select
        v-model:value="presentacionSeleccionada"
        :options="presentacionCombinadaOptions"
        label-field="label"
        value-field="id"
        placeholder="Seleccionar presentación"
        @update:value="focusCantidad"
      />

      <!-- Cantidad -->
      <n-input-number
        v-if="presentacionSeleccionada"
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
const presentacionSeleccionada = ref(null);
const cantidad = ref(1);
const cantidadInput = ref(null);

// Opciones del autocompletado
const medicineOptions = computed(() =>
  searchResults.value.map((m) => ({
    label: `${m.name} (${m.generic_name})`,
    value: m.id,
  }))
);

// Combina dosificación + presentación en una sola lista plana
const presentacionCombinadaOptions = computed(() => {
  if (!medicamentoSeleccionado.value) return [];
  return medicamentoSeleccionado.value.dosages.flatMap((d) =>
    d.presentations.map((p) => ({
      id: p.id /* ${d.form.name} ${d.concentration} | */,
      label: ` ${p.unit_type} x ${p.quantity} - $${p.price}`,
      data: {
        dosage: d,
        presentation: p,
      },
    }))
  );
});

// Buscar medicamentos al escribir
watch(searchQuery, async (q) => {
  if (q.length < 2) return;
  const { data } = await axios.get("/medicines/search", {
    params: { search: q },
  });
  searchResults.value = data;
});

// Al seleccionar un medicamento, limpiar todo
function seleccionarMedicamento(id) {
  medicamentoSeleccionado.value = searchResults.value.find((m) => m.id === id);
  presentacionSeleccionada.value = null;
  cantidad.value = 1;
}

// Enfocar cantidad
function focusCantidad() {
  nextTick(() => cantidadInput.value?.focus());
}

// Emitir datos completos
function agregarAlCarrito() {
  const med = medicamentoSeleccionado.value;
  const seleccion = presentacionCombinadaOptions.value.find(
    (p) => p.id === presentacionSeleccionada.value
  );

  if (!med || !seleccion) return;

  const { dosage, presentation } = seleccion.data;

  emit("select", {
    medicine_id: med.id,
    medicine_name: med.name,
    generic_name: med.generic_name,
    dosage_id: dosage.id,
    concentration: dosage.concentration,
    form_id: null,
    form_name: dosage.form.name,
    presentation_id: presentation.id,
    presentation: presentacionCombinadaOptions.value.label,
    unit_type: presentation.unit_type,
    quantity: presentation.quantity,
    price: parseFloat(presentation.price),
    stock: presentation.stock,
    presentations: presentacionCombinadaOptions.value,
    cantidad: cantidad.value,
  });

  // Limpiar
  searchQuery.value = "";
  medicamentoSeleccionado.value = null;
  presentacionSeleccionada.value = null;
  cantidad.value = 1;
  searchResults.value = [];
}
</script>
