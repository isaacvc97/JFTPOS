<template>
  <Head title="Crear venta" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <template v-slot:header>
      <h2 class="font-semibold text-2xl text-gray-200 dark:text-white tracking-tight">
        Medicamentos
      </h2>
    </template>

    <div xclass="w-full max-w-5xl bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
      <n-layout
        xclass="flex flex-col items-center px-4 py-10 dark:bg-gray-900 min-h-screen"
      >
        <!-- Barra de búsqueda -->
        <n-space vertical size="large" class="m-5">
          <n-card title="Medicamentos">
            <p class="text-gray-600 dark:text-gray-400">
              Aquí puedes gestionar los medicamentos y sus presentaciones.
            </p>

            <template #footer>
              <div class="flex justify-end flex-wrap gap-4 w-full cp-4">
                <n-button primary round type="primary" @click="createMedicine()">
                  Crear Medicamento
                </n-button>
                <!-- <n-button primary round type="primary" @click="createPresentation()">
                  Crear Presentacion
                </n-button> -->
              </div>
            </template>
          </n-card>

          <n-input
            placeholder="Buscar medicamento o genérico..."
            size="large"
            xblur="handleBlur"
            xfocus="handleFocus"
            xchange="handleChange"
            xkeyup="handleKeyUp"
            @input="debouncedFetch()"
            :loading="loading"
            v-model:value="search"
            clearable
          >
            <template #suffix>
              <n-icon :component="Search" />
            </template>
          </n-input>
          <!-- Búsqueda -->
          <!-- <div class="mb-6">
          <input
            v-model="search"
            @input="debouncedFetch()"
            type="text"
            placeholder="Buscar medicamento o genérico..."
            class="w-full border border-gray-300 dark:border-gray-700 rounded-lg px-4 py-3 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white transition"
          />
        </div> -->

          <n-data-table
            remote
            ref="table"
            :columns="columns"
            :data="medicines"
            :loading="loading"
            :pagination="pagination"
            :row-key="(row) => row.id"
            xupdate:sorter="handleSorterChange"
            xupdate:filters="handleFiltersChange"
            @update:page="handlePageChange"
            @update:page-size="handlePageSizeChange"
            default-expand-all
          />
        </n-space>
        <!-- Caja scrollable de resultados -->
        <!-- Tabla de medicamentos -->
        <!-- <table class="w-full table-auto text-left text-gray-700 dark:text-gray-200">
          <thead class="border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="py-3 px-4">Medicamento</th>
              <th class="py-3 px-4">Genérico</th>
              <th class="py-3 px-4">Formas</th>
              <th class="py-3 px-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="medicine, index in medicines" :key="medicine.id">
              <tr
                class="hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-pointer"
              >
                <td
                  @click="editMedicine(medicine, index)"
                  class="py-3 px-4 font-medium flex items-center gap-3"
                >
                  <img
                    :src="medicine.image_url || 'https://placehold.co/48x48?text=No+Img'"
                    class="w-10 h-10 rounded object-cover border border-gray-300 dark:border-gray-600"
                  />
                  {{ medicine.name }}
                </td>
                <td class="py-3 px-4">{{ medicine.generic_name }}</td>
                <td class="py-3 px-4">{{ medicine.dosages.length }}</td>
                <td class="py-3 px-4 text-right">
                  <button
                    class="text-sm text-blue-600 hover:underline"
                    @click="toggleExpanded(medicine.id)"
                  >
                    {{ expanded.has(medicine.id) ? "Ocultar" : "Ver" }} presentaciones
                  </button>
                </td>
              </tr>

              < !-- Fila expandida con presentaciones -- >
              <tr v-if="expanded.has(medicine.id)">
                <td
                  colspan="4"
                  class="px-6 pb-6 pt-2 bg-gray-50 dark:bg-gray-800 rounded-b-lg"
                >
                  <div v-for="dosage in medicine.dosages" :key="dosage.id" class="mb-4">
                    <div class="font-semibold text-gray-800 dark:text-gray-100 mb-2">
                      {{ dosage.form.name }} - {{ dosage.concentration }}
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-600 text-sm">
                      <div
                        v-for="presentation in dosage.presentations"
                        :key="presentation.id"
                        class="flex justify-between py-2"
                      >
                        <div>
                          <span class="font-medium">{{ presentation.unit_type }}</span>
                          - Cantidad: {{ presentation.quantity }}
                        </div>
                        <div>
                          <span
                            :class="
                              presentation.stock < 0
                                ? 'text-red-500'
                                : 'text-gray-800 dark:text-gray-100'
                            "
                          >
                            Stock: {{ presentation.stock }}
                          </span>
                          <span class="ml-4">Precio: ${{ presentation.price }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table> -->
      </n-layout>
    </div>

    <n-modal v-model:show="showModal" transform-origin="center">
      <n-card
        style="width: 600px"
        :title="selectedMedicine?.id ? 'Editar Medicamento' : 'Nuevo Medicamento'"
        :bordered="false"
        size="huge"
        role="dialog"
        aria-modal="true"
      >
        <template #header-extra> </template>
        <!-- Modal -->
        <MedicineModal
          :key="selectedMedicine?.id ?? 'new'"
          :show="showModal"
          :medicine="selectedMedicine"
          @updated="reloadMedicine"
          @close="showModal = false"
        />
        <!-- <template #footer>
        Footer
      </template> -->
      </n-card>
    </n-modal>
    <ModalPresentations
      v-model:show="showPresentationModal"
      title="Presentaciones disponibles"
    />

    <!-- SpeedDial -->
    <!-- <SpeedDial>
      <template #buttons>
        <button
          v-for="(action, index) in actions"
          :key="index"
          @click="action.onClick"
          class="group bg-white text-gray-800 dark:bg-gray-700 hover:bg-black dark:hover:bg-white dark:text-white p-3 rounded-full shadow transition duration-200"
          :title="action.label"
        >
          <component :is="action.icon" class="w-5 h-5 text-black hover:text-white" />
        </button>
      </template>
    </SpeedDial> -->
  </AppLayout>
</template>

<script lang="ts" setup>
import axios from "axios";
import { Head, router } from "@inertiajs/vue3";
import { ref, h, reactive } from "vue";

import AppLayout from "@/layouts/AppLayout.vue";
import MedicineModal from "./Modal.vue";

import {
  NButton,
  NModal,
  NLayout,
  NCard,
  NDataTable,
  NImage,
  NSpace,
  NInput,
  NIcon,
} from "naive-ui";
import { BookOpen, Search } from "lucide-vue-next";
import { Dosage, Medicine, emptyMedicine, emptyMedicineWithAll } from "./medicine";
import { debounce } from "@/lib/utils";
import ModalPresentations from "./ModalPresentations.vue";
// import { useFlashMessages } from '@/composables/useFlashMessages'

defineProps(["medicines"]);

const breadcrumbs = [
  {
    title: "Medicamentos",
    href: "/medicines",
  },
];

const column1 = {
  title: "id",
  key: "id",
  sorter: true,
  sortOrder: false,
  render(rowData: Medicine) {
    return h(
      "h1",
      {
        class: "text-green-500 dark:text-green-400",
        onClick: () => editMedicine(rowData, -1),
      },
      [rowData.name.charAt(0).toUpperCase() /* + rowData.name.slice(1) */]
    );
  },
};

const columns = createColumns({
  editMedicine(rowData: Medicine, index: number) {
    console.log(rowData);
    rowSelected.value = index;
    selectedMedicine.value = JSON.parse(JSON.stringify(rowData));
    showModal.value = true;
  },
});

function createColumns({
  editMedicine,
}: {
  editMedicine: (rowData: Medicine, index: number) => void;
}) {
  return [
    column1,
    {
      type: "expand",
      expandable: (rowData: Medicine) => rowData.dosages.length > 0,
      renderExpand: (rowData: Medicine) => {
        return h(
          "div",
          { class: "p-4" },
          rowData.dosages.map((dosage: Dosage) => {
            return h("div", { class: "mb-2 flex gap-2 items-center space-y-4" }, [
              h(NImage, {
                src: dosage.form.image || "https://placehold.co/48x48?text=No+Img",
                alt: dosage.form.name,
                class: "rounded-lg",
                style: { width: "48px", height: "48px" },
              }),
              h("div", [
                h("strong", {}, `${dosage.form.name} - ${dosage.concentration}`),
                h(
                  "ul",
                  {},
                  dosage.presentations.map((presentation) => {
                    return h(
                      "li",
                      {},
                      `${presentation.unit_type} - Cantidad: ${presentation.quantity} - Stock: ${presentation.stock} - Precio: $${presentation.price}`
                    );
                  })
                ),
              ]),
            ]);
          })
        );
      },
    },
    {
      title: "name",
      key: "name",
    },
    {
      title: "Generico",
      key: "generic_name",
    },
    {
      title: "Concentración",
      key: "dosages.*.form.name",
    },
    {
      title: "Generico",
      key: "generic_name",
    },
    {
      title: "Action",
      key: "actions",
      render(rowData: Medicine, index: number) {
        return h(
          NButton,
          {
            size: "small",
            onClick: () => editMedicine(rowData, index),
          },
          { default: () => "Actualizar" }
        );
      },
    },
  ];
}
// Llama el composable (se ejecutará automáticamente)
// useFlashMessages()

// const medicines = ref<Medicine[]>([]);
const rowSelected = ref<number>(-1); // Para saber qué fila se está editando
// const pagination = ref({});
const loading = ref(false);
const search = ref("");

const showModal = ref(false);
const showPresentationModal = ref<boolean>(false);
// const expanded = ref(new Set());

const selectedMedicine = ref(emptyMedicineWithAll());

function reloadMedicine(/* update: Medicine */) {
  getMedicines();
  // console.log(update, medicines.value);
  // medicines.value[rowSelected.value] = update;
  // axios.get(route('medicines.show', id)).then((res) => {
  //   const updated = res.data;
  //   console.log(updated)
  //   // 1. Si estás mostrando en una tabla, reemplaza ese ítem
  //   // 2. Si solo actualizaste selectedMedicine, actualízalo:
  //   showModal.value = true; // si quieres reabrir con los datos nuevos
  // });
}

// const createPresentation = () => {
//   showPresentationModal.value = true;
//   console.log("Crear nueva presentación");
// };

// function emptyMedicine() {
//   return {
//     id: null,
//     name: "",
//     generic_name: "",
//     description: "",
//     dosages: [
//       {
//         id: null,
//         concentration: "",
//         form: { name: "" },
//         presentations: [],
//       },
//     ],
//   };
// }

function createMedicine() {
  selectedMedicine.value = emptyMedicine();
  showModal.value = true;
}

function editMedicine(medicine: Medicine, index: number) {
  rowSelected.value = index;
  selectedMedicine.value = JSON.parse(JSON.stringify(medicine));
  showModal.value = true;
}

/* function toggleExpanded(id: number | null) {
  if (id === null) return; // No hacer nada si el ID es nulo
  if (expanded.value.has(id)) {
    expanded.value.delete(id);
  } else {
    expanded.value.add(id);
  }
} */

// const actions = [
//   {
//     label: "Nuevo Producto",
//     icon: BookOpen,
//     onClick: () => {
//       showModal.value = true;
//       selectedMedicine.value = emptyMedicine();
//     },
//   },
// ];
const pagination = reactive({
  page: 1,
  pageSize: 10,
  itemCount: 0,
  showSizePicker: false,
});
const handlePageChange = (page: any) => {
  pagination.page = page;
  fetch();
};

const handlePageSizeChange = (pageSize: any) => {
  pagination.pageSize = pageSize;
  pagination.page = 1;
  fetch();
};

const fetch = async (url = "/medicines/search?tipo=inventory") => {
  loading.value = true;
  const { page, pageSize } = pagination;
  const response = await axios.get(url, {
    params: { page, per_page: pageSize, search: search.value },
  });

  const { data, total } = response.data;
  medicines.value = data;
  pagination.itemCount = total; // importante
  console.log("Medicines fetched:", response.data.links);
  loading.value = false;
};

const getMedicines = () => {
  router.reload({
    data: { search: search.value },
    only: ["medicines"],
  });
};
const debouncedFetch = debounce(getMedicines, 400);

// onMounted(fetch);
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
