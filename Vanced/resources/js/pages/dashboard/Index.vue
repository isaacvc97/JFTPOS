<template>
  <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: 'dashboard' }]">
    <div class="p-4 space-y-6">
      <!-- Sección: Totales -->
      <n-grid :cols="4" :x-gap="16" :y-gap="16" responsive="screen">
        <n-gi span="xs:12 sm:6 md:3">
          <n-card size="small" hoverable>
            <n-statistic label="Total Ventas Hoy" :value="totals.salesToday" prefix="$" />
          </n-card>
        </n-gi>
        <n-gi span="xs:12 sm:6 md:3">
          <n-card size="small" hoverable>
            <n-statistic
              label="Ingresos del Mes"
              :value="totals.monthIncome"
              prefix="$"
            />
          </n-card>
        </n-gi>
        <n-gi span="xs:12 sm:6 md:3">
          <n-card size="small" hoverable>
            <n-statistic label="Productos en Stock" :value="totals.productsInStock" />
          </n-card>
        </n-gi>
        <n-gi span="xs:12 sm:6 md:3">
          <n-card size="small" hoverable>
            <n-statistic label="Productos por Agotar" :value="totals.lowStockProducts" />
          </n-card>
        </n-gi>
      </n-grid>

      <!-- Sección: Cuentas por pagar y cobrar -->
      <n-grid :cols="2" :x-gap="16" responsive="screen">
        <n-gi span="xs:12 md:6">
          <n-card title="Cuentas por Cobrar" size="small">
            <n-progress
              type="line"
              :percentage="
                Math.round((cuentas.porCobrar.pagadas / cuentas.porCobrar.total) * 100)
              "
              :indicator-placement="'inside'"
              processing
            />
            <div class="text-sm text-gray-500 mt-2">
              ${{ cuentas.porCobrar.pagadas }} / ${{ cuentas.porCobrar.total }} cobradas
            </div>
          </n-card>
        </n-gi>
        <n-gi span="xs:12 md:6">
          <n-card title="Cuentas por Pagar" size="small">
            <n-progress
              type="line"
              status="error"
              :percentage="
                Math.round((cuentas.porPagar.pagadas / cuentas.porPagar.total) * 100)
              "
              :indicator-placement="'inside'"
            />
            <div class="text-sm text-gray-500 mt-2">
              ${{ cuentas.porPagar.pagadas }} / ${{ cuentas.porPagar.total }} pagadas
            </div>
          </n-card>
        </n-gi>
      </n-grid>

      <!-- Sección: Ventas por fecha -->
      <n-card title="Ventas Últimos 7 días">
        <n-scrollbar style="max-height: 300px">
          <n-data-table
            :columns="ventasColumns"
            :data="ventasSemana"
            size="small"
            :bordered="false"
          />
        </n-scrollbar>
      </n-card>

      <!-- Sección: Productos más vendidos -->
      <n-card title="Productos Más Vendidos">
        <n-data-table
          :columns="masVendidosColumns"
          :data="productosMasVendidos"
          size="small"
          :bordered="false"
        />
      </n-card>

      <!-- Sección: Productos con bajo stock -->
      <n-card title="Productos por Agotar">
        <n-data-table
          :columns="porAgotarColumns"
          :data="productosPorAgotar"
          size="small"
          :bordered="false"
        />
      </n-card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import {
  NCard,
  NStatistic,
  NGrid,
  NGi,
  NProgress,
  NDataTable,
  NScrollbar,
} from "naive-ui";
import AppLayout from "@/layouts/AppLayout.vue";

// Datos simulados (puedes reemplazar con API)
const totals = ref({
  salesToday: 283.5,
  monthIncome: 4572.1,
  productsInStock: 853,
  lowStockProducts: 12,
});

const cuentas = ref({
  porCobrar: {
    total: 1000,
    pagadas: 600,
  },
  porPagar: {
    total: 850,
    pagadas: 400,
  },
});

const ventasSemana = ref([
  { fecha: "2025-07-15", total: 245 },
  { fecha: "2025-07-16", total: 310 },
  { fecha: "2025-07-17", total: 275 },
  { fecha: "2025-07-18", total: 350 },
  { fecha: "2025-07-19", total: 420 },
  { fecha: "2025-07-20", total: 150 },
  { fecha: "2025-07-21", total: 283.5 },
]);

const productosMasVendidos = ref([
  { name: "Paracetamol 500mg", cantidad: 120 },
  { name: "Ibuprofeno 400mg", cantidad: 90 },
  { name: "Omeprazol 20mg", cantidad: 78 },
]);

const productosPorAgotar = ref([
  { name: "Salbutamol inhalador", stock: 3 },
  { name: "Amoxicilina 500mg", stock: 5 },
]);

const ventasColumns = [
  { title: "Fecha", key: "fecha" },
  { title: "Total", key: "total", render: (row) => `$${row.total}` },
];

const masVendidosColumns = [
  { title: "Producto", key: "name" },
  { title: "Vendidos", key: "cantidad" },
];

const porAgotarColumns = [
  { title: "Producto", key: "name" },
  { title: "Stock", key: "stock" },
];
</script>
