<!-- src/components/SessionHandler.vue -->
<template>
  <div></div>
  <!-- No renderiza nada -->
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import dialog from "@/composables/useDialogGlobal";

onMounted(() => {
  // Interceptar errores de Inertia
  router.on("finish", (event: CustomEvent) => {
    const detail = event.detail;
    const status = detail?.response?.status || detail?.data?.status || null;

    // console.warn("[Inertia error captured]" + event, { detail, status });

    if (status === 401) {
      dialog.warning({
        title: "Sesión expirada",
        content: "Tu sesión ha expirado. Por favor, inicia sesión nuevamente.",
        positiveText: "Iniciar sesión",
        onPositiveClick: () => {
          window.location.href = "/login";
        },
      });
    }

    if (status === 419) {
      dialog.warning({
        title: "Token CSRF expirado",
        content: "Tu sesión ha expirado o el token CSRF ha vencido.",
        positiveText: "Recargar página",
        onPositiveClick: () => {
          window.location.reload();
        },
      });
    }
  });

  // Interceptar errores 419 globalmente con fetch (por si falla Inertia)
  const originalFetch = window.fetch;
  window.fetch = async (...args) => {
    const response = await originalFetch(...args);

    if (response.status === 419) {
      dialog.warning({
        title: "Token CSRF expirado",
        content: "Tu sesión ha expirado o el token CSRF ha vencido.",
        positiveText: "Recargar página",
        onPositiveClick: () => {
          window.location.reload();
        },
      });
    }

    return response;
  };
});
</script>
