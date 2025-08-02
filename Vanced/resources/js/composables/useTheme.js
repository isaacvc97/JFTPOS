// resources/js/composables/useTheme.js
import {  computed } from 'vue'
import { darkTheme } from 'naive-ui'
import { useAppearance } from '@/composables/useAppearance.js'

const { appearance } = useAppearance();


// const isOpenWs = ref(localStorage.getItem('open-ws') === 'true')
// const isDark = ref(localStorage.getItem('appearance') === 'dark')

const checkSystmTheme = () => {
  const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
  const systemTheme = mediaQueryList.matches ? 'dark' : 'light';

  return systemTheme;
} 
// Computed theme object for Naive UI
const naiveTheme = computed(() => (appearance == 'dark' ? darkTheme : (checkSystmTheme() === 'dark' ? darkTheme : null)))

// Watcher para guardar en localStorage
// watchEffect(() => {
//   localStorage.setItem('open-ws', isOpenWs.value ? 'true' : 'false')
//   localStorage.setItem('appearance', isDark.value ? 'dark' : 'light')
// })

export function useTheme() {
  return {
    // isDark,
    // isOpenWs,
    naiveTheme,
    toggleModal: () => {
      isOpenWs.value = !isOpenWs.value
    },
    toggleTheme: () => {
      isDark.value = !isDark.value
    },
  }
}
