<template>
  <AppLayout>
    <div class="grid grid-cols-3 gap-4">
      <!-- 🧠 Sección izquierda: búsqueda y carrito -->
      <div class="col-span-2 space-y-4">
        <medicine-search-autocomplete @presentation-selected="openPresentationModal" />
        <cart-table :initial-items="cartItems" @item-updated="fetchCartItems" />
      </div>

      <!-- 💳 Sección derecha: cliente y totales -->
      <div class="bg-white rounded-xl shadow-xl p-4 space-y-4">
        <n-form-item label="Cliente">
          <n-input
            v-model:value="cliente.nombre"
            placeholder="Nombre del cliente (opcional)"
          />
        </n-form-item>

        <n-form-item label="Tipo de venta">
          <n-select v-model:value="venta.tipo" :options="tipoVentaOptions" />
        </n-form-item>

        <n-form-item label="Método de pago">
          <n-select v-model:value="venta.metodo_pago" :options="metodoPagoOptions" />
        </n-form-item>

        <div class="text-right space-y-2">
          <div>Subtotal: ${{ subtotal.toFixed(2) }}</div>
          <div>IVA (12%): ${{ iva.toFixed(2) }}</div>
          <div class="text-xl font-bold">Total: ${{ total.toFixed(2) }}</div>
        </div>

        <n-button type="success" block @click="finalizarVenta">Finalizar Venta</n-button>
      </div>

      <!-- 🎯 Modal de presentaciones -->
      <n-modal
        v-model:show="showPresentations"
        title="Selecciona una presentación"
        @after-enter="focusCantidad"
      >
        <n-card>
          <n-space vertical>
            <n-radio-group v-model:value="presentationId">
              <n-space vertical>
                <n-radio
                  v-for="p in currentPresentations"
                  :key="p.presentation_id"
                  :value="p.presentation_id"
                >
                  {{ p.unit_type }} - {{ p.form_name }} - ${{ p.price }} - Stock:
                  {{ p.stock }}
                </n-radio>
              </n-space>
            </n-radio-group>

            <n-form-item label="Cantidad" class="mt-4">
              <n-input-number
                ref="cantidadInput"
                v-model:value="cantidad"
                :min="1"
                autofocus
              />
            </n-form-item>

            <n-button class="mt-2" type="primary" @click="agregarAlCarrito"
              >Agregar</n-button
            >
          </n-space>
        </n-card>
      </n-modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import {
  NInput,
  NSpace,
  NSelect,
  NFormItem,
  NButton,
  NModal,
  NRadioGroup,
  NRadio,
  NInputNumber,
  NCard,
} from "naive-ui";
import AppLayout from "@/layouts/AppLayout.vue";
import MedicineSearchAutocomplete from "./MedicineSearchAutocomplete.vue";
import CartTable from "./CartTable.vue";
import axios from "axios";
import message from "@/composables/useMessageGlobal";
const cartItems = ref([]);
const cliente = ref({ nombre: "" });
const venta = ref({ tipo: "contado", metodo_pago: "efectivo" });
const tipoVentaOptions = [
  { label: "Contado", value: "contado" },
  { label: "Crédito", value: "credito" },
];
const metodoPagoOptions = [
  { label: "Efectivo", value: "efectivo" },
  { label: "Tarjeta", value: "tarjeta" },
  { label: "Transferencia", value: "transferencia" },
];

const subtotal = computed(
  () =>
    // cartItems.value.reduce((acc, i) => acc + i.quantity * i.price, 0)
    0
);
const iva = computed(() => subtotal.value * 0.12);
const total = computed(() => subtotal.value + iva.value);

const fetchCartItems = async () => {
  console.log(route("purchases.cart.index"));
  const { data } = await axios.get(route("purchases.cart.index"));
  console.error(data);
  cartItems.value = data || [];
};
onMounted(fetchCartItems);

const showPresentations = ref(false);
const currentPresentations = ref([]);
const selectedMedicine = ref(null);
const presentationId = ref(null);
const cantidad = ref(1);
const cantidadInput = ref(null);
const focusCantidad = () => {
  setTimeout(() => {
    cantidadInput.value?.focus?.();
  }, 100);
};
const openPresentationModal = ({ medicine, presentations }) => {
  console.log(medicine, presentations);
  selectedMedicine.value = medicine;
  currentPresentations.value = presentations;
  presentationId.value = null;
  cantidad.value = 1;
  showPresentations.value = true;
};

const agregarAlCarrito = async () => {
  if (!presentationId.value || !cantidad.value) {
    message.warning("Selecciona presentación y cantidad");
    return;
  }

  try {
    await axios.post(route("purchases.cart.store"), {
      presentation_id: presentationId.value,
      quantity: cantidad.value,
    });
    await fetchCartItems();
    showPresentations.value = false;
    message.success("Agregado al carrito");
  } catch (err) {
    message.error("Error al agregar al carrito" + err);
  }
};

const finalizarVenta = async () => {
  try {
    await axios.post("/api/sales", {
      cliente: cliente.value,
      tipo: venta.value.tipo,
      metodo_pago: venta.value.metodo_pago,
    });
    cartItems.value = [];
    message.success("Venta realizada");
  } catch (e) {
    message.error("Error al finalizar venta");
  }
};
</script>
