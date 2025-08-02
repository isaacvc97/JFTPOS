<template>
  <div style="height: 100%; transform: translate(0)">
    <n-float-button :right="10" :bottom="10" menu-trigger="click">
      <n-icon :component="MenuIcon" />
      <template #menu>
        <n-tooltip
          v-for="item in menuItens"
          :key="item.label"
          trigger="hover"
          placement="right"
        >
          <template #trigger>
            <n-float-button
              @click="handleClick(item.action)"
              shape="square"
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
import { MenuIcon, UserPlus } from "lucide-vue-next";

const menuItens = ref([{ icon: UserPlus, label: "Crear cliente", action: "create" }]);

const handleClick = (action: any) => {
  console.log(action);
  emits("create");
};

const emits = defineEmits(["create"]);
</script>
