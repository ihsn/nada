<template>
  <div class="cit-stack">
    <v-breadcrumbs :items="breadcrumbItems" class="cit-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex align-center flex-wrap ga-2">
      <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0 flex-grow-1">
        {{ t('import_citation', 'Import citations') }}
      </h1>
      <v-btn variant="text" size="small" prepend-icon="mdi-arrow-left" :to="{ name: 'citations-list' }">
        {{ t('cit_all_citations', 'All citations') }}
      </v-btn>
    </div>

    <v-alert v-if="loadError" type="error" density="compact">{{ loadError }}</v-alert>
    <v-alert v-if="optionsLoaded && !can.create" type="warning" variant="tonal" density="compact">
      {{ t('cit_access_denied', 'Access denied') }}
    </v-alert>

    <!-- 1. choose a file -->
    <v-card v-if="step === 'upload'" class="pa-4" elevation="1">
      <h2 class="cit-section-title">{{ t('cit_import_step_file', '1. Choose the references') }}</h2>
      <v-row dense>
        <v-col cols="12" md="4">
          <div class="cit-field-label">{{ t('cit_import_format', 'Format') }}</div>
          <v-select v-model="format" :items="formatItems" density="compact" variant="outlined" hide-details />
        </v-col>
        <v-col cols="12" md="8">
          <div class="cit-field-label">{{ t('cit_import_file', 'File') }}</div>
          <v-file-input
            v-model="file"
            density="compact"
            variant="outlined"
            hide-details="auto"
            prepend-icon=""
            prepend-inner-icon="mdi-paperclip"
            accept=".bib,.bibtex,.ris,.enw,.txt,.json"
            :hint="fileHint"
            persistent-hint
          />
        </v-col>
        <v-col cols="12">
          <div class="cit-field-label">{{ t('cit_import_or_paste', 'Or paste the references here') }}</div>
          <v-textarea v-model="text" rows="6" density="compact" variant="outlined" hide-details="auto" :disabled="!!pickedFile" />
        </v-col>
      </v-row>
      <v-alert v-if="parseError" type="error" density="compact" class="mt-3">{{ parseError }}</v-alert>
      <div class="mt-3">
        <v-btn color="primary" variant="flat" prepend-icon="mdi-file-search" :loading="busy" :disabled="!canParse" @click="parse">
          {{ t('cit_import_preview', 'Read and preview') }}
        </v-btn>
      </div>
    </v-card>

    <!-- 2. preview and choose -->
    <template v-if="step === 'preview'">
      <v-card class="pa-4" elevation="1">
        <h2 class="cit-section-title">{{ t('cit_import_step_defaults', '2. Settings for the imported citations') }}</h2>
        <v-row dense>
          <v-col cols="12" md="4">
            <v-switch
              v-model="published"
              color="success"
              density="compact"
              hide-details
              :label="published ? t('published-status-1', 'Published') : t('cit_import_as_draft', 'Import as drafts')"
            />
            <v-switch v-model="skipDuplicates" density="compact" hide-details :label="t('cit_skip_duplicates', 'Skip citations already in the catalog')" />
          </v-col>
          <v-col cols="12" md="4">
            <div class="cit-field-label">{{ t('flag_entry_as', 'Flag') }}</div>
            <v-select v-model="flag" :items="flagItems" density="compact" variant="outlined" hide-details />
          </v-col>
          <v-col cols="12" md="4">
            <div class="cit-field-label">{{ t('cit_related_studies', 'Related studies') }}</div>
            <StudyPicker v-model="studies" />
          </v-col>
        </v-row>
      </v-card>

      <v-card elevation="1">
        <div class="d-flex align-center flex-wrap ga-2 px-4 py-3">
          <h2 class="cit-section-title mb-0 flex-grow-1">
            {{ t('cit_import_step_pick', '3. Choose what to import') }}
            <span class="cit-muted ms-2">{{ t('cit_import_found', '%d found, %d can be imported', rows.length, importable) }}</span>
          </h2>
          <v-btn size="small" variant="text" @click="selectAll">{{ t('cit_select_all', 'Select all') }}</v-btn>
          <v-btn size="small" variant="text" @click="selectNone">{{ t('cit_select_none', 'Select none') }}</v-btn>
        </div>
        <v-table density="comfortable">
          <thead>
            <tr>
              <th style="width: 48px"></th>
              <th>{{ t('title', 'Title') }}</th>
              <th style="width: 150px">{{ t('citation_type', 'Citation type') }}</th>
              <th style="width: 70px">{{ t('cit_col_year', 'Year') }}</th>
              <th style="width: 280px">{{ t('cit_import_notes', 'Notes') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.index">
              <td>
                <v-checkbox-btn v-model="picked" :value="row.index" :disabled="row.errors.length > 0" />
              </td>
              <td>
                <div class="font-weight-medium">{{ row.title || t('cit_import_no_title', '(no title)') }}</div>
                <div v-if="row.authors_text" class="cit-muted">{{ row.authors_text }}</div>
                <div v-if="row.container_title" class="cit-muted font-italic">{{ row.container_title }}</div>
              </td>
              <td>
                <v-chip size="x-small" variant="tonal">{{ typeLabel(row.type) }}</v-chip>
              </td>
              <td>{{ row.pub_year || '' }}</td>
              <td>
                <div v-for="err in row.errors" :key="err" class="text-error text-body-2">{{ errorText(err) }}</div>
                <v-chip v-for="w in row.warnings" :key="w" size="x-small" color="warning" variant="tonal" class="me-1">{{ warningText(w) }}</v-chip>
                <div v-if="row.duplicate_of" class="text-body-2 mt-1">
                  {{ t('cit_import_duplicate', 'Already in the catalog:') }}
                  <router-link :to="{ name: 'citation-edit', params: { id: row.duplicate_of } }" target="_blank" class="cit-title-link">
                    #{{ row.duplicate_of }}
                  </router-link>
                </div>
                <div v-if="row.similar.length" class="cit-muted mt-1">
                  {{ t('cit_import_similar', 'Same title in the catalog:') }}
                  <router-link
                    v-for="d in row.similar"
                    :key="d.id"
                    :to="{ name: 'citation-edit', params: { id: d.id } }"
                    target="_blank"
                    class="cit-title-link me-2"
                  >
                    #{{ d.id }}
                  </router-link>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>
        <v-alert v-if="commitError" type="error" density="compact" class="ma-4">{{ commitError }}</v-alert>
        <div class="d-flex ga-2 pa-4">
          <v-btn color="primary" variant="flat" prepend-icon="mdi-file-import" :loading="busy" :disabled="!picked.length || !can.create" @click="commit">
            {{ t('cit_import_n', 'Import %d citation(s)', picked.length) }}
          </v-btn>
          <v-btn variant="text" :disabled="busy" @click="reset">{{ t('cancel', 'Cancel') }}</v-btn>
        </div>
      </v-card>
    </template>

    <!-- 3. result -->
    <v-card v-if="step === 'done'" class="pa-4" elevation="1">
      <h2 class="cit-section-title">{{ t('cit_import_done', 'Import finished') }}</h2>
      <v-alert type="success" density="compact" variant="tonal">{{ t('cit_import_created', '%d citation(s) imported.', result.created.length) }}</v-alert>
      <ul v-if="result.created.length" class="mt-3 ps-5">
        <li v-for="c in result.created" :key="c.id">
          <router-link :to="{ name: 'citation-edit', params: { id: c.id } }" class="cit-title-link">{{ rowTitle(c.index) }}</router-link>
        </li>
      </ul>
      <v-alert v-if="result.skipped.length" type="warning" density="compact" variant="tonal" class="mt-3">
        {{ t('cit_import_skipped', '%d skipped:', result.skipped.length) }}
        <ul class="ps-5">
          <li v-for="s in result.skipped" :key="s.index">{{ rowTitle(s.index) }} — {{ skipText(s.reason) }}</li>
        </ul>
      </v-alert>
      <div class="d-flex ga-2 mt-4">
        <v-btn color="primary" variant="flat" :to="{ name: 'citations-list' }">{{ t('cit_all_citations', 'All citations') }}</v-btn>
        <v-btn variant="outlined" @click="reset">{{ t('cit_import_more', 'Import more') }}</v-btn>
      </div>
    </v-card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import StudyPicker from '@/shared/components/StudyPicker.vue';
import { useCitationsApi, apiErrorMessage, apiFieldErrors } from '../composables/useCitationsApi';

defineOptions({ name: 'ImportPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const api = useCitationsApi();

const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBase.value}/admin` },
  { title: t('title_citations', 'Citations'), to: { name: 'citations-list' } },
  { title: t('import_citation', 'Import citations'), disabled: true },
]);

const options = ref(null);
const optionsLoaded = ref(false);
const loadError = ref('');
const step = ref('upload');
const format = ref('auto');
const file = ref(null);
const text = ref('');
const busy = ref(false);
const parseError = ref('');
const commitError = ref('');

const token = ref('');
const rows = ref([]);
const picked = ref([]);
const published = ref(false);
const flag = ref('');
const studies = ref([]);
const skipDuplicates = ref(true);
const result = ref({ created: [], skipped: [] });

const can = computed(() => options.value?.can || { create: false });
const pickedFile = computed(() => (Array.isArray(file.value) ? file.value[0] : file.value) || null);
const canParse = computed(() => can.value.create && (!!pickedFile.value || text.value.trim() !== ''));
const importable = computed(() => rows.value.filter((r) => !r.errors.length).length);
const fileHint = computed(() => {
  const mb = options.value?.import?.max_bytes ? Math.round(options.value.import.max_bytes / 1048576) : 0;
  const max = options.value?.import?.max_rows;
  return [mb ? t('cit_max_size', 'Max %d MB', mb) : '', max ? t('cit_import_max_rows', 'Up to %d citations at a time', max) : ''].filter(Boolean).join(' · ');
});
const formatItems = computed(() => [
  { title: t('cit_format_auto', 'Detect from the file'), value: 'auto' },
  { title: 'BibTeX', value: 'bibtex' },
  { title: 'RIS (EndNote, Zotero, Mendeley)', value: 'ris' },
  { title: 'EndNote (tagged text)', value: 'endnote' },
  { title: 'JSON (CSL-JSON or a NADA export)', value: 'json' },
]);
const flagItems = computed(() => [{ title: '—', value: '' }, ...(options.value?.flags || []).map((f) => ({ title: f.label, value: f.code }))]);

function typeLabel(value) {
  const hit = (options.value?.ctypes || []).find((c) => c.value === value);
  return hit ? hit.label : t(value, value);
}
function errorText(err) {
  return err.startsWith('title') ? t('cit_err_title_required', 'The title is required.') : err;
}
function warningText(w) {
  return w === 'NO_AUTHORS' ? t('cit_warn_no_authors', 'No authors') : w === 'NO_DATE' ? t('cit_warn_no_date', 'No date') : w;
}
function skipText(reason) {
  const map = {
    INVALID: ['cit_skip_invalid', 'not valid'],
    DUPLICATE: ['cit_skip_duplicate', 'already in the catalog'],
    NOT_FOUND: ['cit_skip_not_found', 'not found'],
    SERVER_ERROR: ['cit_skip_error', 'could not be saved'],
  };
  return map[reason] ? t(map[reason][0], map[reason][1]) : reason;
}
function rowTitle(index) {
  const r = rows.value.find((x) => x.index === index);
  return (r && r.title) || `#${index + 1}`;
}

async function parse() {
  parseError.value = '';
  busy.value = true;
  try {
    const form = new FormData();
    form.append('format', format.value);
    if (pickedFile.value) form.append('file', pickedFile.value);
    else form.append('text', text.value);
    const res = await api.importParse(form);
    token.value = res.token;
    rows.value = res.rows || [];
    // rows that can be imported and are not already in the catalog start selected
    picked.value = rows.value.filter((r) => !r.errors.length && !r.duplicate_of).map((r) => r.index);
    step.value = 'preview';
  } catch (e) {
    const fields = apiFieldErrors(e, t);
    parseError.value = [apiErrorMessage(e, t), fields.file].filter(Boolean).join(' ');
  } finally {
    busy.value = false;
  }
}

async function commit() {
  commitError.value = '';
  busy.value = true;
  try {
    const res = await api.importCommit({
      token: token.value,
      indexes: picked.value,
      published: published.value ? 1 : 0,
      flag: flag.value || '',
      studies: studies.value.map((s) => s.id),
      skip_duplicates: skipDuplicates.value,
    });
    result.value = { created: res.created || [], skipped: res.skipped || [] };
    step.value = 'done';
  } catch (e) {
    commitError.value = apiErrorMessage(e, t);
  } finally {
    busy.value = false;
  }
}

function selectAll() {
  picked.value = rows.value.filter((r) => !r.errors.length).map((r) => r.index);
}
function selectNone() {
  picked.value = [];
}

function reset() {
  step.value = 'upload';
  file.value = null;
  text.value = '';
  token.value = '';
  rows.value = [];
  picked.value = [];
  parseError.value = '';
  commitError.value = '';
  result.value = { created: [], skipped: [] };
}

onMounted(async () => {
  try {
    options.value = await api.options();
  } catch (e) {
    loadError.value = apiErrorMessage(e, t);
  } finally {
    optionsLoaded.value = true;
  }
});
</script>
