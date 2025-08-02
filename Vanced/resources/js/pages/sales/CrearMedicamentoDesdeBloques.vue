<template>
  <div class="grid md:grid-cols-2 gap-6">
    <!-- Sección izquierda: Transferencia de bloques -->
    <div>
      <n-transfer
        v-model:value="selectedKeys"
        :options="bloquesOptions"
        title="Seleccionar datos para el medicamento"
        virtual-scroll
      />
    </div>

    <!-- Sección derecha: Formulario generado -->
    <div v-if="formVisible" class="space-y-4">
      <n-input v-model:value="form.name" placeholder="Nombre comercial" label="Nombre" />
      <n-input
        v-model:value="form.generic_name"
        placeholder="Nombre genérico"
        label="Genérico"
      />
      <n-select
        v-model:value="form.laboratory_id"
        :options="laboratorios"
        placeholder="Selecciona laboratorio"
        label="Laboratorio"
      />
      <n-input
        type="textarea"
        v-model:value="form.description"
        placeholder="Descripción"
        label="Descripción"
      />

      <div v-if="form.dosages.length" class="space-y-4">
        <div v-for="(dosage, i) in form.dosages" :key="i" class="border p-3 rounded-md">
          <n-input
            v-model:value="dosage.concentration"
            placeholder="Concentración (ej: 50mg)"
          />
          <n-select
            v-model:value="dosage.medicine_form_id"
            :options="formasFarmaceuticas"
            placeholder="Forma farmacéutica"
          />

          <div
            v-for="(pres, j) in dosage.presentations"
            :key="j"
            class="grid grid-cols-2 gap-2 mt-2"
          >
            <n-input-number v-model:value="pres.quantity" placeholder="Cantidad" />
            <n-input
              v-model:value="pres.unit_type"
              placeholder="Tipo unidad (Caja, Fracción...)"
            />
            <n-input-number v-model:value="pres.cost" placeholder="Costo" :min="0" />
            <n-input-number v-model:value="pres.price" placeholder="Precio" :min="0" />
            <n-input v-model:value="pres.barcode" placeholder="Código de barras" />
          </div>
        </div>
      </div>

      <n-button type="primary" @click="emitMedicamento">Guardar medicamento</n-button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { NTransfer, NInput, NSelect, NButton, NInputNumber } from "naive-ui";

const props = defineProps({
  bloques: {
    type: Array,
    default: () => [],
  },
  laboratorios: Array, // ej: [{ label: 'Bayer', value: 1 }]
  formasFarmaceuticas: Array, // ej: [{ label: 'Tabletas', value: 1 }]
});
const emit = defineEmits(["crear"]);

const selectedKeys = ref([]);

// Mapear los bloques a opciones transferibles
const bloquesOptions = computed(() =>
  props.bloques?.map((b, i) => ({
    label: b.titulo,
    value: i,
  }))
);

const form = ref({
  name: "",
  generic_name: "",
  laboratory_id: null,
  description: "",
  dosages: [],
});

const formVisible = computed(() => selectedKeys.value.length > 0);

watch(selectedKeys, (keys) => {
  const seleccionados = keys.map((k) => props.bloques[k]);

  // Limpia campos anteriores
  form.value.name = "";
  form.value.generic_name = "";
  form.value.description = "";
  form.value.dosages = [];

  // Mapear según el título (puedes adaptar a tus títulos reales)
  for (const bloque of seleccionados) {
    const titulo = bloque.titulo.toLowerCase();

    if (titulo.includes("nombre comercial")) form.value.name = bloque.texto;
    else if (titulo.includes("genérico")) form.value.generic_name = bloque.texto;
    else if (titulo.includes("descripción") || titulo.includes("uso"))
      form.value.description = bloque.texto;
    else if (titulo.includes("concentración") || titulo.includes("presentación")) {
      form.value.dosages.push({
        concentration: bloque.texto,
        medicine_form_id: null,
        form: null,
        presentations: [
          {
            quantity: 1,
            unit_type: "Caja",
            cost: null,
            price: null,
            stock: 0,
            barcode: null,
          },
        ],
      });
    }
  }
});

// Emitir medicamento completo
function emitMedicamento() {
  emit("crear", form.value);
}
</script>
