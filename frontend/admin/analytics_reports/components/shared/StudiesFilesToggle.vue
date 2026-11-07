<template>
  <v-btn-toggle v-model="model" mandatory density="compact" color="primary" variant="outlined" divided>
    <v-btn value="studies">{{ t('analytics_studies', 'Studies') }}</v-btn>
    <v-btn value="files">{{ t('analytics_files', 'Files') }}</v-btn>
  </v-btn-toggle>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';

const props = defineProps({
  modelValue: { type: String, default: 'studies' },
});

const emit = defineEmits(['update:modelValue']);

defineOptions({ name: 'StudiesFilesToggle' });

const { t } = useI18n();
const model = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
});
</script>
