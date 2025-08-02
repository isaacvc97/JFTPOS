<template>
  <div
    class="cursor-pointer rounded-lg p-3 hover:bg-gray-100 dark:hover:bg-slate-600 transition"
    @click="$emit('click')"
  >
    <p class="font-medium text-gray-800 dark:text-white">{{ title }}</p>
    <p class="text-sm text-gray-500 dark:text-gray-300">{{ description }}</p>
  </div>
</template>

<script setup>
defineProps({ title: String, description: String });
defineEmits(['click']);
</script>
