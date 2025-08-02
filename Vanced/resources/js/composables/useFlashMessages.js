// composables/useFlashMessages.js
import { useMessage } from 'naive-ui'
import { usePage } from '@inertiajs/vue3'
import { onMounted, watch } from 'vue'

export function useFlashMessages() {
  const message = useMessage()
  const page = usePage()

  onMounted(() => {
    show(page.props.flash)
  })

  watch(() => page.props.flash, (flash) => {
    show(flash)
  })

  function show(flash) {
    if (flash.success) message.success(flash.success)
    if (flash.error) message.error(flash.error)
  }
}
