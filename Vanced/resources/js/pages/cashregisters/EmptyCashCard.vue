<template>
  <n-card
    class="max-w-xl mx-auto mt-10 text-center shadow-md rounded-2xl"
    size="large"
    title="Registradora no iniciada"
    :bordered="true"
  >
    <n-space vertical size="large" class="py-4 px-6">
      <div class="text-gray-500">
        <n-icon size="40" color="#999">
          <Coins />
        </n-icon>
        <p class="mt-2 text-sm">
          No hay caja abierta para hoy. Por favor, ingresa el monto inicial y abre una
          nueva jornada.
        </p>
      </div>

      <n-form :model="form" :rules="rules" ref="formRef">
        <n-form-item label="Monto Inicial" path="initial_amount">
          <n-input
            v-model:value="form.initial_amount"
            placeholder="Ej. 100.00"
            :min="0"
            prefix="$"
            class="w-full"
            clearable
          />
        </n-form-item>

        <n-form-item label="Responsable" path="user">
          <n-input
            xmodel:value="form.user"
            :value="page.props.auth.user?.name"
            :placeholder="page.props.auth.user?.name"
            disabled
          />
        </n-form-item>

        <n-form-item label="Comentario (opcional)">
          <n-input
            v-model:value="form.note"
            placeholder="Observaciones iniciales"
            type="textarea"
            autosize
          />
        </n-form-item>
      </n-form>

      <n-button type="success" strong block size="large" @click="handleOpenCash">
        Aperturar Caja
      </n-button>
    </n-space>
  </n-card>
</template>

<script setup lang="ts">
import axios from "axios";
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { NCard, NSpace, NIcon, NInput, NButton, NForm, NFormItem } from "naive-ui";
import { Coins } from "lucide-vue-next";

const page = usePage();
const formRef = ref();
const form = ref({
  initial_amount: null,
  user: page.props.auth.user?.id?.toString(),
  note: "",
});

const rules = {
  initial_amount: [
    {
      required: true,
      message: "Ingresa un monto inicial",
      trigger: "blur",
    },
  ],
  user: [
    {
      required: true,
      message: "Ingresa el nombre del responsable",
      trigger: "blur",
    },
  ],
};

function handleOpenCash() {
  formRef.value?.validate(async (errors: any) => {
    if (!errors) {
      // Aquí puedes emitir un evento o guardar en backend
      console.log("Caja aperturada:", form.value);
      // alert("Caja iniciada correctamente con $" + form.value.initial_amount);
      const { data } = await axios.post(route("cash.store"), form.value);

      console.info(data);

      // Resetear o cerrar modal si es necesario
      form.value = {
        initial_amount: null,
        user: page.props.auth.user?.id.toString(),
        note: "",
      };
    }
  });
}

const emit = defineEmits(["update:cash"]);
</script>
