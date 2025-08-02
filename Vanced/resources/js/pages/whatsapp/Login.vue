<template>
  <div>
    <h1>Escanea el código QR</h1>

    <div v-if="status === 'qr_required'">
      <img
        :src="`data:image/png;base64,${qr}`"
        alt="Código QR de WhatsApp"
        class="w-64 h-64"
      />
      <p>Escanea el QR con tu teléfono</p>
    </div>

    <div v-if="status === 'logged_in'">
      <p>Sesión iniciada correctamente.</p>
    </div>

    <div v-if="status === 'message_sent'">
      <p>Mensaje enviado al número {{ phone }}</p>
    </div>

    <div v-if="status === 'error'">
      <p class="text-red-500">Error: {{ errorMessage }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import { API_WHATSAPP } from "./env.js";


const status = ref(null);
const qr = ref(null);
const phone = ref("51999999999"); // o usar input
const message = ref("Hola desde Laravel + Vue");
const errorMessage = ref(null);

onMounted(() => {
  pollStatus();
});

function pollStatus() {
  const poll = async () => {
    try {
      const response = await axios.post(`${API_WHATSAPP}/check`, {
        numero: phone.value,
        mensaje: message.value,
      });

      const res = response.data;
      status.value = res.status;

      if (res.status === "qr_required") {
        qr.value = res.qr;
        setTimeout(poll, 4000); // sigue intentando hasta login
      } else if (res.status === "logged_in") {
        setTimeout(poll, 4000); // espera mensaje enviado
      } else if (res.status === "message_sent") {
        // Fin del ciclo
      } else {
        errorMessage.value = res.message ?? "Error desconocido";
      }
    } catch (e) {
      errorMessage.value = e.message;
    }
  };

  poll();
}
</script>
