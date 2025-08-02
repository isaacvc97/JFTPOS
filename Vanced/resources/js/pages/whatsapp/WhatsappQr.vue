<template>
  <div
    xif="!isConnected"
    class="flex flex-col items-center justify-center bg-white text-gray-800 mx-auto"
  >
    <h4 class="text-xl font-semibold mb-4 tracking-tight">Scanear desde app</h4>

    <div v-if="qr">
      <img
        :src="qr"
        alt="QR de WhatsApp"
        class="w-64 h-64 border border-gray-200 rounded"
      />
      <p class="w-full text-center text-sm text-gray-500 mt-2">
        Inicia instancia de whatsapp web
      </p>
    </div>

    <div v-else>
      <p class="text-gray-400">Esperando código QR...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { API_WHATSAPP } from "./env.js";
import axios from "axios";

const qr = ref(null);
const isConnected = ref(false);

let intervalId = null;

const QRandStatus = async () => {
  try {
    const response = await axios.get(`${API_WHATSAPP}/get-qr`);
    qr.value = response.data.qr;
  } catch (e) {
    qr.value = null;
    console.log(e);
  }

  try {
    const res = await axios.get(`${API_WHATSAPP}/status`);
    console.log(res.data);
    if (res.data.connected) {
      isConnected.value = true;
      emit("connected", true);
      clearInterval(intervalId);
    }
  } catch (e) {
    console.log(e);
  }
};

const emit = defineEmits(["connected"]);

onMounted(() => {
  QRandStatus();

  intervalId = setInterval(QRandStatus, 5000);
});

onBeforeUnmount(() => {
  // Limpia el intervalo al desmontar el componente
  clearInterval(intervalId);
});
</script>
