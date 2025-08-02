<template>
  <div class="flex items-center gap-2 w-full">
    <n-auto-complete
      v-model:value="searchText"
      :options="suggestions"
      size="large"
      placeholder="Buscar producto..."
      :clearable="true"
      :input-props="{ round: true }"
      @search="handleSearch"
    >
      <template #prefix>
        <n-icon>
          <Search />
        </n-icon>
      </template>
    </n-auto-complete>

    <n-dropdown
      trigger="click"
      placement="bottom-end"
      :show="showDropdown"
      @clickoutside="showDropdown = false"
    >
      <template #trigger>
        <n-button circle size="large" @click="showDropdown = !showDropdown">
          <template #icon>
            <n-icon :component="Search" />
          </template>
        </n-button>
      </template>

      <div class="px-4 py-2 max-w-lg">
        <!-- Slot para filtros -->
        <slot name="filtros" />
      </div>
    </n-dropdown>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { NAutoComplete, NButton, NIcon, NDropdown } from "naive-ui";
import { Search } from "lucide-vue-next";

const props = defineProps({
  suggestions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["search"]);

const searchText = ref("");
const showDropdown = ref(false);

function handleSearch(value) {
  emit("search", value);
}
</script>
