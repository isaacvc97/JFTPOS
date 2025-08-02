// composables/useScreenSize.js o .ts
import { ref, onMounted, onUnmounted } from 'vue';

export default function useScreenSize() {
  const screenSize = ref('');

  const handleResize = () => {
    const width = window.innerWidth;
    if (width < 640) {
      screenSize.value = 'sm';
    } else if (width < 768) {
      screenSize.value = 'md';
    } else if (width < 1024) {
      screenSize.value = 'lg';
    } else if (width < 1280) {
      screenSize.value = 'xl';
    } else {
      screenSize.value = '2xl';
    }
  };

  onMounted(() => {
    handleResize();
    window.addEventListener('resize', handleResize);
  });

  onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
  });

  return { screenSize };
}

export function detectMobile() {
  const isMobile = ref(window.innerWidth < 768);

  const handleResize = () => {
    // console.log('Resize detected');
    isMobile.value = window.innerWidth < 768;
  };

  onMounted(() => {
    window.addEventListener('resize', handleResize);
  });
  onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
  });

  return { isMobile };
}

export function isMobile0() {
  const { screenSize } = useScreenSize();
  return ['sm', 'md'].includes(screenSize.value);
}