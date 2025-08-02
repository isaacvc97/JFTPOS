<script setup lang="ts">
import { toRef, h, ref, nextTick } from "vue";
import { NButton, NDataTable, NIcon, NInput, NInputNumber, NSelect } from "naive-ui";
import { Trash } from "lucide-vue-next";
import { CartItem } from "./sale";
import { RowData, RowKey } from "naive-ui/es/data-table/src/interface";

const props = defineProps({
  items: { type: Array, required: true },
});
const emit = defineEmits(["update:item", "remove:item"]);

// Usamos una copia directa y reactiva, sin clonación innecesaria
const items = toRef<RowData[]>(props.items as RowData[]);

// Métodos

const handleQuantityChange = (item: CartItem, quantity: number | null) => {
  if (quantity) item.quantity = quantity;
  emit("update:item", { ...item });
};

const handlePresentationChange = (item: CartItem, presentation_id: number) => {
  const selected = item.presentations.find((row) => row.id === presentation_id);
  if (!selected) return;
  item.presentation_id = selected.id;
  item.unit_type = selected.unit_type;
  // item.form_name = selected.form_name;
  item.price = selected.price;

  emit("update:item", item);
};

const handleDiscountChange = (item: CartItem, discount: number | null) => {
  // if (discount) item.discount = discount;
  item.discount = discount || 0;
  emit("update:item", item);
};

const handleRemoveItem = (id: number) => {
  emit("remove:item", id);
};

const expandedRowKeys = ref<RowKey[]>([]);

interface editMap {
  [key: string]: boolean;
}
// Estados de edición por celda
const editMap = ref<editMap>({});

// Helpers para activar edición
function activateEdit(rowKey: number, field: string) {
  editMap.value[`${rowKey}-${field}`] = true;
  nextTick(() => {
    const inputEl: HTMLDivElement | null = document.querySelector(
      `[data-key="${rowKey}-${field}"] div`
    );
    inputEl?.focus();
  });
}

function deactivateEdit(rowKey: number, field: string) {
  editMap.value[`${rowKey}-${field}`] = false;
}

const columns = [
  {
    title: "Cantidad",
    key: "quantity",
    width: 80,
    render(row: any) {
      const cellKey: string = `${row.id}-quantity`;
      const isEdit = editMap.value[cellKey];
      return h(
        "div",
        {
          "data-key": cellKey,
          class: "cursor-context-menu",
          style: "min-height: 22px;",
          onClick: () => activateEdit(row.id, "quantity"),
        },
        isEdit
          ? [
              h(NInputNumber, {
                min: 1,
                size: "large",
                value: row.quantity,
                onBlur: () => deactivateEdit(row.id, "quantity"),
                onChange: (val) =>
                  row.quantity != val ? handleQuantityChange(row, val) : null,
              }),
              h(NSelect, {
                size: "large",
                style: "min-width: 300px",
                onBlur: () => deactivateEdit(row.id, "quantity"),
                renderLabel: (option: any) => {
                  return `${option.label}`;
                  // return `${option.quantity} ${option.unit_type} $${option.price}`;
                },
                value: row.presentation_id,
                options: row.presentations,
                onChange: (val) => handlePresentationChange(row, val),
              }),
            ]
          : `${row.quantity} ${row.unit_type}`
      );
    },
  },
  {
    title: "Medicamento",
    key: "name",
    width: 150,
    render(row: CartItem) {
      return `${row.name}`;
      // return `${row.name} - ${row.concentration} (${row.form})` + (row.iva ? " 🧾" : "");
    },
  },
  {
    title: "$ Precio",
    key: "price",
    width: 80,
    render(row: CartItem) {
      const price = Number(row.price);
      return `$${price.toFixed(2)}`;
    },
  },
  {
    title: "Desc. %",
    key: "discount",
    width: 90,
    render(row: CartItem) {
      const cellKey: string = `${row.id}-discount`;
      const isEdit = editMap.value[cellKey];
      return h(
        "div",
        {
          style: "min-height: 22px",
          class: "cursor-context-menu",
          onClick: () => activateEdit(row.id, "discount"),
        },
        isEdit
          ? h(NInput, {
              size: "large",
              defaultValue: row.discount?.toString(),
              // defaultValue: row.discount,
              min: 0.0,
              // updateValueOnInput: false,
              onBlur: () => deactivateEdit(row.id, "discount"),
              onChange: (val) => {
                const change = row.discount != Number(val);
                if (change) handleDiscountChange(row, Number(val));
              },
            })
          : row.discount || 0 // + "%"
      );
    },
  },
  {
    title: "Subtotal",
    key: "subtotal",
    width: 80,
    render(row: CartItem) {
      return `$${((row.price || 0) * (row.quantity || 0) - (row.discount || 0)).toFixed(
        2
      )}`;
    },
  },
  {
    title: "",
    key: "actions",
    width: 80,
    render(row: CartItem) {
      return h(
        "div",
        { class: "" },
        h(NButton, {
          type: "error",
          tertiary: true,
          renderIcon: () => {
            return h(NIcon, { component: Trash, size: 15 });
          },
          size: "small",
          onClick: () => handleRemoveItem(row.id),
        })
      );
    },
  },
];
</script>

<template>
  <NDataTable
    :columns="columns"
    :data="items"
    :row-key="(row) => row.id"
    :bordered="false"
    :single-line="false"
    :expanded-row-keys="expandedRowKeys"
    @update:expanded-row-keys="(val) => (expandedRowKeys = val)"
    :max-height="700"
    striped
  />
</template>
