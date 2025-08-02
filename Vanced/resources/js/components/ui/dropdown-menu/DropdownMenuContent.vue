<script setup lang="ts">
import { cn } from '@/lib/utils'
import {
  DropdownMenuContent,
  type DropdownMenuContentEmits,
  type DropdownMenuContentProps,
  DropdownMenuPortal,
  useForwardPropsEmits,
} from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

const props = withDefaults(
  defineProps<DropdownMenuContentProps & { class?: HTMLAttributes['class'] }>(),
  {
    sideOffset: 4,
  },
)
const emits = defineEmits<DropdownMenuContentEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <DropdownMenuPortal>
    <DropdownMenuContent
      data-slot="dropdown-menu-content"
      v-bind="{ ...props, ...forwarded }"
      :class="
        cn(
          'bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 z-50 max-h-(--reka-dropdown-menu-content-available-height) min-w-[8rem] origin-(--reka-dropdown-menu-content-transform-origin) overflow-x-hidden overflow-y-auto rounded-md border p-1 shadow-md',
          props.class
        )
      "
    >
      <slot />
    </DropdownMenuContent>
  </DropdownMenuPortal>
</template>
/* La advertencia "[Vue warn] toRefs() expects a reactive object but received a plain one"
se produce cuando se está intentando utilizar la función toRefs() en un objeto que no es
reactivo. En este caso, el problema se encuentra en la línea 33 del código: El problema se
encuentra en la línea v-bind="forwarded", donde se está intentando enlazar las propiedades
del objeto forwarded a las propiedades del componente DropdownMenuContent. El error se
produce porque forwarded es el resultado de la función useForwardPropsEmits(), que
devuelve un objeto plano, y no un objeto reactivo. Vue espera que el objeto pasado a
v-bind sea reactivo, es decir, que los cambios en sus propiedades se reflejen
automáticamente en la vista. Para resolver este problema, puedes hacer lo siguiente:
Desestructurar el objeto forwarded y enlazar cada propiedad individualmente:
DropdownMenuContent v-bind="{ ...props, ...{ ...forwarded } }" */
