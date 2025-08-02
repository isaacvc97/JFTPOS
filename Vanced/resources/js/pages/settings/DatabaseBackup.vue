<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Appearance settings" />

    <SettingsLayout>
      <n-card
        title="Gestión de Backups de Base de Datos"
        :bordered="false"
        style="height: max-content"
      >
        <n-space vertical size="large">
          <!-- Generar backup -->
          <n-form :model="form">
            <n-form-item label="Tablas a respaldar (opcional)">
              <n-select
                v-model:value="form.tablas"
                :options="tablaOptions"
                multiple
                filterable
                placeholder="Selecciona tablas o deja vacío para todo"
              />
            </n-form-item>
            <n-button type="primary" :loading="loading" @click="generarBackup">
              Generar Backup
            </n-button>
          </n-form>

          <!-- Importar backup -->
          <div>
            <n-upload :max="1" accept=".sql" :custom-request="handleUpload">
              <n-button type="info">Importar Backup (.sql)</n-button>
            </n-upload>
          </div>

          <n-alert v-if="error" type="error">{{ error }}</n-alert>
        </n-space>
        <!-- Listar backups guardados -->
        <n-divider>Backups disponibles</n-divider>
        <n-table :single-line="false" :bordered="true">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Tamaño (KB)</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="file in backups" :key="file.name">
              <td>{{ file.name }}</td>
              <td>{{ file.size_kb }}</td>
              <td>{{ new Date(file.created_at * 1000).toLocaleString() }}</td>
              <td>
                <n-button size="small" @click="descargarBackup(file.name)">
                  Descargar
                </n-button>
              </td>
            </tr>
          </tbody>
        </n-table>
      </n-card>
    </SettingsLayout>
  </AppLayout>
</template>

<script setup lang="ts">
import axios from "axios";
import { ref, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import {
  NCard,
  NSpace,
  NForm,
  NFormItem,
  NUpload,
  NButton,
  NAlert,
  NSelect,
  NDivider,
  NTable,
} from "naive-ui";
import AppLayout from "@/layouts/AppLayout.vue";
import SettingsLayout from "@/layouts/settings/Layout.vue";
import { useMessageGlobal } from "@/composables/useMessageGlobal";

const message = useMessageGlobal();

const breadcrumbItems = [
  {
    title: "Configuracion",
    href: "/settings",
  },
  {
    title: "Base de datos",
    href: "/settings/backup",
  },
];

interface File {
  name: string;
  size_kb: string;
  file: File;
  created_at: number;
}
const backups = ref<File[]>([]);
const loading = ref(false);
const error = ref(null);

const form = ref({
  tablas: [],
});
const tablaOptions = ref([]);

const generarBackup = async () => {
  error.value = null;
  loading.value = true;

  try {
    const response = await axios.post(
      "/settings/backup",
      {
        tablas: form.value.tablas,
      },
      {
        responseType: "blob",
      }
    );

    const blob = new Blob([response.data], { type: "application/sql" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    const fecha = new Date().toISOString().slice(0, 19).replace(/:/g, "-");
    link.download = `backup-${fecha}.sql`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    message.success("Backup generado correctamente");
  } catch (err: any) {
    error.value = err.response?.data?.message || "Error al generar backup";
  } finally {
    loading.value = false;
  }
};

const handleUpload = async ({ file }: { file: any }) => {
  const formData = new FormData();
  formData.append("file", file.file);

  try {
    await axios.post("/settings/backup/import", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    message.success("Backup importado correctamente");
  } catch (err: any) {
    error.value = err.response?.data?.message || "Error al importar backup";
  }
};

const fetchBackups = async () => {
  try {
    const res = await axios.get("/settings/backup/list");
    backups.value = res.data;
  } catch (err) {
    console.error(err);
    message.error("No se pudieron cargar los backups");
  }
};

const descargarBackup = (name: string) => {
  const url = `/settings/backup/download/${name}`;
  window.open(url, "_blank");
};

onMounted(() => {
  fetchTablas();
  fetchBackups();
});

// Obtener las tablas disponibles para exportar
const fetchTablas = async () => {
  try {
    const res = await axios.get("/settings/backup/tables");
    tablaOptions.value = res.data.map((t: string) => ({ label: t, value: t }));
  } catch (err) {
    console.error(err);
    message.error("No se pudieron obtener las tablas");
  }
};

onMounted(fetchTablas);
</script>
