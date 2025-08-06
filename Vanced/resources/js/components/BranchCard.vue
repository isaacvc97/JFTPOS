<template>
  <n-card size="small" class="mb-4">
    <div class="font-medium">Sucursal activa</div>
    <div class="text-sm text-gray-600">
      <div>{{ branch?.nombre }}</div>
      <div>RUC: {{ branch?.ruc }}</div>
    </div>
  </n-card>
</template>

<script setup lang="ts">
import { NCard } from "naive-ui";

interface Props {
  branch: {
    nombre: string;
    ruc: string;
  };
}
defineProps<Props>();
</script>
