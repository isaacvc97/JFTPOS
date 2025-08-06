<script setup lang="ts">
import { reactive } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { NIcon, NDropdown } from 'naive-ui';
import { ChevronsUpDownIcon } from 'lucide-vue-next';
import { detectMobile } from '@/composables/useBreakpoint';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();
const {isMobile} = detectMobile();

const menuItems = reactive({...props.items});
function handleSelect(e: string){
  console.log(e);
}
</script>

<template>
  <SidebarGroup class="px-2 py-0">
    <SidebarGroupLabel>Platform</SidebarGroupLabel>
    <SidebarMenu>
      <SidebarMenuItem v-for="item in menuItems" :key="item.title">
        <div class="flex items-center justify-between">
          <SidebarMenuButton
            as-child
            :is-active="item.href === page.url"
            :tooltip="item.title"
          >
            <Link :href="item.href">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
          <n-dropdown
            v-if="item?.children"
            :options="item?.children"
            :trigger="isMobile ? 'hover' : 'click'"
            style="border-radius: 5px; padding: 5px"
            placement="bottom-start"
            :show-arrow="true"
            size="medium"
            @select="handleSelect"
          >
            <div class="m-2">
              <n-icon :component="ChevronsUpDownIcon" class="cursor-pointer" />
            </div>
          </n-dropdown>
        </div>
      </SidebarMenuItem>
    </SidebarMenu>
  </SidebarGroup>
</template>
