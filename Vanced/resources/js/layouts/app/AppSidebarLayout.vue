<script setup lang="ts">
import AppContent from "@/components/AppContent.vue";
import AppShell from "@/components/AppShell.vue";
import AppSidebar from "@/components/AppSidebar.vue";
import AppSidebarHeader from "@/components/AppSidebarHeader.vue";
import type { BreadcrumbItemType } from "@/types";
import { useMessage } from "naive-ui";
import { usePage } from "@inertiajs/vue3";
import { watch } from "vue";

interface Props {
  breadcrumbs?: BreadcrumbItemType[];
}
interface messageType {
  message: string;
  type?: "info" | "success" | "warning" | "error" | "loading" | "default";
}

const page = usePage();
const message = useMessage();

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
});

watch(
  () => page.props.message as messageType,
  (newMessage: messageType) => {
    if (newMessage) {
      message.create(newMessage.message, {
        type: newMessage.type || "info",
        duration: 5000,
        closable: true,
      });
    }
  },
  { immediate: true }
);

watch(
  () => page.props.errors as any,
  (newMessage: messageType) => {
    Object.values(newMessage)?.map((error) => {
      message.create(error, {
        duration: 5000,
        closable: true,
        type: "error",
      });
    });
  },
  { immediate: true }
);
</script>

<template>
  <AppShell variant="sidebar">
    <AppSidebar />
    <AppContent variant="sidebar">
      <AppSidebarHeader :breadcrumbs="breadcrumbs" />
      <slot />
    </AppContent>
  </AppShell>
</template>
