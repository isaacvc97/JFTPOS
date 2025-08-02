<template>
  <n-data-table
    :columns="columns"
    :data="cartItems"
    :pagination="false"
    :scroll-x="1000"
  />
</template>

<script setup>
import { ref, watch, h } from "vue";
import { NInputNumber, NSelect, NDataTable, NButton } from "naive-ui";
import message from "@/composables/useMessageGlobal";
import axios from "axios";

const props = defineProps({
  initialItems: { type: Array, default: () => [] },
});

const cartItems = ref([...props.initialItems]);

watch(
  () => props.initialItems,
  (val) => {
    cartItems.value = [...val];
  }
);

const updateItem = async (item, key, value) => {
  const old = item[key];
  item[key] = value;

  try {
    await axios.put(`/api/cart/items/${item.id}`, {
      [key]: value,
    });
    message.success("Actualizado");
  } catch (e) {
    item[key] = old;
    message.error("Error al actualizar");
  }
};

const columns = [
  {
    title: "Medicamento",
    key: "medicine_name",
  },
  {
    title: "Presentación",
    key: "presentation_id",
    render(row) {
      return h(NSelect, {
        value: row.presentation_id,
        options: row.presentation_options || [],
        "on-update:value": (val) => updateItem(row, "presentation_id", val),
      });
    },
  },
  {
    title: "Cantidad",
    key: "quantity",
    render(row) {
      return h(NInputNumber, {
        value: row.quantity,
        min: 1,
        "on-update:value": (val) => updateItem(row, "quantity", val),
      });
    },
  },
  {
    title: "Subtotal",
    key: "subtotal",
    render(row) {
      return `$${(row.quantity * row.price).toFixed(2)}`;
    },
  },
  {
    title: "",
    key: "actions",
    render(row) {
      return h(
        NButton,
        {
          size: "small",
          type: "error",
          onClick: () => removeItem(row),
        },
        { default: () => "Eliminar" }
      );
    },
  },
];

const removeItem = async (item) => {
  try {
    await axios.delete(`/api/cart/items/${item.id}`);
    cartItems.value = cartItems.value.filter((i) => i.id !== item.id);
    message.success("Eliminado");
  } catch (e) {
    message.error("No se pudo eliminar");
  }
};
</script>
