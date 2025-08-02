// composables/menuItems.ts
import { LayoutGrid } from "lucide-vue-next"
export const menuItems = [
  {
    label: 'Dashboard',
    key: 'dashboard',
    icon: LayoutGrid
  },
  {
    label: 'Ventas',
    key: 'ventas',
    icon: 'mdi-cart',
    children: [
      {
        label: 'Nueva venta',
        key: 'ventas-nueva'
      },
      {
        label: 'Historial',
        key: 'ventas-historial'
      }
    ]
  },
  {
    label: 'Productos',
    key: 'productos',
    icon: 'mdi-pill',
    children: [
      {
        label: 'Listado',
        key: 'productos-listado'
      },
      {
        label: 'Categorías',
        key: 'productos-categorias'
      }
    ]
  }
]
