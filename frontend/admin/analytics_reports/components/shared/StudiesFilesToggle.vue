<template>
  <v-btn-toggle v-model="model" mandatory density="compact" color="primary" variant="outlined" divided>
    <v-btn value="studies">Studies</v-btn>
    <v-btn value="files">Files</v-btn>
  </v-btn-toggle>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: 'studies' },
});

const emit = defineEmits(['update:modelValue']);

defineOptions({ name: 'StudiesFilesToggle' });

const model = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
});
</script>
