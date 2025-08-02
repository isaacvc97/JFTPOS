<script setup>
import {
  NCard,
  NSelect,
  NInputNumber,
  NInput,
  NButton,
  NIcon,
  NScrollbar,
} from "naive-ui";
import { Trash } from "lucide-vue-next";
import useScreenSize from "@/composables/useBreakpoint";

defineProps({
  items: Array,
});
const emit = defineEmits([
  "update:quantity",
  "update:discount",
  "update:presentation",
  "remove:item",
]);
const { screenSize } = useScreenSize();

function handlePresentationChange(item, val) {
  emit("update:presentation", item, val);
}
function handleQuantityChange(item, quantity, discount = false) {
  emit("update:quantity", item, quantity, discount);
}
function handleRemoveItem(id) {
  emit("remove:item", id);
}
</script>

<template>
  <!-- Mobile: Card view -->
  <NScrollbar
    v-if="screenSize === 'sm' || screenSize === 'md'"
    class="xmax-h-screen xspace-y-4"
  >
    <div class="grid sm:grid-cols-2 gap-4 md:gap-6 pr-4">
      <NCard
        v-for="item in items"
        :key="item.id"
        :title="`${item.name} ${item.concentration} (${item.form_name})`"
        class="rounded-xl shadow-sm"
        style="border-radius: 10px"
        :bordered="false"
        size="small"
        hoverable
      >
        <template #header-extra>
          <NButton type="error" size="tiny" tertiary @click="handleRemoveItem(item.id)">
            <template #icon>
              <NIcon :component="Trash" size="15" />
            </template>
            <!-- <span class="hidden sm:inline">Descartar</span> -->
          </NButton>
        </template>
        <template #default>
          <div class="text-sm text-gray-500 xmb-2">
            <span class="font-semibold">Presentación:</span>
            <NSelect
              size="small"
              valueField="id"
              labelField="unit_type"
              :value="item.presentation_id"
              :options="item.presentations"
              :render-label="(opt) => `${opt.quantity} ${opt.unit_type} - $${opt.price}`"
              @update:value="(val) => handlePresentationChange(item, val)"
            />
          </div>

          <div class="grid grid-cols-2 gap-2 text-sm">
            <div>
              <div class="text-gray-500">Cantidad</div>
              <NInputNumber
                size="small"
                :value="item.quantity"
                :min="1"
                @update:value="(val) => handleQuantityChange(item, val, false)"
              />
            </div>

            <div>
              <div class="text-gray-500">Descuento</div>
              <NInput
                size="small"
                :value="item.discount || 0.0"
                @update:value="(val) => handleQuantityChange(item, false, val)"
              />
            </div>
          </div>
          <div class="flex flex-wrap justify-between gap-4 text-sm m-2">
            <div>
              <div class="text-gray-500">Precio</div>
              <div class="font-semibold">$ {{ item.price }}</div>
            </div>

            <div>
              <div class="text-gray-500">Subtotal</div>
              <div class="font-semibold text-green-700">
                ${{
                  (
                    (item.price || 0) * (item.quantity || 0) -
                    (item.discount || 0)
                  ).toFixed(2)
                }}
              </div>
            </div>
          </div>
        </template>
      </NCard>
    </div>
  </NScrollbar>
  <!-- Desktop: Table (usa el data-table existente si deseas aquí) -->
  <div v-else>
    <slot name="table" />
  </div>
</template>
