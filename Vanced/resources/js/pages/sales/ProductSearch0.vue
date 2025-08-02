<template>
  <div
    @keydown.down.prevent="navegar('down')"
    @keydown.up.prevent="navegar('up')"
    @keydown.left.prevent="colapsarSeleccionado"
    @keydown.right.prevent="expandirSeleccionado"
    @keydown.enter.prevent="seleccionarPresentacion"
    @keydown.esc.prevent="agrupados = []"
    tabindex="0"
  >
    <n-input
      v-model:value="search"
      placeholder="Buscar medicamento..."
      clearable
      @input="buscar"
      class="w-full"
      size="large"
      round
    >
      <template #prefix>
        <n-icon :component="Search" />
      </template>
    </n-input>
    <n-spin :show="loading" class="mt-2">
      <n-list bordered v-if="agrupados.length > 0" class="mt-2">
        <n-list-item
          v-for="(grupo, index) in agrupados"
          :key="grupo.nombre"
          :class="{ 'bg-blue-50': index === seleccionado }"
        >
          <div>
            <strong>{{ grupo.nombre }}</strong>
            <p class="text-xs text-gray-500">{{ grupo.generic_name }}</p>
            <div v-if="grupo.expandido">
              <div
                v-for="(unidad, i) in grupo.unidades"
                :key="unidad.presentation_id"
                class="mt-1 border-t pt-1"
                :class="{ 'ring-2 ring-blue-400 rounded-sm': grupo.indice === i }"
              >
                <p class="text-xs">
                  {{ unidad.form_name }} - {{ unidad.concentration }}<br />
                  {{ unidad.unit_type }} x {{ unidad.quantity }} - ${{ unidad.price }} |
                  Stock: {{ unidad.stock }}
                </p>
                <n-button
                  size="tiny"
                  @click="$emit('select', unidad)"
                  :disabled="unidad.stock <= 0"
                >
                  Agregar
                </n-button>
              </div>
            </div>
          </div>
        </n-list-item>
      </n-list>
      <p
        v-if="!loading && agrupados.length === 0 && search.length > 2"
        class="text-xs mt-2 text-gray-400"
      >
        Sin resultados
      </p>
    </n-spin>
  </div>
</template>

<script setup>
import { ref } from "vue";
import axios from "axios";
import { NSpin, NList, NListItem, NInput, NIcon, NButton } from "naive-ui";
import { Search } from "lucide-vue-next";

const emit = defineEmits(["select"]);
const search = ref("");
const loading = ref(false);
const agrupados = ref([]);
const seleccionado = ref(0);

async function buscar() {
  if (search.value.length < 2) return;
  loading.value = true;
  try {
    const { data } = await axios.get("/api/medications/search", {
      params: { q: search.value },
    });

    // Agrupar por medicamento_name
    const mapa = {};
    for (const item of data) {
      if (!mapa[item.medicine_name]) {
        mapa[item.medicine_name] = {
          nombre: item.medicine_name,
          generic_name: item.generic_name,
          unidades: [],
          expandido: false,
          indice: 0,
        };
      }
      mapa[item.medicine_name].unidades.push(item);
    }
    agrupados.value = Object.values(mapa);
    seleccionado.value = 0;
  } finally {
    loading.value = false;
  }
}

function navegar(direccion) {
  if (agrupados.value.length === 0) return;
  if (direccion === "down" && seleccionado.value < agrupados.value.length - 1) {
    seleccionado.value++;
  } else if (direccion === "up" && seleccionado.value > 0) {
    seleccionado.value--;
  }
}

function expandirSeleccionado() {
  const grupo = agrupados.value[seleccionado.value];
  if (grupo) grupo.expandido = true;
}

function colapsarSeleccionado() {
  const grupo = agrupados.value[seleccionado.value];
  if (grupo) grupo.expandido = false;
}

function seleccionarPresentacion() {
  const grupo = agrupados.value[seleccionado.value];
  if (grupo && grupo.expandido) {
    const unidad = grupo.unidades[grupo.indice];
    if (unidad && unidad.stock > 0) emit("select", unidad);
  }
}

// Flecha derecha: mover entre presentaciones
function navegarPresentacion(direccion) {
  const grupo = agrupados.value[seleccionado.value];
  if (!grupo || !grupo.expandido) return;

  const max = grupo.unidades.length - 1;
  if (direccion === "next" && grupo.indice < max) {
    grupo.indice++;
  } else if (direccion === "prev" && grupo.indice > 0) {
    grupo.indice--;
  } else {
    grupo.indice = 0;
  }
}

// map arrow-right to avanzar presentaciones
document.addEventListener("keydown", (e) => {
  if (e.key === "ArrowRight") navegarPresentacion("next");
});
</script>

<style scoped>
.bg-blue-50 {
  background-color: #eff6ff;
}
.ring-2 {
  border-radius: 4px;
}
</style>
