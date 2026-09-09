<script setup>
import { computed } from 'vue';
import { useMetadataFormStore } from '../composables/useMetadataFormStore';
import { useMetadataFormLabels } from '../composables/useMetadataFormLabels';
import MetadataFieldHelp from './MetadataFieldHelp.vue';

const props = defineProps({
  field: { type: Object, required: true },
  path: { type: String, required: true },
});

const store = useMetadataFormStore();
const labels = useMetadataFormLabels();
const helpText = computed(() => props.field.help_text || props.field.help || '');
const label = computed(() => props.field.title || props.field.key || '');

const values = computed({
  get() {
    const v = store.getValue(props.path);
    return Array.isArray(v) ? v : [];
  },
  set(v) {
    store.setValue(props.path, v);
  },
});

const draft = computed({
  get() {
    return values.value.map((x) => (x == null ? '' : String(x)));
  },
  set() {
    /* handled via updateAt / add / remove */
  },
});

function updateAt(index, value) {
  const next = values.value.slice();
  next[index] = value;
  values.value = next;
}

function addItem() {
  values.value = [...values.value, ''];
}

function removeItem(index) {
  const next = values.value.slice();
  next.splice(index, 1);
  values.value = next;
}
</script>

<template>
  <div class="mf-simple-array mb-3">
    <div class="d-flex align-start justify-space-between mb-2 ga-2">
      <div class="flex-grow-1" style="min-width: 160px">
        <MetadataFieldHelp :label="label" :help-text="helpText" />
      </div>
      <v-btn size="small" variant="tonal" class="text-none" @click="addItem">{{ labels.add }}</v-btn>
    </div>
    <div v-for="(val, index) in draft" :key="index" class="d-flex ga-2 mb-2 align-center">
      <v-text-field
        :model-value="val"
        density="compact"
        variant="outlined"
        hide-details
        class="flex-grow-1"
        @update:model-value="updateAt(index, $event)"
      />
      <v-btn
        icon="mdi-close"
        size="x-small"
        variant="text"
        color="error"
        @click="removeItem(index)"
      />
    </div>
  </div>
</template>
