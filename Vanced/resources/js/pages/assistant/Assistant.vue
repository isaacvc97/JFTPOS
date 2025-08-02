<template>
  <div class="p-4 space-y-2">
    <h2 class="text-xl font-bold">Asistente de Medicamentos</h2>
    <div class="border rounded-xl p-4 bg-white shadow space-y-2 h-96 overflow-y-auto">
      <div v-for="(msg, i) in mensajes" :key="i" class="text-sm">
        <span class="font-bold" v-if="msg.tipo === 'usuario'">Tú:</span>
        <span class="font-bold" v-if="msg.tipo === 'asistente'">Asistente:</span>
        <span> {{ msg.texto }}</span>
      </div>
    </div>

    <input
      v-model="texto"
      @keyup.enter="enviar"
      class="border p-2 w-full rounded-lg"
      placeholder="Escribe tu mensaje..."
    />

    <button @click="enviar" class="bg-blue-600 text-white px-4 py-2 rounded-lg w-full">
      Enviar
    </button>

    <div v-if="requiereConfirmacion" class="flex justify-between space-x-2 pt-2">
      <button @click="confirmar" class="bg-green-500 text-white flex-1 py-2 rounded-lg">
        Confirmar
      </button>
      <button @click="cancelar" class="bg-red-500 text-white flex-1 py-2 rounded-lg">
        Cancelar
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import axios from "axios";

const texto = ref("");
const mensajes = ref([]);
const requiereConfirmacion = ref(false);

async function enviar() {
  if (!texto.value.trim()) return;

  mensajes.value.push({ tipo: "usuario", texto: texto.value });

  const res = await axios.post(
    "http://localhost:8010/texto",
    {
      text: texto.value,
      session_id: "vue-user",
    },
    { responseType: "blob" }
  );

  const respuesta = res.headers["x-texto"];
  mensajes.value.push({ tipo: "asistente", texto: respuesta });

  if (respuesta.includes("¿Deseas guardar este cambio?")) {
    requiereConfirmacion.value = true;
  } else {
    requiereConfirmacion.value = false;
  }

  // reproducir respuesta
  const audio = new Audio(URL.createObjectURL(res.data));
  audio.play();

  texto.value = "";
}

function confirmar() {
  texto.value = "confirmar";
  requiereConfirmacion.value = false;
  enviar();
}

function cancelar() {
  texto.value = "cancelar";
  requiereConfirmacion.value = false;
  enviar();
}
</script>

<style scoped>
/* Usando Tailwind, o puedes adaptar a NativeScript si es necesario */
</style>
