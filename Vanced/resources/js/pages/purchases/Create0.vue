<template>
  <AppLayout>
    <div class="flex">
      <!-- Zona principal -->
      <div class="w-3/4 p-4 space-y-4">
        <ProductSearch @select="agregarCompra" endpoint="/api/purchases/search" />

        <n-table :bordered="false" :single-line="false">
          <thead>
            <tr>
              <th>Medicamento</th>
              <th>Forma</th>
              <th>Concentración</th>
              <th>Presentación</th>
              <th>Cant.</th>
              <th>Costo</th>
              <th>Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in carrito" :key="item.presentation_id">
              <td>
                <strong>{{ item.medicine_name }}</strong>
                <p class="text-xs">{{ item.generic_name }}</p>
              </td>
              <td>{{ item.form_name }}</td>
              <td>{{ item.concentration }}</td>
              <td>{{ item.unit_type }} x {{ item.quantity }}</td>
              <td>
                <n-input-number
                  v-model:value="item.cantidad"
                  :min="1"
                  size="small"
                  @update:value="actualizarTotal(index)"
                />
              </td>
              <td>
                <n-input-number
                  v-model:value="item.cost"
                  :min="0"
                  size="small"
                  @update:value="actualizarTotal(index)"
                />
              </td>
              <td>${{ item.total.toFixed(2) }}</td>
              <td>
                <n-button
                  size="tiny"
                  tertiary
                  type="error"
                  @click="carrito.splice(index, 1)"
                  >Quitar</n-button
                >
              </td>
            </tr>
          </tbody>
        </n-table>
      </div>

      <!-- Sidebar -->
      <div class="w-1/4 p-4 border-l bg-gray-50 space-y-4">
        <n-input v-model:value="proveedor" placeholder="Proveedor" />
        <n-input v-model:value="numeroFactura" placeholder="Número de factura" />
        <n-date-picker v-model:value="fecha" type="date" clearable />

        <div class="mt-4">
          <p>
            Total compra: <strong>${{ totalCompra.toFixed(2) }}</strong>
          </p>
        </div>

        <div class="flex gap-2">
          <n-button type="error" @click="reset">Cancelar</n-button>
          <n-button type="primary" @click="procesarCompra">Registrar</n-button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { NTable, NInput, NInputNumber, NButton, NDatePicker } from "naive-ui";
import ProductSearch from "../sales/ProductSearch.vue";
import AppLayout from "@/layouts/AppLayout.vue";

const carrito = ref([]);
const proveedor = ref("");
const numeroFactura = ref("");
const fecha = ref(Date.now());

function agregarCompra(item) {
  if (carrito.value.find((i) => i.presentation_id === item.presentation_id)) return;
  carrito.value.push({ ...item, cantidad: 1, total: parseFloat(item.cost) });
}

function actualizarTotal(index) {
  const item = carrito.value[index];
  item.total = parseFloat(item.cost) * item.cantidad;
}

const totalCompra = computed(() => carrito.value.reduce((acc, i) => acc + i.total, 0));

function reset() {
  carrito.value = [];
  proveedor.value = "";
  numeroFactura.value = "";
}

function procesarCompra() {
  console.log("Compra registrada", {
    proveedor: proveedor.value,
    factura: numeroFactura.value,
    fecha: fecha.value,
    productos: carrito.value,
  });
  // Aquí puedes emitir o enviar por API
}
</script>
