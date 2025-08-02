<template>
  <div
    :class="[
      'flex items-start gap-2 p-2 rounded-xl cursor-pointer transition-all',
      { 'bg-blue-100': !msg.fromMe, 'bg-blue-500 text-white': msg.fromMe }
    ]"
  >
    <div class="flex flex-col">
      <span class="font-semibold">{{ msg.sender?.name }}</span>
      <span class="text-sm truncate">{{ msg.body }}</span>
    </div>
    <div class="text-xs text-gray-400">{{ formatTime(msg.timestamp) }}</div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
  msg: Object,
  formatTime: Function
});
</script>
