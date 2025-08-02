<template>
  <div class="p-4 space-y-4">
    <!-- Panel principal de resumen -->
    <n-card title="Resumen de caja" size="large" bordered class="rounded-2xl shadow-sm">
      <template #header-extra>
        <n-time :time="data.opened_at" />
      </template>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
        <n-statistic label="Ventas del Día" :value="summary.sales">
          <template #prefix>
            <n-icon :component="ShoppingCart" />
          </template>
        </n-statistic>
        <n-statistic label="Ingresos Extras" :value="summary.incomes">
          <template #prefix>
            <n-icon :component="ArrowDown" color="green" />
          </template>
        </n-statistic>

        <n-statistic label="Egresos / Gastos" :value="summary.expenses">
          <template #prefix>
            <n-icon :component="ArrowUp" color="red" />
          </template>
        </n-statistic>
      </div>
      <n-divider />

      <div class="flex justify-between gap-4">
        <div class="flex flex-col justify-around flex-2/3 gap-4">
          <n-progress
            type="line"
            indicator-placement="inside"
            xcolor="themeVars.errorColor"
            xrail-color="changeColor(themeVars.errorColor, { alpha: 0.2 })"
            :percentage="20"
          />
          <n-progress
            type="line"
            xcolor="themeVars.warningColor"
            xrail-color="changeColor(themeVars.warningColor, { alpha: 0.2 })"
            :percentage="20"
            xindicator-text-color="themeVars.warningColor"
          />
        </div>
        <div class="px-4">
          <n-progress type="dashboard" gap-position="bottom" :percentage="80" />
        </div>
      </div>
    </n-card>

    <n-card title="Caja del Día" class="shadow-md" hoverable>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <n-statistic label="Ventas del Día" :value="ventasDia" />
        <n-statistic label="Ingresos Extras" :value="ingresosExtras" />
        <n-statistic label="Gastos / Egresos" :value="gastos" />
        <n-statistic label="Total Caja" :value="totalCaja" />
      </div>
      <div class="mt-4">
        <n-progress
          type="line"
          :percentage="porcentajeObjetivo"
          indicator-placement="inside"
          processing
        />
      </div>
    </n-card>
    <!-- Lista de ingresos/egresos/gastos -->
    <n-card
      title="Movimientos del Día"
      size="small"
      bordered
      class="rounded-2xl shadow-sm"
    >
      <n-list bordered hoverable>
        <n-list-item v-for="(item, index) in movements" :key="index">
          <template #prefix>
            <n-tag :type="item.type === 'ingreso' ? 'success' : 'error'">
              {{ item.type }}
            </n-tag>
          </template>
          <div class="flex justify-between w-full">
            <div>{{ item.description }}</div>
            <div :class="item.type === 'ingreso' ? 'text-green-600' : 'text-red-600'">
              ${{ item.amount }}
            </div>
          </div>
        </n-list-item>
      </n-list>
    </n-card>

    <!-- Botones de acción -->
    <div class="flex justify-end gap-2">
      <n-button type="info" ghost @click="openDrawer = true">Finalizar Día</n-button>
      <n-button type="success" @click="registerMovement('ingreso')"
        >Registrar Ingreso</n-button
      >
      <n-button type="error" @click="registerMovement('egreso')"
        >Registrar Gasto</n-button
      >
    </div>

    <!-- Acciones -->
    <div class="flex gap-2">
      <n-button type="success" ghost @click="abrirCaja" v-if="!cajaAbierta"
        >Abrir Caja</n-button
      >
      <n-button type="error" ghost @click="mostrarCerrarDrawer" v-else
        >Finalizar Día</n-button
      >
      <n-button type="info" ghost @click="mostrarModalMovimientos"
        >Ingresos / Gastos</n-button
      >
    </div>

    <!-- Drawer para cierre de caja -->
    <n-drawer
      v-model:show="openDrawer"
      placement="right"
      width="360"
      Xmask-closable="false"
      :close-on-esc="true"
    >
      <n-drawer-content title="Finalizar Caja" closable>
        <n-space vertical>
          <n-statistic label="Ventas Totales" :value="summary.sales">
            <template #prefix>$</template>
          </n-statistic>
          <n-statistic label="Total Ingresos" :value="summary.incomes">
            <template #prefix>$</template>
          </n-statistic>
          <n-statistic label="Total Egresos" :value="summary.expenses">
            <template #prefix>$</template>
          </n-statistic>
          <n-statistic label="Caja Final" :value="summary.cash">
            <template #prefix>$</template>
          </n-statistic>
        </n-space>

        <n-divider />
        <n-button type="primary" block @click="finalizeDay">Cerrar Caja</n-button>
      </n-drawer-content>
    </n-drawer>

    <n-modal
      v-model:show="showModalMov"
      preset="dialog"
      title="Ingresos / Egresos"
      size="large"
    >
      <div class="space-y-2">
        <n-input-group>
          <n-input v-model:value="nuevoIngreso" placeholder="Valor ingreso" />
          <n-button type="success" ghost @click="agregarIngreso">+ Ingreso</n-button>
        </n-input-group>
        <n-input-group>
          <n-input v-model:value="nuevoGasto" placeholder="Valor gasto" />
          <n-button type="error" ghost @click="agregarGasto">+ Gasto</n-button>
        </n-input-group>
        <div class="mt-2 text-sm text-gray-500">
          Usa esto para registrar ingresos extras o egresos en el día.
        </div>
      </div>
    </n-modal>
  </div>
</template>
<script setup lang="ts">
import { computed, ref } from "vue";
import {
  NCard,
  NStatistic,
  NDrawer,
  NDrawerContent,
  NModal,
  NInput,
  NInputGroup,
  NButton,
  NIcon,
  NDivider,
  NTime,
} from "naive-ui";
import { ArrowDown, ArrowUp, ShoppingCart } from "lucide-vue-next";
import { Cashregister } from "./cashregister";

interface Props {
  cash: Cashregister;
}

defineProps<Props>();

const openDrawer = ref(false);
const openMovements = ref(false);

const nuevoIngreso = ref("");
const nuevoGasto = ref("");

function toggleDrawer() {
  openDrawer.value = !openDrawer.value;
}

function toggleMovementsModal() {
  openMovements.value = !openMovements.value;
}

function agregarIngreso() {
  const val = parseFloat(nuevoIngreso.value);
  if (!isNaN(val) && val > 0) {
    ingresosExtras.value += val;
    nuevoIngreso.value = "";
  }
}

function agregarGasto() {
  const val = parseFloat(nuevoGasto.value);
  if (!isNaN(val) && val > 0) {
    gastos.value += val;
    nuevoGasto.value = "";
  }
}
function registerMovement(type: "ingreso" | "egreso") {
  const description = prompt(`Descripción del ${type}`);
  const amount = parseFloat(prompt(`Monto del ${type}`) || "0");
  if (!description || isNaN(amount)) return;

  movements.value.push({ type, description, amount });

  if (type === "ingreso") {
    summary.value.incomes += amount;
    summary.value.cash += amount;
  } else {
    summary.value.expenses += amount;
    summary.value.cash -= amount;
  }
}

const summary = ref({
  sales: 1250,
  incomes: 150,
  expenses: 300,
  cash: 1100,
});
const movements = ref([
  { type: "ingreso", description: "Recarga de caja", amount: 100 },
  { type: "egreso", description: "Compra de insumos", amount: 120 },
  { type: "egreso", description: "Pago de servicios", amount: 180 },
  { type: "ingreso", description: "Reembolso", amount: 50 },
]);

const totalCaja = computed(() => ventasDia.value + ingresosExtras.value - gastos.value);
const porcentajeObjetivo = computed(() => Math.min((totalCaja.value / 500) * 100, 100));

function finalizeDay() {
  alert("Caja cerrada correctamente.");
  openDrawer.value = false;
  // Aquí podrías emitir evento o guardar en backend
}

defineExpose({
  openDrawer,
});
</script>
