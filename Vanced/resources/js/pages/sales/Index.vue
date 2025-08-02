<template>
  <AppLayout :breadcrumbs="[{ title: 'Ventas', url: '/sales' }]">
    <sale-detail-modal v-model:show="mostrarDetalle" :sale-id="ventaSeleccionadaId" />
    <n-space vertical class="p-4">
      <!-- Filtros -->
      <n-space vertical size="large" class="mb-4">
        <!-- Cliente -->
        <!-- <n-auto-complete
          v-model:value="filtros.cliente"
          :options="clients"
          label-field="name"
          value-field="id"
          placeholder="Buscar cliente..."
          @search="buscarClientes"
          size="large"
          clearable
        >
          <template #default="{ handleInput, handleBlur, handleFocus, value: slotValue }">
            <n-input
              :value="slotValue"
              placeholder="Buscar por cliente, nombre o cédula"
              @input="handleInput"
              @focus="handleFocus"
              @blur="handleBlur"
              size="large"
            >
              <template #prefix>
                <n-icon :component="Search" />
              </template>
            </n-input>
          </template>
        </n-auto-complete> -->

        <n-select
          v-model:value="filtros.client"
          placeholder="Busca por cliente..."
          @update:value="filter"
          label-field="name"
          value-field="id"
          :options="clients"
          filterable
        />
        <div class="grid grid-cols-2 sm:grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Estado -->
          <n-select
            v-model:value="filtros.state"
            :options="estadoOptions"
            @update:value="filter"
            placeholder="Estado de venta"
            clearable
          />

          <!-- Fecha -->
          <!-- <pre>{{ JSON.stringify(filtros.rangeDate) }}</pre> -->
          <n-date-picker
            v-model:formatted-value="filtros.rangeDate"
            @update:value="filter"
            value-format="yyyy-MM-dd"
            type="daterange"
            placeholder="Rango de fechas"
            start-placeholder="Desde"
            end-placeholder="Hasta"
            clearable
          />

          <!-- Producto (opcional/avanzado) -->
          <n-select
            v-model:value="filtros.product"
            placeholder="Filtrar por producto vendido..."
            @keyup.enter="searchProduct($event.target.value)"
            @update:value="filter"
            :options="productosFiltrados"
            label-field="label"
            value-field="value"
            filterable
          >
          </n-select>
          <!-- <n-auto-complete
            v-model:value="filtros.product"
            :options="productosFiltrados"
            placeholder="Filtrar por producto vendido..."
            :on-select="alert"
            clearable
          /> -->
        </div>
        <!-- Botones -->
        <div class="flex flex-wrap flex-end gap-2">
          <n-button @click="filtrarVentas" type="primary">Filtrar</n-button>
          <n-button
            @click="
              () => {
                filtros = { client: null, state: null, dateRange: null, product: null };
                filter();
              }
            "
            secondary
            >Limpiar</n-button
          >
        </div>
      </n-space>

      <!-- Tabla -->
      <n-data-table
        :columns="columns"
        :data="sales"
        :loading="loading"
        :pagination="false"
        size="small"
        striped
        xbordered="false"
        class="rounded-lg shadow-sm"
      />
    </n-space>
  </AppLayout>
  <ButtonMenu @create="createSale" />
</template>

<script setup>
import axios from "axios";
import { h, ref } from "vue";
import { router } from "@inertiajs/vue3";
import {
  NDatePicker,
  NSelect,
  NTime,
  NDataTable,
  NButton,
  NSpace,
  NIcon,
  NTag,
} from "naive-ui";
import AppLayout from "@/layouts/AppLayout.vue";
import ButtonMenu from "./ButtonMenu.vue";
import { Eye, FileUp, RefreshCcw } from "lucide-vue-next";
import SaleDetailModal from "./Show.vue";

defineProps(["sales", "clients"]);
// Estado
const loading = ref(false);

// Filtros
const filtros = ref({ client: null, state: null, dateRange: null, product: null });

const estadoOptions = [
  { label: "Todo", value: null },
  { label: "Procesada", value: "procesada" },
  { label: "Anulada", value: "anulada" },
  { label: "Pendiente", value: "pendiente" },
  { label: "Cancelada", value: "cancelada" },
];

// Clientes / Productos (autocomplete)
const productosFiltrados = ref([]);

const filter = () => {
  router.reload({
    data: { ...filtros.value },
    only: ["sales"],
    preserveUrl: false,
  });
};

// Buscar productos por nombre
async function searchProduct(query) {
  if (query.length < 2) return;
  const { data } = await axios.get(`medicines/search-flat/${query}`);

  console.log("Productos filtrados:", JSON.stringify(data));
  productosFiltrados.value = data.map((p) => ({
    label: p.name,
    value: p.presentation_id,
  }));
}

// Simular carga inicial
// onMounted(fetchVentas);

const mostrarDetalle = ref(false);
const ventaSeleccionadaId = ref(null);

function abrirDetalle(id) {
  ventaSeleccionadaId.value = id;
  mostrarDetalle.value = true;
}

function filtrarVentas() {}

function createSale() {
  router.get(route("sales.create"));
}
const badgeType = (status) => {
  switch (status) {
    case "Procesada":
      return "success";
    case "Anulada":
      return "error";
    case "Pendiente":
      return "warning";
    case "Cancelada":
      return "info";
    default:
      return "default";
  }
};

const sendSRI = async (id) => {
  try {
    const response = await axios.post(route("sales.sri", id));
    if (response.data.success) {
      alert("Factura enviada al SRI exitosamente.");
    } else {
      alert("Error al enviar la factura al SRI.");
    }

    router.reload({ only: ["sales"] });
  } catch (error) {
    console.error("Error al enviar la factura al SRI:", error);
    alert("Ocurrió un error al enviar la factura al SRI.");
  }
};

const checkSRI = async (accessKey) => {
  console.log("Verificando SRI para clave:", accessKey);
  if (!accessKey) return alert("Clave de acceso no válida.");

  try {
    const response = await axios.post(route("sales.authorization", accessKey));
    if (response.data.success) {
      alert("Factura enviada al SRI exitosamente.");
    } else {
      alert("Error al enviar la factura al SRI.");
    }
  } catch (error) {
    console.error("Error al enviar la factura al SRI:", error);
    alert("Ocurrió un error al enviar la factura al SRI.");
  }
};
// Columnas tabla
const columns = [
  {
    title: "Cliente",
    key: "cliente.name",
    render(row) {
      return row.client?.name?.toUpperCase() || "—";
    },
  },
  {
    title: "Vendedor",
    key: "user.name",
    render(row) {
      return row.user?.name || "—";
    },
  },
  {
    title: "Fecha",
    key: "created_at",
    render(row) {
      return h(
        NTime,
        {
          time: new Date(row.created_at),
          format: "MM/dd/yyyy HH:mm",
          style: { fontSize: "0.9em", xcolor: "#666" },
        },
        {
          default: () => row.created_at.toLocaleString(),
        }
      );
    },
  },
  {
    title: "Estado local",
    key: "status",
    render(row) {
      return h(
        NTag,
        {
          bordered: true,
          size: "small",
          type: badgeType(row.status),
        },
        {
          default: () => row.status,
        }
      );
      return row.estado?.charAt(0).toUpperCase() + row.estado?.slice(1);
    },
  },
  {
    title: "SRI",
    key: "status",
    render(row) {
      return h(
        NSpace,
        { vertical: false },
        {
          default: () => [
            h(
              NTag,
              {
                size: "small",
                bordered: true,
                type: badgeType(row.status),
              },
              {
                default: () => row.status,
              }
            ),
            h(
              NButton,
              {
                size: "small",
                xtype: "primary",
                tertiary: true,
                onClick: () => checkSRI(row.access_key),
                renderIcon: () => h(NIcon, { component: RefreshCcw, size: "16" }),
                disabled: row.sri_status === "Enviado",
              },
              {
                // default: () => row.sri_status === "Enviado" ? "Reenviar al SRI" : "Enviar al SRI",
              }
            ),
          ],
        }
      );
    },
  },
  {
    title: "Total",
    key: "total",
    render(row) {
      return `$${parseFloat(row.total).toFixed(2)}`;
    },
    align: "right",
  },
  {
    title: "Acciones",
    key: "actions",
    render(row) {
      return h(
        NSpace,
        { vertical: false, justify: "end" },
        {
          default: () => [
            h(
              NButton,
              {
                xtype: "primary",
                size: "small",
                tertiary: true,
                renderIcon: () => h(NIcon, { component: Eye }),
                onClick: () => abrirDetalle(row.id),
              },
              {
                // default: () => "Ver Detalles",
              }
            ),
            h(
              NButton,
              {
                xtype: "warning",
                size: "small",
                tertiary: true,
                renderIcon: () => h(NIcon, { component: FileUp }),
                onClick: () => sendSRI(row.id),
              },
              {
                // default: () => "Emitir Comprobante (SRI)",
              }
            ),
          ],
        }
      );
    },
    align: "right",
  },
  /* {
    title: "Productos",
    key: "productos",
    render(row) {
      return h(NEllipsis, null, {
        default: () => row.productos.map((p) => p.name).join(", "),
      });
    },
  }, */
];
</script>
