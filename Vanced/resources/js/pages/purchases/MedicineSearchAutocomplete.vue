<template>
  <n-auto-complete
    v-model:value="search"
    :options="options"
    :get-show="() => true"
    :loading="loading"
    placeholder="Buscar producto (teclado)"
    clearable
    @select="onSelect"
    @blur="search = ''"
  />
</template>

<script setup>
import { ref, watch } from "vue";
import { NAutoComplete } from "naive-ui";
import axios from "axios";
import debounce from "lodash/debounce";
import message from "@/composables/useMessageGlobal";
const emit = defineEmits(["presentation-selected"]);

const search = ref("");
const loading = ref(false);
const options = ref([]);

const fetchMedicines = debounce(async (term) => {
  if (!term) return;
  loading.value = true;
  try {
    const { data } = await axios.get("/medicines/search", { params: { search: term } });

    options.value = data.map((m) => ({
      label: `${m.medicine_name} (${m.concentration} - ${m.form_name}) [Stock: ${m.stock}]`,
      value: m.medicine_id,
      raw: m,
    }));
  } catch (e) {
    message.error("Error al buscar productos" + e);
  } finally {
    loading.value = false;
  }
}, 300);

watch(search, (val) => fetchMedicines(val));

const onSelect = async (id) => {
  const medicine = options.value.find((o) => o.value === id)?.raw;
  if (!medicine) return;

  try {
    const { data } = await axios.get(`/medicines/${id}/presentations`);
    emit("presentation-selected", { medicine, presentations: data });
  } catch (e) {
    message.error("No se pudieron cargar las presentaciones");
  }
};
</script>
