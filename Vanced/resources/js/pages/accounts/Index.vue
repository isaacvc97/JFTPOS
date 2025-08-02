<script setup>
import { h, ref, computed } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import {
  NDataTable,
  NButton,
  NModal,
  NForm,
  NInput,
  NSelect,
  NFormItem,
  NTag,
  NSpace,
  NProgress,
  NCard,
  NGrid,
  NGi,
  NIcon,
} from "naive-ui";
import { Search, FilePlus2 } from "lucide-vue-next";
import AppLayout from "@/layouts/AppLayout.vue";
import AccountPaymentModal from "@/pages/accounts/AccountPaymentModal.vue";

const props = defineProps({
  accounts: Array,
  clients: Array,
  providers: Array,
});

const filter = ref({
  status: null,
  type: null,
  search: "",
});

const filteredAccounts = computed(() => {
  return props.accounts?.filter((acc) => {
    return (
      (!filter.value.status || acc.status === filter.value.status) &&
      (!filter.value.type || acc.type === filter.value.type) &&
      (!filter.value.search ||
        acc.client?.name.toLowerCase().includes(filter.value.search.toLowerCase()) ||
        acc.laboratory?.name.toLowerCase().includes(filter.value.search.toLowerCase()))
    );
  });
});

const modalVisible = ref(false);
const editingAccount = ref(null);

const form = useForm({
  id: null,
  name: "",
  amount: 0,
  type: "por_pagar",
  status: "pendiente",
  client_id: null,
  laboratory_id: null,
  // contact_id: null,
});

function openModal(account = null) {
  editingAccount.value = account;
  if (account) {
    form.id = account.id;
    form.name = account?.name;
    form.amount = account.amount;
    form.type = account.type;
    form.status = account.status;
    form.contact_id = account.contact_id;
  } else {
    form.reset();
  }
  modalVisible.value = true;
}

function submitForm() {
  const url = form.id ? `/accounts/${form.id}` : "/accounts";
  const method = form.id ? "put" : "post";
  form.submit(method, url, {
    onSuccess: () => {
      modalVisible.value = false;
    },
  });
}

function statusColor(status) {
  return {
    pagado: "success",
    pendiente: "warning",
    vencido: "error",
  }[status];
}

const selectedAccount = ref(null);
const paymentModalVisible = ref(false);

function openPaymentModal(account) {
  selectedAccount.value = account;
  paymentModalVisible.value = true;
}

function reloadPage() {
  router.reload({ only: ["accounts"] });
}
</script>

<template>
  <AppLayout>
    <AccountPaymentModal
      v-if="selectedAccount"
      :show="paymentModalVisible"
      :account="selectedAccount"
      @close="paymentModalVisible = false"
      @refresh="reloadPage"
    />
    <Head title="Cuentas por pagar / cobrar" />

    <div class="p-4 space-y-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">Cuentas</h1>
        <NButton type="primary" @click="openModal"
          ><template #icon
            ><NIcon><FilePlus2 /></NIcon
          ></template>
          Nueva cuenta</NButton
        >
      </div>

      <NCard>
        <NSpace vertical>
          <NInput
            v-model:value="filter.search"
            placeholder="Buscar..."
            :prefix-icon="Search"
            round
          >
            <template #prefix>
              <NIcon> <Search /></NIcon>
            </template>
          </NInput>
          <NSelect
            v-model:value="filter.status"
            :options="[
              { label: 'Todos', value: null },
              { label: 'Pendiente', value: 'pendiente' },
              { label: 'Pagado', value: 'pagado' },
              { label: 'Vencido', value: 'vencido' },
            ]"
            placeholder="Estado"
          />
          <NSelect
            v-model:value="filter.type"
            :options="[
              { label: 'Todos', value: null },
              { label: 'Por pagar', value: 'por_pagar' },
              { label: 'Por cobrar', value: 'por_cobrar' },
            ]"
            placeholder="Tipo"
          />
        </NSpace>
      </NCard>

      <NCard class="animate-fade-in">
        <NDataTable
          :columns="[
            {
              title: 'Nombre',
              key: 'name',
            },
            {
              title: 'Contacto',
              key: 'contact',
              render: (row) =>
                !!row.client ? row.client?.name : row.laboratory?.name || 'Sin nombre',
            },
            {
              title: 'Monto',
              key: 'amount',
              // render: (row) => `$${(row.amount || '0.00')?.toFixed(2)}`,
            },
            {
              title: 'Tipo',
              key: 'type',
              render: (row) => (row.type === 'por_pagar' ? 'Por pagar' : 'Por cobrar'),
            },
            {
              title: 'Estado',
              key: 'status',
              render: (row) =>
                h(NTag, { type: statusColor(row.status) }, { default: () => row.status }),
            },
            /*{
              title: 'Acciones',
              key: 'actions',
              render: (row) =>
                h(
                  NButton,
                  { size: 'small', onClick: () => openModal(row) },
                  { default: () => 'Editar' }
                ),
            },*/
            {
              title: 'Acciones',
              key: 'actions',
              render: (row) =>
                h(NSpace, {}, () => [
                  h(
                    NButton,
                    { size: 'small', onClick: () => openModal(row) },
                    { default: () => 'Editar' }
                  ),
                  h(
                    NButton,
                    {
                      size: 'small',
                      secondary: true,
                      onClick: () => openPaymentModal(row),
                    },
                    { default: () => 'Pagos' }
                  ),
                ]),
            },
          ]"
          :data="filteredAccounts"
          :pagination="{ pageSize: 10 }"
        />
      </NCard>

      <!-- Modal -->
      <NModal v-model:show="modalVisible">
        <NCard title="Cuenta" size="medium" closable @close="modalVisible = false">
          <NForm :model="form" label-placement="top">
            <NFormItem label="Nombre" path="name">
              <NInput v-model:value="form.name" />
            </NFormItem>
            <NFormItem label="Monto" path="amount">
              <NInput v-model:value="form.amount" type="number" />
            </NFormItem>
            <NFormItem label="Tipo" path="type">
              <NSelect
                v-model:value="form.type"
                :options="[
                  { label: 'Por pagar', value: 'por_pagar' },
                  { label: 'Por cobrar', value: 'por_cobrar' },
                ]"
              />
            </NFormItem>
            <NFormItem label="Estado" path="status">
              <NSelect
                v-model:value="form.status"
                :options="[
                  { label: 'Pendiente', value: 'pendiente' },
                  { label: 'Pagado', value: 'pagado' },
                  { label: 'Vencido', value: 'vencido' },
                ]"
              />
            </NFormItem>
            <NFormItem :label="form.type === 'por_cobrar' ? 'Cliente' : 'Proveedor'">
              <NSelect
                v-if="form.type === 'por_cobrar'"
                v-model:value="form.client_id"
                :options="clients?.map((c) => ({ label: c.name, value: c.id }))"
                placeholder="Selecciona un cliente"
              />
              <NSelect
                v-else
                v-model:value="form.laboratory_id"
                :options="providers?.map((l) => ({ label: l.name, value: l.id }))"
                placeholder="Selecciona un proveedor"
              />
              <!-- <NSelect
                v-model:value="form.contact_id"
                :options="
                  (form.type === 'por_cobrar'
                    ? props.clients
                    : props.providers
                  ).map((c) => ({ label: c.name, value: c.id }))
                "
              /> -->
            </NFormItem>
          </NForm>

          <template #footer>
            <NButton type="primary" @click="submitForm">Guardar</NButton>
          </template>
        </NCard>
      </NModal>

      <!-- Resumen con barra de progreso -->
      <NCard title="Resumen">
        <NGrid x-gap="12" cols="3 s:1 m:2 l:3">
          <NGi>
            <div class="space-y-1">
              <div>Total por pagar</div>
              <NProgress
                type="line"
                :percentage="
                  Math.min(
                    100,
                    (props.accounts?.filter(
                      (a) => a.type === 'por_pagar' && a.status === 'pendiente'
                    ).length /
                      props.accounts?.filter((a) => a.type === 'por_pagar').length) *
                      100
                  )
                "
                status="warning"
              />
            </div>
          </NGi>
          <NGi>
            <div class="space-y-1">
              <div>Total por cobrar</div>
              <NProgress
                type="line"
                :percentage="
                  Math.min(
                    100,
                    (props.accounts?.filter(
                      (a) => a.type === 'por_cobrar' && a.status === 'pendiente'
                    ).length /
                      props.accounts?.filter((a) => a.type === 'por_cobrar').length) *
                      100
                  )
                "
                status="success"
              />
            </div>
          </NGi>
          <NGi>
            <div class="space-y-1">
              <div>Pagadas</div>
              <NProgress
                type="line"
                :percentage="
                  Math.min(
                    100,
                    (props.accounts?.filter((a) => a.status === 'pagado').length /
                      props.accounts?.length) *
                      100
                  )
                "
                status="info"
              />
            </div>
          </NGi>
        </NGrid>
      </NCard>
    </div>
  </AppLayout>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease;
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
