<template>
  <n-modal
    v-model:show="visible"
    :title="`Factura No. ${sale?.id}`"
    preset="card"
    style="width: 50%; max-width: 90dvw; border-radius: 10px"
  >
    <div v-if="loading" class="text-center py-8">
      <n-spin size="large" />
    </div>

    <div v-else class="space-y-4">
      <!-- Cliente -->
      <n-card size="small" title="Datos del Cliente">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
          <div><strong>Nombre:</strong> {{ sale.client?.name }}</div>
          <div><strong>Cédula:</strong> {{ sale.client.identification }}</div>
          <div><strong>Teléfono:</strong> {{ sale.client.phone }}</div>
          <div><strong>Dirección:</strong> {{ sale.client.address || "N/A" }}</div>
          <p class="block"><strong>Vendedor:</strong> {{ sale.seller?.name }}</p>
          <p class="block"><strong>Tipo de venta:</strong> {{ sale?.sale_type }}</p>
          <p class="block"><strong>Metodo de pago:</strong> {{ sale?.payment_type }}</p>
        </div>
      </n-card>

      <!-- Productos -->
      <n-space vertical>
        <n-data-table
          :columns="columns"
          :data="sale.items"
          :pagination="false"
          size="small"
          striped
          xbordered="false"
        />
        <!-- Totales -->
        <div>
          <div class="flex justify-between">
            <div class="text-sm text-gray-500">
              Procesada el: {{ new Date(sale.created_at).toLocaleString() }}
            </div>

            <div class="text-right space-y-1">
              <div><strong>Total:</strong> ${{ parseFloat(sale?.total).toFixed(2) }}</div>
              <div><strong>Pago:</strong> ${{ parseFloat(sale?.pago).toFixed(2) }}</div>
              <div>
                <strong>Cambio:</strong> ${{ parseFloat(sale?.cambio).toFixed(2) }}
              </div>
            </div>
          </div>
        </div>
      </n-space>
    </div>

    <template #action>
      <n-button @click="visible = false">Cerrar</n-button>
    </template>
  </n-modal>
</template>

<script setup>
import { ref, watch, h } from "vue";
import { NModal, NButton, NDataTable, NCard, NSpin, NSpace, NEllipsis } from "naive-ui";
import axios from "axios";

const props = defineProps({
  saleId: Number,
  show: Boolean,
});

const emit = defineEmits(["update:show"]);

const visible = ref(props.show);
const sale = ref(null);
const loading = ref(true);

watch(
  () => props.show,
  (val) => {
    visible.value = val;
    if (val) fetchSale();
  }
);

watch(visible, (val) => emit("update:show", val));

async function fetchSale() {
  loading.value = true;
  try {
    const { data } = await axios.get(route("sales.show", props.saleId));
    console.log("Venta obtenida:", data);
    sale.value = data;
  } catch (e) {
    console.error("Error al obtener la venta:", e);
  } finally {
    loading.value = false;
  }
}

const columns = [
  {
    title: "Producto",
    key: "medicine_name",
    render(row) {
      return h(
        NEllipsis,
        { style: { maxWidth: "300px" } },
        {
          default: () => `${row.medicine_name} ${row.concentration}`,
        }
      );
    },
  },
  {
    title: "Presentación",
    key: "form_name",
    render(row) {
      return h(
        NEllipsis,
        { style: { maxWidth: "300px" } },
        {
          default: () => `${row.form_name}`,
        }
      );
    },
  },
  {
    title: "Cantidad",
    key: "quantity",
    render(row) {
      return h(
        NEllipsis,
        { style: { maxWidth: "300px" } },
        {
          default: () => `${row.quantity} ${row.unit_type}`,
        }
      );
    },
    align: "right",
  },
  {
    title: "Precio",
    key: "price",
    align: "right",
    render(row) {
      return `$${parseFloat(row.price).toFixed(2)}`;
    },
  },
  {
    title: "Subtotal",
    key: "subtotal",
    align: "right",
    render(row) {
      return `$${parseFloat(row.subtotal).toFixed(2)}`;
    },
  },
];
</script>
