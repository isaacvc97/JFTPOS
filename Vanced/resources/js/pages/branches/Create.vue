<template>
  <div class="p-4 max-w-xl xmx-auto items-center">
    <n-card title="Crear nueva sucursal">
      <n-form :model="form" :rules="rules" ref="formRef" label-placement="top">
        <n-form-item label="Nombre" path="nombre">
          <n-input v-model:value="form.nombre" placeholder="Nombre de la sucursal" />
        </n-form-item>

        <n-form-item label="RUC" path="ruc">
          <n-input v-model:value="form.ruc" placeholder="RUC" />
        </n-form-item>

        <n-form-item label="Teléfono" path="telefono">
          <n-input v-model:value="form.telefono" placeholder="Teléfono" />
        </n-form-item>

        <n-form-item label="Dirección" path="direccion">
          <n-input v-model:value="form.direccion" placeholder="Dirección" />
        </n-form-item>

        <div class="mt-4 flex justify-end">
          <n-button type="primary" @click="submit">Crear sucursal</n-button>
        </div>
      </n-form>
    </n-card>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { NCard, NForm, NFormItem, NInput, NButton } from "naive-ui";

const form = ref({
  nombre: "",
  ruc: "",
  telefono: "",
  direccion: "",
});

const formRef = ref<InstanceType<typeof NForm> | null>(null);

const rules = {
  nombre: { required: true, message: "Requerido", trigger: ["blur", "input"] },
  ruc: { required: true, message: "Requerido", trigger: ["blur", "input"] },
};

function submit() {
  formRef.value?.validate((errors: any) => {
    if (!errors) {
      router.post("/branches", form.value);
    }
  });
}
</script>
