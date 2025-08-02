<template>
  <div xstyle="height: 100%; transform: translate(0)">
    <n-float-button :right="20" :bottom="20" :menu-trigger="isMobile ? 'click' : 'hover'">
      <n-icon :component="MenuIcon" />
      <template #menu>
        <n-tooltip
          v-for="item in menuItens"
          :key="item.label"
          trigger="hover"
          placement="left"
          class="text-xs font-semibold rounded-2xl bg-slate-400 text-slate-800 dark:bg-slate-800 dark:text-slate-200"
          xstyle="border-radius: 10px; background-color: gray; color: white; padding: 5px"
        >
          <template #trigger>
            <n-float-button
              @click="handleClick(item.action)"
              shape="circle"
              type="primary"
            >
              <n-icon :component="item.icon" />
            </n-float-button>
          </template>
          {{ item.label }}
        </n-tooltip>
      </template>
    </n-float-button>
  </div>
</template>

<script lang="ts" setup>
import { ref } from "vue";
import { NFloatButton, NIcon, NTooltip } from "naive-ui";
import { MenuIcon, ShoppingCart } from "lucide-vue-next";
import useDeviceDetection from "@/composables/useDeviceDetection";

const { isMobile } = useDeviceDetection();

const menuItens = ref([{ icon: ShoppingCart, label: "Nueva venta", action: "create" }]);

const handleClick = (action: any) => {
  console.log(action);
  emits("create");
};

const emits = defineEmits(["create"]);
</script>
