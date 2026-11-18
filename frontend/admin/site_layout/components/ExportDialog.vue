<template>
  <v-dialog :model-value="modelValue" max-width="480" @update:model-value="(v) => $emit('update:modelValue', v)">
    <v-card class="sl-popup">
      <v-card-title class="text-subtitle-1">{{ t('sl_export_title', 'Export the site layout') }}</v-card-title>
      <v-card-text class="sl-stack">
        <div class="sl-help">
          {{
            t(
              'sl_export_help',
              'Downloads a .zip file with the settings and header images. Import it on another NADA site under Settings > Site layout. Menu items, the site title and languages are not included: they come from the site that imports it.'
            )
          }}
        </div>
        <div class="sl-field">
          <div class="sl-label">{{ t('sl_parts', 'Include') }}</div>
          <div class="sl-switches">
            <v-checkbox v-for="p in partItems" :key="p.value" v-model="parts" :value="p.value" :label="p.title" color="primary" density="compact" hide-details />
          </div>
        </div>
        <div class="sl-field">
          <div class="sl-label">{{ t('sl_export_state', 'Version') }}</div>
          <v-radio-group v-model="state" density="compact" hide-details>
            <v-radio value="published" :label="t('sl_state_published', 'Published')" color="primary" />
            <v-radio value="draft" :label="t('sl_state_draft', 'Draft (the published version where there is no draft)')" color="primary" />
          </v-radio-group>
        </div>
        <v-alert v-if="error" type="error" variant="tonal" density="compact">{{ error }}</v-alert>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="$emit('update:modelValue', false)">{{ t('sl_cancel', 'Cancel') }}</v-btn>
        <v-btn color="primary" variant="flat" prepend-icon="mdi-download" :disabled="!parts.length" :loading="busy" @click="run">
          {{ t('sl_export_download', 'Download') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useSiteLayoutApi } from '../composables/useSiteLayoutApi';
import { packageMessage } from '../composables/packageMessages';

defineOptions({ name: 'SlExportDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'done']);

const { t } = useI18n();
const api = useSiteLayoutApi();

const parts = ref(['header', 'footer', 'styles']);
const state = ref('published');
const busy = ref(false);
const error = ref('');

const partItems = computed(() => [
  { value: 'header', title: t('sl_tab_header', 'Header') },
  { value: 'footer', title: t('sl_tab_footer', 'Footer') },
  { value: 'styles', title: t('sl_tab_styles', 'Styles') },
]);

watch(
  () => props.modelValue,
  (open) => {
    if (open) error.value = '';
  }
);

async function run() {
  busy.value = true;
  error.value = '';
  try {
    const ordered = ['header', 'footer', 'styles'].filter((p) => parts.value.includes(p));
    const { blob, filename } = await api.exportPackage(ordered, state.value);
    const href = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = href;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(href), 1000);
    emit('update:modelValue', false);
    emit('done', t('sl_exported', 'The site layout was exported.'));
  } catch (e) {
    error.value = packageMessage(t, e?.response?.data?.message || 'EXPORT_FAILED');
  } finally {
    busy.value = false;
  }
}
</script>
