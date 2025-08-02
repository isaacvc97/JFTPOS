<template>
  <div class="relative">
    <input
      v-model="search"
      type="text"
      placeholder="Buscar chat..."
      class="w-full p-2 mb-3 border rounded-xl"
    />
    <div v-if="filteredChats.length === 0" class="text-gray-500">No hay chats</div>
    <ul>
      <li
        v-for="chat in filteredChats"
        :key="chat.id"
        @click="openChat(chat)"
        class="p-2 mb-2 rounded-xl cursor-pointer hover:bg-gray-100"
      >
        <MessageItem :msg="chat" :formatTime="formatTime" />
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useChats } from '../composables/useChats.js';
import MessageItem from './MessageItem.vue';

const { chats, fetchChats, updateChat } = useChats();
const search = ref('');
const filteredChats = computed(() =>
  chats.value.filter(chat => chat.name.toLowerCase().includes(search.value.toLowerCase()))
);

const openChat = chat => {
  // Lógica para abrir el chat
};

const formatTime = timestamp => {
  const date = new Date(timestamp);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

fetchChats();
</script>
