<template>
  <AppLayout>
    <div class="space-y-4">
      <div class="flex justify-between items-center">
        <h1 class="text-xl font-bold">Laboratorios</h1>
        <n-button type="primary" @click="showModal = true">Nuevo laboratorio</n-button>
      </div>

      <n-data-table
        :columns="columns"
        :data="laboratories"
        :pagination="false"
        :bordered="false"
      />

      <n-modal v-model:show="showModal" preset="dialog" title="Laboratorio">
        <n-form :model="form" :rules="rules" ref="formRef" label-placement="top">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <n-form-item label="Nombre" path="name">
              <n-input v-model:value="form.name" />
            </n-form-item>
            <n-form-item label="RUC" path="ruc">
              <n-input v-model:value="form.ruc" />
            </n-form-item>
            <n-form-item label="Dirección" path="address">
              <n-input v-model:value="form.address" />
            </n-form-item>
            <n-form-item label="Teléfono" path="phone">
              <n-input v-model:value="form.phone" />
            </n-form-item>
            <n-form-item label="Representante" path="representative_name">
              <n-input v-model:value="form.representative_name" />
            </n-form-item>
            <n-form-item label="Tel. Representante" path="representative_phone">
              <n-input v-model:value="form.representative_phone" />
            </n-form-item>
          </div>
        </n-form>

        <template #action>
          <n-button @click="showModal = false">Cancelar</n-button>
          <n-button type="primary" @click="submitForm">Guardar</n-button>
        </template>
      </n-modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, h } from "vue";
import { NButton, NDataTable, NModal, NInput, NForm, NFormItem } from "naive-ui";
import { useMessageGlobal } from "@/composables/useMessageGlobal";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";

const props = defineProps({ laboratories: Array });

const message = useMessageGlobal();

const showModal = ref(false);
const form = ref({
  id: null,
  name: "",
  ruc: "",
  address: "",
  phone: "",
  representative_name: "",
  representative_phone: "",
});
const formRef = ref(null);

const rules = {
  name: [{ required: true, message: "Nombre requerido", trigger: "blur" }],
};

const columns = [
  { title: "Nombre", key: "name" },
  {
    title: "RUC",
    key: "ruc",
  },
  {
    title: "Teléfono",
    key: "phone",
  },
  {
    title: "Representante",
    key: "representative_name",
  },
  {
    title: "Representante Cell",
    key: "representative_phone",
  },
  {
    title: "Acciones",
    key: "actions",
    render(row) {
      return [
        h(
          NButton,
          { size: "tiny", onClick: () => edit(row) },
          { default: () => "Editar" }
        ),
        h(
          NButton,
          {
            size: "tiny",
            type: "error",
            onClick: () => destroy(row.id),
            style: "margin-left: 8px",
          },
          { default: () => "Eliminar" }
        ),
      ];
    },
  },
];

function submitForm() {
  formRef.value?.validate((errors) => {
    if (errors) return;
    const action = form.value.id
      ? router.put(`/laboratories/${form.value.id}`, form.value)
      : router.post("/laboratories", form.value);
    showModal.value = false;
  });
}

function edit(row) {
  form.value = { ...row };
  showModal.value = true;
}

function destroy(id) {
  if (confirm("¿Eliminar este laboratorio?")) {
    router.delete(`/laboratories/${id}`);
  }
}
</script>
