import { ref } from "vue";

export function useSpeechRecognition(onResult) {
  const transcript = ref("");
  const isListening = ref(false);

  const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
  recognition.lang = "es-ES";
  recognition.interimResults = false;
  recognition.continuous = false;

  recognition.onresult = (event) => {
    const text = event.results[0][0].transcript;
    transcript.value = text;
    onResult(text);
  };

  recognition.onend = () => {
    isListening.value = false;
  };

  function startListening() {
    isListening.value = true;
    transcript.value = "";
    recognition.start();
  }

  function stopListening() {
    isListening.value = false;
    recognition.stop();
  }

  function resetTranscript() {
    transcript.value = "";
  }

  return {
    transcript,
    isListening,
    startListening,
    stopListening,
    resetTranscript,
  };
}
