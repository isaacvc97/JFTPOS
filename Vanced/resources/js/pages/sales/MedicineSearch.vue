<template>
  <div class="flex flex-col gap-2 md:gap-4">
    <n-select
      @search="handleSearch"
      @update:value="handleSelect"
      placeholder="Buscar medicamento nombre | generico"
      :options="options"
      :loading="loading"
      size="large"
      clearable
      filterable
      remote
    />

    <div class="flex items-center gap-2 md:gap-4">
      <n-input-number
        v-model:value="quantity"
        @keydown.enter="handleAddToCart"
        ref="quantityInputRef"
        class="max-w-1/3"
        size="large"
        clearable
      />
      <n-button
        type="primary"
        size="large"
        @click="handleAddToCart"
        :disabled="!optionSelected || loading"
      >
        Agregar al carrito
      </n-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick } from "vue";
import { useDebounceFn } from "@vueuse/core";
import { NInputNumber, NButton, NSelect } from "naive-ui";
import { CartItem } from "./sale";

interface SelectedOption {
  label: string;
  value: number;
  raw: CartItem;
}

const emit = defineEmits(["select"]);

const loading = ref(false);
const options = ref([]);

const optionSelected = ref<SelectedOption | null>(null);
const quantity = ref(1);

const quantityInputRef = ref<HTMLInputElement | null>(null);

const handleSearch = useDebounceFn(async (query) => {
  loading.value = true;

  try {
    const res = await fetch(`/medicines/search-sale/${query}`);
    const data = await res.json();

    options.value = data.map((item: CartItem) => ({
      label: `${item.name}`,
      value: item.presentation_id,
      raw: item,
    }));
    console.log("Fetched options:", options.value);
  } catch (err) {
    console.error("Error al buscar:", err);
    options.value = [];
  } finally {
    loading.value = false;
  }
}, 400);

function handleSelect(value: number, option: SelectedOption) {
  console.log("Selected:", option);

  optionSelected.value = option;

  nextTick(() => {
    if (quantityInputRef.value) {
      quantityInputRef.value.focus();
    }
  });

  return option;
}

const handleAddToCart = () => {
  const { raw } = optionSelected.value || {};
  const item = {
    ...raw,
    quantity: quantity.value,
  };

  emit("select", item);
  quantity.value = 1;
};
</script>
