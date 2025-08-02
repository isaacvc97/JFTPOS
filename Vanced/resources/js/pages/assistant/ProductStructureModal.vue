<template>
  <NModal
    v-model:show="show"
    class="w-full max-w-2xl"
    preset="card"
    title="📘 Guía para Crear Producto"
    :style="{ borderRadius: '1rem' }"
  >
    <div class="space-y-6 text-gray-800 dark:text-gray-100">
      <div class="text-base">
        Asegúrate de usar una estructura correcta para crear un producto.
      </div>

      <!-- Error general -->
      <NAlert
        v-if="validationError"
        type="error"
        class="text-sm"
        title="Error de validación"
      >
        {{ validationError }}
      </NAlert>

      <!-- Estructura esperada -->
      <pre class="bg-gray-50 dark:bg-slate-800 rounded-xl p-4 text-sm overflow-x-auto whitespace-pre text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-slate-600">
{
  "name": "<span :class="errorClass('name')">Ibuprofeno</span>",
  "generic_name": "<span :class="errorClass('generic_name')">Ibuprofeno</span>",
  "total_dosages": <span :class="errorClass('total_dosages')">1</span>,
  "dosages": [
    {
      "concentration": "<span :class="errorClass('dosages.0.concentration')">400mg</span>",
      "form": {
        "name": "<span :class="errorClass('dosages.0.form.name')">Tabletas</span>"
      },
      "presentations": [
        {
          "unit_type": "<span :class="errorClass('dosages.0.presentations.0.unit_type')">Fraccion</span>",
          "quantity": <span :class="errorClass('dosages.0.presentations.0.quantity')">1</span>,
          "price": "<span :class="errorClass('dosages.0.presentations.0.price')">0.00</span>",
          "stock": <span :class="errorClass('dosages.0.presentations.0.stock')">0</span>,
          "barcode": null
        }
      ]
    }
  ]
}
      </pre>

      <!-- Footer -->
      <div class="text-right">
        <NButton type="primary" ghost @click="show = false">Entendido</NButton>
      </div>
    </div>
  </NModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import { NModal, NAlert, NButton } from 'naive-ui';

const props = defineProps({
  modelValue: Boolean,
  validationError: String,
  errorFields: Array
});

const emit = defineEmits(['update:modelValue']);

const show = ref(props.modelValue);

watch(() => props.modelValue, val => (show.value = val));
watch(show, val => emit('update:modelValue', val));

// Función para marcar campos con error
const errorClass = (path) =>
  props.errorFields?.includes(path) ? 'text-red-500 font-semibold' : '';
</script>
