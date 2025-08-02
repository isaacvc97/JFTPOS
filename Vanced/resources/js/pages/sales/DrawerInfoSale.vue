<template>
  <div>
    <NDrawer
      v-model:show="showSidebar"
      placement="right"
      width="400"
      :close-on-esc="true"
    >
      <NDrawerContent title="Detalles de la venta">
        <NSpace vertical>
          <ClientSearch
            :client="currentCart.client"
            @select="handleClientSelected"
            @remove:client="() => (currentCart.client = emptyClient())"
          />

          <NDivider />

          <NSelect
            v-model:value="currentCart.sale_type"
            :options="saleOptions"
            placeholder="Tipo de venta"
          />
          <NSelect
            v-model:value="currentCart.payment_type"
            :options="paymentOptions"
            placeholder="Forma de pago"
          />
          <NInput
            v-model:value="currentCart.note"
            placeholder="Observaciones (opcional)"
            type="textarea"
            :autosize="{ minRows: 2, maxRows: 4 }"
          />
        </NSpace>

        <NDivider />

        <NCard size="small" title="Resumen">
          <NSpace vertical>
            <div class="flex justify-between">
              <span>Subtotal:</span>
              <strong>${{ subtotal.toFixed(2) }}</strong>
            </div>
            <div class="flex justify-between" xv-if="ivaAmount > 0">
              <span>IVA 15%:</span>
              <strong>${{ ivaAmount.toFixed(2) }}</strong>
            </div>
            <div class="flex justify-between">
              <span>Descuento:</span>
              <n-input-number
                v-model:value="discount"
                :default-value="0"
                xparse="parseCurrency"
                xformat="formatCurrency"
                xsuffix="%"
                size="small"
                autosize
                round
                style="max-width: 50%"
              />
            </div>
            <div class="flex justify-between text-lg font-bold">
              <span>Total:</span>
              <span>${{ total.toFixed(2) }}</span>
            </div>
          </NSpace>
        </NCard>

        <div class="mt-4 flex justify-end gap-2">
          <NButton secondary>Cancelar</NButton>
          <NButton type="primary" @click="showConfirmModal = true"
            >Procesar venta</NButton
          >
        </div>
      </NDrawerContent>
    </NDrawer>

    <ConfirmSaleModal
      v-model:show="showConfirmModal"
      :total="total"
      :cart-items="currentCart.items"
      @close="showConfirmModal = false"
      @confirm="processSale"
    />
  </div>
</template>
<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { ref, watch, computed, reactive } from "vue";
import {
  NDrawer,
  NDrawerContent,
  NInputNumber,
  NInput,
  NCard,
  NButton,
  NSelect,
  NSpace,
  NDivider,
} from "naive-ui";
import { useMessageGlobal } from "@/composables/useMessageGlobal";
import ClientSearch from "./ClientSearch.vue";
import ConfirmSaleModal from "./ProcessSaleModal.vue";
import { Cart, emptyClient } from "./sale";

const message = useMessageGlobal();

interface Props {
  cart: Cart;
  open: boolean;
}

const props = defineProps<Props>();

const showSidebar = ref(false);
const showConfirmModal = ref(false);
const currentCart = reactive<Cart>({ ...props.cart });

const paymentOptions = [
  { label: "Efectivo", value: "Efectivo" },
  { label: "Tarjeta", value: "Tarjeta" },
  { label: "Transferencia", value: "Transferencia" },
];

const saleOptions = [
  { label: "CONTADO", value: "CONTADO" },
  { label: "Crédito", value: "Credito" },
];

const discount = ref(0);

const subtotal = computed(() => {
  return currentCart.items.reduce(
    (sum, i) => sum + (i.price || 0) * (i.quantity || 0),
    0
  );
});

const ivaAmount = computed(() => {
  return currentCart.items?.reduce((sum, i) => {
    if (i.iva) return sum + (i.price || 0) * (i.quantity || 0) * 0.15;
    return sum;
  }, 0);
});

const total = computed(() => {
  const discountFactor = 1 - (discount.value || 0) / 100;
  return (subtotal.value + ivaAmount.value) * discountFactor;
});

interface Amounts {
  total: number;
  pago: number;
  cambio: number;
}

const handleClientSelected = (id: number) => {
  console.info("Cliente seleccionado:", id);

  if (currentCart.client) currentCart.client.id = id;

  router.patch(
    route("sales.cart.client", currentCart.id),
    { client_id: id },
    {
      // only: ["currentCart"],
      onSuccess: (resp) => {
        console.log("Cliente actualizado en el carrito:", resp);
      },
      onError: (error) => {
        console.error("Error al actualizar cliente en el carrito:", error);
      },
    }
  );
  // clientName.value = id; // Aquí puedes ajustar cómo mostrar el nombre del cliente
  showSidebar.value = true; // Abrir el sidebar al seleccionar cliente
};

const processSale = (amounts: Amounts) => {
  console.info(currentCart);

  router.post(
    route("sales.store"),
    {
      cart_sale_id: currentCart.id,
      client_id: currentCart.client?.id || null,
      sale_type: currentCart.sale_type || 1,
      payment_type: currentCart.payment_type || 1,
      discount: discount.value,
      total: amounts.total,
      pago: amounts.pago,
      cambio: amounts.cambio,
    },
    {
      onSuccess: () => {
        message.success("Venta procesada correctamente");

        router.visit(route("sales.index"));
      },
      onError: (error) => {
        message.error("Error al procesar la venta", error);
      },
      // only: ["currentCart"],
    }
  );
};

watch(
  () => props.open,
  (newVal) => {
    showSidebar.value = newVal;
    if (!newVal) emit("close");
  }
);

const emit = defineEmits(["close"]);
</script>
