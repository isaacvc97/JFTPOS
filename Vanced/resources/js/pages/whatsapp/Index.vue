<template>
  <div class="mx-auto">
      <div class="cmx-auto my-2 lg:my-6 w-full min-w-80 bg-white border border-gray-200 rounded-2xl shadow-lg p-4 zc-50 overflow-y-auto max-h-[85vh]">
        
        <div v-show="isConnected">
          <!-- Search -->
          <input
            v-model="search"
            type="text"
            placeholder="Buscar chat..."
            class="w-full mb-3 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-1 focus:ring-gray-400 outline-none transition"
          />

          <!-- Chat List -->
          <div v-if="filteredChats.length === 0" class="text-gray-400 text-sm text-center py-4">
            No hay chats
          </div>
          <ul v-else class="space-y-2">
            <li
              v-for="chat in filteredChats"
              :key="chat.id._serialized"
              @click="openChat(chat)"
              class="cursor-pointer hover:bg-gray-100 p-2 rounded-xl transition"
            >
              <div class="flex justify-between items-center">
                <div class="truncate">
                  <p class="font-medium text-gray-900">
                    {{ chat.name }} <span class="text-sm text-gray-500">({{ chat.sender?.name }})</span>
                  </p>
                  <p :class="chat.unread ? 'font-semibold text-gray-800' : 'text-gray-500'" class="text-sm truncate">
                    {{ chat.lastMessage }}
                  </p>
                </div>
                <span class="text-xs text-gray-400 ml-2 whitespace-nowrap">
                  {{ chat.lastMessageTime }}
                </span>
              </div>
            </li>
          </ul>
        </div>

        <!-- QR Component -->
        <WhatsappQr @connected="handleConnection" />
      </div>
    </div>

    <!-- Chat Modal -->
    <transition name="fade">
      <div v-if="selectedChat" class="fixed inset-0 bg-black/10 bg-opacity-30 flex items-center justify-center z-50">
        <div class="bg-white w-full max-w-md h-[75vh] rounded-2xl shadow-xl p-4 flex flex-col border border-gray-200">
          <div class="flex justify-between items-center mb-3">
            <h2 class="text-base font-semibold text-gray-900">Chat con {{ selectedChat.name }}</h2>
            <button @click="closeChat" class="text-sm text-gray-500 hover:text-red-500 transition">
              Cerrar
            </button>
          </div>

          <!-- Messages -->
          <div ref="messageContainer" class="flex-1 overflow-y-auto space-y-2 px-1 scroll-smooth">
            <div v-if="isLoading" class="text-sm text-gray-400">Cargando mensajes...</div>
            <template v-else>
              <div
                v-for="msg in messages"
                :key="msg.id._serialized"
                :class="msg.fromMe ? 'text-right' : 'text-left'"
              >
                <div
                  class="inline-block max-w-[80%] rounded-2xl px-4 py-2 text-sm"
                  :class="msg.fromMe ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-800'"
                >
                  <div v-if="isBase64Image(msg.body)">
                    <img :src="'data:image/jpeg;base64,' + msg.body" class="rounded-xl max-w-full max-h-60" />
                  </div>
                  <div v-else>
                    {{ msg.body }}
                  </div>
                  <div class="text-[10px] text-gray-400 mt-1 text-right">
                    {{ formatTime(msg.timestamp) }}
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Input -->
          <form @submit.prevent="sendMessage" class="flex gap-2 mt-3">
            <input
              v-model="newMessage"
              type="text"
              placeholder="Escribe un mensaje..."
              class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-1 focus:ring-gray-400 outline-none"
            />
            <button
              type="submit"
              class="bg-gray-900 text-white px-4 py-2 rounded-xl text-sm hover:bg-black transition"
            >
              Enviar
            </button>
          </form>
        </div>
      </div>
    </transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';
import axios from 'axios';
import socket from './socket.js';
import { API_WHATSAPP } from './env.js';
import WhatsappQr from './WhatsappQr.vue';

// Refs
const isConnected = ref(localStorage.getItem('status-ws') === 'connected');
const search = ref('');
const chats = ref([]);
const selectedChat = ref(null);
const messages = ref([]);
const newMessage = ref('');
const isLoading = ref(false);
const messageContainer = ref(null);

// Computed
const filteredChats = computed(() =>
  chats.value.filter((chat) =>
    chat.name?.toLowerCase().includes(search.value.toLowerCase())
  )
);

// Scroll to bottom
const scrollToBottom = () => {
  nextTick(() => {
    const el = messageContainer.value;
    if (el) el.scrollTop = el.scrollHeight;
  });
};

// Open/close chat
const openChat = async (chat) => {
  selectedChat.value = chat;
  isLoading.value = true;
  try {
    const cached = localStorage.getItem(`chat-${chat.id._serialized}`);
    if (cached) {
      messages.value = JSON.parse(cached);
    } else {
      const { data } = await axios.get(`${API_WHATSAPP}/chat/${chat.id._serialized}`);
      messages.value = data;
      localStorage.setItem(`chat-${chat.id._serialized}`, JSON.stringify(data));
    }
    scrollToBottom();
    markChatAsRead(chat.id._serialized);
  } catch (e) {
    console.error("Error al cargar mensajes", e);
  } finally {
    isLoading.value = false;
  }
};

const closeChat = () => {
  selectedChat.value = null;
  newMessage.value = '';
};

const sendMessage = async () => {
  if (!newMessage.value.trim()) return;

  const msg = {
    fromMe: true,
    body: newMessage.value,
    id: Date.now(),
    timestamp: Date.now(),
  };

  try {
    await axios.post(`${API_WHATSAPP}/send-message`, {
      to: selectedChat.value.id._serialized,
      message: newMessage.value,
    });

    messages.value.push(msg);
    localStorage.setItem(`chat-${selectedChat.value.id._serialized}`, JSON.stringify(messages.value));
    updateInboxPreview(selectedChat.value.id._serialized, msg.body);
    newMessage.value = '';
    scrollToBottom();
  } catch (e) {
    console.error("Error enviando mensaje", e);
  }
};

// Utilidades
const isBase64Image = (str) => /^[A-Za-z0-9+/=\s]{200,}$/.test(str);

const formatTime = (timestamp) => {
  const date = new Date(timestamp);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const markChatAsRead = (id) => {
  const index = chats.value.findIndex(c => c.id === id);
  if (index >= 0) {
    chats.value[index].unread = false;
    localStorage.setItem('chats', JSON.stringify(chats.value));
  }
};

const updateInboxPreview = (chatId, body) => {
  const preview = isBase64Image(body) ? '[Imagen]' : body.slice(0, 30);
  const index = chats.value.findIndex((c) => c.id === chatId);

  if (index >= 0) {
    chats.value[index].lastMessage = preview;
    chats.value[index].unread = true;
    const chat = chats.value.splice(index, 1)[0];
    chats.value.unshift(chat);
  } else {
    chats.value.unshift({ id: chatId, name: chatId, lastMessage: preview, unread: true });
  }

  localStorage.setItem("chats", JSON.stringify(chats.value));
};

// Chat connection
const fetchChats = async () => {
  try {
    localStorage.removeItem('chats');
    const { data } = await axios.get(`${API_WHATSAPP}/inbox`);
    chats.value = data;
    localStorage.setItem("chats", JSON.stringify(data));
  } catch (e) {
    console.error("Error obteniendo chats", e);
  }
};

const handleConnection = () => {
  isConnected.value = true;
  localStorage.setItem('status-ws', 'connected');
  fetchChats();
};

// Notificaciones
const notify = (msg) => {
  const beep = new Audio("/sounds/notification.mp3");
  beep.play().catch(() => {});
  if (document.hidden && "Notification" in window && Notification.permission === "granted") {
    new Notification(`Nuevo mensaje de ${msg.from}`, { body: msg.body });
  }
};

// Socket
socket.on("whatsapp-message", async (msg) => {
  console.log(msg)
  // if (msg.fromMe) {
  //   await fetch('http://0.0.0.0:8050/procesar', {
  //     method: 'POST',
  //     headers: { 'Content-Type': 'application/json' },
  //     body: JSON.stringify({ text: msg.body }),
  //   })
  // }
  const chatId = msg.chatId || msg.from;
  const key = `chat-${chatId}`;
  const message = {
    fromMe: msg.fromMe,
    body: msg.body,
    id: msg.id,
    timestamp: msg.timestamp || Date.now(),
  };

  if (!msg.fromMe) notify(msg);

  const existing = JSON.parse(localStorage.getItem(key) || "[]");
  existing.push(message);
  localStorage.setItem(key, JSON.stringify(existing));

  if (selectedChat.value?.id === chatId) {
    messages.value = existing;
    scrollToBottom();
  }

  updateInboxPreview(chatId, msg.body);
});

// Lifecycle
onMounted(fetchChats);
onUnmounted(() => socket.off("whatsapp-message"));
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-thumb {
  background-color: rgba(100, 100, 100, 0.2);
  border-radius: 4px;
}
</style>
