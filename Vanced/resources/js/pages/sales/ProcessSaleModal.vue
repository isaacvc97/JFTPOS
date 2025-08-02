<template>
  <n-modal
    v-model:show="visible"
    preset="dialog"
    title="Procesar Venta"
    :mask-closable="false"
  >
    <div class="space-y-4">
      <div class="flex justify-between">
        <div>Total a pagar:</div>
        <div class="font-semibold text-green-600">$ {{ totalVenta.toFixed(2) }}</div>
      </div>

      <div class="flex justify-between gap-4">
        <n-input-number
          v-model:value="pagoCliente"
          placeholder="Monto recibido"
          :min="0"
          class="w-full"
          @keydown.enter="confirmarVenta"
          :status="pagoCliente < totalVenta ? 'error' : undefined"
        />
        <n-button tertiary type="success" @click="pagoCliente = totalVenta">
          Exacto
        </n-button>
      </div>

      <div v-if="pagoCliente >= totalVenta" class="text-right text-sm text-gray-500">
        Cambio:
        <span class="font-semibold">$ {{ (pagoCliente - totalVenta).toFixed(2) }}</span>
      </div>
    </div>

    <template #action>
      <n-button @click="visible = false">Cancelar</n-button>
      <n-button
        type="primary"
        :disabled="pagoCliente < totalVenta"
        @click="confirmarVenta"
        >Confirmar</n-button
      >
    </template>
  </n-modal>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { NModal, NButton, NInputNumber } from "naive-ui";

const props = defineProps({
  show: Boolean,
  total: Number,
  cartItems: Array,
});
const emit = defineEmits(["close", "confirm"]);

const visible = ref(props.show);
watch(
  () => props.show,
  (v) => (visible.value = v)
);
watch(
  () => visible.value,
  (v) => !v && emit("close")
);

const totalVenta = computed(() => props.total || 0);
const pagoCliente = ref(0);

function confirmarVenta() {
  emit("confirm", {
    total: totalVenta.value,
    pago: pagoCliente.value,
    cambio: pagoCliente.value - totalVenta.value,
    items: props.cartItems,
  });
  visible.value = false;
}
</script>
