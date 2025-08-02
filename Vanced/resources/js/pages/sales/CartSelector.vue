<template>
  <NPopover trigger="click" xstyle="border-radius: 10px">
    <template #trigger>
      <NButton type="primary" tertiary>
        <template #icon>
          <n-icon size="16" :component="ShoppingCart" />
        </template>
        Carritos activos
      </NButton>
    </template>
    <n-list hoverable clickable>
      <template #header>
        <p class="text-lg font-semibold">Venta en curso</p>

        <div class="flex gap-4 justify-between items-center mt-4">
          <div>
            <div class="content-center">
              <NIcon :component="KeyRoundIcon" size="15" />
              {{ currentCart?.id }}
            </div>
            <div>
              <NIcon :component="Calendar" size="15" />
              {{ formatDate(new Date(currentCart.created_at), "YYYY/MM/DD : H:I") }}
            </div>
          </div>

          <n-button
            tertiary
            size="small"
            type="error"
            @click="handleDeleteCart(currentCart.id)"
          >
            <template #icon>
              <n-icon :component="Trash" size="15" />
            </template>
          </n-button>
        </div>
      </template>

      <n-list-item
        v-for="cart in carts"
        :key="cart.id"
        v-show="cart.id !== currentCart.id"
      >
        <template #prefix></template>

        <div class="space-y-2">
          <p class="block">IDENTIFICADOR: {{ cart.id }}</p>

          <span class="">
            <NIcon :component="Calendar" size="15" />
            {{ cart.created_at }}
          </span>
        </div>

        <template #suffix>
          <div class="flex items-center gap-2">
            <n-button
              tertiary
              size="small"
              type="info"
              @click="handleChangeCart(cart.id)"
            >
              <template #icon><n-icon size="14" :component="ExternalLink" /></template>
            </n-button>
            <n-button
              tertiary
              size="small"
              type="error"
              @click="
                router.delete(route('sales.cart.destroy', cart.id), {
                  only: ['carts'],
                })
              "
            >
              <template #icon><n-icon size="14" :component="TrashIcon" /></template>
            </n-button>
          </div>
        </template>
      </n-list-item>
      <template #footer> </template>
    </n-list>
    <template #footer>
      <div class="flex items-center justify-between gap-2">
        <n-button
          size="small"
          type="success"
          @click="() => router.post(route('sales.cart.store'), {})"
        >
          <template #icon>
            <n-icon :component="BadgePlus" size="15" />
          </template>

          Cear carrito
        </n-button>
      </div>
    </template>
  </NPopover>
</template>

<script setup lang="ts">
// import axios from "axios";
import { router } from "@inertiajs/vue3";
// import { computed, ref } from "vue";
import { useMessageGlobal } from "@/composables/useMessageGlobal";
import { NPopover, NList, NListItem, NButton, NIcon } from "naive-ui";
import {
  TrashIcon,
  ShoppingCart,
  Calendar,
  Trash,
  KeyRoundIcon,
  BadgePlus,
  ExternalLink,
} from "lucide-vue-next";
import { formatDate } from "@vueuse/core";
import { Cart } from "./sale";

interface SelectorProps {
  currentCart: Cart;
  carts: Cart[];
}

defineProps<SelectorProps>();

const message = useMessageGlobal();
// const emit = defineEmits(["change", "items-loaded"]);

const handleDeleteCart = (id: number) => {
  console.info("Eliminando carrito", id);

  router.delete(route("sales.cart.destroy", id), {
    onSuccess: () => {
      // router.reload();
    },
    onError: (error) => {
      console.error("Error al eliminar el carrito", error);
    },
  });
};

const handleChangeCart = (id: number) => {
  router.reload({
    only: ["currentCart"], //, 'carts'
    data: {
      cart_sale_id: id,
    },
  });

  message.info("Carrito cargado ID:" + id);
};

// #############################

// const selectedCartId = ref(null);
// const carritoOptions = ref([]);

// async function cargarCarritos() {
//   const resp = await axios.get(route("sales.cart.actives"));
//   const { carts, cart } = resp.data;
//   console.table(carts);
//   if (!props.carts.length) return;

//   carritoOptions.value = props.carts.map((c) => ({
//     label: `#${c.id} ${c.created_at.slice(0, 10)}`,
//     value: c.id,
//   }));

//   if (!selectedCartId.value && carts.length > 0) {
//     selectedCartId.value = carts[0].id;

//     emit("items-loaded", cart);

//     await cargarItems(selectedCartId.value);
//     emitirCambio();
//   }
// }
// const cartsOptions = computed(() => {
//   return props.carts.map((c) => ({
//     label: `#${c.id} ${c.created_at.slice(0, 10)}`,
//     value: c.id,
//   }));
// });

// async function cargarItems(cartId: number) {
//   if (!cartId) return;
//   // const { data } = await axios.get(`/sales/cart/${cartId}`);
//   emit("items-loaded", cartId);
// }

// async function emitirCambio() {
//   emit("change", selectedCartId.value);
//   await cargarItems(selectedCartId.value);
// }

// async function deleteCurrent() {
//   if (!selectedCartId.value) return;
//   const resp = await axios.delete(`/sales/cart/${selectedCartId.value}`);
//   if (resp.status === 200) {
//     selectedCartId.value = null;
//     cargarCarritos();
//   }
//   // await cargarCarritos();
// }

// async function crearNuevo() {
//   const { data } = await axios.put("/sales/cart", {});
//   selectedCartId.value = data.id;
//   emit("items-loaded", data);
//   // await cargarCarritos();
//   // emitirCambio();
// }

// onMounted(cargarCarritos);
</script>
