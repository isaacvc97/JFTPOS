import { ref, onMounted } from 'vue';

export default function useDeviceDetection() {
  const isMobile = ref(false);

  function detectMobileDevice() {
    isMobile.value = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  }

  onMounted(detectMobileDevice);

  function handleInteraction(mobileHandler, desktopHandler) {
    if (isMobile.value) {
      mobileHandler();
    } else {
      desktopHandler();
    }
  }

  return {
    isMobile,
    handleInteraction,
  };
}