<script setup lang="ts">
// import AppLogoIcon from "@/components/AppLogoIcon.vue";
import { Store } from "lucide-vue-next";
// import { NIcon } from "naive-ui";
import { usePage, Link } from "@inertiajs/vue3";
const page = usePage();

const branch = page.props.auth.user.branch;
</script>

<template>
  <div
    class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
  >
    <!-- <AppLogoIcon class="size-5 fill-current text-white dark:text-black" /> -->
    <Store class="text-white dark:text-slate-600" />
  </div>
  <div class="ml-1 grid flex-1 text-left text-sm">
    <!-- <span class="mb-0.5 truncate leading-tight font-semibold">Laravel Starter Kit</span> -->
    <Link href="branches" class="mb-0.5 truncate leading-tight font-semibold">
      {{ branch?.nombre || "Mi Sucursal" }}
    </Link>
  </div>
</template>
