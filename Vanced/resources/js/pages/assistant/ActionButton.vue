<template>
  <button
    :class="buttonClasses"
    @click="$emit('click')"
  >
    {{ label }}
  </button>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({
  label: String,
  variant: {
    type: String,
    default: 'primary' // primary, secondary, ghost
  }
});

const buttonClasses = computed(() => {
  switch (props.variant) {
    case 'primary':
      return 'bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl transition';
    case 'secondary':
      return 'bg-gray-200 dark:bg-slate-600 text-black dark:text-white px-4 py-2 rounded-xl hover:bg-gray-300 dark:hover:bg-slate-500 transition';
    case 'ghost':
      return 'text-blue-600 hover:underline text-sm';
    default:
      return '';
  }
});
defineEmits(['click']);
</script>
