<template>
  <n-card title="Notificaciones" class="p-4">
    <n-list>
      <n-list-item v-for="n in notifications" :key="n.id">
        <n-thing>
          <template #header>
            {{ formatTipo(n.data?.tipo) }}
          </template>

          <template #description>
            <n-text depth="3">{{ n.data?.mensaje }}</n-text>
          </template>

          <template #header-extra>
            <n-space>
              <n-button v-if="n.data?.url" size="tiny" @click="visitar(n.data.url)"
                >Ver</n-button
              >
              <n-button size="tiny" type="error" @click="borrar(n.id)">Borrar</n-button>
            </n-space>
          </template>
        </n-thing>
      </n-list-item>
    </n-list>
  </n-card>
</template>

<script setup>
import axios from "axios";
import { onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useMessageGlobal } from "@/composables/useMessageGlobal";
import { NCard, NList, NListItem, NThing, NText, NButton, NSpace } from "naive-ui";

const page = usePage();
const message = useMessageGlobal();
const notifications = ref(page.props.auth.user.invitaciones_recibidas ?? []);

function formatTipo(tipo) {
  switch (tipo) {
    case "invitacion":
      return "Invitación a sucursal";
    case "stock":
      return "Stock bajo";
    case "cuenta":
      return "Cuenta por pagar";
    default:
      return "Notificación";
  }
}

function visitar(url) {
  window.location.href = url;
}

function borrar(id) {
  axios.delete(`/notificaciones/${id}`).then(() => {
    notifications.value = notifications.value.filter((n) => n.id !== id);
    message.success("Notificación eliminada");
  });
}

onMounted(() => console.log(page.props.auth));
</script>
