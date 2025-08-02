<template>
  <n-layout>
    <!-- <n-layout-header bordered>
      <h2 style="margin: 0; padding: 5px">📥 WhatsApp Inbox</h2>
    </n-layout-header> -->
    <n-layout-content v-if="isOnline" style="padding: 5px">
      <n-list bordered>
        <n-list-item
          v-for="msg in messages"
          :key="msg.id"
          @click="openChat(msg)"
          style="cursor: pointer"
        >
          <template #prefix>
            <n-icon :size="24"><MessageCircle /></n-icon>
          </template>
          <div>
            <div>
              <strong>{{ msg.contact?.name }}</strong>
              <span class="text-sm text-gray-500">({{ msg.id?._serialized }})</span>
            </div>
            <div style="font-size: 13px; color: gray">{{ msg.lastMessage }}</div>
          </div>
        </n-list-item>
      </n-list>
    </n-layout-content>

    <WhatsappQr
      v-else
      @connected="
        (status) => {
          isOnline = status;
          console.info('Whatsapp status: ', status);
          fetchMessages();
        }
      "
    />

    <n-modal v-model:show="chatModalOpen" preset="dialog" style="width: 500px">
      <ChatModal :message="selectedMessage" @close="chatModalOpen = false" />
    </n-modal>
  </n-layout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import axios from "axios";
import {
  NList,
  NListItem,
  NIcon,
  NLayout,
  // NLayoutHeader,
  NLayoutContent,
  NModal,
} from "naive-ui";
import { MessageCircle } from "lucide-vue-next";
import ChatModal from "./ChatModal.vue";
import socket from "./socket.js";
import { API_WHATSAPP } from "./env.js";
import WhatsappQr from "./WhatsappQr.vue";

const messages = ref([]);
const chatModalOpen = ref(false);
const selectedMessage = ref(null);

const isOnline = ref(false);

const fetchMessages = async () => {
  const res = await axios.get(`${API_WHATSAPP}/inbox`);
  messages.value = res.data;
};

const openChat = (msg) => {
  selectedMessage.value = msg;
  chatModalOpen.value = true;
};

socket.on("whatsapp-message", async (msg) => {
  console.log(msg);
  messages.value = messages.value.filter((m) => m.id !== msg.id); // Remove duplicates
  messages.value.unshift({
    id: msg.id,
    contact: msg.contact || { name: msg.from },
    lastMessage: msg.body.length > 50 ? msg.body.substring(0, 50) + "..." : msg.body,
    timestamp: new Date(msg.timestamp * 1000).toLocaleString(),
  });
  // Optionally, you can save the message to localStorage
  // const messages = JSON.parse(localStorage.getItem(`chat-${msg.chatId || msg.from}`)) || [];
  // messages.unshift({
  //   id: msg.id,
  //   contact: msg.contact || { name: msg.from },
  //   lastMessage: msg.body.length > 50 ? msg.body.substring(0, 50) + '...' : msg.body,
  //   timestamp: new Date(msg.timestamp * 1000).toLocaleString(),
  // });
  // localStorage.setItem(`chat-${msg.chatId || msg.from}`, JSON.stringify(messages));
  // Uncomment the following lines if you want to update localStorage with the new message
  // const messages = ref(JSON.parse(localStorage.getItem(`chat-${msg.chatId || msg.from}`)) || []);
  // if (messages.value.length >= 100) {
  //   messages.value.pop(); // Remove the oldest message if we have more than 100
  // }
  // messages.value.unshift({
  //   id: msg.id,
  //   contact: msg.contact || { name: msg.from },
  //   lastMessage: msg.body.length > 50 ? msg.body.substring(0, 50) + '...' : msg.body,
  //   timestamp: new Date(msg.timestamp * 1000).toLocaleString(),
  // });
  // const chatId = msg.chatId || msg.from;
  // const key = `chat-${chatId}`;
  // const message = {
  //   id: msg.id,
  //   sender: msg.from,
  //   preview: msg.body.length > 50 ? msg.body.substring(0, 50) + '...' : msg.body,
  //   timestamp: new Date(msg.timestamp * 1000).toLocaleString(),
  // };
  // const existingIndex = messages.value.findIndex(m => m.id === message.id);
  // if (existingIndex !== -1) {
  //   messages.value[existingIndex] = message; // Update existing message
  // } else {
  //   messages.value.unshift(message); // Add new message at the top
  // }
  // localStorage.setItem(key, JSON.stringify(messages.value));
  // if (selectedMessage.value && selectedMessage.value.id === msg.id) {
  //   selectedMessage.value = message; // Update chat modal if it's open
  // }
});
onMounted(fetchMessages);
onUnmounted(() => socket.off("whatsapp-message"));
</script>
