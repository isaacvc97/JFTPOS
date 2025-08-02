import { createDiscreteApi, darkTheme, useOsTheme } from 'naive-ui'
import { computed } from 'vue'

export function useMessageGlobal() {
  const osThemeRef = useOsTheme()

  const configProviderProps = computed(() => ({
    theme: osThemeRef.value === 'dark' ? darkTheme : null
  }))

  const { message } = createDiscreteApi(['message'], {
    configProviderProps: configProviderProps.value,
    messageProviderProps: {
      placement: 'bottom-left',
      containerStyle: {
        border: '1px solid slateblue',
        borderRadius: '10px'
      }
    }
  })

  return message
}

