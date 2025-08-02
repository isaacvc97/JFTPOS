<template>
  <n-modal :show="true" @close="$emit('close')">
    <n-card title="Seleccionar presentación" style="width: 500px">
      <n-radio-group v-model:value="selected" class="grid gap-2">
        <n-radio v-for="opt in presentationOptions" :key="opt.id" :value="opt.id">
          {{ opt.label }}
        </n-radio>
      </n-radio-group>

      <template #footer>
        <div class="flex justify-end gap-2">
          <n-button @click="$emit('close')">Cancelar</n-button>
          <n-button type="primary" @click="confirm">Agregar</n-button>
        </div>
      </template>
    </n-card>
  </n-modal>
</template>

<script setup>
import { ref, computed } from "vue";
import { NModal, NCard, NRadioGroup, NRadio, NButton } from "naive-ui";
const props = defineProps({ medicine: Object });
const emit = defineEmits(["select", "close"]);

const selected = ref(null);

const presentationOptions = computed(() =>
  props.medicine.dosages.flatMap((d) =>
    d.presentations.map((p) => ({
      id: p.id,
      label: `${d.concentration} ${d.form.name} - ${p.unit_type} ($${p.price})`,
      dosage: d,
      presentation: p,
    }))
  )
);

function confirm() {
  for (const d of props.medicine.dosages) {
    const pres = d.presentations.find((p) => p.id === selected.value);
    if (pres) {
      emit("select", {
        medicine: props.medicine,
        dosage: d,
        presentation: pres,
      });
      return;
    }
  }
}
</script>
