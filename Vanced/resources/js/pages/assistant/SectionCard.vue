<template>
  <div class="bg-gray-50 dark:bg-slate-700 rounded-xl p-4 shadow-sm">
    <h3 class="text-lg font-medium mb-2 text-gray-900 dark:text-white">{{ title }}</h3>
    <div><slot /></div>
  </div>
</template>

<script setup>
defineProps({ title: String });
</script>
