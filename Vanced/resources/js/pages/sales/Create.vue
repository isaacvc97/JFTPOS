<script setup lang="ts">
import axios from "axios";
import { ref, reactive, watch, computed } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";

import {
  NButton,
  NCard,
  NIcon,
  NModal,
} from "naive-ui";
import {
  Search,
} from "lucide-vue-next";
import { useMessageGlobal } from '@/composables/useMessageGlobal'
import { type BreadcrumbItem } from '@/types';
import MedicineSearch from "./MedicineSearch.vue";
import CartItems from "./CartItems.vue";
import CartTable from "./CartTable.vue";
import DrawerInfoSale from "./DrawerInfoSale.vue";
import CartSelector from "./CartSelector.vue";
import MedicineModal from "../medicines/Modal.vue";
import WebSearch from "./WebSearch.vue";
import { type Cart, CartItem } from "./sale";


interface CartProps {
  currentCart: Cart;
  carts: Cart[];
}

const props = defineProps<CartProps>();
const message = useMessageGlobal()



const hotSwapCart = reactive<Cart>({
  ...props.currentCart,
});

function applyCart(newCart: Cart) {
  hotSwapCart.id = newCart.id
  hotSwapCart.client = newCart.client

  hotSwapCart.items.splice(0, hotSwapCart.items.length, ...newCart.items)
}

watch(() => props.currentCart, applyCart, { immediate: true })

const showSidebar = ref(false);

// Añadir item al carrito
const handleAddToCart = async (item: CartItem) => {

  try {
    const { data } = await axios.post(
      route("sales.cart.store"),
      {
        cart_sale_id: props.currentCart.id,
        medicine_id: item.medicine_id,
        cantidad: item.quantity,
        presentation_id: item.presentation_id,
        descuento: item.discount,
      }
    );
    console.info("Item añadido al carrito:", data);

    if(!data.id) return message.error("Error al añadir item.")

    message.success("Item añadido al carrito correctamente.")

    item.id = data.id
    hotSwapCart.items.push(item);

    /* router.post(
      route("sales.cart.store"),
      {
        cart_sale_id: props.currentCart.id,
        medicine_id: item.id,
        cantidad: item.quantity,
        presentation_id: item.presentations[0].id,
      },
      {
        onSuccess: () => {
          console.info("Item añadido al carrito:", item);
        },
        onError: (error) => {
          console.error("Error al añadir al carrito:", error);
        },
        only: ["currentCart", "message"],
        preserveState: true,
        preserveScroll: true
      }
    ); */

  } catch (e) {
    console.error("Error al guardar en backend:", e);
  }
};

// const handleUpdateQuantity = (item: any, val: number) => {
//   console.info("Actualizo cantidad:", item, val);



//   const resp = router.put(
//     route("sales.cart.update", item.id),
//     {
//       cart_sale_id: props.currentCart.id,
//       medicine_id: item.medicine_id,
//       presentation_id: item.presentation_id,
//       cantidad: item.quantity,
//       descuento: item.discount,
//     }
//   );
//   console.info(item, resp);
//   message.success("Se actualizó el item del carrito");
// };

const handleUpdateCart = async (item: any) => {

  const { data } = await axios.put(
    route("sales.cart.update", item.id),
    {
      cart_sale_id: hotSwapCart.id,
      medicine_id: item.medicine_id,
      presentation_id: item.presentation_id,
      cantidad: item.quantity,
      descuento: item.discount,
    }
  );
  console.info("Actualizando item en el carrito:", data);

  if(!data.item) return message.error("Error al actualizar el item del carrito");

  const index = hotSwapCart.items.findIndex((row) => row.id === item.id)
  hotSwapCart.items[index] = item;

  message.success("Se actualizó el item del carrito");
};

// const handleUpdateDiscount = (item: any) => {
//   console.info("Actualizando item en el carrito:", item);

//   const resp = router.put(
//     route("sales.cart.update", item.id),
//     {
//       // cart_sale_id: props.currentCart.id,
//       medicine_id: item.medicine_id,
//       presentation_id: item.presentation_id,
//       cantidad: item.quantity,
//       descuento: 0,
//     }
//   );
//   console.info(item, resp);
//   message.success("Se actualizó el item del carrito");
// };


const handleRemoveCart = async (id:number/* , index: number */) => {
  console.info("Eliminando item del carrito:", id);

  const response = await axios.delete(route("sales.cart.remove", id));

  if(response.status != 200) return message.error("Error al eliminar item");
  //  hotSwapCart.items.splice(index, 1);
  // router.delete(route("sales.cart.remove", id), {});

  hotSwapCart.items.splice(
    hotSwapCart.items.findIndex((row) => row.id === id),
    1
  );

};


// Cálculos
// const subtotal = computed(() => {
//   return hotSwapCart.items?.reduce((sum, i) => sum + (i.price || 0) * (i.quantity || 0), 0);
// });

// const ivaAmount = computed(() => {
//   return hotSwapCart.items?.reduce((sum, i) => {
//     if (i.iva) return sum + (i.price || 0) * (i.quantity || 0) * 0.15;
//     return sum;
//   }, 0);
// });

// const total = computed(() => {
//   const discountFactor = 1 - (discount.value || 0) / 100;
//   return (subtotal.value + ivaAmount.value) * discountFactor;
// });

// interface Amounts {
//   total: number;
//   pago: number;
//   cambio: number;
// }

// const processSale = (amounts: Amounts) => {
//   console.info(hotSwapCart);

//   router.post(
//     route("sales.store"),
//     {
//       cart_sale_id: hotSwapCart.id,
//       client_id: hotSwapCart.client?.id || null,
//       sale_type: hotSwapCart.sale_type || 1,
//       payment_type: hotSwapCart.payment_type || 1,
//       discount: discount.value,
//       total: amounts.total,
//       pago: amounts.pago,
//       cambio: amounts.cambio,
//     },
//     {
//       onSuccess: () => {
//         message.success("Venta procesada correctamente");

//         router.visit(route("sales.index"));
//       },
//       onError: (error) => {
//         message.error("Error al procesar la venta", error);
//       },
//       // only: ["currentCart"],
//     }
//   );
// };

const discountEc = 0.15;
const subtotal = computed(() => {
  return hotSwapCart.items.reduce(
    (sum, i) => sum + (i.price || 0) * (i.quantity || 0),
    0
  );
});

const ivaAmount = computed(() => {
  return hotSwapCart.items?.reduce((sum, i) => {
    if (i.iva) return sum + (i.price || 0) * (i.quantity || 0) * discountEc;
    return sum;
  }, 0);
});

const discount = computed(() => {
  return hotSwapCart.items?.reduce((sum, i) => {
    if (i.discount) return sum + (i.discount || 0);
    return sum;
  }, 0);
});

const total = computed(() => {
  // const discountFactor = 1 - (discount.value || 0) / 100;
  return (subtotal.value + ivaAmount.value) // - discount.value //* discountFactor;
});
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: "Ventas",
    href: route("sales.index"),
  },
  {
    title: "Crear venta",
    href: route("sales.create"),
  },
];

const isOpenWebSearch = ref(false);
const isOpenCreateMedicine = ref(false);
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex fex-col max-w-svw">
      <WebSearch
        v-model:show="isOpenWebSearch"
        @close="isOpenWebSearch = false"
        @create-medicine="isOpenCreateMedicine = !isOpenCreateMedicine"
      />

      <NModal v-model:show="isOpenCreateMedicine" preset="card" draggable>
        <template #default>
          <NCard title="Nuevo Medicamento" :bordered="false" role="dialog">
            <MedicineModal
              key="new"
              :show="isOpenCreateMedicine"
              @close="isOpenCreateMedicine = false"
            />
          </NCard>
        </template>
      </NModal>

      <!-- 📦 Buscador y Carrito -->
      <NCard :bordered="false" style="flex: 1">
        <template #header>
          <MedicineSearch @select="handleAddToCart" />
        </template>
        <template #default>
          <CartItems
            :items="hotSwapCart.items"
            xupdate:quantity="handleUpdateQuantity"
            xupdate:presentation="handleUpdatePresentation"
            xupdate:discount="handleUpdateDiscount"
            @remove:item="handleRemoveCart"
          >
            <template #table>
              <CartTable
                :items="hotSwapCart.items"
                @update:item="handleUpdateCart"
                @remove:item="handleRemoveCart"
              />
            </template>
          </CartItems>
        </template>
        <template #action>
          <div class="flex flex-end gap-4 items-center">
            <CartSelector :currentCart="hotSwapCart" :carts="carts" />

            <NButton @click="isOpenWebSearch = true" secondary>
              <template #icon>
                <NIcon size="16">
                  <Search />
                </NIcon>
              </template>
              Buscar en web
            </NButton>
          </div>
        </template>
      </NCard>

      <!-- 🧾 Botón flotante -->
      <div class="fixed bottom-4 right-4">
        <NButton
          type="primary"
          size="large"
          class="rounded-full shadow-xl px-6 py-3"
          @click="showSidebar = !showSidebar"
        >
          ${{ total.toFixed(2) }} • {{ discount }} •
          <p class="capitalize px-0.5">
            {{ !!currentCart?.client ? currentCart.client.name : "Cliente" }}
          </p>
        </NButton>
      </div>

      <!-- 📋 Sidebar de datos de venta -->
      <DrawerInfoSale
        :open="showSidebar"
        :cart="currentCart"
        :calc="{ total, ivaAmount, discount }"
        @close="showSidebar = true"
      />
    </div>
  </AppLayout>
</template>
