<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import type { Component } from 'vue';
import { h } from 'vue';
import { NIcon } from 'naive-ui';
import { Link, router } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Package, Coins, MessageCircleX, ShoppingBag, Truck, UserRound, UsersRound, FlaskConical, Notebook, ShoppingCartIcon } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';


const renderIcon = (icon: Component) => {
  return () => {
    return h(NIcon, null, {
      default: () => h(icon)
    })
  }
}

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Compras',
        href: route('purchases.create'),
        icon: Truck,
        open: false,
        children: [
          {
            key: 'create-purchase',
            title: 'nueva compra',
            href: '/purchases/create',
            icon: renderIcon(ShoppingCartIcon),
            props: {
              onClick: () => {  router.get('/purchases/create') }
            }
          },
          {
            key: 'view-purchases',
            title: 'historial compras',
            href: '/purchases',
            icon: renderIcon(Notebook),
            props: {
              onClick: () => {  router.get('/purchases') }
            }
          },
        ]
    },
    {
        title: 'Ventas',
        href: '/sales',
        icon: ShoppingBag,
        open: true,
        children: [
          {
            key: 'create-sale',
            title: 'crear venta',
            icon: renderIcon(ShoppingCartIcon),
            href: '/sales/create',
            props: {
              onClick: () =>  router.get('/sales/create')
            }
          },
          {
            key: 'view-sales',
            title: 'nueva ventas',
            icon: renderIcon(Notebook),
            href: '/sales',
            props: {
              onClick: () =>  router.get('/sales')
            }
          },
        ]
    },
    {
        title: 'Registradora',
        href: '/cashregisters',
        icon: Coins,
    },
    {
        title: 'Medicamentos',
        href: '/medicines',
        icon: Package,
    },
    {
        title: 'Laboratorios',
        href: '/laboratories',
        icon: FlaskConical,
    },
    {
        title: 'Clientes',
        href: '/clients',
        icon: UsersRound,
    },
    {
        title: 'Usuarios',
        href: '/users',
        icon: UserRound,
    },
    {
        title: 'Asistente',
        href: '/assistant',
        icon: MessageCircleX,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="route('dashboard')">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
