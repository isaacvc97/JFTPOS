import { router, usePage } from '@inertiajs/vue3'
import { onMounted, onUnmounted } from 'vue'
import message from './useMessageGlobal'

export function useFlashMessage() {
  const showFlash = () => {
    const flash = usePage().props.flash?.message as { message?: string, type?: string } | undefined

    console.info(flash)

    if (flash?.message) {
      const type = flash.type || 'info'

      switch (type) {
        case 'success':
          message.success(flash.message)
          break
        case 'error':
          message.error(flash.message)
          break
        case 'warning':
        case 'warn':
          message.warning(flash.message)
          break
        default:
          message.info(flash.message)
      }
    }
  }

  const handler = () => setTimeout(showFlash, 10) // pequeña espera para asegurar que usePage().props esté actualizado

  const inertiaHandler = () => router.on('finish', handler)
  
  onMounted(() => {
    inertiaHandler()
  })

  onUnmounted(() => {
    inertiaHandler()
  })
}
