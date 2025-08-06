<template>
  <div class="p-4">
    <n-card title="Bandeja de notificaciones">
      <n-empty v-if="!notificaciones.length" description="No hay notificaciones" />

      <n-list v-else bordered>
        <n-list-item v-for="noti in notificaciones" :key="noti.id">
          <template #default>
            <div class="flex justify-between items-center">
              <div>
                <div class="font-semibold">{{ noti.data.mensaje }}</div>
                <div class="text-xs text-gray-500">
                  {{ formatFecha(noti.created_at) }}
                </div>
              </div>
              <div v-if="noti.data.tipo === 'invitacion'">
                <n-button size="small" type="primary" @click="aceptar(noti.data.token)"
                  >Aceptar</n-button
                >
                <n-button
                  size="small"
                  type="error"
                  class="ml-2"
                  @click="rechazar(noti.data.token)"
                  >Rechazar</n-button
                >
              </div>
            </div>
          </template>
        </n-list-item>
      </n-list>
    </n-card>
  </div>
</template>

<script setup>
import { usePage, router } from "@inertiajs/vue3";
import { NCard, NEmpty, NList, NListItem, NButton } from "naive-ui";

const page = usePage();
const notificaciones = page.props.auth.user.notifications || [];

function aceptar(token) {
  router.visit(`/invitaciones/aceptar/${token}`);
}

function rechazar(token) {
  router.post(`/invitaciones/rechazar/${token}`);
}

function formatFecha(dateStr) {
  return new Date(dateStr).toLocaleString();
}
</script>
