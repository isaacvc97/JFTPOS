<script setup>
import { ref, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import {
  NModal,
  NCard,
  NForm,
  NInput,
  NSelect,
  NFormItem,
  NInputNumber,
  NDatePicker,
  NButton,
  NDataTable,
  NDivider,
  useMessage,
} from "naive-ui";

const props = defineProps({
  account: Object,
  show: Boolean,
});
const emit = defineEmits(["close", "refresh"]);

const message = useMessage();
const modalVisible = ref(props.show);

const form = useForm({
  amount: null,
  date: null,
  method: "Efectivo",
  note: "",
});

/* watch(
  () => props.account,
  (newValue) => (form != newValue ? useForm(newValue) : useForm({})),
  { immediate: true }
);
 */
watch(
  () => props.show,
  (v) => (modalVisible.value = v)
);

const paymentOptions = [
  { label: "Efectivo", value: "Efectivo" },
  { label: "Tarjeta", value: "Tarjeta" },
  { label: "Transferencia", value: "Transferencia" },
];

const columns = [
  { title: "Fecha", key: "date" },
  {
    title: "Monto",
    key: "amount",
    render: (row) => `$${parseFloat(row.amount).toFixed(2)}`,
  },
  { title: "Método", key: "method" },
  { title: "Nota", key: "note" },
];

const totalPaid = computed(() => {
  return props.account.payments?.reduce((sum, p) => sum + parseFloat(p.amount), 0);
});

const pendingAmount = computed(() => {
  return parseFloat(props.account.amount) - totalPaid.value;
});

function submit() {
  if (form.amount <= 0) {
    return message.warning("El monto debe ser mayor que cero");
  }

  form.post(`/accounts/${props.account.id}/payments`, {
    preserveScroll: true,
    onSuccess: () => {
      message.success("Pago registrado");
      emit("refresh");
      form.reset();
    },
    onError: () => {
      message.error("Error al guardar el pago");
    },
  });
}
</script>

<template>
  <NModal
    v-model:show="modalVisible"
    @close="emit('close')"
    title="Resumen de cuenta"
    style="width: 90%; max-width: 90%; padding: 20px"
  >
    <NCard title="RESUMEN DE LA CUENTA" closable @close="emit('close')">
      <div class="space-y-2">
        <div>
          <strong>Entidad: </strong>
          <span v-if="account.client">{{ account.client.name }} (Cliente)</span>
          <span v-else-if="account.laboratory"
            >{{ account.laboratory.name }} (Proveedor)</span
          >
          <span v-else>N/A</span>
        </div>
        <div>
          <strong>Cuenta: </strong> {{ account.description || "Sin descripción" }}
        </div>
        <div><strong>Total: </strong> ${{ parseFloat(account.amount)?.toFixed(2) }}</div>
        <div><strong>Pagado: </strong> ${{ totalPaid?.toFixed(2) }}</div>
        <div><strong>Pendiente: </strong> ${{ pendingAmount?.toFixed(2) }}</div>

        <NDivider />
        {{ form }}
        <NForm
          :model="form"
          label-placement="top"
          class="grid grid-cols-1 md:grid-cols-2 gap-4"
        >
          <NFormItem label="Fecha">
            <NDatePicker
              v-model:formatted-value="form.date"
              type="datetime"
              value-format="yyyy-MM-dd HH:mm:ss"
              placeholder="Selecciona la fecha y hora del pago"
            />
            <!-- <NDatePicker
              v-model:value="form.datetime"
              xupdate:value="
                (event) => {
                  console.log(event);
                  form.datetime = event;
                }
              "
              value-format="yyyy-MM-dd HH:mm:ss"
              type="datetime"
            /> -->
          </NFormItem>
          <NFormItem label="Monto">
            <NInputNumber v-model:value="form.amount" :min="0.01" :max="pendingAmount" />
          </NFormItem>
          <NFormItem label="Método" class="col-span-2">
            <NSelect
              v-model:value="form.method"
              default-value="Efectivo"
              :options="paymentOptions"
              placeholder="Forma de pago"
            />
            <!-- <NInput
              v-model:value="form.method"
              placeholder="Efectivo, Transferencia, etc."
            /> -->
          </NFormItem>
          <NFormItem label="Nota" class="col-span-2">
            <NInput v-model:value="form.note" type="textarea" />
          </NFormItem>
        </NForm>

        <NButton type="primary" block @click="submit">Registrar Pago</NButton>

        <NDivider>Pagos anteriores</NDivider>

        <NDataTable
          :columns="columns"
          :data="account.payments"
          size="small"
          :pagination="false"
        />
      </div>
    </NCard>
  </NModal>
</template>
