<script setup>
import axios from "axios";
import { onMounted, ref, watch, computed, reactive } from "vue";
import { useForm } from "@inertiajs/vue3";
import { X, Mic, Pause, SkipBack, Play, Trash } from "lucide-vue-next";
import { emptyDosage, emptyMedicineWithAll, emptyPresentation } from "./medicine";
import {
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NButton,
  NSpace,
  NDivider,
  NIcon,
  useMessage,
  NModal,
  NSelect,
  NUpload,
  // NImage,
  NCollapse,
  NCollapseItem,
  NTag,
} from "naive-ui";
const props = defineProps({
  show: Boolean,
  medicine: {
    type: Object,
    default: () => emptyMedicineWithAll(),
  },
});

const emits = defineEmits(["updated", "close"]);
const isEdit = !!props.medicine?.id;
const message = useMessage();
const formRef = ref(null);

const previewUrl = ref(null);

const handleCustomUpload = ({ file, onFinish }) => {
  // Crear URL temporal para mostrar preview
  previewUrl.value = URL.createObjectURL(file.file);

  // Aquí podrías hacer la subida al servidor si quieres
  // Por ahora solo simulamos éxito:
  onFinish();
};

const handleRemove = () => {
  previewUrl.value = null;
};

const form = useForm(props.medicine, {
  resetOnSuccess: true,
  resetOnError: false,
});

const errorServer = reactive({
  name: "",
  generic_name: "",
});

function addDosage() {
  form.dosages.push(
    emptyDosage()
    /* {
    id: null,
    concentration: "",
    form: { name: "" },
    presentations: [],
  } */
  );
}
function removeDosage(index) {
  if (confirm("¿Eliminar esta dosificación?")) {
    form.dosages.splice(index, 1);
  }
}
function addPresentation(dosageIndex) {
  form.dosages[dosageIndex].presentations.push(
    emptyPresentation()
    /* {
    id: null,
    unit_type: "",
    quantity: 1,
    cost: 0.0,
    price: 0.0,
    stock: 0,
    barcode: null,
  } */
  );
}
function removePresentation(dosageIndex, presentationIndex) {
  if (confirm("Eliminar esta presentación?")) {
    form.dosages[dosageIndex].presentations.splice(presentationIndex, 1);
  }
}

const parseCurrency = (input) => {
  const nums = input.replace(/(,|\$|\s)/g, "").trim();
  if (/^\d+(\.(\d+)?)?$/.test(nums)) return Number(nums);
  return nums === "" ? null : Number.NaN;
};
const formatCurrency = (value) => {
  if (value === null) return "";
  return `\u{24} ${value.toLocaleString("en-US")}`;
};

function submit() {
  formRef.value?.validate((errors) => {
    if (!errors) {
      const method = isEdit ? "post" : "put";
      const url = isEdit ? route("medicines.update", form.id) : route("medicines.store");

      form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
          emits("updated", form);
          message.success("Medicamento guardado");
          emits("close");
        },
        onError: (err) => {
          if (err) {
            Object.keys(err).forEach((field) => {
              console.error(`Error en el campo ${field}:`, err[field]);
              errorServer[field] = err[field];
            });
            console.error("Errores del servidor:", err);
          }
        },
      });
    }
  });
}

watch(
  () => props.show,
  (val) => {
    if (!val) form.reset();
  }
);

const voiceModal = ref(false);
const currentStep = ref(0);
const isListening = ref(false);
const transcript = ref("");
const autoMode = ref(false);

let recognition;
if (
  typeof window !== "undefined" &&
  (window.SpeechRecognition || window.webkitSpeechRecognition)
) {
  recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
  recognition.lang = "es-ES";
  recognition.interimResults = false;
  recognition.continuous = false;
}

const stepFields = computed(() => {
  const steps = [
    { label: "Nombre comercial", path: ["name"] },
    { label: "Nombre genérico", path: ["generic_name"] },
    { label: "Descripción", path: ["description"] },
  ];

  form.dosages.forEach((d, i) => {
    steps.push({
      label: `Concentración ${i + 1}`,
      path: ["dosages", i, "concentration"],
    });
    steps.push({ label: `Forma ${i + 1}`, path: ["dosages", i, "form", "name"] });

    d.presentations.forEach((p, j) => {
      steps.push({
        label: `Unidad ${i + 1}.${j + 1}`,
        path: ["dosages", i, "presentations", j, "unit_type"],
      });
      steps.push({
        label: `Cantidad ${i + 1}.${j + 1}`,
        path: ["dosages", i, "presentations", j, "quantity"],
      });
      steps.push({
        label: `Precio ${i + 1}.${j + 1}`,
        path: ["dosages", i, "presentations", j, "price"],
      });
      steps.push({
        label: `Stock ${i + 1}.${j + 1}`,
        path: ["dosages", i, "presentations", j, "stock"],
      });
      steps.push({
        label: `Código ${i + 1}.${j + 1}`,
        path: ["dosages", i, "presentations", j, "barcode"],
      });
    });
  });

  return steps;
});

/* function getPathValue(path) {
  return path.reduce((obj, key) => obj?.[key], form);
} */
function setPathValue(path, value) {
  let obj = form;
  for (let i = 0; i < path.length - 1; i++) obj = obj[path[i]];
  obj[path[path.length - 1]] = value;
}

function speakLabel(label, callback) {
  if (!window.speechSynthesis) return callback?.();
  const utterance = new SpeechSynthesisUtterance(label);
  utterance.lang = "es-ES";
  utterance.onend = () => callback?.();
  window.speechSynthesis.speak(utterance);
}
const awaitingExtraInput = ref(false);
if (recognition) {
  recognition.onresult = (event) => {
    const result = event.results[0][0].transcript.trim().toLowerCase();
    transcript.value = result;

    if (awaitingExtraInput.value) {
      if (result.includes("dosificación")) {
        addDosage();
        message.success("Nueva dosificación añadida");
        currentStep.value = stepFields.value.length - 2;
        awaitingExtraInput.value = false;

        const nextLabel = stepFields.value[currentStep.value]?.label;
        speakLabel(nextLabel, () => {
          setTimeout(() => recognition.start(), 600);
        });
      } else if (result.includes("presentación")) {
        const lastIndex = form.dosages.length - 1;
        addPresentation(lastIndex);
        message.success("Nueva presentación añadida");
        currentStep.value = stepFields.value.length - 5;
        awaitingExtraInput.value = false;

        const nextLabel = stepFields.value[currentStep.value]?.label;
        speakLabel(nextLabel, () => {
          setTimeout(() => recognition.start(), 600);
        });
      } else if (result.includes("no")) {
        message.info("Finalizado");
        voiceModal.value = false;
        currentStep.value = 0;
        awaitingExtraInput.value = false;
      } else {
        speakLabel(
          "No entendí. ¿Desea añadir una nueva dosificación o una nueva presentación?",
          () => {
            setTimeout(() => recognition.start(), 600);
          }
        );
      }
      return;
    }

    // Normal flow
    const step = stepFields.value[currentStep.value];
    if (!step) return stopListening();

    setPathValue(step.path, isNaN(result) ? result : parseFloat(result));
    currentStep.value++;

    if (currentStep.value < stepFields.value.length && autoMode.value) {
      const nextLabel = stepFields.value[currentStep.value]?.label;
      speakLabel(nextLabel, () => setTimeout(() => recognition.start(), 500));
    } else if (currentStep.value >= stepFields.value.length) {
      stopListening();
      awaitingExtraInput.value = true;
      speakLabel("¿Desea añadir una nueva dosificación o una nueva presentación?");
    }
  };

  recognition.onend = () => (isListening.value = false);
}

function startListening() {
  if (!recognition) return;
  transcript.value = "";
  isListening.value = true;
  const step = stepFields.value[currentStep.value];
  if (step) {
    speakLabel(step.label, () => recognition.start());
  } else {
    recognition.start();
  }
}
function stopListening() {
  if (!recognition) return;
  isListening.value = false;
  recognition.stop();
}
function resetVoiceFlow() {
  currentStep.value = 0;
  transcript.value = "";
  autoMode.value = false;
}
function goBackStep() {
  currentStep.value = Math.max(0, currentStep.value - 1);
}
function startAutoFlow() {
  autoMode.value = true;
  startListening();
}

const forms = ref([]);
const presentations = ref([]);
const laboratories = ref([]);

async function fetchData() {
  if (localStorage.getItem("forms")) {
    forms.value = JSON.parse(localStorage.getItem("forms"));
  } else {
    const response = await axios.get("/api/medicine-forms");
    if (response.status === 200) {
      forms.value = response.data;
      localStorage.setItem("forms", JSON.stringify(response.data));
    } else {
      console.error("Error fetching forms:", response.statusText);
    }
  }

  if (localStorage.getItem("presentations")) {
    presentations.value = JSON.parse(localStorage.getItem("presentations"));
  } else {
    const response = await axios.get("/api/medicine-presentations");
    if (response.status === 200) {
      presentations.value = response.data;
      localStorage.setItem("presentations", JSON.stringify(response.data));
    } else {
      console.error("Error fetching presentations:", response.statusText);
    }
  }

  /* if (localStorage.getItem("laboratories")) {
    laboratories.value = JSON.parse(localStorage.getItem("laboratories"));
  } else {
    const response = await axios.get("/medicines/laboratories");
    if (response.status === 200) {
      laboratories.value = response.data;
      localStorage.setItem("laboratories", JSON.stringify(response.data));
    } else {
      console.error("Error fetching laboratories:", response.statusText);
    }
  } */
}

const handleSearchLaboratory = async (searchTerm) => {
  console.warn(searchTerm);
  const response = await axios.get("/medicines/laboratories");
  if (response.status === 200) laboratories.value = response.data;
};

onMounted(() => fetchData());
</script>

<template>
  <n-space vertical>
    <!-- <pre>
  {{ form }}
  </pre > -->
    <n-upload
      list-type="image-card"
      :max="1"
      accept="image/*"
      :custom-request="handleCustomUpload"
      :default-upload="false"
      :on-remove="handleRemove"
    >
      <n-button>Subir imagen</n-button>
    </n-upload>

    <div v-if="previewUrl" class="mt-4">
      <p>Vista previa:</p>
      <img :src="previewUrl" alt="Preview" style="max-width: 200px" />
    </div>
    <n-form
      inline:model="form"
      crules="rules"
      ref="formRef"
      label-placement="top"
      class="mt-4"
    >
      <NSpace vertical>
        <n-form-item
          label="Nombre comercial"
          :feedback="errorServer.name"
          :validation-status="errorServer.name ? 'error' : ''"
        >
          <n-input v-model:value="form.name" placeholder="Nombre comercial" />
        </n-form-item>

        <n-form-item
          label="Nombre genérico"
          :feedback="errorServer.generic_name"
          :validation-status="errorServer.generic_name ? 'error' : ''"
        >
          <n-input v-model:value="form.generic_name" placeholder="Nombre genérico" />
        </n-form-item>

        <!-- <n-form-item label="Laboratorio">
          <n-input
            v-model:value="form.laboratory_id"
            placeholder="Nombre de laboratorio"
          />
        </n-form-item> -->
        <n-select
          label-field="name"
          value-field="id"
          v-model:value="form.laboratory_id"
          :options="laboratories"
          filterable
          clearable
          remote
          @search="handleSearchLaboratory"
        >
        </n-select>

        <n-form-item label="Descripción">
          <n-input
            v-model:value="form.description"
            placeholder="Descripción"
            type="textarea"
          />
        </n-form-item>
      </NSpace>

      <n-collapse :default-expanded-names="[form.dosages.length]">
        <n-collapse-item
          v-for="(dosage, i) in form.dosages"
          :key="i"
          class="mt-6"
          xtitle="dosage.form.name + ' ' + dosage.concentration"
          :name="i"
        >
          <template #header>
            <n-tag v-if="!!dosage.form.name && !!dosage.concentration" :bordered="false">
              {{ dosage.form.name + " " + dosage.concentration }}
            </n-tag>
            <n-tag v-else :bordered="false" type="success">Nueva dosis</n-tag>
          </template>
          <template #header-extra>
            <n-icon size="15" @click="removeDosage(i)" color="#922b21">
              <Trash class="hover:text-red-600 hover:scale-125 cursor-pointer"
            /></n-icon>
          </template>
          <!-- <div class="flex justify-between items-center">
              <n-divider title-placement="left">
                Dosificación {{ i + 1 }}
                <span
                  class="mx-2 p-2 rounded-lg bg-red-100 text-xs text-red-500 animate-pulse"
                  >{{ errorServer.dosages }}
                </span>
              </n-divider>
              <n-icon size="20" @click="removeDosage(i)" color="#922b21"
                ><X class="hover:text-red-600 hover:scale-125 cursor-pointer"
              /></n-icon>
            </div> -->

          <NSpace vertical>
            <n-form-item
              label="Concentración"
              :feedback="errorServer[`dosages.${i}.concentration`]"
              :validation-status="
                errorServer[`dosages.${i}.concentration`] ? 'error' : ''
              "
            >
              <n-input v-model:value="dosage.concentration" placeholder="500mg" />
            </n-form-item>
            <n-form-item
              label="Forma farmacéutica"
              :feedback="errorServer[`dosages.${i}.form.name`]"
              :validation-status="errorServer[`dosages.${i}.form.name`] ? 'error' : ''"
            >
              <n-select
                v-model:value="dosage.form.name"
                label-field="name"
                value-field="name"
                filterable
                tag
                placeholder="Selecciona una forma"
                :options="forms"
              />
              <n-input v-model:value="dosage.form.name" placeholder="Tabletas" />
            </n-form-item>
            <n-collapse :default-expanded-names="[dosage.presentations.length]">
              <n-collapse-item
                v-for="(presentation, j) in dosage.presentations"
                :key="j"
                :name="j"
                xclass="my-4 xbg-gray-50 p-4 rounded border border-green-400/60"
              >
                <template #header>
                  <n-tag v-if="!!presentation.unit_type" :bordered="false">
                    {{ presentation.unit_type }}
                  </n-tag>
                  <n-tag v-else :bordered="false" type="success"
                    >Nueva presentacion</n-tag
                  >
                </template>
                <template #header-extra>
                  <n-icon size="15" @click="removePresentation(i, j)" color="#922b21">
                    <Trash class="hover:text-red-600 hover:scale-125 cursor-pointer"
                  /></n-icon>
                </template>

                <!-- <div class="flex justify-between items-center mb-2">
                <n-divider title-placement="left">Presentación {{ j + 1 }}</n-divider>
                <n-icon size="20" @click="removePresentation(i, j)" color="#922b21"
                  ><X class="hover:text-red-600 hover:scale-125 cursor-pointer"
                /></n-icon>
              </div> -->

                <n-form-item
                  label="Tipo de unidad"
                  :feedback="errorServer?.presentation?.unit_type"
                  :validation-status="errorServer?.presentation?.unit_type ? 'error' : ''"
                >
                  <n-select
                    v-model:value="presentation.unit_type"
                    label-field="unit_type"
                    value-field="unit_type"
                    filterable
                    placeholder="Selecciona una forma"
                    :options="presentations"
                  />
                  <n-input v-model:value="presentation.unit_type" />
                </n-form-item>
                <n-form-item label="Cantidad">
                  <n-input-number v-model:value="presentation.quantity" clearable />
                </n-form-item>
                <n-form-item label="Costo ($)">
                  <n-input-number
                    v-model:value="presentation.cost"
                    xdefault-value="presentation.cost ?: 0"
                    :parse="parseCurrency"
                    :format="formatCurrency"
                    step="0.25"
                  >
                    <template #prefix>USD</template>
                  </n-input-number>
                </n-form-item>
                <n-form-item label="Precio ($)">
                  <n-input-number
                    v-model:value="presentation.price"
                    xdefault-value="presentation.price ? 0"
                    :parse="parseCurrency"
                    :format="formatCurrency"
                    step="0.25"
                  >
                    <template #prefix>USD</template>
                  </n-input-number>
                </n-form-item>

                <n-form-item label="Stock">
                  <n-input-number v-model:value="presentation.stock" clearable />
                </n-form-item>

                <n-form-item label="Código de barras">
                  <n-input v-model:value="presentation.barcode" />
                </n-form-item>
              </n-collapse-item>
            </n-collapse>
            <div class="mt-6">
              <n-button @click="addPresentation(i)"> + Agregar presentación </n-button>
            </div>
          </NSpace>
        </n-collapse-item>
      </n-collapse>

      <div class="flex justify-between gap-4 mt-6">
        <n-button @click="addDosage"> + Agregar dosificación </n-button>

        <n-button @click="voiceModal = true" type="warning">
          <n-icon><Mic /></n-icon>
        </n-button>

        <n-button strong type="primary" @click="submit">
          {{ isEdit ? "Actualizar Medicamento" : "Guardar Medicamento" }}
        </n-button>
      </div>
    </n-form>
  </n-space>
  <!-- Modal control de voz -->
  <n-modal
    v-model:show="voiceModal"
    title="Llenado paso a paso por voz"
    style="width: 400px"
  >
    <div>
      <p>Paso {{ currentStep + 1 }} de {{ stepFields.length }}</p>
      <p><strong>🏷️</strong> {{ stepFields[currentStep]?.label || "Terminado" }}</p>
      <p><em>Texto reconocido:</em> {{ transcript }}</p>

      <div class="flex justify-between mt-4">
        <n-button @click="goBackStep" :disabled="currentStep === 0">
          <n-icon><SkipBack /></n-icon> Anterior
        </n-button>

        <n-button
          @click="isListening ? stopListening() : startListening()"
          type="primary"
        >
          <n-icon v-if="!isListening"><Mic /></n-icon>
          <n-icon v-else><Pause /></n-icon>
          {{ isListening ? "Pausar" : "Iniciar" }}
        </n-button>

        <n-button @click="startAutoFlow" type="success">
          <n-icon><Play /></n-icon> Automático
        </n-button>

        <n-button
          @click="
            () => {
              stopListening();
              voiceModal = false;
              resetVoiceFlow();
            }
          "
          type="error"
        >
          Cerrar
        </n-button>
      </div>
    </div>
  </n-modal>

  <!-- Botón flotante para activar el llenado por voz -->
  <div class="fixed bottom-4 right-4">
    <n-button
      circle
      size="large"
      class="shadow-lg bg-white hover:bg-gray-100 transition-all duration-300"
      @click="voiceModal = true"
    >
      <n-icon size="24">
        <Mic />
      </n-icon>
    </n-button>
  </div>
</template>
