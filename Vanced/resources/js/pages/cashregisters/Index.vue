<template>
  <AppLayout :breadcrumbs="[{ title: 'Cashregister', href: '/cashregister' }]">
    <EmptyCashCard v-if="!cashregister.id" />
    <DataCash :cash="cashregister" v-else />
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Cashregister, emptyCashregister } from "./cashregister";
import EmptyCashCard from "./EmptyCashCard.vue";
import DataCash from "./DataCash.vue";

interface Props {
  cashregister: Cashregister;
}

withDefaults(defineProps<Props>(), {
  cashregister: emptyCashregister,
});
</script>

<style scoped>
.n-progress {
  transition: all 0.5s ease;
}

/* Animación sutil al aparecer elementos */
.n-card,
.n-button,
.n-list-item {
  transition: all 0.3s ease;
}
</style>
