<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="tr-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex flex-wrap align-start justify-space-between ga-3 mb-4">
      <div>
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-1">
          {{ t('translate', 'Translate') }} — {{ langLabel }}
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          <template v-if="file">
            {{ t('File', 'File') }}: {{ file }}
            <span v-if="hasUserdata" class="ms-2">
              <v-chip size="x-small" color="info" variant="tonal">
                {{ t('lang_modified', 'Modified') }}
              </v-chip>
            </span>
          </template>
          <template v-else>
            {{ t('translator_help_text', 'Click on a language file to edit the translations.') }}
          </template>
        </p>
      </div>
      <div class="d-flex flex-wrap ga-2">
        <v-btn variant="outlined" :href="api.exportUrl(lang)" prepend-icon="mdi-download-outline">
          {{ t('download', 'Download') }}
        </v-btn>
      </div>
    </div>

    <v-alert v-if="pageError" type="error" class="mb-4" closable @click:close="pageError = ''">
      {{ pageError }}
    </v-alert>

    <v-row>
      <v-col cols="12" md="3">
        <v-card elevation="1" class="tr-file-nav">
          <div class="px-3 pt-3 pb-2">
            <v-select
              v-model="selectedLang"
              :items="languageItems"
              item-title="name"
              item-value="code"
              density="compact"
              variant="outlined"
              hide-details
              @update:model-value="onLangChange"
            />
          </div>
          <div class="px-3 pb-3">
            <v-text-field
              v-model="sidebarSearch"
              :placeholder="t('translate_search_placeholder', 'Search key, English text or translation')"
              density="compact"
              variant="outlined"
              hide-details
              clearable
              prepend-inner-icon="mdi-magnify"
              @keyup.enter="goSidebarSearch"
              @click:prepend-inner="goSidebarSearch"
            />
          </div>
          <v-divider />
          <v-list density="compact" nav :selected="file ? [file] : []">
            <v-list-item
              v-for="f in files"
              :key="f.file"
              :value="f.file"
              :to="{ name: 'language-edit', params: { lang, file: f.file } }"
              :title="f.label"
            >
              <template #prepend>
                <v-icon
                  :icon="f.complete ? 'mdi-check-circle' : 'mdi-alert-circle'"
                  :color="f.complete ? 'success' : 'error'"
                  size="18"
                />
              </template>
              <template #append>
                <span class="text-caption text-medium-emphasis">
                  {{ f.translated }}/{{ f.total }}
                </span>
              </template>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>

      <v-col cols="12" md="9">
        <v-card v-if="!file" elevation="1">
          <v-card-text class="py-8 text-center text-medium-emphasis">
            <h2 class="text-h6 mb-2">{{ t('translator_help_title', 'Help with translating') }}</h2>
            <p class="mb-0">
              {{ t('translator_help_text', 'Click on a language file to edit the translations.') }}
            </p>
          </v-card-text>
        </v-card>

        <v-card v-else elevation="1">
          <div class="d-flex flex-wrap align-center ga-3 px-4 py-3">
            <v-text-field
              v-model="filterText"
              prepend-inner-icon="mdi-magnify"
              :placeholder="t('filter_this_file', 'Filter this file by key, English text or translation')"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              style="max-width: 360px"
            />
            <v-checkbox
              v-model="onlyMissing"
              :label="t('show_only_missing', 'Show only missing translations')"
              density="compact"
              hide-details
            />
            <span class="text-caption text-medium-emphasis">
              {{ visibleEntries.length }} / {{ entries.length }}
            </span>
            <v-spacer />
            <v-btn
              v-if="hasUserdata"
              variant="text"
              color="warning"
              :loading="resetting"
              @click="resetDialog = true"
            >
              {{ t('reset', 'Reset') }}
            </v-btn>
            <v-btn color="primary" :loading="saving" :disabled="!dirty" @click="save">
              {{ t('save', 'Save') }}
            </v-btn>
          </div>
          <v-divider />

          <v-progress-linear v-if="loadingFile" indeterminate />

          <div v-else class="pa-0">
            <v-table density="comfortable" class="tr-table">
              <thead>
                <tr>
                  <th style="width: 22%">{{ t('Key', 'Key') }}</th>
                  <th>{{ t('Translation', 'Translation') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in visibleEntries"
                  :key="row.key"
                  :class="{ 'bg-error-lighten': isMissing(row) }"
                >
                  <td class="tr-key align-top pt-3">{{ row.key }}</td>
                  <td class="pt-2 pb-3">
                    <div class="tr-master">{{ row.english }}</div>
                    <v-textarea
                      v-model="draft[row.key]"
                      variant="outlined"
                      density="compact"
                      rows="2"
                      auto-grow
                      hide-details
                      :dir="isRtl ? 'rtl' : undefined"
                      @update:model-value="markDirty"
                    />
                  </td>
                </tr>
              </tbody>
            </v-table>
            <div v-if="!visibleEntries.length" class="text-medium-emphasis text-center py-8">
              {{ t('search_no_results', 'No matching translations found.') }}
            </div>
          </div>

          <v-divider />
          <div class="d-flex justify-end ga-2 pa-3">
            <v-btn color="primary" :loading="saving" :disabled="!dirty" @click="save">
              {{ t('save', 'Save') }}
            </v-btn>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="resetDialog" max-width="460">
      <v-card>
        <v-card-title class="text-h6">{{ t('reset', 'Reset') }}</v-card-title>
        <v-card-text>
          {{
            t(
              'translate_reset_file_confirm',
              'Remove the userdata override for this file and fall back to the official translation?'
            )
          }}
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="resetDialog = false">{{ t('cancel', 'Cancel') }}</v-btn>
          <v-btn color="warning" variant="flat" :loading="resetting" @click="confirmReset">
            {{ t('reset', 'Reset') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useTranslateApi } from '../composables/useTranslateApi';
import { useFlashToast } from '../composables/useFlashToast';

defineOptions({ name: 'LanguageEditPage' });

const props = defineProps({
  lang: { type: String, required: true },
  file: { type: String, default: '' },
});

const RTL = new Set(['arabic']);

const { t } = useI18n();
const { config } = useAppConfig();
const api = useTranslateApi();
const route = useRoute();
const router = useRouter();
const { toast, showToast, consumeFlash } = useFlashToast();

const selectedLang = ref(props.lang);
const languageItems = ref([]);
const files = ref([]);
const entries = ref([]);
const draft = reactive({});
const dirty = ref(false);
const hasUserdata = ref(false);
const loadingFiles = ref(false);
const loadingFile = ref(false);
const saving = ref(false);
const resetting = ref(false);
const resetDialog = ref(false);
const pageError = ref('');
const filterText = ref(typeof route.query.q === 'string' ? route.query.q : '');
const onlyMissing = ref(false);
const sidebarSearch = ref('');

const langLabel = computed(() => props.lang.charAt(0).toUpperCase() + props.lang.slice(1));
const isRtl = computed(() => RTL.has(props.lang));

const breadcrumbItems = computed(() => {
  const items = [
    { title: t('Home', 'Home'), href: config.value.siteUrl || '/' },
    { title: t('translate', 'Translate'), to: { name: 'languages-list' } },
    {
      title: langLabel.value,
      to: props.file ? { name: 'language-edit-root', params: { lang: props.lang } } : undefined,
      disabled: !props.file,
    },
  ];
  if (props.file) {
    items.push({ title: props.file, disabled: true });
  }
  return items;
});

const visibleEntries = computed(() => {
  const q = filterText.value.trim().toLowerCase();
  return entries.value.filter((row) => {
    const translation = draft[row.key] ?? row.translation ?? '';
    // Use loaded missing flag so typing into a blank row does not hide it mid-edit.
    if (onlyMissing.value && !row.missing) return false;
    if (!q) return true;
    const hay = `${row.key} ${row.english} ${translation}`.toLowerCase();
    return hay.includes(q);
  });
});

function isMissing(row) {
  return (draft[row.key] ?? '') === '';
}

function markDirty() {
  dirty.value = true;
}

function applyEntries(list) {
  entries.value = list || [];
  Object.keys(draft).forEach((k) => delete draft[k]);
  for (const row of entries.value) {
    draft[row.key] = row.translation ?? '';
  }
  dirty.value = false;
}

async function loadLanguages() {
  try {
    const data = await api.listLanguages();
    languageItems.value = (data.languages || []).map((l) => ({
      code: l.code,
      name: l.name,
    }));
  } catch {
    languageItems.value = [{ code: props.lang, name: langLabel.value }];
  }
}

async function loadFiles() {
  loadingFiles.value = true;
  pageError.value = '';
  try {
    const data = await api.listFiles(props.lang);
    files.value = data.files || [];
  } catch (e) {
    pageError.value = e.message;
    files.value = [];
  } finally {
    loadingFiles.value = false;
  }
}

async function loadFile() {
  if (!props.file) {
    entries.value = [];
    hasUserdata.value = false;
    return;
  }
  loadingFile.value = true;
  pageError.value = '';
  try {
    const data = await api.getFile(props.lang, props.file);
    hasUserdata.value = !!data.has_userdata;
    applyEntries(data.entries);
  } catch (e) {
    pageError.value = e.message;
    entries.value = [];
  } finally {
    loadingFile.value = false;
  }
}

async function save() {
  if (!props.file) return;
  saving.value = true;
  pageError.value = '';
  try {
    const translations = {};
    for (const row of entries.value) {
      translations[row.key] = draft[row.key] ?? '';
    }
    const data = await api.saveFile(props.lang, props.file, translations);
    hasUserdata.value = !!data.has_userdata;
    applyEntries(data.entries);
    showToast(t('success', 'Saved'));
    await loadFiles();
  } catch (e) {
    pageError.value = e.message;
  } finally {
    saving.value = false;
  }
}

async function confirmReset() {
  if (!props.file) return;
  resetting.value = true;
  try {
    const data = await api.resetFile(props.lang, props.file);
    hasUserdata.value = !!data.has_userdata;
    applyEntries(data.entries);
    resetDialog.value = false;
    showToast(data.message || t('success', 'Reset'));
    await loadFiles();
  } catch (e) {
    pageError.value = e.message;
  } finally {
    resetting.value = false;
  }
}

function onLangChange(code) {
  if (props.file) {
    router.push({ name: 'language-edit', params: { lang: code, file: props.file } });
  } else {
    router.push({ name: 'language-edit-root', params: { lang: code } });
  }
}

function goSidebarSearch() {
  const q = sidebarSearch.value.trim();
  router.push({
    name: 'language-search',
    params: { lang: props.lang },
    query: q ? { q } : {},
  });
}

watch(
  () => [props.lang, props.file],
  async () => {
    selectedLang.value = props.lang;
    await loadFiles();
    await loadFile();
  }
);

watch(
  () => route.query.q,
  (q) => {
    if (typeof q === 'string') filterText.value = q;
  }
);

onMounted(async () => {
  consumeFlash();
  await loadLanguages();
  await loadFiles();
  await loadFile();
});
</script>

<style scoped>
.bg-error-lighten {
  background: rgba(var(--v-theme-error), 0.08);
}
</style>
