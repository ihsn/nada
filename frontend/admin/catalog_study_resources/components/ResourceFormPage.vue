<template>
  <div class="csr-form-page">
    <h2 class="text-h6 mb-4">
      {{ isEdit ? (lbl.edit || 'Edit resource') : (lbl.add_resource || 'Add resource') }}
    </h2>

    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-3" />
    <v-alert v-if="error" type="error" density="compact" class="mb-3" closable @click:close="error = ''">
      {{ error }}
    </v-alert>

    <div class="csr-form-fields">
      <div class="csr-field">
        <div class="csr-field-label">{{ lbl.col_type || 'Type' }}</div>
        <v-select
          v-model="form.dctype"
          :items="dtypeItems"
          item-title="title"
          item-value="value"
          variant="outlined"
          density="compact"
          hide-details="auto"
        />
      </div>
      <div class="csr-field">
        <div class="csr-field-label">{{ lbl.col_title || 'Title' }} *</div>
        <v-text-field
          v-model="form.title"
          variant="outlined"
          density="compact"
          hide-details="auto"
          autocomplete="off"
        />
      </div>
      <div class="csr-field">
        <div class="csr-field-label">{{ lbl.description || 'Description' }}</div>
        <v-textarea
          v-model="form.description"
          variant="outlined"
          density="compact"
          rows="3"
          hide-details="auto"
        />
      </div>
      <div class="csr-field">
        <div class="csr-field-label">{{ lbl.format || 'Format' }}</div>
        <v-select
          v-model="form.dcformat"
          :items="formatItems"
          item-title="title"
          item-value="value"
          clearable
          variant="outlined"
          density="compact"
          hide-details="auto"
        />
      </div>
      <div class="csr-field">
        <div class="csr-field-label">{{ lbl.resource_url || 'File name or URL' }}</div>
        <v-text-field
          v-model="form.filename"
          variant="outlined"
          density="compact"
          hide-details="auto"
          autocomplete="off"
          :placeholder="lbl.url_placeholder || 'report.pdf or https://…'"
        />
      </div>

      <v-expansion-panels variant="accordion" class="csr-additional-info">
        <v-expansion-panel>
          <v-expansion-panel-title class="text-body-2">
            {{ lbl.additional_info || 'Additional information' }}
          </v-expansion-panel-title>
          <v-expansion-panel-text>
            <div class="csr-field">
              <div class="csr-field-label">{{ lbl.resource_identifier || 'Resource identifier' }}</div>
              <v-text-field
                v-model="form.resource_idno"
                variant="outlined"
                density="compact"
                hide-details="auto"
                maxlength="100"
                autocomplete="off"
              />
            </div>
            <div class="csr-field">
              <div class="csr-field-label">{{ lbl.authors || 'Authors' }}</div>
              <v-text-field v-model="form.author" variant="outlined" density="compact" hide-details="auto" />
            </div>
            <div class="csr-field">
              <div class="csr-field-label">{{ lbl.date || 'Date' }}</div>
              <v-text-field v-model="form.dcdate" variant="outlined" density="compact" hide-details="auto" />
            </div>
            <div class="csr-field">
              <div class="csr-field-label">{{ lbl.country || 'Country' }}</div>
              <v-text-field v-model="form.country" variant="outlined" density="compact" hide-details="auto" />
            </div>
            <div class="csr-field">
              <div class="csr-field-label">{{ lbl.language || 'Language' }}</div>
              <v-text-field v-model="form.language" variant="outlined" density="compact" hide-details="auto" />
            </div>
            <div class="csr-field">
              <div class="csr-field-label">{{ lbl.contributors || 'Contributors' }}</div>
              <v-text-field v-model="form.contributor" variant="outlined" density="compact" hide-details="auto" />
            </div>
            <div class="csr-field">
              <div class="csr-field-label">{{ lbl.publishers || 'Publishers' }}</div>
              <v-text-field v-model="form.publisher" variant="outlined" density="compact" hide-details="auto" />
            </div>
            <div class="csr-field">
              <div class="csr-field-label">{{ lbl.abstract || 'Abstract' }}</div>
              <v-textarea v-model="form.abstract" variant="outlined" density="compact" rows="3" hide-details="auto" />
            </div>
            <div class="csr-field">
              <div class="csr-field-label">{{ lbl.toc || 'Table of contents' }}</div>
              <v-textarea v-model="form.toc" variant="outlined" density="compact" rows="4" hide-details="auto" />
            </div>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>

      <div class="csr-form-actions d-flex justify-end flex-wrap gap-2">
        <v-btn variant="text" :disabled="saving" @click="$emit('cancel')">
          {{ lbl.cancel || 'Cancel' }}
        </v-btn>
        <v-btn color="primary" :loading="saving" :disabled="!canSave" @click="submit">
          {{ lbl.submit || 'Submit' }}
        </v-btn>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyResourcesApi } from '../composables/useStudyResourcesApi';

defineOptions({ name: 'ResourceFormPage' });

const props = defineProps({
  /** null = create */
  resourceId: { type: [Number, String], default: null },
});

const emit = defineEmits(['saved', 'cancel']);

const { config } = useAppConfig();
const { getResource, createResource, updateResource } = useStudyResourcesApi();

const lbl = computed(() => config.value?.labels || {});
const dtypeItems = computed(() => {
  const raw = config.value?.dctypes || {};
  return Object.entries(raw).map(([value, title]) => ({ value, title: String(title) }));
});
const formatItems = computed(() => {
  const raw = config.value?.dcformats || {};
  return Object.entries(raw).map(([value, title]) => ({ value, title: String(title || value) }));
});

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const form = reactive(emptyForm());

const isEdit = computed(() => props.resourceId != null && props.resourceId !== '');
const canSave = computed(
  () => String(form.title || '').trim().length > 0 && String(form.dctype || '').trim().length > 0
);

function emptyForm() {
  return {
    dctype: '',
    title: '',
    description: '',
    dcformat: '',
    filename: '',
    resource_idno: '',
    author: '',
    dcdate: '',
    country: '',
    language: '',
    contributor: '',
    publisher: '',
    abstract: '',
    toc: '',
  };
}

function resetForm() {
  Object.assign(form, emptyForm());
  error.value = '';
}

/** Match stored dctype label/code to select value (codes preferred). */
function resolveDtypeValue(stored) {
  const s = String(stored || '').trim();
  if (!s) return '';
  const items = dtypeItems.value;
  if (items.some((i) => i.value === s)) return s;
  const byTitle = items.find((i) => i.title === s);
  if (byTitle) return byTitle.value;
  const m = s.match(/\[([^\]]+)\]\s*$/);
  if (m && items.some((i) => i.value === m[1])) return m[1];
  return s;
}

function applyResource(row) {
  if (!row) return;
  form.dctype = resolveDtypeValue(row.dctype);
  form.title = row.title || '';
  form.description = row.description || '';
  form.dcformat = row.dcformat || '';
  form.filename = row.filename || '';
  form.resource_idno = row.resource_idno || '';
  form.author = row.author || '';
  form.dcdate = row.dcdate || '';
  form.country = row.country || '';
  form.language = row.language || '';
  form.contributor = row.contributor || '';
  form.publisher = row.publisher || '';
  form.abstract = row.abstract || '';
  form.toc = row.toc || '';
}

function buildPayload() {
  const payload = {
    dctype: form.dctype,
    title: String(form.title || '').trim(),
    description: form.description || '',
    filename: String(form.filename || '').trim() || null,
    author: form.author || '',
    dcdate: form.dcdate || '',
    country: form.country || '',
    language: form.language || '',
    contributor: form.contributor || '',
    publisher: form.publisher || '',
    abstract: form.abstract || '',
    toc: form.toc || '',
  };
  if (form.dcformat) payload.dcformat = form.dcformat;
  const idno = String(form.resource_idno || '').trim();
  if (idno) payload.resource_idno = idno;
  return payload;
}

async function loadEdit() {
  if (!isEdit.value) {
    resetForm();
    if (dtypeItems.value.length) form.dctype = dtypeItems.value[0].value;
    return;
  }
  loading.value = true;
  error.value = '';
  try {
    const row = await getResource(props.resourceId);
    if (!row) throw new Error('RESOURCE_NOT_FOUND');
    applyResource(row);
  } catch (e) {
    error.value = e?.message || 'Failed to load';
  } finally {
    loading.value = false;
  }
}

async function submit() {
  if (!canSave.value) return;
  saving.value = true;
  error.value = '';
  try {
    const payload = buildPayload();
    if (isEdit.value) {
      await updateResource(props.resourceId, { ...payload, resource_id: Number(props.resourceId) });
    } else {
      await createResource(payload);
    }
    emit('saved');
  } catch (e) {
    error.value = e?.message || 'Save failed';
  } finally {
    saving.value = false;
  }
}

watch(
  () => props.resourceId,
  () => {
    loadEdit();
  }
);

onMounted(() => {
  loadEdit();
});
</script>

<style scoped>
.csr-form-fields {
  max-width: 720px;
}

.csr-field + .csr-field {
  margin-top: 0.75rem;
}

.csr-field-label {
  font-size: 0.8125rem;
  font-weight: 600;
  margin-bottom: 4px;
  color: rgba(var(--v-theme-on-surface), 0.9);
}

.csr-additional-info {
  margin-top: 1.5rem;
}

.csr-form-actions {
  margin-top: 1.75rem;
}
</style>
