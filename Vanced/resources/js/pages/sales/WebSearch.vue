<template>
  <NModal
    draggable
    v-model:show="localShow"
    preset="card"
    style="width: 90%; max-width: 90%; padding: 5px; border-radius: 7px"
    title="Buscar en la Web"
    @close="emit('close')"
  >
    <template #default>
      <NCard :bordered="false">
        <template #header>
          <NInput
            v-model:value="query"
            @keydown.enter="buscar"
            placeholder="Descripción del producto | Laboratorio | Marca"
            clearable
            size="large"
          >
            <template #prefix>
              <NIcon :component="Search" />
            </template>
          </NInput>
          <div class="grid sm:grid-cols-2 gap-4 my-4">
            <NSelect
              v-model:value="estructura"
              :options="estructuras"
              placeholder="Estructura del producto"
              size="large"
            />
            <NButton type="primary" size="large" @click="buscar"
              >Buscar medicamento</NButton
            >
          </div>
        </template>

        <n-space vertical>
          <div v-if="loading" class="text-center my-4 text-gray-500">Buscando...</div>

          <NScrollbar style="max-height: 60vh">
            <div
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 xmax-h-[70vh] xp-4 xoverflow-y-auto"
            >
              <NCard
                v-for="(producto, index) in productos"
                :key="index"
                @click="getDetails(producto)"
                :title="producto.descripcion || producto.nombre"
                xclass="relative"
                size="small"
                :bordered="false"
                hoverable
              >
                <template #cover>
                  <img
                    :src="
                      producto.imagen || 'https://placehold.co/150x150.png?text=No+Image'
                    "
                    alt="Medicine"
                    xclass="w-full h-32 object-cover"
                  />
                </template>
                <!-- <template #header>
                  <div class="flex justify-between items-start">
                    <div xclass="font-semibold truncate">{{ producto.nombre }}</div>
                    < !-- <NCheckbox v-model:checked="seleccionados" :value="producto" /> -- >
                  </div>
                </template> -->

                <template #header-extra>
                  <div xclass="text-xs text-gray-500">
                    {{ producto.precio }}
                  </div>
                </template>

                <!-- <NImage width="100" alt="producto" :src="producto.imagen" /> -->

                <template #footer>
                  <div class="text-sm text-gray-500 truncate">
                    {{ producto.descripcion }}
                  </div>

                  <div class="mt-2 text-sm">
                    <span class="text-green-600 font-medium">
                      {{ producto.precio }}
                    </span>
                    <span
                      v-if="producto.precioDescuento"
                      class="line-through text-gray-400 ml-2"
                    >
                      {{ producto.precioDescuento }}
                    </span>
                  </div>
                </template>

                <template #action>
                  <NSpace justify="space-between" align="center">
                    <NButton
                      type="primary"
                      size="small"
                      @click.stop="seleccionados.push(producto)"
                    >
                      Seleccionar
                    </NButton>
                    <NButton type="info" size="small" @click.stop="getDetails(producto)">
                      Detalles
                    </NButton>
                  </NSpace>
                </template>
              </NCard>
            </div>

            <div v-if="mensaje" class="text-center text-green-600 font-medium mt-6">
              {{ mensaje }}
            </div>
          </NScrollbar>

          <NSpace class="mb-4" justify="end">
            <NButton @click="toggleModalMedicine"> Crear nuevo medicamento </NButton>
            <NButton
              @click="seleccionarTodo"
              :disabled="seleccionados.length >= productos.length"
            >
              Seleccionar todo ({{ productos.length }})
            </NButton>

            <NButton
              type="primary"
              @click="guardarSeleccionados"
              :disabled="seleccionados.length === 0"
            >
              Guardar seleccionados ({{ seleccionados.length }})
            </NButton>
          </NSpace>
        </n-space>
      </NCard>
    </template>
  </NModal>

  <WebSearchDetails
    v-model:show="isOpen"
    :data="details"
    :estructura="details.estructura"
    @cerrar="isOpen = false"
  />
</template>

<script setup>
import axios from "axios";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import {
  NModal,
  NCard,
  NInput,
  NSelect,
  NButton,
  NSpace,
  NImage,
  NIcon,
  NScrollbar,
} from "naive-ui";
import { Search } from "lucide-vue-next";
import { useMessageGlobal } from "@/composables/useMessageGlobal";
import WebSearchDetails from "./WebSearchDetails.vue";

const message = useMessageGlobal();

const props = defineProps({
  show: {
    type: Boolean,
    required: true,
  },
});

const localShow = computed({
  get() {
    return props.show;
  },
  set(value) {
    emit("update:show", value);
  },
});

const emit = defineEmits(["update:show", "close", "create-medicine"]);

const query = ref("");
const productos = ref([]);
const seleccionados = ref([]);
const details = ref([]);
const isOpen = ref(false);
const loading = ref(false);
const mensaje = ref("");
const estructuras = ref([
  { label: "Fybeca", value: "estructura1" },
  { label: "Cruz azul", value: "estructura2" },
  { label: "Estructura 3", value: "estructura3" },
]);
const estructura = ref("estructura1");

const buscar = async () => {
  if (!query.value) return;
  loading.value = true;
  const res = await axios.get(route("web-search"), {
    params: { query: query.value, estructura: estructura.value },
  });
  if (res.data.error) {
    message.error(res.data.error);
    mensaje.value = res.data.error;
    loading.value = false;
    return;
  }

  productos.value = res.data.productos || [];
  loading.value = false;
};

const getDetails = async (product) => {
  if (!product.link) return;

  loading.value = true;
  console.log(
    "Link:",
    product.link,
    "encodeURIComponent(link):",
    encodeURIComponent(product.link)
  );
  const res = await axios.get(route("web-details"), {
    params: { url: encodeURIComponent(product.link) },
  });
  details.value = res.data || [];
  loading.value = false;
  isOpen.value = true;
};
const toggleModalMedicine = () => {
  emit("create-medicine");
};

const seleccionarTodo = () => {
  seleccionados.value = [...productos.value];
};

const guardarSeleccionados = () => {
  if (seleccionados.value.length === 0) {
    mensaje.value = "No hay productos seleccionados.";
    return;
  }

  router.post(
    route("guardar-productos"),
    { productos: seleccionados.value },
    {
      onSuccess: (page) => {
        mensaje.value = page.props.flash.mensaje || "Productos guardados.";
        seleccionados.value = [];
      },
      onError: (error) => {
        console.error("Error al guardar:", error);
        mensaje.value = "Error al guardar productos.";
      },
    }
  );
};
</script>

<style scoped>
.n-card {
  border-radius: 16px;
}
</style>
