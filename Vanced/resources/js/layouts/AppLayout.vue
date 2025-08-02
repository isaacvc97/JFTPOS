<script setup lang="ts">
import AppLayout from "@/layouts/app/AppSidebarLayout.vue";
import type { BreadcrumbItemType } from "@/types";
import {
  darkTheme,
  useOsTheme,
  NConfigProvider,
  NDialogProvider,
  NMessageProvider,
} from "naive-ui";
import SessionHandler from "./SessionHandler.vue";
import { computed } from "vue";

interface Props {
  breadcrumbs?: BreadcrumbItemType[];
}
withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
});

const osThemeRef = useOsTheme();

const theme = computed(() => {
  return osThemeRef.value === "dark" ? darkTheme : null;
});
</script>

<template>
  <SessionHandler />
  <n-config-provider :theme="theme">
    <n-message-provider placement="bottom-left" :duration="5000">
      <n-dialog-provider>
        <AppLayout :breadcrumbs="breadcrumbs">
          <slot />
        </AppLayout>
      </n-dialog-provider>
    </n-message-provider>
  </n-config-provider>
</template>
