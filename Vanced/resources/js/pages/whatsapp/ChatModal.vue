<template>
  <div class="w-full">
    <div class="text-lg font-semibold mb-4">
      Chat con {{ message.sender }}
    </div>

    <div class="max-h-[300px] overflow-y-auto xbg-gray-100 rounded-lg p-4 space-y-3 mb-4">
      <div
        v-for="(msg, i) in chat"
        :key="i"
        :class="[
          'flex flex-col',
          msg.fromMe ? 'items-end text-right' : 'items-start text-left'
        ]"
      >
        <!-- Imagen base64 -->
        <n-image
          v-if="isBase64Image(msg.body)"
          :src="getImageSrc(msg)"
          width="200"
          lazy
          :previewed-img-props="{ style: { maxHeight: '80vh' } }"
          class="rounded-md shadow"
        />
        <!-- Texto -->
        <n-tag
          v-else
          :type="msg.fromMe ? 'success' : 'default'"
          class="mb-1"
        >
          {{ msg.body }}
        </n-tag>
        <div class="text-[10px] text-gray-400 mt-1 text-right">
          {{ formatTime(msg.timestamp) }}
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <n-input
        v-model:value="newMessage"
        placeholder="Escribe un mensaje"
        @keyup.enter="sendMessage"
        class="flex-1"
      />
      <n-button type="primary" @click="sendMessage">Enviar</n-button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { NTag, NInput, NButton, NImage } from 'naive-ui';
import socket from './socket.js';
import { API_WHATSAPP } from './env.js';
const props = defineProps({
  message: Object
});


const chat = ref([]);
const newMessage = ref('');
// Utilidades
const isBase64Image = (str) => /^[A-Za-z0-9+/=\s]{200,}$/.test(str);
const formatTime = (timestamp) => {
  const date = timestamp ? new Date(timestamp) : new Date();
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
const getImageSrc = (msg) => {
  // Usa la imagen en alta si está disponible
  if (msg.fileUrl) return msg.fileUrl;

  // Si no hay URL, usa base64 como fallback
  return `data:${msg.mimetype || 'image/jpeg'};base64,${msg.body}`;
};
const fetchChat = async () => {
  const res = await axios.get(`${API_WHATSAPP}/chat/${props.message.id._serialized}`);
  chat.value = res.data;
};

const sendMessage = async () => {
  if (!newMessage.value.trim()) return;

  await axios.post(`${API_WHATSAPP}/send-message`, {
    to: props.message.id._serialized,
    message: newMessage.value
  });

  chat.value.push({
    fromMe: true,
    type: 'text',
    body: newMessage.value
  });

  newMessage.value = '';
};

watch(() => props.message, () => {
  if (props.message) fetchChat();
}, { immediate: true });
</script>
