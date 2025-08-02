<template>
  <div v-if="selectedChat" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center">
    <div class="bg-white w-full max-w-md h-[75vh] rounded-2xl shadow-xl p-4 flex flex-col">
      <div class="flex justify-between items-center mb-3">
        <h2 class="text-base font-semibold">{{ selectedChat.name }}</h2>
        <button @click="closeChat" class="text-sm text-red-500">Cerrar</button>
      </div>
      <div class="flex-1 overflow-y-auto space-y-2">
        <div v-if="isLoading" class="text-sm text-gray-400">Cargando mensajes...</div>
        <div v-else>
          <MessageItem v-for="msg in messages" :key="msg.id" :msg="msg" :formatTime="formatTime" />
        </div>
      </div>
      <form @submit.prevent="sendMessage" class="flex gap-2 mt-3">
        <input
          v-model="newMessage"
          type="text"
          placeholder="Escribe un mensaje..."
          class="flex-1 border border-gray-300 rounded-xl px-3 py-1.5 text-sm"
        />
        <button type="submit" class="bg-blue-500 text-white px-4 py-1.5 rounded-xl text-sm">Enviar</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useChats } from '../composables/useChats.js';
import MessageItem from './MessageItem.vue';

const { selectedChat, messages, isLoading, newMessage, sendMessage, closeChat } = useChats();
</script>
