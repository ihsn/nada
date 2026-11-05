<template>
  <v-dialog
    :model-value="modelValue"
    max-width="640"
    persistent
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="text-h6 d-flex align-center justify-space-between">
        <span>{{ lbl.generate_study_pdf || 'Generate study PDF' }}</span>
        <v-btn icon variant="text" size="small" :disabled="saving" @click="close">
          <v-icon icon="mdi-close" />
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="px-6 py-5">
        <div v-if="loading" class="text-body-2 text-medium-emphasis">{{ lbl.loading || 'Loading…' }}</div>
        <template v-else>
          <v-alert v-if="error" type="error" density="compact" class="mb-4" closable @click:close="error = ''">
            {{ error }}
          </v-alert>
          <v-alert v-if="!canGenerate" type="warning" density="compact" class="mb-4">
            {{ lbl.pdf_surveys_only || 'PDF can only be generated for surveys.' }}
          </v-alert>
          <v-alert v-if="warning" type="warning" density="compact" class="mb-4">
            {{ warning }}
          </v-alert>
          <v-alert v-if="saving" type="info" density="compact" variant="tonal" class="mb-4">
            {{ lbl.processing_pdf_report || 'Generating PDF report, it may take a few minutes…' }}
          </v-alert>

          <div class="pdf-field">
            <div class="pdf-field-label">
              {{ lbl.website_title || 'Website title' }} <span class="text-error">*</span>
            </div>
            <v-text-field
              v-model="form.website_title"
              variant="outlined"
              density="comfortable"
              hide-details
              class="pdf-field-input"
              :disabled="saving || !canGenerate"
            />
          </div>
          <div class="pdf-field">
            <div class="pdf-field-label">
              {{ lbl.study_title || 'Study title' }} <span class="text-error">*</span>
            </div>
            <v-text-field
              v-model="form.study_title"
              variant="outlined"
              density="comfortable"
              hide-details
              class="pdf-field-input"
              :disabled="saving || !canGenerate"
            />
          </div>
          <div class="pdf-field">
            <div class="pdf-field-label">{{ lbl.publisher || 'Publisher' }}</div>
            <v-text-field
              v-model="form.publisher"
              variant="outlined"
              density="comfortable"
              hide-details
              class="pdf-field-input"
              :disabled="saving || !canGenerate"
            />
          </div>
          <div class="pdf-field">
            <div class="pdf-field-label">
              {{ lbl.website_url || 'Website URL' }} <span class="text-error">*</span>
            </div>
            <v-text-field
              v-model="form.website_url"
              variant="outlined"
              density="comfortable"
              hide-details
              class="pdf-field-input"
              :disabled="saving || !canGenerate"
            />
          </div>
          <div class="pdf-field">
            <div class="pdf-field-label">{{ lbl.report_lang || 'Report language' }}</div>
            <v-select
              v-model="form.report_lang"
              :items="languages"
              item-title="title"
              item-value="code"
              variant="outlined"
              density="comfortable"
              hide-details
              class="pdf-field-input"
              :disabled="saving || !canGenerate"
            />
          </div>

          <div class="pdf-options">
            <div class="pdf-field-label">{{ lbl.report_options || 'Report options' }}</div>
            <v-checkbox
              v-model="form.toc_variable"
              :label="variableListLabel"
              density="comfortable"
              hide-details
              class="pdf-check"
              :disabled="saving || !canGenerate"
            />
            <v-checkbox
              v-model="form.data_dic_desc"
              :label="lbl.include_variable_desc || 'Variable description'"
              density="comfortable"
              hide-details
              class="pdf-check"
              :disabled="saving || !canGenerate"
            />
            <v-checkbox
              v-model="form.ext_resources"
              :label="lbl.include_external_resources || 'External resources'"
              density="comfortable"
              hide-details
              class="pdf-check"
              :disabled="saving || !canGenerate"
            />
          </div>
        </template>
      </v-card-text>
      <v-divider />
      <v-card-actions class="px-6 py-4">
        <v-spacer />
        <v-btn variant="text" :disabled="saving" @click="close">{{ lbl.cancel || 'Cancel' }}</v-btn>
        <v-btn color="primary" :loading="saving" :disabled="loading || !canGenerate" @click="submit">
          {{ lbl.generate_pdf || 'Generate PDF' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyPdfApi } from './useStudyPdfApi';

defineOptions({ name: 'GeneratePdfDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'generated']);

const { config } = useAppConfig();
const { fetchPdfOptions, generatePdf } = useStudyPdfApi();
const lbl = computed(() => config.value?.labels || {});

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const warning = ref('');
const canGenerate = ref(true);
const varcount = ref(0);
const languages = ref([]);

const form = reactive({
  website_title: '',
  study_title: '',
  publisher: '',
  website_url: '',
  report_lang: 'en',
  toc_variable: false,
  data_dic_desc: false,
  ext_resources: false,
});

const variableListLabel = computed(() => {
  const base = lbl.value.include_variable_toc || 'Variable list';
  return `${base} (${varcount.value})`;
});

function close() {
  if (saving.value) return;
  emit('update:modelValue', false);
}

function applyDefaults(data) {
  const d = data?.defaults || {};
  form.website_title = d.website_title || '';
  form.study_title = d.study_title || '';
  form.publisher = d.publisher || '';
  form.website_url = d.website_url || '';
  form.report_lang = d.report_lang || 'en';
  form.toc_variable = !!Number(d.toc_variable);
  form.data_dic_desc = !!Number(d.data_dic_desc);
  form.ext_resources = !!Number(d.ext_resources);
  varcount.value = Number(data?.varcount || 0);
  warning.value = data?.warning || '';
  canGenerate.value = data?.can_generate !== false;
  languages.value = Array.isArray(data?.languages) ? data.languages : [];
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    applyDefaults(await fetchPdfOptions());
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Could not load PDF options';
    canGenerate.value = false;
  } finally {
    loading.value = false;
  }
}

async function submit() {
  error.value = '';
  if (!form.website_title.trim() || !form.study_title.trim() || !form.website_url.trim()) {
    error.value = lbl.value.pdf_required_fields || 'Website title, study title, and website URL are required.';
    return;
  }
  saving.value = true;
  try {
    await generatePdf({
      website_title: form.website_title.trim(),
      study_title: form.study_title.trim(),
      publisher: form.publisher.trim(),
      website_url: form.website_url.trim(),
      language: form.report_lang,
      variable_toc: form.toc_variable ? 1 : 0,
      variable_description: form.data_dic_desc ? 1 : 0,
      include_resources: form.ext_resources ? 1 : 0,
    });
    emit('generated', lbl.value.pdf_generated || 'PDF report generated.');
    emit('update:modelValue', false);
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'PDF generation failed';
  } finally {
    saving.value = false;
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) load();
  }
);
</script>

<style scoped>
.pdf-field + .pdf-field,
.pdf-options {
  margin-top: 16px;
}

.pdf-field-label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 600;
  line-height: 1.25rem;
  letter-spacing: 0.01em;
  margin-bottom: 6px;
  color: rgba(var(--v-theme-on-surface), 0.87);
}

.pdf-field-input :deep(.v-field__input) {
  font-size: 0.875rem;
  min-height: 40px;
}

.pdf-options .pdf-field-label {
  margin-bottom: 4px;
}

.pdf-check {
  margin-inline-start: -6px;
}

.pdf-check :deep(.v-label) {
  font-size: 0.875rem;
  line-height: 1.25rem;
  opacity: 1;
}

.pdf-check + .pdf-check {
  margin-top: 2px;
}
</style>
