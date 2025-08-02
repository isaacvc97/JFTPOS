import { ref } from 'vue';

export function useChats() {
  const chats = ref([]);
  const selectedChat = ref(null);
  const messages = ref([]);
  const newMessage = ref('');
  const isLoading = ref(false);

  // Notificaciones
  const notify = (msg) => {
    const beep = new Audio("/sounds/notification.mp3");
    beep.play().catch(() => {});
    if (document.hidden && "Notification" in window && Notification.permission === "granted") {
      new Notification(`Nuevo mensaje de ${msg.from}`, { body: msg.body });
    }
  };

  const fetchChats =  async () => {
    // Lógica para obtener los chats desde localStorage
    try {
      localStorage.clear();
      const { data } = await axios.get(`${API_WHATSAPP}/inbox`);
      chats.value = data;
      localStorage.setItem("chats", JSON.stringify(data));
    } catch (e) {
      console.error("Error obteniendo chats", e);
    }
  };

  const updateChat = (chatId, body) => {
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

  const sendMessage = () => {
    // Lógica para enviar un mensaje y actualizar localStorage
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

  const closeChat = () => {
    selectedChat.value = null;
    messages.value = [];
  };

  // Socket
  socket.on("whatsapp-message", (msg) => {
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

  return {
    chats,
    selectedChat,
    messages,
    newMessage,
    isLoading,
    fetchChats,
    updateChat,
    sendMessage,
    closeChat
  };
}
