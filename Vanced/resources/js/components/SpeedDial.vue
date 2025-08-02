<template>
  <div class="fixed bottom-4 right-4">
    <div v-if="isOpen" class="flex flex-col items-end space-y-4">
      <!-- Botones dinámicos según vista -->
      <div v-for="action in actions" :key="action.label">
        <button
          @click="actionHandler(action)"
          class="bg-blue-500 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg transition transform hover:scale-110 focus:outline-none">
          <span class="material-icons">{{ action.icon }}</span>
        </button>
      </div>
    </div>
    
    <!-- Botón principal para abrir/cerrar el speed dial -->
    <button
      @click="toggleSpeedDial"
      class="bg-blue-500 text-white rounded-full w-16 h-16 flex items-center justify-center shadow-lg transition transform hover:scale-110 focus:outline-none">
      <span class="material-icons">add</span>
    </button>
  </div>
</template>

<script>
import { usePage } from '@inertiajs/vue3';
export default {
  data() {
    return {
      isOpen: false,
      actions: [], // Acciones dinámicas
    };
  },
  methods: {
    toggleSpeedDial() {
      this.isOpen = !this.isOpen;
    },
    // Lógica para manejar las acciones
    actionHandler(action) {
      console.log(`Acción ejecutada: ${action.label}`);
      // Aquí puedes manejar la lógica de cada acción
    },
    // Lógica para actualizar las acciones según la vista actual
    updateActions(view) {
      switch(view) {
        case 'inventory':
          this.actions = [
            { label: 'Agregar Medicamento', icon: 'add_circle_outline' },
            { label: 'Buscar Medicamento', icon: 'search' },
            { label: 'Ver Inventario', icon: 'view_list' },
          ];
          break;
        case 'sales':
          this.actions = [
            { label: 'Nueva Venta', icon: 'shopping_cart' },
            { label: 'Historial de Ventas', icon: 'history' },
          ];
          break;
        case 'orders':
          this.actions = [
            { label: 'Agregar Orden', icon: 'add_box' },
            { label: 'Ver Órdenes', icon: 'list_alt' },
          ];
          break;
        default:
          this.actions = [];
      }
    }
  },
  watch: {
    // Observar el cambio en la vista actual
    '$route.path'(newPath) {
      // Ajustar las acciones según la vista (ruta)
      if (newPath.includes('inventory')) {
        this.updateActions('inventory');
      } else if (newPath.includes('sales')) {
        this.updateActions('sales');
      } else if (newPath.includes('orders')) {
        this.updateActions('orders');
      }
    }
  },
  created() {
    // Establecer las acciones según la vista inicial
    // this.updateActions(this.$route.path);
    console.log(usePage())
  }
};
</script>